<?php

namespace App\Orchid\Layouts\Check;

use Carbon\Carbon;
use App\Models\Check;
use Orchid\Screen\TD;
use App\Models\Department;
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
                    ),
                TD::make('municipality', __('Municipality'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('position_name', __('Position'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('tel', __('Mobile'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('device_key', __('Key'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('created_at', __('Report date'))
                    ->filter(
                        DateTimer::make('open')
                            ->format('Y-m-d')
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
                    ->render(function(Check $check) {
                        $distance = $check->distance*1000;
                        return $this->badge([
                            'text'  => $distance,
                            'color' => $distance<500 ? 'info' : 'danger',
                        ]);
                    })->alignCenter(),
                TD::make('report_time', __('Report time')),
                TD::make('time', __('Arrival time')),
                TD::make('time_difference_minutes', __('Time difference (minutes)'))->alignCenter(),
                TD::make('code', __('Position code'))
                    ->filter(TD::FILTER_TEXT)
                    ->alignCenter(),
                TD::make('type', __('Type'))
                    ->filter(
                        Select::make()
                            ->options([
                                'checkin'  => __('Check-in'),
                                'checkout' => __('Check-out'),
                            ])
                            ->empty(__('All'))
                    )
                    ->filterValue(fn($value) => match ($value) {
                        'checkin' => __('Check-in'),
                        'checkout' => __('Check-out'),
                        default => $value,
                    })
                    ->render(function(Check $check) {
                        return $this->badge([
                            'text'  => $check->type == 'checkin' ? __('Check-in') : __('Check-out'),
                            'color' => $check->type == 'checkin' ? 'success' : 'info',
                        ]);
                    }),
        ];
    }
}
