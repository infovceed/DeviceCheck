<?php

namespace App\Orchid\Layouts\Device\Modal;

use Orchid\Screen\Contracts\Fieldable;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;

class BulkUpdateByExcelModalLayout extends Rows
{
    /**
     * @return iterable<Fieldable>
     */
    protected function fields(): iterable
    {
        return [
            Group::make([
                Input::make('payload.file')
                    ->type('file')
                    ->title(__('Excel File'))
                    ->help(__('Required columns: id_dispositivo_cambio, telefono, imei, llave'))
                    ->accept('.xlsx,.xls,.csv')
                    ->required(),
            ]),
        ];
    }
}
