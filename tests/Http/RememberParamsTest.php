<?php

use Vits\Svelme\Http\SvelmeController;
use Vits\Svelme\Tests\Support\TestController;

beforeEach(function () {
    $this->controller = new TestController();
});

it('stores and retrieves remembered value', function () {
    $this->controller->rememberIndexParam('testing', 123);

    expect($this->controller->getRememberedIndexParam('testing'))
        ->toBe(123);
});

it('returns null if value is not remembered', function () {
    expect($this->controller->getRememberedIndexParam('unknown'))
        ->toBeNull();
});

it('returns given default value if value is not remembered', function () {
    expect($this->controller->getRememberedIndexParam('unknown', 'default'))
        ->toBe('default');
});

it('remembers values per class name', function () {
    $other = new class extends SvelmeController {};

    $this->controller->rememberIndexParam('test', 123);
    $other->rememberIndexParam('test', 456);
    $sameClass = new TestController();

    expect($this->controller->getRememberedIndexParam('test'))
        ->toBe(123);
    expect($other->getRememberedIndexParam('test'))
        ->toBe(456);
    expect($sameClass->getRememberedIndexParam('test'))
        ->toBe(123);
});

describe('getRememberedParamNames()', function () {
    test('returns empty array', function () {
        $names = $this->controller->getRememberedParamNames();
        expect($names)
            ->toBe([]);
    });

    test('returns names from rememberIndexParams property array', function () {
        setPrivateProperty(
            $this->controller,
            'rememberIndexParams',
            ['first', 1, false, null, 'second']
        );

        $names = $this->controller->getRememberedParamNames();
        expect($names)
            ->toBe(['first', 'second']);
    });

    test('returns names from rememberIndexParams property string', function () {
        setPrivateProperty(
            $this->controller,
            'rememberIndexParams',
            'first  ,  , second'
        );

        $names = $this->controller->getRememberedParamNames();
        expect($names)
            ->toBe(['first', 'second']);
    });

    test('returns names useed by indexQuery', function () {
        setPrivateProperty(
            $this->controller,
            'indexQueryOptions',
            ['searchable' => true, 'sortable' => 'name']
        );

        $names = $this->controller->getRememberedParamNames();
        expect($names)
            ->toBe(['search', 'orderby', 'desc']);
    });
});

describe('getRememberedParams()', function () {
    test('returns empty array', function () {
        $params = $this->controller->getRememberedParams();

        expect($params)
            ->toBe([]);
    });

    test('returns allowed params if they have remembered value', function () {
        setPrivateProperty(
            $this->controller,
            'rememberIndexParams',
            'first,second'
        );

        $this->controller->rememberIndexParam('first', 123);
        $this->controller->rememberIndexParam('unknown', 456);

        $params = $this->controller->getRememberedParams();

        expect($params)
            ->toMatchArray([
                'first' => 123
            ]);
    });
});
