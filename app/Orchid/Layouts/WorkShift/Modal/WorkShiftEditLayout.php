<?php

namespace App\Orchid\Layouts\WorkShift\Modal;

use Orchid\Screen\Contracts\Fieldable;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class WorkShiftEditLayout extends Rows
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * @return iterable<Fieldable>
     */
    protected function fields(): iterable
    {
        return [
            Group::make([
                Input::make('name')
                    ->title(__('Name'))
                    ->required(),
            ]),
        ];
    }
}
