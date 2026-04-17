<?php

namespace App\Orchid\Layouts\Check;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class MissingDevicesLayout extends Table
{
    private const TEXT_COLUMN_WIDTH = '220px';

    protected $target = 'missingDevices';

    protected function singleLine(TD $column): TD
    {
        return $column
            ->class('text-nowrap')
            ->style('white-space: nowrap;');
    }

    protected function columns(): iterable
    {
        return [
            $this->singleLine(TD::make('department', __('Department'))->cantHide()->width(self::TEXT_COLUMN_WIDTH)),
            $this->singleLine(TD::make('municipality', __('Municipality'))->cantHide()->width(self::TEXT_COLUMN_WIDTH)),
            $this->singleLine(TD::make('position_name', __('Position'))->width(self::TEXT_COLUMN_WIDTH)),
            $this->singleLine(TD::make('code', __('Position code'))->width('150px')),
            $this->singleLine(TD::make('tel', __('Mobile'))->width('120px')),
            $this->singleLine(TD::make('device_key', __('Key'))->width('100px')),
            $this->singleLine(
                TD::make('report_time_arrival', __('Report time (Arrival)'))
                    ->width('180px')
                    ->render(function ($row) {
                        return $row->report_time_arrival ? \Carbon\Carbon::parse($row->report_time_arrival)->format('H:i:s') : null;
                    })
            ),
            $this->singleLine(
                TD::make('report_time_departure', __('Report time (Departure)'))
                    ->width('180px')
                    ->render(function ($row) {
                        return $row->report_time_departure ? \Carbon\Carbon::parse($row->report_time_departure)->format('H:i:s') : null;
                    })
            ),
        ];
    }
}
