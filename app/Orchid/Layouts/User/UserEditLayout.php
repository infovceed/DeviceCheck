<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Field;
use App\Models\Department;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\Select;

class UserEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        $departments = Department::orderBy('name', 'asc')->pluck('name', 'id')->toArray();
        return [
            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('Name')),
            Input::make('user.document')
                ->type('text')
                ->max(20)
                ->title(__('Document'))
                ->placeholder(__('Document')),
            Select::make('user.department_id')
                ->options($departments)
                ->applyScope('orderByName')
                ->title(__('Department'))
                ->empty(__('Select a department'))
                ->required()
                ->help('Select a department from the list'),

            Input::make('user.email')
                ->type('email')
                ->required()
                ->title(__('Email'))
                ->placeholder(__('Email')),
        ];
    }
}
