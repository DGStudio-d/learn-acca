<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run production seeder for production environment
        if (app()->environment('production')) {
            $this->call([
                ProductionSeeder::class,
            ]);
            return;
        }

        // Development/testing seeders
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
        ]);

        // Create default admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@learnacademy.com',
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        // Create test teacher
        $teacher = User::factory()->create([
            'name' => 'Teacher User',
            'email' => 'teacher@learnacademy.com',
            'role' => 'teacher',
        ]);
        $teacher->assignRole('teacher');

        // Create test student
        $student = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@learnacademy.com',
            'role' => 'student',
        ]);
        $student->assignRole('student');

        // Run test data seeder for development
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestDataSeeder::class,
            ]);
        }
    }
}
