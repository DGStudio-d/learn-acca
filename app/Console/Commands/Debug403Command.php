<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class Debug403Command extends Command
{
    protected $signature = 'debug:403 {--token= : Test with specific token}';
    protected $description = 'Debug 403 Forbidden issues in the API';

    public function handle()
    {
        $this->info('=== API 403 Debugging Tool ===');
        
        // 1. Check Sanctum configuration
        $this->checkSanctumConfig();
        
        // 2. Check middleware configuration
        $this->checkMiddlewareConfig();
        
        // 3. Check user roles and permissions
        $this->checkUserRoles();
        
        // 4. Test specific token if provided
        if ($token = $this->option('token')) {
            $this->testToken($token);
        }
        
        $this->info("\n=== Debug Complete ===");
    }
    
    private function checkSanctumConfig()
    {
        $this->info("\n1. Sanctum Configuration:");
        
        $statefulDomains = config('sanctum.stateful');
        $this->line("   Stateful domains: " . implode(', ', $statefulDomains));
        
        $guards = config('sanctum.guard');
        $this->line("   Guards: " . implode(', ', $guards));
        
        $expiration = config('sanctum.expiration');
        $this->line("   Token expiration: " . ($expiration ? $expiration . ' minutes' : 'Never'));
        
        $middleware = config('sanctum.middleware');
        $this->line("   Middleware count: " . count($middleware));
    }
    
    private function checkMiddlewareConfig()
    {
        $this->info("\n2. Middleware Configuration:");
        
        // Check if custom middleware are registered
        $middlewares = [
            'auth:sanctum' => 'Laravel Sanctum Auth',
            'role:admin' => 'Role Middleware (Admin)',
            'role:teacher' => 'Role Middleware (Teacher)',
            'role:student' => 'Role Middleware (Student)',
            'permission:manage-quizzes' => 'Permission Middleware',
            'guest.access:languages' => 'Guest Access Middleware',
            'teacher.language.access' => 'Teacher Language Access',
        ];
        
        foreach ($middlewares as $alias => $description) {
            try {
                app(\Illuminate\Routing\Router::class)->getMiddleware()[$alias] ?? 
                app(\Illuminate\Routing\Router::class)->getMiddlewareGroups()[$alias] ?? null;
                $this->line("   ✅ {$description}");
            } catch (\Exception $e) {
                $this->error("   ❌ {$description} - Not found");
            }
        }
    }
    
    private function checkUserRoles()
    {
        $this->info("\n3. User Roles Distribution:");
        
        $roles = ['admin', 'teacher', 'student'];
        
        foreach ($roles as $role) {
            $count = User::where('role', $role)->count();
            $this->line("   {$role}: {$count} users");
        }
        
        // Check if spatie/laravel-permission is being used
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $this->line("\n   Using Spatie Permissions:");
            $spatieRoles = \Spatie\Permission\Models\Role::all();
            foreach ($spatieRoles as $role) {
                $userCount = $role->users()->count();
                $this->line("     {$role->name}: {$userCount} users");
            }
        }
    }
    
    private function testToken($token)
    {
        $this->info("\n4. Token Testing:");
        
        try {
            // Find the token
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                $this->error("   ❌ Token not found in database");
                return;
            }
            
            $this->line("   ✅ Token found");
            $this->line("   Token ID: {$accessToken->id}");
            $this->line("   Token name: {$accessToken->name}");
            $this->line("   Created: {$accessToken->created_at}");
            $this->line("   Last used: " . ($accessToken->last_used_at ?? 'Never'));
            
            // Check the user
            $user = $accessToken->tokenable;
            if ($user) {
                $this->line("   User ID: {$user->id}");
                $this->line("   User email: {$user->email}");
                $this->line("   User role: {$user->role}");
                
                // Check user permissions if using Spatie
                if (method_exists($user, 'getRoleNames')) {
                    $userRoles = $user->getRoleNames();
                    $this->line("   Spatie roles: " . $userRoles->implode(', '));
                }
                
                if (method_exists($user, 'getPermissionNames')) {
                    $permissions = $user->getPermissionNames();
                    $this->line("   Permissions: " . $permissions->implode(', '));
                }
            } else {
                $this->error("   ❌ No user associated with token");
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing token: " . $e->getMessage());
        }
    }
}