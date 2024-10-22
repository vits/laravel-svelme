<?php

declare(strict_types=1);

namespace Vits\Svelme;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Vits\Svelme\Http\SvelmePaginator;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->alias(SvelmePaginator::class, LengthAwarePaginator::class);
        $this->app->alias(SvelmePaginator::class, LengthAwarePaginatorContract::class);
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
