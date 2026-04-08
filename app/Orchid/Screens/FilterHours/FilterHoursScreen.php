<?php

namespace App\Orchid\Screens\FilterHours;

use App\Models\FilterHours;
use App\Orchid\Layouts\FilterHours\FilterHoursListLayout;
use App\Orchid\Layouts\FilterHours\Modal\FilterHoursEditModalLayout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class FilterHoursScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $hours = FilterHours::query()->paginate();
        return [
            'times' => $hours,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Filter Hours');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Create'))
                ->modal('createFilterHourModal')
                ->icon('bs.plus')
                ->canSee(auth()->user()->hasAccess('platform.systems.device-check.filter-hours.create'))
                ->method('create'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            FilterHoursListLayout::class,
            Layout::modal('createFilterHourModal', [
                FilterHoursEditModalLayout::class
            ])->title(__('Create Filter Hour'))
              ->applyButton(__('Save'))
              ->closeButton(__('Cancel')),
            Layout::modal('editFilterHourModal', [
                FilterHoursEditModalLayout::class
            ])->title(__('Edit Filter Hour'))
                ->async('asyncGetFilterHour')
                ->applyButton(__('Save'))
                ->closeButton(__('Cancel')),
        ];
    }

    public function create(Request $request): void
    {
        $request->validate([
            'hour' => ['required', 'date_format:H:i', Rule::unique('filter_hours', 'hour')],
        ], [
            'hour.unique' => __('This hour has already been registered.'),
        ]);

        try {
            FilterHours::create([
                'hour' => $request->input('hour'),
            ]);

            Toast::info(__('Filter hour was created successfully.'));
        } catch (\Exception $e) {
            Toast::error(__('There was an error creating the filter hour. Please try again.'));
        }
    }

    public function update(Request $request, FilterHours $filterHour): void
    {
        $request->validate([
            'hour' => [
                'required',
                'date_format:H:i',
                Rule::unique('filter_hours', 'hour')->ignore($filterHour->id),
            ],
        ], [
            'hour.unique' => __('This hour has already been registered.'),
        ]);

        try {
            $filterHour->update([
                'hour' => $request->input('hour'),
            ]);

            Toast::info(__('Filter hour was updated successfully.'));
        } catch (\Exception $e) {
            Toast::error(__('There was an error updating the filter hour. Please try again.'));
        }
    }

    public function delete(Request $request, FilterHours $filterHour): void
    {
        try {
            $filterHour->delete();
            Toast::info(__('Filter hour was deleted successfully.'));
        } catch (\Exception $e) {
            Toast::error(__('There was an error deleting the filter hour. Please try again.'));
        }
    }

    public function asyncGetFilterHour(FilterHours $filterHour): array
    {
        return [
            'hour' => $filterHour->hour->format('H:i:s'),
        ];
    }
}
