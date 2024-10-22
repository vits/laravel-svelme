<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Vits\Svelme\Http\IndexResponseBuilder;
use Vits\Svelme\Http\SvelmeController;
use Vits\Svelme\Http\SvelmeMiddleware;
use Vits\Svelme\Http\SvelmePaginator;
use Vits\Svelme\Http\Traits\IndexQuery;
use Vits\Svelme\Tests\Support\SecondController;
use Vits\Svelme\Tests\Support\TestController;
use Vits\Svelme\Tests\Support\TestModel;

beforeEach(function () {
    $this->controller = new TestController();
});

it('extends Illuminate\Routing\Controller', function () {
    expect($this->controller)->toBeInstanceOf(\Illuminate\Routing\Controller::class);
});

it('uses IndexQuery trait', function () {
    expect(IndexQuery::class)
        ->toBeIn(class_uses_recursive(SvelmeController::class));
});

test('indexResponse() returns instance of IndexResponseBuilder', function () {
    expect($this->controller)
        ->indexResponse(TestModel::query())
        ->toBeInstanceOf(IndexResponseBuilder::class);
});

test('indexResponse() stores remembered params', function () {
    setPrivateProperty($this->controller, 'rememberIndexParams', 'some,more');
    setPrivateProperty(
        $this->controller,
        'indexQueryOptions',
        ['sortable' => ['name', 'other'], 'searchable' => true]
    );

    request()->merge([
        'search' => 'query',
        'orderby' => 'other',
        'desc' => true,
        'some' => 'value',
        'unknown' => 'ignored'
    ]);

    $this->controller
        ->indexResponse(TestModel::query())
        ->render('test');

    expect($this->controller->getRememberedParams())
        ->toBe([
            'some' => 'value',
            'search' => 'query',
            'orderby' => 'other',
            'desc' => true,
        ]);
});

describe('redirect()', function () {
    test('redirects to index action', function () {
        Route::resource('tests', TestController::class);
        $redirect = $this->controller->redirect();

        expect($redirect)
            ->toBeInstanceOf(RedirectResponse::class)
            ->getTargetUrl()->toBe('http://localhost/tests');
    });

    test('redirects to given page', function () {
        Route::resource('tests', TestController::class);
        $redirect = $this->controller->redirect(5);

        expect($redirect)
            ->getTargetUrl()->toBe('http://localhost/tests?page=5');
    });

    test('redirects without page number if page == 1', function () {
        Route::resource('tests', TestController::class);
        $redirect = $this->controller->redirect(1);

        expect($redirect)
            ->getTargetUrl()->toBe('http://localhost/tests');
    });

    test('redirects to remembered page', function () {
        Route::resource('tests', TestController::class);
        session()->put(TestController::class . '--index-page', 3);

        $redirect = $this->controller->redirect();

        expect($redirect)
            ->getTargetUrl()->toBe('http://localhost/tests?page=3');
    });

    test('adds optional URL parameters', function () {
        Route::resource('tests', TestController::class);
        $redirect = $this->controller->redirect(3, ['more' => 'yes']);

        expect($redirect)
            ->getTargetUrl()->toBe('http://localhost/tests?page=3&more=yes');

        $redirect = $this->controller->redirect(null, ['more' => 'yes']);

        expect($redirect)
            ->getTargetUrl()->toBe('http://localhost/tests?more=yes');
    });

    test('calls getRememberedParams() and adds received URL parameters', function () {
        $mock = Mockery::mock(TestController::class)->makePartial();
        $mock
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getRememberedParams')
            ->once()
            ->andReturn(['test' => 1, 'other' => 'test']);
        $mock
            ->shouldReceive('getIndexRouteName')
            ->andReturn('tests.index');

        Route::resource('tests', TestController::class);
        /** @disregard P1013 */
        $redirect = $mock->redirect();

        expect($redirect)
            ->getTargetUrl()->toBe('http://localhost/tests?test=1&other=test');
    });

    test('adds registered remembered URL parameters except page and given params', function () {
        setPrivateProperty($this->controller, 'rememberIndexParams', 'some,more');

        $this->controller->rememberIndexParam('test', 123);
        $this->controller->rememberIndexParam('some', 'more');
        $this->controller->rememberIndexParam('more', 'no');
        $this->controller->rememberIndexParam('page', 10);

        Route::resource('tests', TestController::class);
        $redirect = $this->controller->redirect(3, ['more' => 'yes']);

        expect($redirect)
            ->getTargetUrl()->toBe('http://localhost/tests?some=more&more=yes&page=3');
    });

    describe('when request has redirectto', function () {
        test('redirects to defined route', function () {
            $mock = Mockery::mock(TestController::class)->makePartial();

            $mock
                ->shouldAllowMockingProtectedMethods()
                ->shouldReceive('redirectTargets')
                ->once()
                ->andReturn([
                    'target' => 'http://targeted/test'
                ]);

            request()->merge([
                'redirectto' => 'target',
            ]);

            /** @disregard P1013 */
            $redirect = $mock->redirect();

            expect($redirect)
                ->getTargetUrl()->toBe('http://targeted/test');
        });

        test('redirects to defined controller index with page and remembered values', function () {
            Route::resource('second', SecondController::class);
            $model = TestModel::create(['name' => 'first']);
            $mock = Mockery::mock(TestController::class)->makePartial();
            $mock
                ->shouldAllowMockingProtectedMethods()
                ->shouldReceive('redirectTargets')
                ->once()
                ->andReturn([
                    'target' => [
                        'controller' => SecondController::class,
                        'params' => [$model, request()]
                    ]
                ]);

            session()->put(SecondController::class . '--index-page', 3);
            session()->put(SecondController::class . '--index-testit', 'yes');

            request()->merge([
                'redirectto' => 'target',
            ]);

            /** @disregard P1013 */
            $redirect = $mock->redirect();

            expect($redirect)
                ->getTargetUrl()->toBe('http://localhost/second?testit=yes&page=3');
        });

        test('ignores unknown redirect target', function () {
            request()->merge([
                'redirectto' => 'unknown',
            ]);

            Route::resource('tests', TestController::class);
            $redirect = $this->controller->redirect();

            expect($redirect)
                ->toBeInstanceOf(RedirectResponse::class)
                ->getTargetUrl()->toBe('http://localhost/tests');
        });
    });
});

