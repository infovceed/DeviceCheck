<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;

class TerritoryFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Territory Filter';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [
            'filter.department.name',
            'filter.department.code',
            'filter.municipality.name',
            'filter.municipality.code',
        ];
    }

    /**
     * Apply to a given Eloquent query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        $filter = $this->request->input('filter');
        if (isset($filter['department.name'])) {
            $builder->whereHas('department', function ($query) use ($filter) {
                return $query->where('name', 'like', "%{$filter['department.name']}%");
            });
        }
        if (isset($filter['department.code'])) {
            $builder->whereHas('department', function ($query) use ($filter) {
                return $query->where('code', 'like', "%{$filter['department.code']}%");
            });
        }
        if (isset($filter['municipality.name'])) {
            $builder->whereHas('municipality', function ($query) use ($filter) {
                return $query->where('name', 'like', "%{$filter['municipality.name']}%");
            });
        }
        if (isset($filter['municipality.code'])) {
            $builder->whereHas('municipality', function ($query) use ($filter) {
                return $query->where('code', 'like', "%{$filter['municipality.code']}%");
            });
        }
        return $builder;
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        return [];
    }
}
