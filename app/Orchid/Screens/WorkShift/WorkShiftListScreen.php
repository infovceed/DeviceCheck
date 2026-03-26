<?php

namespace App\Orchid\Screens\WorkShift;

use App\Http\Requests\WorkShift\EditWorkShiftRequest;
use App\Models\WorkShift;
use App\Orchid\Layouts\WorkShift\Modal\WorkShiftEditLayout;
use App\Orchid\Layouts\WorkShift\WorkShiftListLayout;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class WorkShiftListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'workShifts' => WorkShift::query()->filters()->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Work Shifts');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Create work shift'))
                ->icon('bs.plus-lg')
                ->modal('createWorkShiftModal')
                ->method('create')
                ->canSee(auth()->user()->hasAccess('platform.systems.work-shifts.create')),
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
            WorkShiftListLayout::class,
            Layout::modal('createWorkShiftModal', [
                WorkShiftEditLayout::class
            ])->title(__('Create work shift'))
              ->applyButton(__('Save'))
              ->closeButton(__('Cancel')),
            Layout::modal('editWorkShiftModal', [
                WorkShiftEditLayout::class
             ])->title(__('Edit work shift'))
               ->applyButton(__('Save'))
               ->closeButton(__('Cancel'))
               ->async('asyncGetWorkShift'),
        ];
    }

    public function permission(): ?iterable
    {
        return [
            'platform.systems.work-shifts',
        ];
    }

    public function create(EditWorkShiftRequest $data)
    {
        try {
            WorkShift::create($data->validated());
            Toast::info(__('Work shift was created successfully.'));
        } catch (\Exception $e) {
            Log::error($e);
            Alert::error(__('There was an error creating the work shift. Please try again.'));
            return;
        }
    }

    public function update(EditWorkShiftRequest $data, WorkShift $workShift)
    {
        try {
            $workShift->update($data->validated());
            Toast::info(__('Work shift was updated successfully.'));
        } catch (\Exception $e) {
            Log::error($e);
            Alert::error(__('There was an error updating the work shift. Please try again.'));
            return;
        }
    }

    public function asyncGetWorkShift(WorkShift $workShift): array
    {
        return [
            'name' => $workShift->name,
        ];
    }
}
