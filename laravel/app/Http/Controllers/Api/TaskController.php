<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected TaskService $taskService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     tags={"Tasks"},
     *     summary="Get user's tasks",
     *     description="Retrieve paginated list of authenticated user's tasks with optional filters",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"pending", "done"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="due_from",
     *         in="query",
     *         description="Filter tasks due from this date",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="date", example="2025-10-20")
     *     ),
     *
     *     @OA\Parameter(
     *         name="due_to",
     *         in="query",
     *         description="Filter tasks due until this date",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="date", example="2025-10-30")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, example=15)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tasks retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Complete project documentation"),
     *                     @OA\Property(property="description", type="string", example="Write comprehensive API documentation"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="due_date", type="string", format="date", example="2025-10-25"),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2025-10-20T10:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer", example=50),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=4)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Generate cache key based on user and filters
        $filterParams = $request->only(['status', 'due_from', 'due_to', 'per_page', 'page']);
        $cacheKey = sprintf(
            'user:%d:tasks:%s',
            $user->id,
            md5((string) json_encode($filterParams))
        );

        // Cache for 5 minutes (300 seconds)
        $tasks = Cache::remember($cacheKey, 300, function () use ($user, $request) {
            return $this->taskService->getUserTasks(
                $user,
                $request->query('status'),
                $request->query('due_from'),
                $request->query('due_to'),
                $request->query('per_page', 15)
            );
        });

        return $this->successResponse(
            TaskResource::collection($tasks),
            'Tasks retrieved successfully',
            200,
            [
                'pagination' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ],
            ]
        );
    }

    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     tags={"Tasks"},
     *     summary="Create a new task",
     *     description="Create a new task for the authenticated user",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"title"},
     *
     *             @OA\Property(property="title", type="string", example="Complete project documentation"),
     *             @OA\Property(property="description", type="string", example="Write comprehensive API documentation"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-10-25")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Complete project documentation"),
     *                 @OA\Property(property="description", type="string", example="Write comprehensive API documentation"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="due_date", type="string", format="date", example="2025-10-25")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $task = $this->taskService->createTask(
            $user,
            $request->validated()
        );

        // Clear all task list caches for this user
        $this->clearUserTaskCache($user->id);

        return $this->successResponse(
            new TaskResource($task),
            'Task created successfully',
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Get a specific task",
     *     description="Retrieve details of a specific task",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Task retrieved successfully"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Cache individual task for 10 minutes
        $cacheKey = "user:{$user->id}:task:{$id}";
        $task = Cache::remember($cacheKey, 600, function () use ($user, $id) {
            return $this->taskService->getUserTask($user, $id);
        });

        if (! $task) {
            return $this->errorResponse(
                'Task not found',
                null,
                404
            );
        }

        return $this->successResponse(
            new TaskResource($task),
            'Task retrieved successfully'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Update a task",
     *     description="Update an existing task",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="string", example="Updated task title"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-10-30")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Task updated successfully"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $task = $this->taskService->getUserTask($user, $id);

        if (! $task) {
            return $this->errorResponse(
                'Task not found',
                null,
                404
            );
        }

        $updatedTask = $this->taskService->updateTask($task, $request->validated());

        // Clear cache for this user - clear specific keys
        Cache::forget("user:{$user->id}:task:{$id}");
        $this->clearUserTaskCache($user->id);

        return $this->successResponse(
            new TaskResource($updatedTask),
            'Task updated successfully'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Delete a task",
     *     description="Soft delete a task",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Task deleted successfully"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $task = $this->taskService->getUserTask($user, $id);

        if (! $task) {
            return $this->errorResponse(
                'Task not found',
                null,
                404
            );
        }

        $this->taskService->deleteTask($task);

        // Clear cache for this user
        Cache::forget("user:{$user->id}:task:{$id}");
        $this->clearUserTaskCache($user->id);

        return $this->successResponse(
            null,
            'Task deleted successfully'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/tasks/{id}/complete",
     *     tags={"Tasks"},
     *     summary="Mark task as complete",
     *     description="Mark a pending task as done and trigger webhook notification",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Task marked as complete",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Task title"),
     *                 @OA\Property(property="status", type="string", example="done")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Task is already completed"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $task = $this->taskService->getUserTask($user, $id);

        if (! $task) {
            return $this->errorResponse(
                'Task not found',
                null,
                404
            );
        }

        if ($task->isDone()) {
            return $this->errorResponse(
                'Task is already completed',
                null,
                400
            );
        }

        $completedTask = $this->taskService->markTaskAsComplete($task);

        // Clear cache for this user
        Cache::forget("user:{$user->id}:task:{$task->id}");
        $this->clearUserTaskCache($user->id);

        return $this->successResponse(
            new TaskResource($completedTask),
            'Task marked as complete'
        );
    }

    /**
     * Clear all task list cache entries for a specific user
     */
    private function clearUserTaskCache(int $userId): void
    {
        // Check if Redis is available
        $driver = config('cache.default');

        if ($driver === 'array') {
            // For array cache driver (used in tests), just flush all cache
            // since we can't use pattern matching
            Cache::flush();

            return;
        }

        if ($driver !== 'redis' && $driver !== 'dynamodb') {
            // For other drivers that don't support pattern matching, skip
            return;
        }

        // Clear all possible cache keys for user's task lists using Redis
        try {
            /** @var \Illuminate\Cache\RedisStore $store */
            $store = Cache::store('redis');
            $redis = $store->connection();
            $prefix = config('cache.prefix') ? config('cache.prefix').':' : '';
            $pattern = "{$prefix}user:{$userId}:tasks:*";

            $keys = $redis->keys($pattern);
            if (! empty($keys)) {
                // Remove the prefix from keys before deleting
                $keysToDelete = array_map(function ($key) use ($prefix) {
                    return str_replace($prefix, '', $key);
                }, $keys);
                foreach ($keysToDelete as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            // If Redis is not available or any error, just skip cache clearing
            // The cache will expire naturally
        }
    }
}
