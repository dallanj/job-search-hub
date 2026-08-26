<?php

namespace App\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
class ByLocation
{
    public function __construct(private readonly ?string $location) {}

    /**
     * @param  Builder<TModel>  $query
     * @param  Closure(Builder<TModel>): Builder<TModel>  $next
     * @return Builder<TModel>
     */
    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->location !== null) {
            $query->whereLike(
                $query->getModel()->qualifyColumn('location'),
                "%{$this->location}%",
            );
        }

        return $next($query);
    }
}
