<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Relation;
use App\Models\Department;

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
        return ['department'];
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
        return $builder->whereHas('divipole', function (Builder $query) {
            $query->whereHas('department', function (Builder $query) {
                $query->where('id', $this->request->get('department'));
            });
        });
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        return [
            Relation::make('department')
                ->fromModel(Department::class, 'name')
                ->applyScope('myDepartment')
                ->empty()
                ->value($this->request->get('department'))
                ->title(__('Departments')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        return $this->name().': '.Department::where('id', $this->request->get('department'))->first()->name;
    }
}
