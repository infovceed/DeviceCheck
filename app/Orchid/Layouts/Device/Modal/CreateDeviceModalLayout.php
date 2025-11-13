<?php

namespace App\Orchid\Layouts\Device\Modal;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\Select;

class CreateDeviceModalLayout extends Rows
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

        $fields[] = Label::make()
            ->title(__('Device Codes'));
        $j=0;
        for ($i = 0; $i < 5; $i++) {
            $input = Input::make("Device.codes.{$j}")
                ->type('text');
            if ($i === 0) {
                $input->required();
            }
            $fields[] = Group::make([
                $input,
                Input::make('Device.codes.' . ($j + 1))
                    ->type('text'),
            ]);
            $j += 2;
        }
        return $fields;
    }
}
