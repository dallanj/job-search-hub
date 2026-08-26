<?php

namespace App\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
class ByDateRange
{
    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
    ) {}

    /**
     * @param  Builder<TModel>  $query
     * @param  Closure(Builder<TModel>): Builder<TModel>  $next
     * @return Builder<TModel>
     */
    public function handle(Builder $query, Closure $next): Builder
    {
        $query
            ->when(
                $this->from,
                fn (Builder $query, string $from): Builder => $query->whereDate('applied_at', '>=', $from),
            )
            ->when(
                $this->to,
                fn (Builder $query, string $to): Builder => $query->whereDate('applied_at', '<=', $to),
            );

        return $next($query);
    }
}
