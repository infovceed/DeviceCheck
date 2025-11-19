<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\DateTimer;

class ChartDateFilter extends Filter
{
    public function name(): string
    {
        return __('Date');
    }

    public function parameters(): array
    {
        return ['chart_date'];
    }

    public function run(Builder $builder): Builder
    {
        // This filter doesn't modify Eloquent queries directly because charts
        // are generated from Department::getChartData; we only need the
        // request parameter to be available. Return builder unchanged.
        return $builder;
    }

    public function display(): array
    {
        return [
            DateTimer::make('chart_date')
                ->format('Y-m-d')
                ->value(request('chart_date', now()->toDateString()))
                ->title($this->name()),
        ];
    }

    public function value(): string
    {
        $v = $this->request->get('chart_date');
        return $v ? $this->name().': '.$v : '';
    }
}
