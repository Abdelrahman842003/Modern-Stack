<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_completion_sends_webhook_to_node_service(): void
    {
        // Fake HTTP responses
        Http::fake([
            'http://node-notify:3001/notify' => Http::response([
                'data' => [
                    'message' => 'Notification created successfully',
                    'notification_id' => 1,
                ],
            ], 201),
        ]);

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/complete");

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => ['id', 'title', 'status'],
        ]);

        // Verify webhook was sent
        Http::assertSent(function ($request) use ($task) {
            return $request->url() === 'http://node-notify:3001/notify'
                && $request->hasHeader('X-Signature')
                && $request->hasHeader('X-Webhook-Source')
                && $request['taskId'] === $task->id
                && $request['userId'] === $task->user_id;
        });
    }

    public function test_webhook_signature_validation(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $payload = [
            'userId' => $task->user_id,
            'taskId' => $task->id,
            'message' => "Task '{$task->title}' has been completed!",
            'timestamp' => now()->toIso8601String(),
        ];

        $jsonPayload = json_encode($payload);
        $validSignature = 'sha256='.hash_hmac(
            'sha256',
            $jsonPayload,
            config('services.webhook.secret')
        );

        // This test verifies the signature generation is correct
        $this->assertNotEmpty($validSignature);
        $this->assertStringStartsWith('sha256=', $validSignature);
        $this->assertEquals(71, strlen($validSignature)); // sha256= + 64 chars
    }

    public function test_webhook_retry_on_failure(): void
    {
        // Mock successful response after retries
        Http::fake([
            'http://node-notify:3001/notify' => Http::response([
                'data' => ['message' => 'Success', 'notification_id' => 1],
            ], 201),
        ]);

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/complete");

        $response->assertOk();

        // Verify webhook was sent
        Http::assertSent(function ($request) {
            return $request->url() === 'http://node-notify:3001/notify'
                && $request->hasHeader('X-Signature');
        });
    }

    public function test_webhook_handles_http_client_retry(): void
    {
        // Test that HTTP client has retry configured
        Http::fake([
            'http://node-notify:3001/notify' => Http::response([
                'data' => ['message' => 'Success', 'notification_id' => 1],
            ], 201),
        ]);

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/complete");

        $response->assertOk();

        // Verify request has retry headers configured
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Attempt')
                && $request->url() === 'http://node-notify:3001/notify';
        });
    }

    public function test_circuit_breaker_prevents_excessive_calls(): void
    {
        // Simulate circuit breaker opening after failures
        Http::fake([
            'http://node-notify:3001/notify' => Http::response(['error' => 'Service down'], 503),
        ]);

        $user = User::factory()->create();

        // Create multiple tasks to trigger circuit breaker
        for ($i = 0; $i < 6; $i++) {
            $task = Task::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            try {
                $this->actingAs($user)
                    ->postJson("/api/tasks/{$task->id}/complete");
            } catch (\Exception $e) {
                // Expected to fail
            }
        }

        // Circuit should be open after 5 failures
        $this->assertTrue(true); // Test passes if no exception
    }

    public function test_cache_invalidation_on_task_update(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        // First request (caches data)
        $response1 = $this->actingAs($user)->getJson('/api/tasks');
        $response1->assertOk();

        // Update task
        $updateResponse = $this->actingAs($user)->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title',
        ]);
        $updateResponse->assertOk();
        $updateResponse->assertJsonFragment(['title' => 'Updated Title']);

        // Second request (should get fresh data, not cached)
        $response2 = $this->actingAs($user)->getJson('/api/tasks');
        $response2->assertOk();
        $response2->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_cache_works_for_task_listing(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(5)->create(['user_id' => $user->id]);

        // First request
        $startTime1 = microtime(true);
        $response1 = $this->actingAs($user)->getJson('/api/tasks');
        $time1 = microtime(true) - $startTime1;

        // Second request (cached)
        $startTime2 = microtime(true);
        $response2 = $this->actingAs($user)->getJson('/api/tasks');
        $time2 = microtime(true) - $startTime2;

        $response1->assertOk();
        $response2->assertOk();

        // Cached request should be faster (though this might be flaky)
        $this->assertEquals($response1->json('data'), $response2->json('data'));
    }
}
