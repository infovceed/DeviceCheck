<?php

namespace App\Orchid\Filters;

use App\Models\Divipole;
use Orchid\Screen\Field;
use App\Models\Department;
use Orchid\Filters\Filter;
use App\Models\Municipality;
use Orchid\Screen\Fields\Relation;
use Illuminate\Database\Eloquent\Builder;

class DeviceFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Device Filter';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [
            'filter.municipality.name',
            'filter.department.name',
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
        $f = $this->request->input('filter');
        return $builder
        ->when($f['municipality.name'] ?? null,
            fn($q, $v) => $q->whereHas('divipole.municipality', fn($q) => $q->where('name', 'like', "%{$v}%"))
        )
        ->when($f['department.name'] ?? null,
            fn($q, $v) => $q->whereHas('divipole.department', fn($q) => $q->where('name', 'like', "%{$v}%"))
    );

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
