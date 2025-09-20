<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create languages
        $english = Language::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'active' => true]
        );

        $arabic = Language::firstOrCreate(
            ['code' => 'ar'],
            ['name' => 'Arabic', 'active' => true]
        );

        $spanish = Language::firstOrCreate(
            ['code' => 'es'],
            ['name' => 'Spanish', 'active' => true]
        );

        // Create levels for each language
        $levels = ['Beginner', 'Intermediate', 'Advanced'];
        
        foreach ([$english, $arabic, $spanish] as $language) {
            foreach ($levels as $index => $levelName) {
                $level = Level::firstOrCreate(
                    [
                        'language_id' => $language->id,
                        'name' => $levelName,
                    ],
                    ['order' => $index + 1]
                );

                // Create program for each language-level combination
                Program::firstOrCreate(
                    [
                        'language_id' => $language->id,
                        'level_id' => $level->id,
                    ],
                    [
                        'title' => "{$language->name} - {$levelName}",
                        'description' => "Learn {$language->name} at {$levelName} level",
                        'active' => true,
                    ]
                );
            }
        }
    }
}