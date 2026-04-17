<?php

namespace App\Orchid\Layouts\FilterHours;

use Carbon\Carbon;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class FilterHoursListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'times';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [

            TD::make('id', 'ID')
                    ->align(TD::ALIGN_CENTER)->width('100px'),
            TD::make('hour', __('Hour'))
                ->render(function ($model) {
                    return Carbon::parse((string) $model->hour)->format('h:i:s a');
                })
                ->sort(),
            TD::make('department_name', __('Department Name')),
            TD::make('type', __('Report Type'))
                ->render(function ($model) {
                    if ($model->type === 'checkin') {
                        return __('Arrival');
                    } elseif ($model->type === 'checkout') {
                        return __('Departure');
                    }
                    return $model->type;
                }),
             TD::make('position_name', __('Position Name')),
            TD::make('position_name', __('Position Name')),
            TD::make('Actions', __('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('250px')
                ->render(function ($model) {
                    return Group::make([
                            ModalToggle::make(__('Edit'))
                                ->icon('bs.pencil')
                                ->asyncParameters([
                                    'filterHour' => $model->id,
                                ])
                                ->modal('editFilterHourModal')
                                ->method('update', ['filterHour' => $model->id])
                                ->canSee(auth()->user()->hasAccess('platform.systems.device-check.filter-hours.edit')),
                            Button::make(__('Delete'))
                                ->icon('bs.trash')
                                ->confirm(__('Are you sure you want to delete this filter hour? This action cannot be undone.'))
                                ->method('delete', ['filterHour' => $model->id])
                                ->canSee(auth()->user()->hasAccess('platform.systems.device-check.filter-hours.delete')),
                        ]);
                }),
        ];
    }
}
