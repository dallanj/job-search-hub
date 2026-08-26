<?php

namespace App\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
class ByCompany
{
    public function __construct(private readonly ?int $companyId) {}

    /**
     * @param  Builder<TModel>  $query
     * @param  Closure(Builder<TModel>): Builder<TModel>  $next
     * @return Builder<TModel>
     */
    public function handle(Builder $query, Closure $next): Builder
    {
        $query->when(
            $this->companyId,
            fn (Builder $query, int $companyId): Builder => $query->where('company_id', $companyId),
        );

        return $next($query);
    }
}
