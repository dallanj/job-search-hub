<?php

namespace App\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SearchField
{
    public function __construct(public readonly string $column) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function apply(Builder $query, string $pattern): void
    {
        $query->orWhereLike($query->qualifyColumn($this->column), $pattern);
    }
}
