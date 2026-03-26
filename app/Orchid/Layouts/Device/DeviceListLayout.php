<?php

namespace App\Orchid\Layouts\Device;

use App\Models\Department;
use App\Models\Device;
use App\Models\Divipole;
use App\Models\Municipality;
use App\Models\User;
use App\Traits\ComponentsTrait;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class DeviceListLayout extends Table
{
    use ComponentsTrait;

    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'devices';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
                TD::make('id', 'ID')
                    ->sort()
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn(Device $device) =>
                        ModalToggle::make((string)$device->id)
                            ->asyncParameters(['device' => $device->id])
                            ->modal('editDeviceModal')
                            ->method('update', ['device' => $device->id])),
                TD::make('department', __('Department'))
                    ->sort()
                    ->filter(
                        Relation::make('department')
                            ->fromModel(Department::class, 'name', 'name')
                            ->multiple()
                    )
                    ->render(fn(Device $device) => $device->divipole->department->name ?? ''),
                TD::make('municipality', __('Municipality'))
                    ->sort()
                    ->filter(
                        Relation::make('municipality')
                            ->fromModel(Municipality::class, 'name', 'name')
                            ->multiple()
                    )
                    ->render(fn(Device $device) => $device->divipole->municipality->name ?? ''),
                TD::make('position_name', __('Position'))
                    ->sort()
                    ->width('220px')
                    ->filter(
                        Relation::make('position_name')
                            ->fromModel(Divipole::class, 'position_name', 'position_name')
                            ->multiple()
                    )->filterValue(function ($value) {
                        if (is_array($value)) {
                            return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 10, '...'), $value));
                        }
                    })
                    ->render(fn(Device $device) => $device->divipole->position_name ?? ''),
                TD::make('operative', __('Operative'))
                    ->sort()
                    ->filter(
                        Relation::make('operative')
                            ->fromModel(User::class, 'name')
                            ->applyScope('agents')
                            ->multiple()
                    )
                    ->filterValue(function ($value) {
                        if (is_array($value)) {
                            $names = User::whereIn('id', $value)->pluck('name')->toArray();
                            return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 10, '...'), $names));
                        }
                    })
                    ->render(fn(Device $device) => $device->divipole->users->pluck('name')->join(', ') ?: $this->badge([
                        'text'  => __('No operative assigned'),
                        'color' => 'warning',
                    ])),
                TD::make('tel', __('Phone'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->width('120px')
                    ->render(fn(Device $device) => $device->tel ?? ''),
                TD::make('imei', __('IMEI'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->width('180px')
                    ->render(fn(Device $device) => $device->imei ?? ''),
                TD::make('device_key', __('Key'))
                    ->filter(TD::FILTER_TEXT)
                    ->width('160px')
                    ->render(fn(Device $device) => $device->device_key ?? ''),
                TD::make('sequential', __('Consecutivo'))
                    ->filter(TD::FILTER_TEXT)
                    ->width('160px')
                    ->render(fn(Device $device) => $device->sequential ?? ''),
                TD::make('latitude', __('Latitud'))
                    ->width('120px')
                    ->render(fn(Device $device) => $device->latitude ?? '(N/A)'),
                TD::make('longitude', __('Longitud'))
                    ->width('120px')
                    ->render(fn(Device $device) => $device->longitude ?? '(N/A)'),
                TD::make('report_time', __('Report time (Arrival)'))
                    ->sort()
                    ->width('120px')
                    ->render(fn(Device $device) => $device->report_time ?? __('Not Reported')),
                TD::make('report_time_departure', __('Report time (Departure)'))
                    ->sort()
                    ->width('150px')
                    ->render(fn(Device $device) => $device->report_time_departure ?? __('Not Reported')),
                TD::make('status_incidents', __('Incidents'))
                    ->sort()
                    ->filter(
                        Select::make('status_incidents')
                            ->options([
                                1 => __('Opened'),
                                2 => __('Closed'),
                            ])
                            ->empty(__('No Incidents'))
                            ->title(__('Incidents'))
                    )
                    ->filterValue(function ($value) {
                        if ($value === 1) {
                            return __('Opened');
                        } elseif ($value === 2) {
                            return __('Closed');
                        }
                        return __('No Incidents');
                    })
                    ->render(function (Device $device) {
                        $value = $device->status_incidents;
                        return $this->getBadgeForStatus($value);
                    })->canSee(config('incidents.enabled') && auth()->user()->hasAccess('platform.systems.incidents.report')),
                TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (Device $device) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list(array_filter([
                        config('incidents.enabled')
                            ? Link::make(__('Incidents'))
                                ->route('platform.systems.incidents', ['device' => $device->id])
                                ->icon('bs.pencil')
                                ->canSee(auth()->user()->hasAccess('platform.systems.incidents.report'))
                            : null,

                        ModalToggle::make(__('Edit'))
                            ->icon('bs.pencil')
                            ->asyncParameters([
                                'device' => $device->id,
                            ])
                            ->modal('editDeviceModal')
                            ->method('update', ['device' => $device->id])
                            ->canSee(auth()->user()->hasAccess('platform.systems.devices.edit')),
                    ])))
                ->canSee((
                        config('incidents.enabled') &&
                        auth()->user()->hasAccess('platform.systems.incidents.report')
                    ) ||
                    auth()->user()->hasAccess('platform.systems.devices.edit')),
                ];
    }

    public function getBadgeForStatus($value)
    {
        switch ($value) {
            case 1:
                return $this->badge([
                    'text'  => __('Opened'),
                    'color' => 'danger',
                ]);
            case 2:
                return $this->badge([
                    'text'  => __('Closed'),
                    'color' => 'success',
                ]);
            default:
                return $this->badge([
                    'text'  => __('No Incidents'),
                    'color' => 'info',
                ]);
        }
    }
}
