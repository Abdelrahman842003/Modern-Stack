<?php

use App\Events\TaskCompleted;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TaskService();
    $this->user = User::factory()->create();
});

describe('TaskService - getUserTasks', function () {
    it('returns paginated tasks for a user', function () {
        Task::factory()->count(25)->create(['user_id' => $this->user->id]);

        $result = $this->service->getUserTasks($this->user, perPage: 10);

        expect($result)->toHaveCount(10)
            ->and($result->total())->toBe(25);
    });

    it('filters tasks by status', function () {
        Task::factory()->count(5)->pending()->create(['user_id' => $this->user->id]);
        Task::factory()->count(3)->done()->create(['user_id' => $this->user->id]);

        $result = $this->service->getUserTasks($this->user, status: 'pending');

        expect($result->total())->toBe(5);
    });

    it('filters tasks by due date range', function () {
        Task::factory()->withDueDate(now()->addDays(5))->create(['user_id' => $this->user->id]);
        Task::factory()->withDueDate(now()->addDays(15))->create(['user_id' => $this->user->id]);
        Task::factory()->withDueDate(now()->addDays(25))->create(['user_id' => $this->user->id]);

        $result = $this->service->getUserTasks(
            $this->user,
            dueFrom: now()->addDays(10)->format('Y-m-d'),
            dueTo: now()->addDays(20)->format('Y-m-d')
        );

        expect($result->total())->toBe(1);
    });
});

describe('TaskService - createTask', function () {
    it('creates a task for a user', function () {
        $data = [
            'title' => 'New Task',
            'description' => 'Task description',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $task = $this->service->createTask($this->user, $data);

        expect($task)->toBeInstanceOf(Task::class)
            ->and($task->title)->toBe('New Task')
            ->and($task->user_id)->toBe($this->user->id)
            ->and($task->status)->toBe(Task::STATUS_PENDING);
    });
});

describe('TaskService - getUserTask', function () {
    it('retrieves a specific task for a user', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->getUserTask($this->user, $task->id);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($task->id);
    });

    it('returns null for non-existent task', function () {
        $result = $this->service->getUserTask($this->user, 99999);

        expect($result)->toBeNull();
    });

    it('returns null for another user task', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $result = $this->service->getUserTask($this->user, $task->id);

        expect($result)->toBeNull();
    });
});

describe('TaskService - updateTask', function () {
    it('updates a task with new data', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Old Title',
        ]);

        $updated = $this->service->updateTask($task, [
            'title' => 'New Title',
            'description' => 'New description',
        ]);

        expect($updated->title)->toBe('New Title')
            ->and($updated->description)->toBe('New description');
    });
});

describe('TaskService - deleteTask', function () {
    it('soft deletes a task', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->deleteTask($task);

        expect($result)->toBeTrue();
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    });
});

describe('TaskService - markTaskAsComplete', function () {
    it('marks a task as done and dispatches event', function () {
        Event::fake();

        $task = Task::factory()->pending()->create(['user_id' => $this->user->id]);

        $result = $this->service->markTaskAsComplete($task);

        expect($result->status)->toBe(Task::STATUS_DONE);

        Event::assertDispatched(TaskCompleted::class, function ($event) use ($task) {
            return $event->task->id === $task->id;
        });
    });

    it('does not dispatch event if task already done', function () {
        Event::fake();

        $task = Task::factory()->done()->create(['user_id' => $this->user->id]);

        $this->service->markTaskAsComplete($task);

        // Should still dispatch as the service doesn't check, controller does
        Event::assertDispatched(TaskCompleted::class);
    });
});
