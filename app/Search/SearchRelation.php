<?php

namespace App\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SearchRelation
{
    public function __construct(
        public readonly string $relation,
        public readonly string $column,
    ) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function apply(Builder $query, string $pattern): void
    {
        $query->orWhereHas(
            $this->relation,
            fn (Builder $relationQuery): Builder => $relationQuery
                ->whereLike($relationQuery->qualifyColumn($this->column), $pattern),
        );
    }
}
