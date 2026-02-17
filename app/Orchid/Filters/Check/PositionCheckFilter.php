<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use App\Models\Divipole;
use Orchid\Filters\Filter;
use App\Models\Municipality;
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
        $departments = (array) $this->request->input('filter.department');
        $municipalities = (array) $this->request->input('filter.municipality');
        if(count($departments) === 0 || count($municipalities) === 0){
            $options = ['' => __('Select Department and Municipality first')];
            return [
            Select::make('filter[position]')
                ->options($options)
                ->empty(__('Select Department and Municipality first'))
                ->value($this->request->get('filter.position'))
                ->multiple()
                ->title(__('Positions')),
        ];
        }
        $divipoles = Divipole::when($departments, function (Builder $query) use ($departments) {
            $query->whereHas('department', function (Builder $query) use ($departments) {
                    $query->whereIn('name', $departments);
                });
        })->when($municipalities, function (Builder $query) use ($municipalities) {
            $query->whereHas('municipality', function (Builder $query) use ($municipalities) {
                    $query->whereIn('name', $municipalities);
                });
        })->get();
        $options = $divipoles->pluck('position_name')->unique()->sort()->values()->mapWithKeys(function ($item) {
            return [$item => $item];
        })->toArray();
        return [
            Select::make('filter[position_name]')
                ->options($options)
                ->empty(__('All Positions'))
                ->value($this->request->input('filter.position_name'))
                ->multiple()
                ->title(__('Positions')),
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
            return $this->name().': '.__('All');
        }

        return $this->name().': '.implode(', ', $selected);
    }
}
