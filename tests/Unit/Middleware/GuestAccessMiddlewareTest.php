<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\GuestAccessMiddleware;
use App\Models\Settings;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuestAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private GuestAccessMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new GuestAccessMiddleware(new SettingsService());
    }

    public function test_allows_authenticated_users_regardless_of_settings()
    {
        // Disable guest access
        Settings::setValue('allow_guest_languages', false, 'boolean');

        $user = User::factory()->create();
        $request = Request::create('/test');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'languages');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_allows_guest_when_feature_is_enabled()
    {
        Settings::setValue('allow_guest_languages', true, 'boolean');

        $request = Request::create('/test');
        $request->setUserResolver(fn() => null); // No authenticated user

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'languages');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_denies_guest_when_feature_is_disabled()
    {
        Settings::setValue('allow_guest_languages', false, 'boolean');

        $request = Request::create('/test');
        $request->setUserResolver(fn() => null); // No authenticated user

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'languages');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('GUEST_ACCESS_DENIED', $data['error']['code']);
        $this->assertEquals('languages', $data['error']['feature']);
        $this->assertStringContainsString('Guest access to languages is not allowed', $data['error']['message']);
    }

    public function test_handles_different_features()
    {
        Settings::setValue('allow_guest_teachers', true, 'boolean');
        Settings::setValue('allow_guest_quizzes', false, 'boolean');

        $request = Request::create('/test');
        $request->setUserResolver(fn() => null);

        // Test teachers feature (enabled)
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'teachers');

        $this->assertEquals(200, $response->getStatusCode());

        // Test quizzes feature (disabled)
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'quizzes');

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('quizzes', $data['error']['feature']);
    }

    public function test_handles_nonexistent_feature()
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn() => null);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'nonexistent');

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('GUEST_ACCESS_DENIED', $data['error']['code']);
        $this->assertEquals('nonexistent', $data['error']['feature']);
    }
}