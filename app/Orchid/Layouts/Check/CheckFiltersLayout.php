<?php

namespace App\Orchid\Layouts\Check;

use App\Orchid\Filters\Check\CheckDateFilter;
use App\Orchid\Filters\Check\DepartmentFilter;
use App\Orchid\Filters\Check\MunicipalityCheckFilter;
use App\Orchid\Filters\Check\OperativeFilter;
use App\Orchid\Filters\Check\PositionCheckFilter;
use App\Orchid\Filters\Check\ReportTimeFilter;
use App\Orchid\Filters\Check\ReportTypeFilter;
use App\Orchid\Filters\PageFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class CheckFiltersLayout extends Selection
{
    public $template = 'orchid.layouts.check-filters-line';
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            PageFilter::class,
            OperativeFilter::class,
            ReportTimeFilter::class,
            CheckDateFilter::class,
            ReportTypeFilter::class,
            DepartmentFilter::class,
            MunicipalityCheckFilter::class,
            PositionCheckFilter::class,
        ];
    }
}
