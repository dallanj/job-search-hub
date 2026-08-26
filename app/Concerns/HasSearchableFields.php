<?php

namespace App\Concerns;

use App\Search\SearchField;
use App\Search\SearchRelation;

trait HasSearchableFields
{
    protected function searchField(string $column): SearchField
    {
        return new SearchField($column);
    }

    protected function searchRelation(string $relation, string $column): SearchRelation
    {
        return new SearchRelation($relation, $column);
    }
}
