<?php

namespace App\Filters;

use App\Contracts\Searchable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @template TModel of Model
 */
class BySearchTerm
{
    public function __construct(private readonly ?string $term) {}

    /**
     * @param  Builder<TModel>  $query
     * @param  Closure(Builder<TModel>): Builder<TModel>  $next
     * @return Builder<TModel>
     */
    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->term !== null) {
            $model = $query->getModel();

            if (! $model instanceof Searchable) {
                throw new InvalidArgumentException(sprintf(
                    '%s must implement %s to use %s.',
                    $model::class,
                    Searchable::class,
                    self::class,
                ));
            }

            $query->where(function (Builder $query) use ($model): void {
                foreach ($model->searchableFields() as $searchableField) {
                    $searchableField->apply($query, "%{$this->term}%");
                }
            });
        }

        return $next($query);
    }
}
