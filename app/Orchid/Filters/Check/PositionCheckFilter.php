<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use App\Models\Divipole;
use App\Models\FilterHoursDepartment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;
use Illuminate\Database\Eloquent\Builder;

class PositionCheckFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return __('Positions');
    }

    /**
     * The array of matched parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return [
            'filter.position_name',
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
        return $builder;
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        $filters = $this->request->input('filter', []);
        $options = FilterHoursDepartment::query()
                ->select('position_name')
                ->join('departments as d', 'filter_hours_departments.department_id', '=', 'd.id')
                ->when(!empty($filters['department']), function (Builder $query) use ($filters) {
                    $departments = is_array($filters['department']) ? $filters['department'] : array_map('trim', explode(',', (string) $filters['department']));
                    $departments = array_values(array_filter($departments, static fn ($d) => is_string($d) && trim($d) !== ''));
                    if (!empty($departments)) {
                        $query->whereIn('d.name', $departments);
                    }
                })
                ->when(!empty($filters['type']), function (Builder $query) use ($filters) {
                    $types = is_array($filters['type']) ? $filters['type'] : array_map('trim', explode(',', (string) $filters['type']));
                    if (!empty($types)) {
                        $query->whereIn('filter_hours_departments.type', $types);
                    }
                })
                ->when(!empty($filters['report_time']), function (Builder $query) use ($filters) {
                    $reportTimes = is_array($filters['report_time']) ? $filters['report_time'] : array_map('trim', explode(',', (string) $filters['report_time']));
                    if (!empty($reportTimes)) {
                        $query->whereIn('filter_hours_departments.filter_hours_id', $reportTimes);
                    }
                })
                ->distinct()
                ->orderBy('position_name', 'asc')
                ->pluck('position_name', 'position_name')
                ->toArray();

        return [
            Select::make('filter[position_name]')
                ->options($options)
                ->multiple()
                ->title(__('Positions'))
                ->empty(__('Select Positions'))
                ->value($this->request->input('filter.position_name')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        $selected = (array) $this->request->input('filter.position_name');
        $selected = array_values(array_unique(array_filter(array_map('trim', $selected))));
        if (empty($selected)) {
            return $this->name() . ': ' . __('All');
        }

        return $this->name() . ': ' . implode(', ', $selected);
    }
}
