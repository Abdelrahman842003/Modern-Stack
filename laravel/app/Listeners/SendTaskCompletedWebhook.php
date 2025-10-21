<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTaskCompletedWebhook
{
    /**
     * Handle the event.
     */
    public function handle(TaskCompleted $event): void
    {
        $task = $event->task;
        $task->load('user');

        $payload = [
            'userId' => $task->user_id,
            'taskId' => $task->id,
            'message' => "Task '{$task->title}' has been completed",
            'timestamp' => now()->toIso8601String(),
        ];

        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            Log::error('Failed to encode payload to JSON', ['task_id' => $task->id]);

            return;
        }

        $signature = 'sha256=' . hash_hmac(
            'sha256',
            $jsonPayload,
            config('services.webhook.secret')
        );

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Signature' => $signature,
            ])
                ->timeout(5)
                ->withBody($jsonPayload, 'application/json')
                ->post(config('services.webhook.url'));

            if ($response->successful()) {
                Log::info('Webhook sent successfully', [
                    'task_id' => $task->id,
                    'user_id' => $task->user_id,
                    'status_code' => $response->status(),
                ]);
            } else {
                Log::error('Webhook failed', [
                    'task_id' => $task->id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook exception', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            // Don't throw in testing/local environments where webhook service might not be available
            if (!app()->environment(['testing', 'local'])) {
                throw $e;
            }
        }
    }
}
