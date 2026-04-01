<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $user = auth()->user();
        $cacheVersion = (int) Cache::get('filter_options_version', 1);
        $cacheKey = 'department_filter_options:v' . $cacheVersion . ':' . ($user->id ?? 'guest') . ':' . ($user->department_id ?? 'all');

        $options = Cache::remember($cacheKey, 60, function () use ($user) {
            return Department::query()
                ->select('departments.name')
                ->join('divipoles', 'divipoles.department_id', '=', 'departments.id')
                ->join('devices as d', 'd.divipole_id', '=', 'divipoles.id')
                ->join('configurations as c', DB::raw('c.id'), '=', DB::raw('1'))
                ->whereColumn('d.work_shift_id', 'c.current_work_shift_id')
                ->when($user?->hasAccess('platform.systems.devices.show-department'), function (Builder $query) use ($user) {
                    $departmentId = $user?->department_id;

                    if ($departmentId !== null) {
                        $query->where('departments.id', $departmentId);
                    }
                })
                ->orderBy('departments.name', 'asc')
                ->distinct()
                ->pluck('departments.name', 'departments.name')
                ->toArray();
        });

        return [
            Select::make('filter[department]')
                ->options($options)
                ->empty(__('Select departments'))
                ->title(__('Departments'))
                ->multiple()
                ->value(function () {
                    $department = request()->query('filter', [])['department'] ?? null;
                    if ($department === null || $department === '') {
                        return null;
                    }

                    $values = is_array($department)
                        ? $department
                        : explode(',', (string) $department);

                    return array_values(array_unique(array_filter(array_map('trim', $values))));
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
            return $this->name() . ': ' . __('All');
        }

        $departments = Department::whereIn('name', $selected)
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->join(', ');

        return $this->name() . ': ' . $departments;
    }
}
