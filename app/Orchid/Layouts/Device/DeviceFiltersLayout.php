<?php

namespace App\Orchid\Layouts\Device;

use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;
use App\Orchid\Filters\DeviceFilter;
use App\Orchid\Filters\DepartmentFilter;
use App\Orchid\Filters\MunicipalityFilter;

class DeviceFiltersLayout extends Selection
{
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            DepartmentFilter::class,
            MunicipalityFilter::class,
            DeviceFilter::class,
        ];
    }
}
