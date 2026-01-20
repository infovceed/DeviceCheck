<?php

namespace App\Orchid\Layouts\Device\Modal;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;

class BulkUpdateByExcelModalLayout extends Rows
{
    protected function fields(): iterable
    {
        return [
            Group::make([
                Input::make('payload.file')
                    ->type('file')
                    ->title(__('Archivo Excel'))
                    ->help(__('Columnas requeridas: id_dispositivo_cambio, telefono, imei'))
                    ->accept('.xlsx,.xls,.csv')
                    ->required(),
            ]),
        ];
    }
}
