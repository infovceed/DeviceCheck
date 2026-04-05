<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use App\Models\Divipole;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class MunicipalityCheckFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return __('Municipalities');
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
            'filter.municipality',
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
        $departmentID = (array) $this->request->input('filter.department');
        $cacheTtl = (int) config('cache.filter_options_ttl', 60);
        $cacheVersion = (int) Cache::get('filter_options_version', 1);
        if (empty($departmentID)) {
            return [
                Select::make('filter[municipality]')
                    ->multiple()
                    ->title(__('Municipalities'))
                    ->empty(__('Select departments first'))
                    ->value($this->request->get('filter.municipality')),
            ];
        }
        $cacheKey = 'municipality_ids:v' . $cacheVersion . ':' . md5(implode(',', $departmentID));
        $municipalityIDs = Cache::remember($cacheKey, $cacheTtl, function () use ($departmentID) {
            return Divipole::when($departmentID, function (Builder $query) use ($departmentID) {
                $query->whereHas('department', function (Builder $query) use ($departmentID) {
                    $query->whereIn('name', $departmentID);
                });
            })->pluck('municipality_id')->unique()->toArray();
        });
        $cacheKey = 'municipality_filter_options:v' . $cacheVersion . ':' . md5(implode(',', $municipalityIDs));
        $options = Cache::remember($cacheKey, $cacheTtl, function () use ($municipalityIDs) {
             return Municipality::whereIn('id', $municipalityIDs)
                ->orderBy('name', 'asc')
                ->pluck('name', 'name');
        });
        return [
            Select::make('filter[municipality]')
                ->options($options)
                ->multiple()
                ->title(__('Municipalities'))
                ->empty(__('Select municipalities'))
                ->value($this->request->input('filter.municipality')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        $selected = (array) $this->request->input('filter.municipality');
        $selected = array_values(array_unique(array_filter(array_map('trim', $selected))));
        if (empty($selected)) {
            return $this->name() . ': ' . __('All');
        }

        return $this->name() . ': ' . implode(', ', $selected);
    }
}
