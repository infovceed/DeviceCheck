<?php

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;
use App\Orchid\Filters\ChartDateFilter;
use App\Orchid\Filters\DepartmentChartsFilter;

class ChartFiltersLayout extends Selection
{
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            DepartmentChartsFilter::class,
            ChartDateFilter::class,
        ];
    }
}