test('getIndexUrl() builds index URL using values from indexRouteName() and indexRouteParams()', function () {
    class IndexController extends SvelmeController
    {
        protected function indexRouteParams()
        {
            return [123];
        }
    }
    $controller = new IndexController();

    Route::resource('nested.route-tests', IndexController::class);
    $url = invokePrivateMethod($controller, 'getIndexUrl');

    expect($url)->toBe('http://localhost/nested/123/route-tests');
});

test('getIndexUrl() adds optional URL parameters', function () {
    Route::resource('route-tests', TestController::class);
    $url = invokePrivateMethod($this->controller, 'getIndexUrl', ['test' => 1, 'more' => 'yes']);

    expect($url)->toBe('http://localhost/route-tests?test=1&more=yes');
});

test('getIndexRouteName() finds route matching controller index action', function () {
    Route::as('some')->resource('nested.route-tests', TestController::class);
    $route = invokePrivateMethod($this->controller, 'getIndexRouteName');

    expect($route)->toBe('some.nested.route-tests.index');
});

test('index action returns correct Inertia response', function () {
    app()->alias(SvelmePaginator::class, LengthAwarePaginator::class);

    $model = TestModel::create(['name' => 'first']);
    $this->assertDatabaseCount('test_models', 1);

    Route::resource('tests', TestController::class)
        ->middleware(['web', SvelmeMiddleware::class]);

    $this->get('/tests')
        ->assertInertia(
            fn(Assert $page) => expect($page)
                ->component('tests/Index')
                ->toArray()
                ->{'props'}
                ->toMatchArray([
                    'items' => [
                        'data' => [$model->toArray()],
                        'pagination' => [
                            'total' => 1,
                            'perPage' => 1,
                            'currentPage' => 1,
                            'totalPages' => 1
                        ]
                    ],
                    'index_query_options' => [
                        'sortable' => [],
                        'searchable' => false,
                        'filters' => []
                    ],
                    'auth' => [
                        'user' => null
                    ],
                    'shared' => 'value',
                    'more' => 123
                ])
        );
});
