<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User management
            'manage-users',
            'view-users',
            'create-users',
            'update-users',
            'delete-users',
            
            // Language management
            'manage-languages',
            'view-languages',
            'create-languages',
            'update-languages',
            'delete-languages',
            
            // Program management
            'manage-programs',
            'view-programs',
            'create-programs',
            'update-programs',
            'delete-programs',
            
            // Quiz management
            'manage-quizzes',
            'view-quizzes',
            'create-quizzes',
            'update-quizzes',
            'delete-quizzes',
            'attempt-quizzes',
            
            // Meeting management
            'manage-meetings',
            'view-meetings',
            'create-meetings',
            'update-meetings',
            'delete-meetings',
            
            // Teacher management
            'manage-teachers',
            'assign-teacher-languages',
            'upload-teacher-images',
            
            // Student management
            'manage-students',
            'approve-student-access',
            'view-student-progress',
            
            // Settings management
            'manage-settings',
            'update-guest-access',
            
            // Notification management
            'send-notifications',
            'view-notification-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin role - has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Teacher role - limited permissions
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $teacherRole->givePermissionTo([
            'view-programs',
            'view-quizzes',
            'create-quizzes',
            'update-quizzes',
            'delete-quizzes',
            'view-meetings',
            'create-meetings',
            'update-meetings',
            'delete-meetings',
            'upload-teacher-images',
            'view-student-progress',
            'send-notifications',
        ]);

        // Student role - very limited permissions
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $studentRole->givePermissionTo([
            'view-programs',
            'view-quizzes',
            'attempt-quizzes',
            'view-meetings',
        ]);
    }
}