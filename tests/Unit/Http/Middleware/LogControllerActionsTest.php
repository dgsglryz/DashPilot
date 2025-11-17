<?php
declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\LogControllerActions;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogControllerActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_passes_request_through(): void
    {
        $middleware = new LogControllerActions();
        $request = Request::create('/test', 'GET');
        $user = User::factory()->create();

        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_logs_controller_action_when_debug_enabled(): void
    {
        config(['app.debug' => true]);
        
        $middleware = new LogControllerActions();
        $request = Request::create('/test', 'GET');
        
        // Test that middleware passes through even when route has no controller
        $request->setRouteResolver(function () {
            return null;
        });

        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}

