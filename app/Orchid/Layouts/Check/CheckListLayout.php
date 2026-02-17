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
            TD::make('department', __('Department')),
            TD::make('municipality', __('Municipality')),
            TD::make('position_name', __('Position'))
                ->width('220px')
                ->filterValue(function ($value) {
                        if (is_array($value)) {
                            return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 20, '...'), $value));
                        }
                }),
            TD::make('operative', __('Operative'))
                ->sort()
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
                ->width('230px')
                ->render(function(Check $check) {
                    if (! $check->created_at) {
                        return null;
                    }
                    $formatted = Carbon::parse($check->created_at)
                        ->locale(app()->getLocale() ?: 'es')
                        ->isoFormat('dddd D [de] MMMM [de] YYYY');

                    $first = mb_substr($formatted, 0, 1, 'UTF-8');
                    $rest  = mb_substr($formatted, 1, null, 'UTF-8');

                    $text = mb_strtoupper($first, 'UTF-8') . $rest;

                    return '<span class="no-word-cut">' . e($text) . '</span>';
                }),
            TD::make('distance', __('Distance').' (m)')
            ->width('160px')
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
            ->width('160px')
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
            ->width('160px')
            ->render(function (Check $check) {
                $t = is_string($check->time)
                    ? Carbon::createFromFormat('H:i:s', $check->time)
                    : Carbon::parse($check->time);
                return $t->format('h:i:s a');
            }),
            TD::make('time_difference_minutes', __('Time difference (minutes)'))
                ->alignCenter()
                ->width('160px')
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
                ->width('160px')
                ->alignCenter(),
            TD::make('type', __('Report type'))
                ->width('160px')
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
