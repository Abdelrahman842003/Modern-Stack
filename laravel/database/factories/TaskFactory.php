<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('now', '+3 months'),
            'status' => fake()->randomElement([Task::STATUS_PENDING, Task::STATUS_DONE]),
        ];
    }

    /**
     * Indicate that the task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Task::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the task is done.
     */
    public function done(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Task::STATUS_DONE,
        ]);
    }

    /**
     * Indicate that the task has a due date.
     *
     * @param  mixed  $date
     */
    public function withDueDate($date = null): static
    {
        return $this->state(fn(array $attributes) => [
            'due_date' => $date ?? fake()->dateTimeBetween('now', '+3 months'),
        ]);
    }

    /**
     * Indicate that the task has no due date.
     */
    public function withoutDueDate(): static
    {
        return $this->state(fn(array $attributes) => [
            'due_date' => null,
        ]);
    }
}
