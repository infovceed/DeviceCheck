<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Orchid\Filters\Filter;
use App\Models\Department;
use Orchid\Screen\Fields\Select;

class DepartmentChartsFilter extends Filter
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
        $user = auth()->user();
        $cacheKey = "departments.chart.filter.{$user->id}";
        $options = Cache::remember($cacheKey, 60, function () use ($user) {
            return Department::when(!$user->hasAccess('platform.systems.dashboard.show-all'), function (Builder $query) use ($user) {
                $query->where('id', $user->department_id);
            })
            ->orderBy('name', 'asc')
            ->pluck('name', 'id');
        });
        return [
            Select::make('department')
                ->options($options)
                ->empty(__('All Departments'))
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
