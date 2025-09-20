<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestingSeeder extends Seeder
{
    /**
     * Run the database seeds for testing environment.
     * This seeder creates minimal test data required for CI/CD pipeline tests.
     */
    public function run(): void
    {
        // Disable foreign key checks for SQLite and MySQL
        $this->disableForeignKeyChecks();

        // Create test users
        $this->createTestUsers();

        // Create any other essential test data
        $this->createTestData();

        // Re-enable foreign key checks
        $this->enableForeignKeyChecks();
    }

    /**
     * Disable foreign key checks based on database driver.
     */
    private function disableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                break;
            case 'sqlite':
                DB::statement('PRAGMA foreign_keys=OFF;');
                break;
            case 'pgsql':
                // PostgreSQL doesn't have a global foreign key check disable
                break;
        }
    }

    /**
     * Enable foreign key checks based on database driver.
     */
    private function enableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                break;
            case 'sqlite':
                DB::statement('PRAGMA foreign_keys=ON;');
                break;
            case 'pgsql':
                // PostgreSQL doesn't have a global foreign key check disable
                break;
        }
    }

    /**
     * Create test users for authentication and authorization tests.
     */
    private function createTestUsers(): void
    {
        // Admin user for testing admin functionality
        User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'phone' => '+1234567890',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'preferred_language' => 'en',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);

        // Regular user for testing standard functionality
        User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'phone' => '+1234567891',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'student',
            'preferred_language' => 'en',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);

        // Unverified user for testing email verification
        User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@test.com',
            'phone' => '+1234567892',
            'password' => Hash::make('password'),
            'email_verified_at' => null,
            'role' => 'student',
            'preferred_language' => 'en',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);
    }

    /**
     * Create additional test data as needed.
     */
    private function createTestData(): void
    {
        // Add any additional test data that your application needs
        // For example: roles, permissions, categories, etc.

        // Example: Create test roles if using Spatie Laravel Permission
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
        }
    }
}
