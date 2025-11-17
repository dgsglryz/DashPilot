<?php
declare(strict_types=1);

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Searchable trait provides reusable search functionality for models.
 *
 * This trait eliminates duplication of LIKE search patterns across controllers
 * by centralizing search logic in models.
 */
trait Searchable
{
    /**
     * Scope a query to search across specified fields.
     *
     * @param Builder $query The query builder instance
     * @param string|null $search The search term
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $searchableFields = $this->getSearchableFields();

        if (empty($searchableFields)) {
            return $query;
        }

        return $query->where(function ($q) use ($search, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Get the list of fields that should be searchable.
     *
     * Override this method in your model to specify which fields to search.
     *
     * @return array<int, string>
     */
    protected function getSearchableFields(): array
    {
        return [];
    }
}

