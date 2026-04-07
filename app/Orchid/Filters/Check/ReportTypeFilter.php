<?php

namespace App\Orchid\Filters\Check;

use Orchid\Screen\Field;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;
use Illuminate\Database\Eloquent\Builder;

class ReportTypeFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Report Type';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [
            'filter.type',
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
        return [
            Select::make('filter[type]')
                ->title(__('Report Type'))
                ->options([
                    'checkin'  => __('Arrival'),
                    'checkout' => __('Departure'),
                ])
                ->empty(__('All'))
                ->value($this->request->input('filter.type')),
        ];
    }
}
