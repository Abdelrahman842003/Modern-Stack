<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create demo users
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
        ]);

        $testUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create additional random users
        $randomUsers = User::factory(3)->create();

        // Create tasks for demo user
        Task::factory(5)->pending()->for($demoUser)->create();
        Task::factory(3)->done()->for($demoUser)->create();
        Task::factory(2)->withDueDate()->for($demoUser)->create();

        // Create tasks for test user
        Task::factory(4)->pending()->for($testUser)->create();
        Task::factory(2)->done()->for($testUser)->create();

        // Create tasks for random users
        foreach ($randomUsers as $user) {
            Task::factory(rand(2, 5))->for($user)->create();
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Demo credentials:');
        $this->command->info('  Email: demo@example.com');
        $this->command->info('  Password: password');
    }
}
