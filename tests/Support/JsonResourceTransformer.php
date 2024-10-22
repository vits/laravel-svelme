<?php

namespace Vits\Svelme\Tests\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JsonResourceTransformer extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'transformed' => true
        ];
    }
}
