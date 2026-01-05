<?php

namespace App\Orchid\Filters;

use App\Models\User;
use Orchid\Screen\Field;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Relation;
use Illuminate\Database\Eloquent\Builder;

class OperativeFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Operative Filter';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [
            'filter.operative',
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
            Relation::make('filter[operative]')
                    ->fromModel(User::class, 'name')
                    ->applyScope('agents')
                    ->title(__('Operative assigned'))
                    ->multiple()
                    ->value(function () {
                        $operative = request()->query('filter', [])['operative'] ?? null;
                        if ($operative) {
                            return is_array($operative) ? $operative : explode(',', $operative);
                        }
                        return null;
                    })
                    ->help(__('Choose one or more operatives.'))
        ];
    }
}
