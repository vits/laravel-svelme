<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Vits\Svelme\Http\SvelmeMiddleware;
use Vits\Svelme\Tests\Support\TestController;

it('extends Inertia\Middleware', function () {
    $middleware = new SvelmeMiddleware();

    expect($middleware)->toBeInstanceOf(\Inertia\Middleware::class);
});

describe('share()', function () {
    test('builds shared array from parent class and controller shared data', function () {
        setupController();
        $middleware = new SvelmeMiddleware();
        $shared = $middleware->share(request());

        expect($shared)
            ->toMatchArray([
                'shared' => 'value',
                'auth' => [
                    'user' => null
                ],
                'flash' => [
                    'success' => null,
                    'error' => null
                ]
            ])->not->toHaveKey('form')
            ->{'errors'}
            ->toBeInstanceOf(\Inertia\AlwaysProp::class);

        expect($shared['errors'])->toBeInstanceOf(\Inertia\AlwaysProp::class);
    });

    test('calls controller sharedFormData() for create and edit actions', function ($action) {
        setupController(['action' => $action]);

        $middleware = new SvelmeMiddleware();
        $shared = $middleware->share(request());

        expect($shared)->toMatchArray([
            'shared' => 'value',
            'formdata' => 'shared'
        ]);
    })->with(['create', 'edit']);

    test('returns session flash values', function () {
        setupController();
        session()->flash('success', 'Success!');
        session()->flash('error', 'Error!');

        $middleware = new SvelmeMiddleware();
        $shared = $middleware->share(request());

        expect($shared)->toMatchArray([
            'flash' => [
                'success' => 'Success!',
                'error' => 'Error!',
            ]
        ]);
    });

    test('uses authData()', function () {
        setupController();

        $middleware = Mockery::mock(SvelmeMiddleware::class)->makePartial();
        $middleware->shouldReceive('authData')->andReturn([
            'user' => 'testing',
            'other' => 'data'
        ]);
        /** @disregard P1013 Undefined method */
        $shared = $middleware->share(request());

        expect($shared)->toMatchArray([
            'auth' => [
                'user' => 'testing',
                'other' => 'data'
            ]
        ]);
    });
});

describe('authData()', function () {
    test('returns correct data if no auth user', function () {
        $middleware = new SvelmeMiddleware();

        expect($middleware->authData())->toBe([
            'user' => null,
        ]);
    });

    test('returns current user with permissions from userPermissions()', function () {
        $middleware = Mockery::mock(SvelmeMiddleware::class)->makePartial();
        $middleware
            ->shouldReceive('userPermissions')
            ->once()
            ->andReturn([
                'testing',
                'permissions'
            ]);

        $user = new class {
            public $id = 1;
            public $name = 'test';
            public $email = 'test@example.com';
        };
        Auth::shouldReceive('user')->andReturn($user);

        /** @disregard P1013 */
        expect($middleware->authData())->toBe([
            'user' => [
                'id' => 1,
                'name' => 'test',
                'email' => 'test@example.com',
                'permissions' => ['testing', 'permissions']
            ],
        ]);
    });
});

function setupController($options = [])
{
    $controller = new TestController();

    $mockRoute = Mockery::mock(\Illuminate\Routing\Route::class);
    $mockRoute
        ->shouldReceive('getController')
        ->andReturn($controller);
    $mockRoute
        ->shouldReceive('getActionMethod')
        ->andReturn($options['action'] ?? 'index');
    Route::shouldReceive('current')
        ->andReturn($mockRoute);

    Auth::shouldReceive('user')
        ->andReturn($options['user'] ?? null);

    return $controller;
}
