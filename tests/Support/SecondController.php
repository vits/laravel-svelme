<?php

namespace Vits\Svelme\Tests\Support;

use Illuminate\Http\Request;
use Vits\Svelme\Http\SvelmeController;

class SecondController extends SvelmeController
{
    protected $rememberIndexParams = "testit";

    public function index(Request $request)
    {
        $query = $this->indexQuery(TestModel::class);

        return $this->indexResponse($query)
            ->render('tests/Index');
    }

    public function show(TestModel $test, Request $request)
    {
        return $test->id;
    }
}
