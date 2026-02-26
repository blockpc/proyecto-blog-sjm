<?php

declare(strict_types=1);

namespace Blockpc\App\Mixins;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

final class Search
{
    /**
     * Create a query-scope closure that applies SQL LIKE filters to the model's attributes and to attributes on related models.
     *
     * The returned closure accepts an attribute or array of attributes and an optional search term, and adds OR'ed LIKE conditions
     * for each attribute. If an attribute contains a dot-separated relation path (e.g. "relation.field"), the condition is applied
     * inside an orWhereHas on that relation (the relation query uses distinct() and a LIKE comparison on the related field).
     * Attributes are normalized via Arr::wrap. The closure returns `$this` to allow fluent chaining.
     *
     * @return \Closure A closure with signature function ($attributes, ?string $searchTerm = null) that applies the described filters and returns `$this`.
     */
    public function whereLike()
    {
        return function ($attributes, ?string $searchTerm = null) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $buffer = explode('.', $attribute);
                            $attributeField = array_pop($buffer);
                            $relationPath = implode('.', $buffer);
                            $query->orWhereHas($relationPath, function (Builder $query) use ($attributeField, $searchTerm) {
                                $query->distinct()->where($attributeField, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });

            return $this;
        };
    }
}
