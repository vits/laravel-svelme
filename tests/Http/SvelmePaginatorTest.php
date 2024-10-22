<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Vits\Svelme\Http\SvelmePaginator;

test('SvelmePaginator extends LengthAwarePaginator', function () {
    expect(SvelmePaginator::class)->toExtend(LengthAwarePaginator::class);
});

test('returns items and pagination array', function () {
    $paginator = new SvelmePaginator(['items'], 10, 5, 1);

    expect($paginator->toArray())->toBe([
        'data' => ['items'],
        'pagination' => [
            'total' => 10,
            'perPage' => 5,
            'currentPage' => 1,
            'totalPages' => 2
        ]
    ]);
});
