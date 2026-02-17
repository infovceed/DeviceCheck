<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Check;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\DateTimer;

class CheckDateFilter extends Filter
{
    public function name(): string
    {
        return __('Report date');
    }

    public function parameters(): array
    {
        return ['filter.created_at'];
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
            DateTimer::make('filter[created_at]')
                ->format('Y-m-d')
                ->value(request('filter.created_at', now()->toDateString()))
                ->title($this->name()),
        ];
    }

    public function value(): string
    {
        $v = $this->request->input('created_at');
        return $v ? $this->name().': '.$v : '';
    }
}
