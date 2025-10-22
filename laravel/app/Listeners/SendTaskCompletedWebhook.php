<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Services\CircuitBreakerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTaskCompletedWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [10, 30, 60, 300, 900]; // 10s, 30s, 1m, 5m, 15m

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 30;

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
            'message' => "Task '{$task->title}' has been completed!",
            'timestamp' => now()->toIso8601String(),
        ];

        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            Log::error('Failed to encode payload to JSON', ['task_id' => $task->id]);
            throw new \RuntimeException('Failed to encode payload to JSON');
        }

        $signature = 'sha256=' . hash_hmac(
            'sha256',
            $jsonPayload,
            config('services.webhook.secret')
        );

        // Use Circuit Breaker for webhook calls
        $circuitBreaker = new CircuitBreakerService(
            serviceName: 'node-notify-webhook',
            failureThreshold: 5,
            successThreshold: 2,
            timeout: 60
        );

        try {
            $circuitBreaker->call(function () use ($jsonPayload, $signature, $task) {
                $response = Http::timeout(30)
                    ->retry(1, 500, function ($exception, $request) {
                        // Only retry on connection errors, not on 5xx responses
                        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                    })
                    ->withHeaders([
                        'X-Signature' => $signature,
                        'Content-Type' => 'application/json',
                        'X-Webhook-Source' => 'task-management-api',
                        'X-Attempt' => $this->attempts(),
                    ])
                    ->withBody($jsonPayload, 'application/json')
                    ->post(config('services.webhook.url'));

                if ($response->failed()) {
                    Log::error('Webhook failed after retries', [
                        'task_id' => $task->id,
                        'user_id' => $task->user_id,
                        'status_code' => $response->status(),
                        'response' => $response->body(),
                        'attempt' => $this->attempts(),
                    ]);

                    // Throw exception to trigger queue retry
                    throw new \RuntimeException('Webhook failed: ' . $response->body());
                }

                Log::info('Webhook sent successfully', [
                    'task_id' => $task->id,
                    'user_id' => $task->user_id,
                    'status_code' => $response->status(),
                    'attempt' => $this->attempts(),
                    'circuit_breaker_status' => 'closed',
                ]);

                return $response;
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circuit breaker is OPEN')) {
                Log::warning('Circuit breaker is OPEN, skipping webhook', [
                    'task_id' => $task->id,
                    'user_id' => $task->user_id,
                    'circuit_breaker_status' => 'open',
                ]);

                // Don't retry if circuit is open
                return;
            }

            // Re-throw for normal error handling
            throw $e;
        } catch (\Exception $e) {
            Log::error('Webhook exception', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw in testing/local environments where webhook service might not be available
            if (app()->environment(['testing', 'local'])) {
                Log::warning('Skipping webhook retry in local/testing environment');

                return;
            }

            // Re-throw to trigger queue retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(TaskCompleted $event, \Throwable $exception): void
    {
        $task = $event->task;

        Log::error('Webhook permanently failed after all retries', [
            'task_id' => $task->id,
            'user_id' => $task->user_id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Optionally send alert to admin/monitoring system
        // Notification::route('mail', config('mail.admin'))
        //     ->notify(new WebhookFailedNotification($task, $exception));
    }
}
