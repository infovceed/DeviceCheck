<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use App\Models\Divipole;
use Orchid\Filters\Filter;
use App\Models\Municipality;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Relation;
use Illuminate\Database\Eloquent\Builder;

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
        if (empty($departmentID)) {
            return [
                Select::make('filter[municipality]')
                    ->empty(__('All Municipalities'))
                    ->value($this->request->get('filter.municipality'))
                    ->multiple()
                    ->title(__('Municipalities')),
            ];
        }
        $divipoles = Divipole::when($departmentID, function (Builder $query) use ($departmentID) {
            $query->whereHas('department', function (Builder $query) use ($departmentID) {
                $query->whereIn('name', $departmentID);
            });
        });
        $municipalityIDs = $divipoles->pluck('municipality_id')->unique()->toArray();
        $options = Municipality::whereIn('id', $municipalityIDs)
            ->orderBy('name', 'asc')
            ->pluck('name', 'name');
        return [
            Select::make('filter[municipality]')
                ->options($options)
                ->empty(__('All Municipalities'))
                ->value($this->request->input('filter.municipality'))
                ->multiple()
                ->title(__('Municipalities')),
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
            return $this->name().': '.__('All');
        }

        return $this->name().': '.implode(', ', $selected);
    }
}
