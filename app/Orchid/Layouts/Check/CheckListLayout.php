<?php

namespace App\Orchid\Layouts\Check;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Check;
use Orchid\Screen\TD;
use App\Models\Device;
use App\Models\Divipole;
use App\Models\Department;
use App\Models\Municipality;
use App\Traits\ComponentsTrait;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\DateTimer;

class CheckListLayout extends Table
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
    protected $target = 'checks';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID')
                    ->align(TD::ALIGN_CENTER),
            TD::make('department', __('Department'))
                ->filter(
                    Relation::make('department')
                        ->fromModel(Department::class, 'name','name')
                        ->multiple()
                ),
            TD::make('municipality', __('Municipality'))
                ->filter(
                    Relation::make('municipality')
                        ->fromModel(Municipality::class, 'name','name')
                        ->multiple()
                ),
            TD::make('position_name', __('Position'))
                ->width('220px')
                ->filter(
                    Relation::make('position_name')
                        ->fromModel(Divipole::class, 'position_name','position_name')
                        ->multiple()
                )->filterValue(function ($value) {
                        if (is_array($value)) {
                            return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 20, '...'), $value));
                        }
                }),
            TD::make('operative', __('Operative'))
                ->sort()
                ->filter(
                    Relation::make('operative')
                        ->fromModel(User::class, 'name')
                        ->applyScope('agents')
                        ->multiple()
                        ->value(function () {
                            $operative = request()->query('filter', [])['operative'] ?? null;
                            if ($operative) {
                                return is_array($operative) ? $operative : explode(',', $operative);
                            }
                            return null;
                        })
                )
                ->filterValue(function ($value) {
                    if (is_array($value)) {
                        $names = User::whereIn('id', $value)->pluck('name')->toArray();
                        return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 10, '...'), $names));
                    }
                })
                ->render(fn(Check $check) => $check->device->divipole->users->pluck('name')->join(', ') ?: $this->badge([
                        'text'  => __('No operative assigned'),
                        'color' => 'warning',
                    ])),
            TD::make('tel', __('Mobile'))
                ->width('120px')
                ->filter(
                    Relation::make('tel')
                        ->fromModel(Device::class, 'tel','tel')
                        ->multiple()
                ),
            TD::make('device_key', __('Key'))
                ->filter(
                    Relation::make('device_key')
                        ->fromModel(Device::class, 'device_key','device_key')
                        ->multiple()
                ),
            TD::make('created_at', __('Report date'))
                ->width('160px')
                ->filter(
                    // Use 'created_at' as filter key so model filters are applied
                    DateTimer::make('created_at')
                        ->format('Y-m-d')
                        ->multiple()
                )
                ->render(function(Check $check) {
                    if (! $check->created_at) {
                        return null;
                    }
                    $formatted = Carbon::parse($check->created_at)
                        ->locale(app()->getLocale() ?: 'es')
                        ->isoFormat('dddd D [de] MMMM [de] YYYY');

                    $first = mb_substr($formatted, 0, 1, 'UTF-8');
                    $rest  = mb_substr($formatted, 1, null, 'UTF-8');

                    return mb_strtoupper($first, 'UTF-8') . $rest;
                }),
            TD::make('distance', __('Distance').' (m)')
            ->filter(
                Select::make()
                    ->options([
                        'le' => 'Menores o iguales a 500 m',
                        'gt' => 'Mayores a 500 m',
                    ])
                    ->empty(__('All'))
            )
            ->filterValue(fn($value) => match ($value) {
                'le' => 'Menores o iguales a 500 m',
                'gt' => 'Mayores a 500 m',
                default => $value,
            })
                ->render(function(Check $check) {
                    $distance = $check->distance*1000;
                    return $this->badge([
                        'text'  => $distance,
                        'color' => $distance<500 ? 'success' : 'danger',
                    ]);
                })->alignCenter(),
            TD::make('report_time', __('Scheduled Time'))
            ->filter(
                DateTimer::make('report_time')
                    ->noCalendar()
                    ->serverFormat('H:i:s')
                    ->format('H:i')
                    ->placeholder('00:00:00')
                    ->allowInput()
                    ->multiple()
            )->render(function (Check $check) {
                if (! $check->report_time) {
                    return __('Not Scheduled');
                }
                $t = is_string($check->report_time)
                    ? Carbon::createFromFormat('H:i:s', $check->report_time)
                    : Carbon::parse($check->report_time);
                return $t->format('h:i:s a');
            }),
            TD::make('time', __('Report hour'))
            ->render(function (Check $check) {
                $t = is_string($check->time)
                    ? Carbon::createFromFormat('H:i:s', $check->time)
                    : Carbon::parse($check->time);
                return $t->format('h:i:s a');
            }),
            TD::make('time_difference_minutes', __('Time difference (minutes)'))->alignCenter()
                ->render(function(Check $check) {
                    if($check->type === 'checkout'){
                        return $this->badge([
                        'text'  => abs($check->time_difference_minutes),
                        'color' => $check->time_difference_minutes > 0 ? 'success' : 'danger',
                        ]);
                    }
                    return $this->badge([
                        'text'  => abs($check->time_difference_minutes),
                        'color' => $check->time_difference_minutes < 1 ? 'success' : 'danger',
                    ]);
                }),
            TD::make('code', __('Position code'))
                ->filter(TD::FILTER_TEXT)
                ->alignCenter(),
            TD::make('type', __('Report type'))
                ->filter(
                    Select::make()
                        ->options([
                            'checkin'  => __('Arrival'),
                            'checkout' => __('Departure'),
                        ])
                        ->empty(__('All'))
                )
                ->filterValue(fn($value) => match ($value) {
                    'checkin' => __('Arrival'),
                    'checkout' => __('Departure'),
                    default => $value,
                })
                ->render(function(Check $check) {
                    return $this->badge([
                        'text'  => $check->type == 'checkin' ? __('Arrival') : __('Departure'),
                        'color' => $check->type == 'checkin' ? 'info' : 'warning',
                    ]);
                }),
        ];
    }

    protected function hoverable(): bool
    {
        return true;
    }

}
