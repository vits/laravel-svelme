<?php

use Illuminate\Contracts\Database\Eloquent\Builder;
use Inertia\Inertia;
use Vits\Svelme\Tests\Support\TestController;
use Vits\Svelme\Tests\Support\TestModel;

beforeEach(function () {
    $this->controller = new TestController();
});

describe('indexQuery()', function () {
    test('returns query builder', function () {
        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::query()
        );

        expect($query)->toBeInstanceOf(Builder::class);
    });

    test('uses given query', function () {
        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::where('id', '>', 10)
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models" where "id" > ?')
            ->getBindings()
            ->toBe([10]);
    });

    test('uses empty model query if class name given', function () {
        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::class
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models"');
    });

    test('calls getIndexQueryOptions() and uses received value', function () {
        $mock = Mockery::mock(TestController::class)->makePartial();
        $mock
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getIndexQueryOptions')
            ->once()
            ->andReturn([
                'sortable' => ['field'],
                'searchable' => true,
            ]);

        $query = invokePrivateMethod(
            $mock,
            'indexQuery',
            TestModel::class
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models" order by "field" asc');
    });

    test('uses first sortable field', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['sortable' => ['name', 'other']]
        );

        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::class
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models" order by "name" asc');
    });

    test('uses sortable field name from request', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['sortable' => ['name', 'other']]
        );
        request()->merge(['orderby' => 'other', 'desc' => 1]);

        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::class
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models" order by "other" desc');
    });

    test('ignores unknown sortable field from request', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['sortable' => ['name', 'other']]
        );
        request()->merge(['order' => 'unknown', 'desc' => 1]);

        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::class
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models" order by "name" desc');
    });

    test('uses search string from request', function () {
        $mock = Mockery::mock(TestModel::query());
        $mock->shouldReceive('simpleSearch')
            ->with('something')
            ->andReturn(TestModel::where('search', 'something'));

        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['searchable' => true]
        );
        request()->merge(['search' => 'something']);

        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            $mock
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models" where "search" = ?');
    });

    test('ignores search string from request if not searchable', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['searchable' => false]
        );
        request()->merge(['search' => 'something']);

        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::class
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models"');
    });

    test('uses search scope set by controller property', function () {
        $mock = Mockery::mock(TestModel::query());
        $mock
            ->shouldReceive('searchScope')
            ->with('something')
            ->andReturn(TestModel::where('search', 'something'));

        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['searchable' => true]
        );
        setPrivateProperty(
            $this->controller,
            'indexQuerySearchScope',
            'searchScope'
        );
        request()->merge(['search' => 'something']);

        $query = invokePrivateMethod(
            $this->controller,
            'indexQuery',
            $mock
        );

        expect($query)
            ->toSql()
            ->toBe('select * from "test_models" where "search" = ?');
    });

    test('adds full index query options to Inertia shared array', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['sortable' => ['name', 'other'], 'searchable' => true]
        );
        request()->merge(['search' => 'something', 'orderby' => 'other']);
        invokePrivateMethod(
            $this->controller,
            'indexQuery',
            TestModel::class
        );

        expect(Inertia::getShared())->toMatchArray([
            'index_query_options' => [
                'sortable' => ['name', 'other'],
                'searchable' => true,
                'search' => 'something',
                'orderby' => 'other',
                'desc' => false,
                'filters' => [],
            ]
        ]);
    });
});

describe('getIndexQueryOptions()', function () {
    test('returns default options', function () {
        $options = invokePrivateMethod(
            $this->controller,
            'getIndexQueryOptions'
        );

        expect($options)->toBe([
            'sortable' => [],
            'searchable' => false,
            'filters' => []
        ]);
    });

    test('returns normalized options from class properties', function () {
        setPrivateProperty($this->controller, 'indexQueryOptions', [
            'searchable' => 1,
            'sortable' => 'name'
        ]);

        $options = invokePrivateMethod(
            $this->controller,
            'getIndexQueryOptions'
        );

        expect($options)->toBe([
            'sortable' => ['name'],
            'searchable' => true,
            'filters' => []
        ]);
    });

    test('ignores invalid class properties', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            'invalid type'
        );

        $options = invokePrivateMethod(
            $this->controller,
            'getIndexQueryOptions'
        );

        expect($options)
            ->toBe([
                'sortable' => [],
                'searchable' => false,
                'filters' => [],
            ]);
    });

    test('ignores unknown options', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            [
                'searchable' => true,
                'unknown' => true
            ]
        );

        $options = invokePrivateMethod(
            $this->controller,
            'getIndexQueryOptions'
        );

        expect($options)
            ->toBe([
                'sortable' => [],
                'searchable' => true,
                'filters' => []
            ]);
    });
});
