<?php

namespace Vits\Svelme\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', Str::random(32));
        Config::set('inertia.testing.ensure_pages_exist', false);

        \Inertia\Response::macro('getProps', function ($key = null) {
            // render any response objects to arrays
            /** @disregard P014 */
            $res = new JsonResponse($key ? $this->props[$key] : $this->props);
            return $res->getData(true);
        });

        /** @disregard P1013 */
        View::getFinder()->prependLocation(
            __DIR__ . '/resources'
        );

        $this->setupDatabase($this->app);
    }

    protected function setupDatabase($app): void
    {
        $builder = $app['db']->connection()->getSchemaBuilder();

        $builder->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
        });
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        $serviceProviders = [
            LaravelDataServiceProvider::class,
            ServiceProvider::class
        ];

        return $serviceProviders;
    }
}
