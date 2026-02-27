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
            $searchPattern = "%{$escapedSearchTerm}%";

            $this->where(function (Builder $query) use ($attributes, $searchPattern) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchPattern) {
                            $buffer = explode('.', $attribute);
                            $attributeField = array_pop($buffer);
                            $relationPath = implode('.', $buffer);
                            $query->orWhereHas($relationPath, function (Builder $query) use ($attributeField, $searchPattern) {
                                $wrappedColumn = $query->getQuery()->getGrammar()->wrap($attributeField);
                                $query->whereRaw("{$wrappedColumn} LIKE ? ESCAPE '\\\\'", [$searchPattern]);
                            });
                        },
                        function (Builder $query) use ($attribute, $searchPattern) {
                            $wrappedColumn = $query->getQuery()->getGrammar()->wrap($attribute);
                            $query->orWhereRaw("{$wrappedColumn} LIKE ? ESCAPE '\\\\'", [$searchPattern]);
                        }
                    );
                }
            });

            return $this;
        };
    }
}
