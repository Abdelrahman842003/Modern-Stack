<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

describe('Task Creation', function () {
    it('can create a task with valid data', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/tasks', [
                'title' => 'Test Task',
                'description' => 'This is a test task',
                'due_date' => now()->addDays(7)->format('Y-m-d'),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'due_date',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Task',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Test Task',
        ]);
    });

    it('can create a task without due date', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/tasks', [
                'title' => 'Task Without Due Date',
                'description' => 'This task has no deadline',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Task Without Due Date',
            'due_date' => null,
        ]);
    });

    it('fails to create task without authentication', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
        ]);

        $response->assertStatus(401);
    });

    it('fails to create task without title', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/tasks', [
                'description' => 'Task without title',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('fails to create task with invalid due date', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/tasks', [
                'title' => 'Test Task',
                'due_date' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    });
});

describe('Task Retrieval', function () {
    it('can get list of user tasks', function () {
        Task::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'status', 'due_date'],
                ],
                'meta' => ['pagination'],
            ])
            ->assertJsonCount(5, 'data');
    });

    it('can filter tasks by status', function () {
        Task::factory()->count(3)->pending()->create(['user_id' => $this->user->id]);
        Task::factory()->count(2)->done()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/tasks?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('can filter tasks by due date range', function () {
        Task::factory()->withDueDate(now()->addDays(5))->create(['user_id' => $this->user->id]);
        Task::factory()->withDueDate(now()->addDays(15))->create(['user_id' => $this->user->id]);
        Task::factory()->withDueDate(now()->addDays(25))->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/tasks?due_from=' . now()->addDays(10)->format('Y-m-d') . '&due_to=' . now()->addDays(20)->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('only shows user own tasks', function () {
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);

        $otherUser = User::factory()->create();
        Task::factory()->count(5)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('can get a specific task', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Specific Task',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Specific Task',
                ],
            ]);
    });

    it('cannot get other user task', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(404);
    });
});

describe('Task Update', function () {
    it('can update own task', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Old Title',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                    'description' => 'Updated description',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
        ]);
    });

    it('cannot update other user task', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(404);
    });

    it('fails to update with invalid data', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/tasks/{$task->id}", [
                'title' => '', // Empty title
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });
});

describe('Task Deletion', function () {
    it('can delete own task', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    });

    it('cannot delete other user task', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(404);
    });
});

describe('Task Completion', function () {
    it('can mark task as complete', function () {
        $task = Task::factory()->pending()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'done',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);
    });

    it('fails to complete already completed task', function () {
        $task = Task::factory()->done()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(400);
    });

    it('cannot complete other user task', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->pending()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(404);
    });
});

describe('Pagination', function () {
    it('paginates task list correctly', function () {
        Task::factory()->count(30)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/tasks?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.pagination.total', 30)
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonPath('meta.pagination.last_page', 3);
    });
});
