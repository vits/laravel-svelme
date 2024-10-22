<?php

namespace Vits\Svelme\Tests\Support;

use Illuminate\Http\Request;
use Vits\Svelme\Http\SvelmeController;

class TestController extends SvelmeController
{
    protected $indexQueryOptions = null;
    protected $indexQuerySearchScope = null;
    protected $rememberIndexParams = null;

    public function index(Request $request)
    {
        $query = $this->indexQuery(TestModel::class);

        return $this->indexResponse($query)
            ->paginate(1)
            ->with(['more' => 123])
            ->render('tests/Index');
    }

    public function sharedData()
    {
        return [
            'shared' => 'value'
        ];
    }

    public function sharedFormData()
    {
        return [
            'formdata' => 'shared'
        ];
    }
}
