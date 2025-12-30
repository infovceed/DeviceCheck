<?php

namespace App\Orchid\Screens\Divipole;

use Orchid\Screen\TD;
use App\Models\Divipole;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use App\Orchid\Filters\TerritoryFilter;

class DivipoleListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {

        $divipoles = Divipole::query()
                ->filters([TerritoryFilter::class])
                ->defaultSort('id', 'asc')
                ->with(['department', 'municipality'])
                ->paginate();

        return [
            'divipoles' => $divipoles,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Divipole';
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
            Layout::table('divipoles', [
                TD::make('id', 'ID')
                    ->sort()
                    ->align(TD::ALIGN_CENTER),
                TD::make('code', __('Code'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT),
                TD::make('department.code', __('Department Code'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn (Divipole $divipole) => $divipole->department->code),
                TD::make('department.name', __('Department'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT),
                TD::make('municipality.code', __('Municipality Code'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn (Divipole $divipole) => $divipole->municipality->code),
                TD::make('municipality.name', __('Municipality'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT),
                TD::make('position_name', __('Position'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT),
                TD::make('position_address', __('Address'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT),
                TD::make('created_at', __('Created'))
                    ->sort()
                    ->render(fn (Divipole $divipole) => $divipole->created_at->format('Y-m-d H:i:s')),
            ]),
        ];
    }
}
