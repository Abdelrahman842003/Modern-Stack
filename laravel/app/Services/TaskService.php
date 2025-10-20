<?php

namespace App\Services;

use App\Events\TaskCompleted;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    /**
     * Get paginated tasks for a user with optional filters.
     */
    public function getUserTasks(
        User $user,
        ?string $status = null,
        ?string $dueFrom = null,
        ?string $dueTo = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $user->tasks()
            ->filterByStatus($status)
            ->filterByDueDateRange($dueFrom, $dueTo)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create a new task for a user.
     */
    public function createTask(User $user, array $data): Task
    {
        return $user->tasks()->create($data);
    }

    /**
     * Get a specific task for a user.
     */
    public function getUserTask(User $user, int $taskId): ?Task
    {
        return $user->tasks()->find($taskId);
    }

    /**
     * Update a task.
     */
    public function updateTask(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh();
    }

    /**
     * Delete a task (soft delete).
     */
    public function deleteTask(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Mark a task as completed.
     */
    public function markTaskAsComplete(Task $task): Task
    {
        $task->markAsDone();

        // Dispatch event for webhook notification
        TaskCompleted::dispatch($task->fresh());

        return $task->fresh();
    }
}
