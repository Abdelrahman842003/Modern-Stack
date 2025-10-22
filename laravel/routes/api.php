<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes with rate limiting
Route::middleware('throttle:auth')->prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Protected routes with rate limiting
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    });

    // Tasks routes
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/{id}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'service' => 'task-management-api',
    ]);
})->name('health');

// Detailed health check endpoint
Route::get('/health/detailed', function () {
    $checks = [
        'database' => false,
        'redis' => false,
        'queue' => false,
        'disk_space' => false,
    ];

    $errors = [];

    // Database check
    try {
        DB::connection()->getPdo();
        DB::select('SELECT 1');
        $checks['database'] = true;
    } catch (\Exception $e) {
        $errors['database'] = $e->getMessage();
    }

    // Redis check
    try {
        Cache::store('redis')->put('health_check', true, 10);
        $checks['redis'] = Cache::store('redis')->get('health_check') === true;
    } catch (\Exception $e) {
        $errors['redis'] = $e->getMessage();
    }

    // Queue check (size should be reasonable)
    try {
        $queueSize = Queue::size();
        $checks['queue'] = $queueSize < 1000; // Less than 1000 jobs pending
        if (! $checks['queue']) {
            $errors['queue'] = "Queue size too large: {$queueSize} jobs";
        }
    } catch (\Exception $e) {
        $errors['queue'] = $e->getMessage();
    }

    // Disk space check (more than 1GB free)
    try {
        $freeSpace = disk_free_space('/');
        $checks['disk_space'] = $freeSpace > (1024 * 1024 * 1024); // > 1GB
        if (! $checks['disk_space']) {
            $freeSpaceGB = round($freeSpace / (1024 * 1024 * 1024), 2);
            $errors['disk_space'] = "Low disk space: {$freeSpaceGB}GB free";
        }
    } catch (\Exception $e) {
        $errors['disk_space'] = $e->getMessage();
    }

    $healthy = ! in_array(false, $checks, true);

    $response = [
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'timestamp' => now()->toISOString(),
        'service' => 'task-management-api',
        'checks' => $checks,
    ];

    if (! empty($errors)) {
        $response['errors'] = $errors;
    }

    return response()->json($response, $healthy ? 200 : 503);
})->name('health.detailed');
