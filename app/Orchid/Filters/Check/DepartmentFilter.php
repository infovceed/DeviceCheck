<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use App\Models\Department;
use Orchid\Screen\Fields\Select;

class DepartmentFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return __('Departments');
    }

    /**
     * The array of matched parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return [
            'filter.department',
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
        $selected = $this->request->input('filter.department');
        if (empty($selected)) {
            return $builder;
        }

        $selected = is_array($selected)
            ? array_values(array_unique(array_filter($selected)))
            : array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $selected)))));

        if (empty($selected)) {
            return $builder;
        }

        return $builder->whereHas('divipole.department', function (Builder $query) use ($selected) {
            $query->whereIn('name', $selected);
        });
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        return [
            Select::make('filter[department]')
                ->fromModel(Department::class, 'name','name')
                ->empty(__('All Departments'))
                ->multiple()
                ->title(__('Departments'))
                ->value(function () {
                    $department = request()->query('filter', [])['department'] ?? null;
                    if ($department) {
                        $values = is_array($department) ? $department : explode(',', (string) $department);
                        $values = array_values(array_unique(array_filter(array_map('trim', $values))));
                        return $values ?: null;
                    }
                    return null;
                }),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        $selected = (array) $this->request->input('filter.department');
        $selected = array_values(array_unique(array_filter(array_map('trim', $selected))));
        if (empty($selected)) {
            return $this->name().': '.__('All');
        }

        $departments = Department::whereIn('name', $selected)
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->join(', ');

        return $this->name().': '.$departments;
    }
}
