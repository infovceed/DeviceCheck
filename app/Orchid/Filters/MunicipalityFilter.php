<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\Divipole;
use Orchid\Filters\Filter;
use App\Models\Municipality;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Relation;
use Illuminate\Database\Eloquent\Builder;

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
        if (!$this->request->get('department')) {
            return $builder;
        }
        $selected = (array) $this->request->get('municipality');
        return $builder->whereHas('divipole', function (Builder $query) use ($selected) {
            $query->whereHas('municipality', function (Builder $query) use ($selected) {
                $query->whereIn('name', $selected);
            });
        });
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        $departmentID = (array) $this->request->get('department');
        if(count($departmentID) === 0){
            return [
                Select::make('municipality')
                    ->options(['' => __('Select Department first')])
                    ->empty(__('All Municipalities'))
                    ->value($this->request->get('municipality'))
                    ->multiple()
                    ->title(__('Municipalities')),
            ];
        }
        $divipoles = Divipole::when($departmentID, function (Builder $query) use ($departmentID) {
            $query->whereHas('department', function (Builder $query) use ($departmentID) {
                $query->whereIn('id', $departmentID);
            });
        });
        $municipalityIDs = $divipoles->pluck('municipality_id')->unique()->toArray();
        $options = Municipality::whereIn('id', $municipalityIDs)
            ->orderBy('name', 'asc')
            ->pluck('name', 'name');
        return [
            Select::make('municipality')
                ->options($options)
                ->empty(__('All Municipalities'))
                ->value($this->request->get('municipality'))
                ->multiple()
                ->title(__('Municipalities')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        $selected = (array) $this->request->get('municipality');
        if (empty($selected)) {
            return $this->name().': All';
        }
        return $this->name().': '.implode(', ', $selected);
    }
}
