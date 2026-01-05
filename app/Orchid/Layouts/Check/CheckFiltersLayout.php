<?php

namespace App\Orchid\Layouts\Check;

use Orchid\Filters\Filter;
use App\Orchid\Filters\PageFilter;
use Orchid\Screen\Layouts\Selection;
use App\Orchid\Filters\OperativeFilter;

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
        ];
    }
}
