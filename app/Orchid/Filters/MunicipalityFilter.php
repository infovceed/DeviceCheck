<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\Municipality;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Relation;

class MunicipalityFilter extends Filter
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
        return ['municipality'];
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
            $query->whereHas('municipality', function (Builder $query) {
                $query->where('id', $this->request->get('municipality'));
            });
        });
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        return [
            Relation::make('municipality')
                ->fromModel(Municipality::class, 'name')
                ->applyScope('myMunicipality')
                ->empty()
                ->value($this->request->get('municipality'))
                ->title(__('Municipalities')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        return $this->name().': '.Municipality::where('id', $this->request->get('municipality'))->first()->name;
    }
}
