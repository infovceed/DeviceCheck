<?php

namespace App\Orchid\Layouts\WorkShift;

use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class WorkShiftListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'workShifts';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id', __('ID'))
                ->sort()
                ->filter(TD::FILTER_NUMERIC)
                ->render(function ($workShift) {
                    return $workShift->id;
                }),
            TD::make('name', __('Name'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function ($workShift) {
                    return $workShift->name;
                }),
             TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn ($workShift)=> DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        ModalToggle::make(__('Edit'))
                            ->icon('bs.pencil')
                            ->asyncParameters([
                                'workShift' => $workShift->id,
                            ])
                            ->modal('editWorkShiftModal')
                            ->method('update', ['workShift' => $workShift->id])
                            ->canSee(auth()->user()->hasAccess('platform.systems.work-shifts.edit')),
                    ])),

        ];
    }
}
