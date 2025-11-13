<?php

namespace App\Orchid\Screens\Municipality;

use App\Models\Municipality;
use Orchid\Screen\TD;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class MunicipalityListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $municipalities = Municipality::query()
                        ->filters()
                        ->defaultSort('name', 'asc')
                        ->paginate();

        return [
            'municipalities' => $municipalities,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Municipalities');
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
            Layout::table('municipalities', [
                TD::make('id', 'ID')
                    ->sort()
                    ->align(TD::ALIGN_CENTER),
                TD::make('name', __('Name'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT),
                TD::make('code', __('Code'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(function (Municipality $municipality) {
                        if (strlen($municipality->code) < 2) {
                            return str_pad($municipality->code, 2, '0', STR_PAD_LEFT);
                        }
                        return $municipality->code;
                    }),
                TD::make('created_at', __('Created'))
                    ->sort()
                    ->render(fn (Municipality $municipality) => $municipality->created_at->format('Y-m-d H:i:s')),
            ]),
        ];
    }
}
