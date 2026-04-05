<?php

namespace App\Orchid\Layouts\FilterHours\Modal;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Layouts\Rows;

class FilterHoursEditModalLayout extends Rows
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            DateTimer::make('hour')
                    ->noCalendar()
                    ->serverFormat('H:i:s')
                    ->format('H:i')
                    ->placeholder('00:00')
                    ->allowInput()
        ];
    }
}
