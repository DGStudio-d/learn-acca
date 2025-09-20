<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for production environment.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
        ]);

        // Create default admin user if it doesn't exist
        if (!User::where('email', 'admin@learnacademy.com')->exists()) {
            $admin = User::create([
                'name' => 'System Administrator',
                'email' => 'admin@learnacademy.com',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'admin123')),
                'phone' => '+1234567890',
                'role' => 'admin',
                'notify_email' => true,
                'notify_whatsapp' => false,
                'email_verified_at' => now(),
            ]);
            
            $admin->assignRole('admin');
            
            $this->command->info('Default admin user created: admin@learnacademy.com');
        }

        // Create initial languages if they don't exist
        $languages = [
            ['code' => 'en', 'name' => 'English', 'active' => true],
            ['code' => 'ar', 'name' => 'Arabic', 'active' => true],
            ['code' => 'es', 'name' => 'Spanish', 'active' => true],
        ];

        foreach ($languages as $languageData) {
            $language = Language::firstOrCreate(
                ['code' => $languageData['code']],
                $languageData
            );

            // Create levels for each language if they don't exist
            $levels = ['Beginner', 'Intermediate', 'Advanced'];
            
            foreach ($levels as $index => $levelName) {
                $level = Level::firstOrCreate([
                    'language_id' => $language->id,
                    'name' => $levelName,
                ], [
                    'order' => $index + 1,
                ]);

                // Create program for each language-level combination
                Program::firstOrCreate([
                    'language_id' => $language->id,
                    'level_id' => $level->id,
                ], [
                    'name' => "{$language->name} - {$levelName}",
                    'description' => "Learn {$language->name} at {$levelName} level",
                    'active' => true,
                ]);
            }
        }

        $this->command->info('Initial languages, levels, and programs created');
    }
}