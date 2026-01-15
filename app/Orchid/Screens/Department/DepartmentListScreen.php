<?php

namespace App\Orchid\Screens\Department;

use Orchid\Screen\TD;
use Orchid\Screen\Screen;
use App\Models\Department;
use Orchid\Screen\Fields\Relation;
use Orchid\Support\Facades\Layout;

class DepartmentListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'departments' => Department::query()
                                        ->filters()
                                        ->defaultSort('name', 'asc')
                                        ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Departments');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('departments', [
                TD::make('id', 'ID')
                    ->sort()
                    ->align(TD::ALIGN_CENTER),
                TD::make('name', __('Name'))
                    ->sort()
                    ->filter(
                        Relation::make('department')
                            ->fromModel(Department::class, 'name','name')
                    ),
                TD::make('code', __('Code'))
                    ->sort()
                    ->render(function (Department $department) {
                        if (strlen($department->code) < 2) {
                            return str_pad($department->code, 2, '0', STR_PAD_LEFT);
                        }
                        return $department->code;
                    })
                    ->filter(
                        TD::FILTER_TEXT
                    ),
                TD::make('created_at', __('Created'))
                    ->sort()
                    ->render(fn (Department $department) => $department->created_at->format('Y-m-d H:i:s')),
            ]),
        ];
    }
}
