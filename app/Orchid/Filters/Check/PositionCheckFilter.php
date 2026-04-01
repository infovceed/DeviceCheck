<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use App\Models\Divipole;
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
        $cacheVersion = (int) Cache::get('filter_options_version', 1);
        $cacheKey = 'position_filter_options:v' . $cacheVersion . ':positions';
        $options = Cache::remember($cacheKey, 60, function () {
            return Divipole::query()
                ->select('divipoles.position_name')
                ->join('devices as d', 'd.divipole_id', '=', 'divipoles.id')
                ->join('configurations as c', DB::raw('c.id'), '=', DB::raw('1'))
                ->whereColumn('d.work_shift_id', 'c.current_work_shift_id')
                ->whereNotNull('divipoles.position_name')
                ->where('divipoles.position_name', '!=', '')
                ->distinct()
                ->orderBy('divipoles.position_name', 'asc')
                ->pluck('divipoles.position_name', 'divipoles.position_name')
                ->toArray();
        });

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
