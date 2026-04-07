<?php

namespace App\Orchid\Filters\Check;

use App\Models\FilterHours;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class ReportTimeFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return __('Report Time');
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [
            'filter.report_time',
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
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        $cacheTtl = (int) config('cache.filter_options_ttl', 60);
        $cacheVersion = (int) Cache::get('filter_options_version', 1);
        $options = Cache::remember("filter_hours_options_{$cacheVersion}", $cacheTtl, function () {
            return FilterHours::query()
                ->orderBy('hour')
                ->pluck('hour', 'id')
                ->mapWithKeys(static fn ($hour, $id) => [
                    $id => Carbon::parse($hour)->format('H:i'),
                ])
                ->all();
        });
        return [
            Select::make('filter[report_time]')
                ->title(__('Report Time'))
                ->options($options)
                ->empty(__('All'))
                ->multiple()
                ->value($this->request->input('filter.report_time')),
        ];
    }
}
