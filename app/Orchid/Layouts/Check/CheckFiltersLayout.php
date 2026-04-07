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
    public $template = self::TEMPLATE_LINE;
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            PageFilter::class,
            OperativeFilter::class,
            DepartmentFilter::class,
            ReportTimeFilter::class,
            MunicipalityCheckFilter::class,
            PositionCheckFilter::class,
            CheckDateFilter::class,
            ReportTypeFilter::class,
        ];
    }
}
