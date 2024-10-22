<?php

namespace Vits\Svelme\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $fillable = ['name'];

    public function scopeSimpleSearch($query, $search)
    {
        return $query->where('name', $search);
    }
}
