<?php

namespace App\Orchid\Layouts\Device\Modal;

use App\Models\Divipole;
use Orchid\Screen\Contracts\Fieldable;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\DateTimer;

class EditDeviceModalLayout extends Rows
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
     * @return iterable<Fieldable>
     */
    protected function fields(): iterable
    {
        return [

            Group::make([
                Relation::make('payload.divipole_id')
                    ->title(__('DIVIPOLE (por código)'))
                    ->fromModel(Divipole::class, 'code', 'id')
                    ->allowEmpty()
                    ->placeholder(__('Select a divipole'))
                    ->displayAppend('label')
                    ->set('required', true),
            ]),

            Group::make([
                Input::make('payload.tel')
                    ->title(__('Phone'))
                    ->placeholder('3001234567')
                    ->set('required', true),

                Input::make('payload.imei')
                    ->title(__('IMEI'))
                    ->maxlength(30)
                    ->set('required', true),
            ]),

            Group::make([
                Input::make('payload.device_key')
                    ->title(__('Key'))
                    ->set('required', true),

                Input::make('payload.sequential')
                    ->title(__('Consecutivo'))
                    ->set('required', true),
            ]),
            Group::make([
                Input::make('payload.latitude')
                    ->title(__('Latitud'))
                    ->type('text')
                    ->placeholder('4,7110')
                    ->help(__('Report latitude'))
                    ->style('max-width: 100%')
                    ->set('required', true),

                Input::make('payload.longitude')
                    ->title(__('Longitud'))
                    ->type('text')
                    ->placeholder('-74,0721')
                    ->help(__('Report longitude'))
                    ->style('max-width: 100%')
                    ->set('required', true),
            ]),

            Group::make([
                DateTimer::make('payload.report_time')
                    ->title(__('Report time (Arrival)'))
                    ->noCalendar()
                    ->format('H:i')
                    ->serverFormat('H:i:s')
                    ->allowInput()
                    ->placeholder('HH:mm')
                    ->set('required', true),

                DateTimer::make('payload.report_time_departure')
                    ->title(__('Report time (Departure)'))
                    ->noCalendar()
                    ->format('H:i')
                    ->serverFormat('H:i:s')
                    ->allowInput()
                    ->placeholder('HH:mm')
                    ->set('required', true),
            ]),
        ];
    }
}
