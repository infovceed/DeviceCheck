<?php

namespace App\Orchid\Layouts\Check;

use Orchid\Filters\Filter;
use App\Orchid\Filters\PageFilter;
use Orchid\Screen\Layouts\Selection;
use App\Orchid\Filters\Check\CheckDateFilter;
use App\Orchid\Filters\Check\OperativeFilter;
use App\Orchid\Filters\Check\DepartmentFilter;
use App\Orchid\Filters\Check\ReportTypeFilter;
use App\Orchid\Filters\Check\PositionCheckFilter;
use App\Orchid\Filters\Check\MunicipalityCheckFilter;

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
            MunicipalityCheckFilter::class,
            PositionCheckFilter::class,
            CheckDateFilter::class,
            ReportTypeFilter::class,
        ];
    }
}
