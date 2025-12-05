<?php

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Screen\Layouts\Chart;

class PieChartLayout extends Chart
{
    /**
     * Use pie chart type.
     *
     * @var string
     */
    protected $type = 'pie';

    /**
     * Determines whether to display the export button.
     *
     * @var bool
     */
    protected $export = true;

    /**
     * Pie colors: [Pendientes (gris), Reportados (verde)]
     * El orden coincide con los labels/values construidos en PlatformScreen.
     *
     * @var array
     */
    protected $colors = [
        '#DBDBDB', // Pending / No reportado (gris)
        '#92D050', // Reported / Reportado (verde)
    ];
}
