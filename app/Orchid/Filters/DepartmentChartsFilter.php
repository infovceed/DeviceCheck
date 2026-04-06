<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Orchid\Filters\Filter;
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
        $cacheTtl = (int) config('cache.filter_options_ttl', 60);
        $cacheKey = "departments.chart.filter.department.{$user->id}";
        $options = Cache::remember($cacheKey, $cacheTtl, function () use ($user) {
            return Department::query()
                ->select('departments.name as name', 'departments.id as id')
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
                ->pluck('name', 'id');
        });
        return [
            Select::make('department')
                ->options($options)
                ->empty(__('All Departments'))
                ->multiple()
                ->title(__('Departments'))
                ->value($this->request->get('department')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        $departments = Department::whereIn('id', (array)$this->request->get('department'))
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->join(', ');
        return $this->name() . ': ' . $departments;
    }
}
