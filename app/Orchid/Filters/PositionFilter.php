<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\Divipole;
use Orchid\Filters\Filter;
use App\Models\Municipality;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Relation;
use Illuminate\Database\Eloquent\Builder;

class PositionFilter extends Filter
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
        return ['position'];
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
        $municipalities = (array) $this->request->get('municipality');
        if (empty($municipalities)) {
            return $builder;
        }
        $selected = (array) $this->request->get('position');
        return $builder->whereHas('divipole', function (Builder $query) use ($municipalities) {
            $query->whereHas('municipality', function (Builder $query) use ($municipalities) {
                $query->whereIn('name', $municipalities);
            });
            $query->whereHas('department', function (Builder $query) {
                $query->whereIn('id', (array)$this->request->get('department'));
            });
        })
        ->whereIn('position_name', $selected);
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        $departmentID = (array) $this->request->get('department');
        $municipalities = (array) $this->request->get('municipality');
        if(count($departmentID) === 0 || count($municipalities) === 0){
            $options = ['' => __('Select Department and Municipality first')];
            return [
            Select::make('position')
                ->options($options)
                ->empty(__('All Positions'))
                ->value($this->request->get('position'))
                ->multiple()
                ->title(__('Positions')),
        ];
        }
        $divipoles = Divipole::when($departmentID, function (Builder $query) {
            $query->whereHas('department', function (Builder $query) {
                    $query->whereIn('id', (array)$this->request->get('department'));
                });
        })->when($municipalities, function (Builder $query) {
            $query->whereHas('municipality', function (Builder $query) {
                $query->whereIn('name', (array)$this->request->get('municipality'));
            });
        })->get();
        $options = $divipoles->pluck('position_name')->unique()->sort()->values()->mapWithKeys(function ($item) {
            return [$item => $item];
        })->toArray();
        return [
            Select::make('position')
                ->options($options)
                ->empty(__('All Positions'))
                ->value($this->request->get('position'))
                ->multiple()
                ->title(__('Positions')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        $selected = (array) $this->request->get('position');
        if (empty($selected)) {
            return $this->name().': All';
        }
        return $this->name().': '.implode(', ', $selected);
    }
}
