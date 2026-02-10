<?php

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Screen\Layouts\Chart;

class ChartsLayout extends Chart
{
    /**
     * Available options:
     * 'bar', 'line',
     * 'pie', 'percentage'.
     *
     * @var string
     */
    protected $type = 'bar';

    /**
     * Determines whether to display the export button.
     *
     * @var bool
     */
    protected $export = true;
    protected $valuesOverPoints = 1;

    protected $colors = [
        '#92D050', // Meta (Verde)
        '#002060', // Reportados (Azul oscuro)
        '#FF8805', // Checkout (naranja)
    ];
}
