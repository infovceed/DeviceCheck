<?php

namespace App\Orchid\Layouts\Check;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class MissingDevicesLayout extends Table
{
    protected $target = 'missingDevices';

    protected function columns(): iterable
    {
        return [
            TD::make('department', __('Department'))->cantHide(),
            TD::make('municipality', __('Municipality'))->cantHide(),
            TD::make('position_name', __('Position')),
            TD::make('code', __('Position code')),
            TD::make('tel', __('Mobile')),
            TD::make('device_key', __('Key')),
            TD::make('report_time', __('Report time (Arrival)'))
                ->render(function ($row) {
                    return $row->report_time ? \Carbon\Carbon::parse($row->report_time)->format('H:i:s') : null;
                }),
            TD::make('report_time_departure', __('Report time (Departure)'))
                ->render(function ($row) {
                    return $row->report_time_departure ? \Carbon\Carbon::parse($row->report_time_departure)->format('H:i:s') : null;
                }),
        ];
    }
}
