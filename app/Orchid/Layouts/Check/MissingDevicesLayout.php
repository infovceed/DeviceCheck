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
            TD::make('department', __('Department'))->cantHide()->width('220px'),
            TD::make('municipality', __('Municipality'))->cantHide()->width('220px'),
            TD::make('position_name', __('Position'))->width('220px'),
            TD::make('code', __('Position code'))->width('150px'),
            TD::make('tel', __('Mobile'))->width('120px'),
            TD::make('device_key', __('Key'))->width('100px'),
            TD::make('report_time', __('Report time (Arrival)'))
                ->width('180px')
                ->render(function ($row) {
                    return $row->report_time ? \Carbon\Carbon::parse($row->report_time)->format('H:i:s') : null;
                }),
            TD::make('report_time_departure', __('Report time (Departure)'))
                ->width('180px')
                ->render(function ($row) {
                    return $row->report_time_departure ? \Carbon\Carbon::parse($row->report_time_departure)->format('H:i:s') : null;
                }),
        ];
    }
}
