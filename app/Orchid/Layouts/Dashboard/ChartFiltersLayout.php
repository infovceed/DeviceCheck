<?php

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;
use App\Orchid\Filters\PositionFilter;
use App\Orchid\Filters\ChartDateFilter;
use App\Orchid\Filters\MunicipalityFilter;
use App\Orchid\Filters\DepartmentChartsFilter;

class ChartFiltersLayout extends Selection
{
    public $template = self::TEMPLATE_LINE;
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            DepartmentChartsFilter::class,
            MunicipalityFilter::class,
            PositionFilter::class,
            ChartDateFilter::class,
        ];
    }
}
