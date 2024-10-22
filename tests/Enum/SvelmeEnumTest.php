<?php

use Illuminate\Contracts\Translation\Translator;
use Vits\Svelme\Tests\Support\TestEnum;

beforeEach(function () {
    $this->translator = Mockery::mock(Translator::class);

    app()->bind('translator', function () {
        return $this->translator;
    });
});

it('has translated label', function () {
    $this->translator
        ->shouldReceive('get')
        ->with('app.enums.test_enum.first', [], null)
        ->andReturn('First');

    expect(TestEnum::FIRST->value)->toBe('first');
    expect(TestEnum::FIRST->label())->toBe('First');
});

test('values() returns array of case values', function () {
    expect(TestEnum::values())->toBe(['first', 'second']);
});

test('toOptions() returns array of values and labels', function () {
    $this->translator
        ->shouldReceive('get')
        ->with('app.enums.test_enum.first', [], null)
        ->andReturn('First');
    $this->translator
        ->shouldReceive('get')
        ->with('app.enums.test_enum.second', [], null)
        ->andReturn('Second');

    expect(TestEnum::toOptions())->toBe([
        ['value' => 'first', 'label' => 'First'],
        ['value' => 'second', 'label' => 'Second'],
    ]);
});
