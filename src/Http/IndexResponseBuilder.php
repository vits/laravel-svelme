<?php

namespace Vits\Svelme\Http;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Spatie\LaravelData\Data;

class IndexResponseBuilder
{
    protected null|bool|int $paginate = false;
    protected \Closure|string|null $transform = null;
    protected array $withData = [];

    public function __construct(
        protected $controller,
        protected ?Builder $query = null
    ) {}

    public function paginate(?int $perPage = null)
    {
        $this->paginate = $perPage;

        return $this;
    }

    public function transform(\Closure|string $transform)
    {
        $this->transform = $transform;

        return $this;
    }

    public function with(array $data = [])
    {
        $this->withData = $data;

        return $this;
    }

    public function render(string $component)
    {
        $query = $this->query;

        if (false !== $this->paginate) {
            $data = $query->paginate($this->paginate);

            if ($data->currentPage() < 1 || $data->currentPage() > $data->lastPage()) {
                return $this->controller->redirect(
                    $data->lastPage() > 1 ? $data->lastPage() : null
                );
            }

            $this->controller->rememberIndexParam('page', $data->currentPage());
        } else {
            $data = $query->get();
        }

        if ($transform = $this->transform ?? false) {
            $items = (false === $this->paginate) ? $data : $data->getCollection();

            if (is_callable($transform)) {
                $items = $transform($items);
            } else if (is_string($transform)) {
                if (is_subclass_of($transform, JsonResource::class)) {
                    $items = $transform::collection($items);
                } else if (
                    class_exists(Data::class)
                    && is_subclass_of($transform, Data::class)
                ) {
                    $items = $transform::collect($items);
                } else {
                    $items = $transform::collection($items);
                }
            }

            if (false === $this->paginate) {
                $data = $items;
            } else {
                $data->setCollection(collect($items));
            }
        }

        return Inertia::render(
            $component,
            [
                'items' => $data,
                ...$this->withData,
            ],
        );
    }
}
