<?php

namespace App\Orchid\Layouts\Check;

use App\Models\FilterHours;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Check;
use Orchid\Screen\TD;
use App\Models\Device;
use App\Traits\ComponentsTrait;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Fields\Relation;

class CheckListLayout extends Table
{
    use ComponentsTrait;

    private const STANDARD_TIME_WIDTH = '160px';

    protected function singleLine(TD $column): TD
    {
        return $column
            ->class('text-nowrap')
            ->style('white-space: nowrap;');
    }

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
            ...$this->identityColumns(),
            ...$this->deviceColumns(),
            ...$this->scheduleColumns(),
        ];
    }

    /**
     * @return array<int, TD>
     */
    protected function identityColumns(): array
    {
        return [
            $this->singleLine(
                TD::make('id', 'ID')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
            ),
            $this->singleLine(TD::make('department', __('Department'))),
            $this->singleLine(TD::make('municipality', __('Municipality'))),
            $this->singleLine(
                TD::make('position_name', __('Position'))
                    ->width('220px')
                    ->filterValue(function ($value) {
                        if (is_array($value)) {
                            return implode(', ', array_map(fn($item) => mb_strimwidth($item, 0, 20, '...'), $value));
                        }

                        return null;
                    })
            ),
            $this->singleLine(
                TD::make('operative', __('Operative'))
                    ->sort()
                    ->width('120px')
                    ->filterValue(function ($value) {
                        if (is_array($value)) {
                            $names = User::whereIn('id', $value)->pluck('name')->toArray();

                            return implode(', ', array_map(fn($item) => mb_strimwidth($item, 0, 10, '...'), $names));
                        }

                        return null;
                    })
                    ->render(fn(Check $check) => $check->device->divipole->users->pluck('name')->join(', ') ?: $this->badge([
                        'text' => __('No operative assigned'),
                        'color' => 'warning',
                    ]))
            ),
        ];
    }

    /**
     * @return array<int, TD>
     */
    protected function deviceColumns(): array
    {
        return [
            $this->singleLine(
                TD::make('tel', __('Mobile'))
                    ->width('120px')
                    ->filter(
                        Relation::make('tel')
                            ->fromModel(Device::class, 'tel', 'tel')
                            ->multiple()
                    )
            ),
            $this->singleLine(
                TD::make('device_key', __('Key'))
                    ->filter(
                        Relation::make('device_key')
                            ->fromModel(Device::class, 'device_key', 'device_key')
                            ->multiple()
                    )
            ),
            $this->singleLine(
                TD::make('created_at', __('Report date'))
                    ->width('230px')
                    ->render(function (Check $check) {
                        if (! $check->created_at) {
                            return null;
                        }

                        $formatted = Carbon::parse($check->created_at)
                            ->locale(app()->getLocale() ?: 'es')
                            ->isoFormat('dddd D [de] MMMM [de] YYYY');

                        $first = mb_substr($formatted, 0, 1, 'UTF-8');
                        $rest = mb_substr($formatted, 1, null, 'UTF-8');
                        $text = mb_strtoupper($first, 'UTF-8') . $rest;

                        return '<span class="no-word-cut">' . e($text) . '</span>';
                    })
            ),
        ];
    }

    /**
     * @return array<int, TD>
     */
    protected function scheduleColumns(): array
    {
        return [
            $this->distanceColumn(),
            $this->scheduledTimeColumn(),
            $this->reportHourColumn(),
            $this->timeDifferenceColumn(),
            $this->singleLine(
                TD::make('code', __('Position code'))
                    ->filter(TD::FILTER_TEXT)
                    ->width(self::STANDARD_TIME_WIDTH)
                    ->alignCenter()
            ),
            $this->reportTypeColumn(),
        ];
    }

    protected function distanceColumn(): TD
    {
        return $this->singleLine(
            TD::make('distance', __('Distance') . ' (m)')
                ->width(self::STANDARD_TIME_WIDTH)
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
                ->render(function (Check $check) {
                    $distance = $check->distance * 1000;

                    return $this->badge([
                        'text' => $distance,
                        'color' => $distance < 500 ? 'success' : 'danger',
                    ]);
                })
                ->alignCenter()
        );
    }

    protected function scheduledTimeColumn(): TD
    {
        return $this->singleLine(
            TD::make('report_time', __('Scheduled Time'))
                ->width(self::STANDARD_TIME_WIDTH)
                ->filterValue(function ($value) {
                    if (is_array($value)) {
                        $times = FilterHours::whereIn('id', $value)
                            ->pluck('hour')
                            ->map(function ($hour) {
                                return Carbon::parse($hour)->format('H:i:s');
                            })
                            ->toArray();

                        return implode(', ', $times);
                    }

                    return $value;
                })
                ->render(function (Check $check) {
                    if (! $check->report_time) {
                        return __('Not Scheduled');
                    }

                    $time = is_string($check->report_time)
                        ? Carbon::createFromFormat('H:i:s', $check->report_time)
                        : Carbon::parse($check->report_time);

                    return $time->format('h:i:s a');
                })
        );
    }

    protected function reportHourColumn(): TD
    {
        return $this->singleLine(
            TD::make('time', __('Report hour'))
                ->width(self::STANDARD_TIME_WIDTH)
                ->render(function (Check $check) {
                    $time = is_string($check->time)
                        ? Carbon::createFromFormat('H:i:s', $check->time)
                        : Carbon::parse($check->time);

                    return $time->format('h:i:s a');
                })
        );
    }

    protected function timeDifferenceColumn(): TD
    {
        return $this->singleLine(
            TD::make('time_difference_minutes', __('Time difference (minutes)'))
                ->alignCenter()
                ->width(self::STANDARD_TIME_WIDTH)
                ->render(function (Check $check) {
                    $raw = $check->time_difference_minutes;

                    if (! is_numeric($raw)) {
                        return $raw;
                    }

                    $minutes = (float) $raw;
                    $color = 'danger';

                    if ($check->type === 'checkout') {
                        $color = $minutes > 0 ? 'success' : 'danger';
                    } elseif ($minutes < 1) {
                        $color = 'success';
                    }

                    return $this->badge([
                        'text' => abs($minutes),
                        'color' => $color,
                    ]);
                })
        );
    }

    protected function reportTypeColumn(): TD
    {
        return $this->singleLine(
            TD::make('type', __('Report type'))
                ->width(self::STANDARD_TIME_WIDTH)
                ->filterValue(fn($value) => match ($value) {
                    'checkin' => __('Arrival'),
                    'checkout' => __('Departure'),
                    default => $value,
                })
                ->render(function (Check $check) {
                    return $this->badge([
                        'text' => $check->type == 'checkin' ? __('Arrival') : __('Departure'),
                        'color' => $check->type == 'checkin' ? 'info' : 'warning',
                    ]);
                })
        );
    }

    protected function hoverable(): bool
    {
        return true;
    }
}
