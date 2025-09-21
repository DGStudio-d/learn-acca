<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'allow_guest_languages',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Allow unauthenticated users to view available languages'
            ],
            [
                'key' => 'allow_guest_teachers',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Allow guests to see teacher profiles and their associated languages'
            ],
            [
                'key' => 'allow_guest_quizzes',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Allow anonymous quiz attempts'
            ]
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
