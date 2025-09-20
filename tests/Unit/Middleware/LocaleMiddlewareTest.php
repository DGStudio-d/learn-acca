<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\LocaleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocaleMiddlewareTest extends TestCase
{
    private LocaleMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new LocaleMiddleware();
    }

    public function test_sets_locale_from_accept_language_header()
    {
        // Arrange
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept-Language', 'es');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('es', App::getLocale());
    }

    public function test_sets_locale_from_query_parameter()
    {
        // Arrange
        $request = Request::create('/?locale=ar', 'GET');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('ar', App::getLocale());
    }

    public function test_query_parameter_takes_precedence_over_header()
    {
        // Arrange
        $request = Request::create('/?locale=ar', 'GET');
        $request->headers->set('Accept-Language', 'es');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('ar', App::getLocale());
    }

    public function test_falls_back_to_default_locale_for_unsupported_locale()
    {
        // Arrange
        $request = Request::create('/?locale=fr', 'GET');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('en', App::getLocale()); // Default fallback
    }

    public function test_falls_back_to_default_locale_when_no_locale_specified()
    {
        // Arrange
        $request = Request::create('/', 'GET');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('en', App::getLocale()); // Default fallback
    }

    public function test_supports_arabic_locale()
    {
        // Arrange
        $request = Request::create('/?locale=ar', 'GET');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('ar', App::getLocale());
    }

    public function test_supports_english_locale()
    {
        // Arrange
        $request = Request::create('/?locale=en', 'GET');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('en', App::getLocale());
    }

    public function test_supports_spanish_locale()
    {
        // Arrange
        $request = Request::create('/?locale=es', 'GET');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('es', App::getLocale());
    }

    public function test_handles_malformed_accept_language_header()
    {
        // Arrange
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept-Language', 'invalid-locale-format');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('en', App::getLocale()); // Should fallback to default
    }

    public function test_handles_empty_locale_parameter()
    {
        // Arrange
        $request = Request::create('/?locale=', 'GET');

        // Act
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert
        $this->assertEquals('en', App::getLocale()); // Should fallback to default
    }

    public function test_middleware_continues_request_processing()
    {
        // Arrange
        $request = Request::create('/?locale=es', 'GET');
        $nextCalled = false;

        // Act
        $response = $this->middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('Success');
        });

        // Assert
        $this->assertTrue($nextCalled);
        $this->assertEquals('Success', $response->getContent());
    }
}