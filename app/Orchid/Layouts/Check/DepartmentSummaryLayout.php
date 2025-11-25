<?php

namespace App\Orchid\Layouts\Check;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class DepartmentSummaryLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    protected $target = 'departmentSummary';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('department', __('Department'))->render(function ($row) {
                return $row->department ?? $row->name ?? '';
            }),
            TD::make('municipality', __('Municipalities'))->render(function ($row) {
                return $row->municipality ?? '';
            }),
            TD::make('total', __('Total'))->render(function ($row) {
                return $row->total ?? 0;
            })->alignCenter(),
            TD::make('reported', __('# Reportados'))->render(function ($row) {
                return $row->reported ?? 0;
            })->alignCenter(),
            TD::make('pending', __('# Pendientes'))->render(function ($row) {
                return $row->pending ?? 0;
            })->alignCenter(),
            TD::make('pct_reported', __('% Reportados'))->render(function ($row) {
                return ($row->pct_reported ?? 0) . ' %';
            })->alignCenter(),
            TD::make('pct_pending', __('% Faltan'))->render(function ($row) {
                return ($row->pct_pending ?? 0) . ' %';
            })->alignCenter(),
        ];
    }
}
