<?php

declare(strict_types=1);

namespace Blockpc\App\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

final class Search
{
    /**
     * Add a "where like" clause to the query for searching across attributes.
     *
     * @return Closure(array<int, string>|string $attributes, ?string $searchTerm): static
     */
    public function whereLike(): Closure
    {
        return function ($attributes, ?string $searchTerm = null) {
            if (blank($searchTerm)) {
                return $this;
            }

            $escapedSearchTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);

            $this->where(function (Builder $query) use ($attributes, $escapedSearchTerm) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $escapedSearchTerm) {
                            $buffer = explode('.', $attribute);
                            $attributeField = array_pop($buffer);
                            $relationPath = implode('.', $buffer);
                            $query->orWhereHas($relationPath, function (Builder $query) use ($attributeField, $escapedSearchTerm) {
                                $query->distinct()->where($attributeField, 'LIKE', "%{$escapedSearchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $escapedSearchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$escapedSearchTerm}%");
                        }
                    );
                }
            });

            return $this;
        };
    }
}
