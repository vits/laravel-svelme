<?php

declare(strict_types=1);

namespace Vits\Svelme\Http;

use Illuminate\Pagination\LengthAwarePaginator;

class SvelmePaginator extends LengthAwarePaginator
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->items->toArray(),
            'pagination' => [
                'total' => $this->total(),
                'perPage' => $this->perPage(),
                'currentPage' => $this->currentPage(),
                'totalPages' => $this->lastPage(),
            ],
        ];
    }
}
