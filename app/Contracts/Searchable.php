<?php

namespace App\Contracts;

use App\Search\SearchField;
use App\Search\SearchRelation;

interface Searchable
{
    /**
     * @return list<SearchField|SearchRelation>
     */
    public function searchableFields(): array;
}
