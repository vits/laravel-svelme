<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Vits\Svelme\Http\IndexResponseBuilder;
use Vits\Svelme\Http\SvelmePaginator;
use Vits\Svelme\Tests\Support\CustomTransformer;
use Vits\Svelme\Tests\Support\JsonResourceTransformer;
use Vits\Svelme\Tests\Support\LaravelDataTransformer;
use Vits\Svelme\Tests\Support\TestController;
use Vits\Svelme\Tests\Support\TestModel;

beforeEach(function () {
    $this->model = TestModel::create(['name' => 'first']);
    $this->second = TestModel::create(['name' => 'second']);
    $this->controller = new TestController(TestModel::query());
    $this->response = new IndexResponseBuilder(
        $this->controller,
        TestModel::query()
    );
});

it('has paginate() method with chaining', function () {
    expect($this->response->paginate())
        ->toBeInstanceOf(IndexResponseBuilder::class);
});

it('has transform() method with chaining', function () {
    expect($this->response->transform('Test'))
        ->toBeInstanceOf(IndexResponseBuilder::class);
});

it('has with() method with chaining', function () {
    expect($this->response->with())
        ->toBeInstanceOf(IndexResponseBuilder::class);
});

it('applies pagination and transformer', function ($page, $transformer) {
    if ($transformer) {
        $this->response->transform($transformer);
    }

    if ($page) {
        app()->alias(SvelmePaginator::class, LengthAwarePaginator::class);
        request()->merge(['page' => $page]);

        $this->response->paginate(1);
    }

    $props = $this->response
        ->render('Testing')->getProps();

    $data = $transformer ? [[
        'id' => $this->model->id,
        'name' => $this->model->name,
        'transformed' => true
    ], [
        'id' => $this->second->id,
        'name' => $this->second->name,
        'transformed' => true
    ]] : [
        $this->model->toArray(),
        $this->second->toArray(),
    ];

    if ($page) {
        expect($props)
            ->{'items'}
            ->toMatchArray([
                'data' => [
                    $data[$page - 1]
                ],
                'pagination' => [
                    'currentPage' => $page,
                    'total' => 2,
                    'perPage' => 1,
                    'totalPages' => 2
                ]
            ])
            ->{'items'}
            ->{'data'}
            ->toHaveCount(1);
    } else {
        expect($props)
            ->{'items'}
            ->toHaveCount(2)
            ->toMatchArray($data);
    }
})->with([
    // page
    null,
    1,
    2
])->with([
    // transformer
    null,
    JsonResourceTransformer::class,
    LaravelDataTransformer::class,
    CustomTransformer::class,
    function ($items) {
        return collect($items)->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'transformed' => true
        ]);
    }
]);

describe('render()', function () {
    test('returns Inertia\Response instance with a given component', function () {
        $rendered = $this->response->render('Testing');

        expect($rendered)
            ->toBeInstanceOf(\Inertia\Response::class);

        expect(getPrivateProperty($rendered, 'component'))
            ->toBe('Testing');
    });

    test('sets component items prop', function () {
        $props = $this->response
            ->render('Testing')->getProps();

        expect($props)
            ->{'items'}
            ->toBeArray();
    });

    test('returns with() data as props', function () {
        $data = ['testing' => 'data', 'props' => true];

        $props = $this->response
            ->with($data)
            ->render('Testing')->getProps();

        expect($props)
            ->toMatchArray($data);
    });

    test('redirects to last page if requested page is too large', function () {
        Route::resource('tests', TestController::class);
        app()->alias(SvelmePaginator::class, LengthAwarePaginator::class);
        request()->merge(['page' => 10]);

        $response = $this->response->paginate(1)->render('test');

        expect($response)
            ->toBeInstanceOf(RedirectResponse::class)
            ->getTargetUrl()->toBe('http://localhost/tests?page=2');
    });

    test('redirects without page number if only single page exists', function () {
        Route::resource('tests', TestController::class);
        app()->alias(SvelmePaginator::class, LengthAwarePaginator::class);
        request()->merge(['page' => 10]);

        $response = $this->response->paginate(2)->render('test');

        expect($response)
            ->toBeInstanceOf(RedirectResponse::class)
            ->getTargetUrl()->toBe('http://localhost/tests');
    });

    test('remembers current page number', function () {
        Route::resource('tests', TestController::class);
        app()->alias(SvelmePaginator::class, LengthAwarePaginator::class);
        request()->merge(['page' => 2]);

        $this->response->paginate(1)->render('test');

        expect(session(TestController::class . '--index-page'))
            ->toBe(2);
    });
});
