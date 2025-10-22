<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CircuitBreakerService
{
    private string $serviceName;

    private int $failureThreshold;

    private int $successThreshold;

    private int $timeout;

    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $successThreshold = 2,
        int $timeout = 60
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeout = $timeout;
    }

    /**
     * Execute a callable with circuit breaker protection
     *
     * @return mixed
     */
    public function call(callable $callback)
    {
        $state = $this->getState();

        // If circuit is open, don't attempt the call
        if ($state === 'open') {
            if ($this->shouldAttemptReset()) {
                $this->setState('half-open');
            } else {
                throw new \RuntimeException("Circuit breaker is OPEN for {$this->serviceName}");
            }
        }

        try {
            $result = $callback();
            $this->onSuccess();

            return $result;
        } catch (\Exception $e) {
            $this->onFailure();
            throw $e;
        }
    }

    /**
     * Record a successful call
     */
    private function onSuccess(): void
    {
        $state = $this->getState();

        if ($state === 'half-open') {
            $successCount = $this->incrementSuccessCount();

            if ($successCount >= $this->successThreshold) {
                $this->setState('closed');
                $this->resetCounts();
            }
        } elseif ($state === 'closed') {
            $this->resetFailureCount();
        }
    }

    /**
     * Record a failed call
     */
    private function onFailure(): void
    {
        $failureCount = $this->incrementFailureCount();

        if ($failureCount >= $this->failureThreshold) {
            $this->setState('open');
            $this->setLastFailureTime();
        }
    }

    /**
     * Get current circuit breaker state
     */
    private function getState(): string
    {
        return Cache::get($this->getStateKey(), 'closed');
    }

    /**
     * Set circuit breaker state
     */
    private function setState(string $state): void
    {
        Cache::put($this->getStateKey(), $state, 3600);
    }

    /**
     * Check if we should attempt to reset the circuit
     */
    private function shouldAttemptReset(): bool
    {
        $lastFailureTime = Cache::get($this->getLastFailureTimeKey());

        if (!$lastFailureTime) {
            return true;
        }

        return (time() - $lastFailureTime) >= $this->timeout;
    }

    /**
     * Increment failure count
     */
    private function incrementFailureCount(): int
    {
        $key = $this->getFailureCountKey();
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600);

        return $count;
    }

    /**
     * Increment success count
     */
    private function incrementSuccessCount(): int
    {
        $key = $this->getSuccessCountKey();
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600);

        return $count;
    }

    /**
     * Reset failure count
     */
    private function resetFailureCount(): void
    {
        Cache::forget($this->getFailureCountKey());
    }

    /**
     * Reset all counts
     */
    private function resetCounts(): void
    {
        Cache::forget($this->getFailureCountKey());
        Cache::forget($this->getSuccessCountKey());
        Cache::forget($this->getLastFailureTimeKey());
    }

    /**
     * Set last failure timestamp
     */
    private function setLastFailureTime(): void
    {
        Cache::put($this->getLastFailureTimeKey(), time(), 3600);
    }

    /**
     * Get cache keys
     */
    private function getStateKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:state";
    }

    private function getFailureCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }

    private function getSuccessCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:successes";
    }

    private function getLastFailureTimeKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:last_failure";
    }

    /**
     * Get circuit breaker status
     */
    public function getStatus(): array
    {
        return [
            'service' => $this->serviceName,
            'state' => $this->getState(),
            'failures' => Cache::get($this->getFailureCountKey(), 0),
            'successes' => Cache::get($this->getSuccessCountKey(), 0),
            'last_failure' => Cache::get($this->getLastFailureTimeKey()),
        ];
    }

    /**
     * Force reset the circuit breaker
     */
    public function reset(): void
    {
        $this->setState('closed');
        $this->resetCounts();
    }
}
