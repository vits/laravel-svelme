<?php

namespace Vits\Svelme\Http;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Vits\Svelme\Http\Traits\IndexQuery;
use Vits\Svelme\Http\Traits\RememberParams;

class SvelmeController extends Controller
{
    use IndexQuery;
    use RememberParams;

    public function indexResponse(Builder|Relation $query)
    {
        $response = new IndexResponseBuilder($this, $query);

        foreach ($this->getRememberedParamNames() as $name) {
            $this->rememberIndexParam($name, request($name));
        }

        return $response;
    }

    /**
     * Renders Inertia or JSON response.
     *
     * @param string $component
     * @param array $props
     * @return JsonResponse|Response
     * @throws BindingResolutionException
     */
    public function render(string $component, $props = [])
    {
        if (!request()->inertia() && request()->expectsJson()) {
            $shared = [];
            if (method_exists($this, 'sharedData')) {
                $shared = [...$shared, ...$this->sharedData()];
            }

            $method = Route::current()->getActionMethod();
            if (('create' === $method || 'edit' === $method)
                && method_exists($this, 'sharedFormData')
            ) {
                $shared = [
                    ...$shared,
                    ...$this->sharedFormData(),
                ];
            }
            return response()->json([...$shared, ...$props]);
        }

        return Inertia::render(
            $component,
            $props
        );
    }

    /**
     * Returns redirector back to index page with saved parameters applied.
     *
     * @param array $params
     *
     * @return Redirector|RedirectResponse
     *
     * @throws BindingResolutionException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function redirect(
        ?int $page = null,
        $params = []
    ) {
        if ($redirectTo = request()->get('redirectto')) {
            $all = request()->all();
            Arr::forget($all, ['redirectto']);
            request()->replace($all);

            $targets = $this->redirectTargets();
            if ($target = $targets[$redirectTo] ?? null) {
                if (is_string($target)) {
                    return redirect($target);
                }

                if ($controller = $target['controller'] ?? null) {
                    return (new $controller())->redirect();
                }
            }
        }

        if (null === $page) {
            $page = (int) $this->getRememberedIndexParam('page');
        }

        if ($page < 2) {
            $page = null;
        }

        return redirect($this->getIndexUrl([
            ...$this->getRememberedParams(),
            'page' => $page,
            ...$params,
        ]));
    }

    public function redirectOrJson(
        $data = [],
        $success = null,
        ?int $page = null,
        $params = [],
        $error = null
    ) {
        if (request()->inertia()) {
            $response = $this->redirect($page, $params);
            if ($error) {
                $response = $response->with('error', $error);
            } elseif ($success) {
                $response = $response->with('success', $success);
            }
        } elseif (request()->expectsJson()) {
            if (is_callable($data)) {
                $data = $data();
            }
            if ($error) {
                $data['_error'] = $error;
            } elseif ($success) {
                $data['_success'] = $success;
            }
            $response = response()->json($data);
        } else {
            $response = response();
        }

        return $response;
    }

    /**
     * Returns URL for index route of current controller.
     *
     * @param array $params
     *
     * @return string
     *
     * @throws BindingResolutionException
     */
    protected function getIndexUrl($params = [])
    {
        return route(
            $this->getIndexRouteName(),
            [
                ...$this->indexRouteParams(),
                ...$params,
            ]
        );
    }

    /**
     * Returns index route name for current controller.
     *
     * @return string
     */
    protected function getIndexRouteName()
    {
        return Route::getRoutes()
            ->getByAction(static::class . '@index')
            ->action['as'];
    }

    /**
     * Returns parameters required to build index route.
     *
     * @return array
     */
    protected function indexRouteParams()
    {
        return [];
    }

    /**
     * Returns available redirect target routes.
     *
     * @return array
     */
    protected function redirectTargets()
    {
        return [];
    }
}
