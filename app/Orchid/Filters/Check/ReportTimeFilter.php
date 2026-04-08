<?php

namespace App\Orchid\Filters\Check;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;

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
        $selected = $this->request->input('filter.report_time');

        if (is_array($selected)) {
            $values = $selected;
        } elseif (is_string($selected) && trim($selected) !== '') {
            $values = array_map('trim', explode(',', $selected));
        } else {
            $values = [];
        }

        $values = array_values(array_filter($values, static fn ($value): bool => is_scalar($value) && (string) $value !== ''));

        return array_map(
            static fn ($value): Field => Input::make('filter[report_time][]')
                ->type('hidden')
                ->withoutFormType()
                ->value((string) $value),
            $values
        );
    }
}
