<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;

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
        $selected = $this->request->input('filter.department');

        if (is_array($selected)) {
            $values = $selected;
        } elseif (is_string($selected) && trim($selected) !== '') {
            $values = array_map('trim', explode(',', $selected));
        } else {
            $values = [];
        }

        $values = array_values(array_filter($values, static fn ($value): bool => is_scalar($value) && (string) $value !== ''));

        return array_map(
            static fn ($value): Field => Input::make('filter[department][]')
                ->type('hidden')
                ->withoutFormType()
                ->value((string) $value),
            $values
        );
    }
}
