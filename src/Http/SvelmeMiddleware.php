<?php

namespace Vits\Svelme\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware as InertiaMiddleware;

class SvelmeMiddleware extends InertiaMiddleware
{
    /**
     * Returns shared props.
     *
     * @throws BindingResolutionException
     * @throws RuntimeException
     */
    public function share(Request $request): array
    {
        $data = [];

        if ($controller = Route::current()->getController()) {
            if (method_exists($controller, 'sharedData')) {
                $data = $controller->sharedData();
            }

            $method = Route::current()->getActionMethod();
            if (($method === 'create' || $method === 'edit')
                && method_exists($controller, 'sharedFormData')
            ) {
                $data = [
                    ...$data,
                    ...$controller->sharedFormData(),
                ];
            }
        }

        return [
            ...parent::share($request),
            ...$data,
            'auth' => $this->authData(),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'data' => session('data'),
            ],
        ];
    }

    public function authData(): array
    {
        $user = Auth::user();

        return [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $this->userPermissions(),
            ] : null,
        ];
    }

    public function userPermissions(): array
    {
        return [];
    }
}
