<?php

namespace App\Orchid\Screens\FilterHours;

use App\Models\Device;
use App\Models\FilterHours;
use App\Models\FilterHoursDepartment;
use App\Orchid\Layouts\FilterHours\FilterHoursListLayout;
use App\Orchid\Layouts\FilterHours\Modal\FilterHoursEditModalLayout;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
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
        $hours = FilterHours::query()
                ->select('filter_hours.id', 'filter_hours.hour', 'fhd.type', 'fhd.position_name', 'd.name as department_name')
                ->join('filter_hours_departments as fhd', 'filter_hours.id', '=', 'fhd.filter_hours_id')
                ->join('departments as d', 'fhd.department_id', '=', 'd.id')
                ->orderBy('hour')
                    ->paginate();
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
            Button::make(__('Sync Filter Hours'))
                ->icon('bs.arrow-repeat')
                //->canSee(auth()->user()->hasAccess('platform.systems.device-check.filter-hours.sync'))
                ->method('syncFilterHours'),
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

    public function syncFilterHours(): void
    {
        try {
            /** @var \Illuminate\Support\Collection<int, object{hour:string, department_id:int, type:string, position_name:string}> $reportTimes */
            $reportTimes = Device::query()
                ->select(
                    'report_time as hour',
                    'department_id',
                    'position_name',
                    DB::raw('"checkin" as type')
                )
                ->join('divipoles', 'devices.divipole_id', '=', 'divipoles.id')
                ->join('configurations as c', DB::raw('c.id'), '=', DB::raw('1'))
                ->whereColumn('devices.work_shift_id', 'c.current_work_shift_id')
                ->whereNotNull('report_time')
                ->whereNotNull('department_id')
                ->whereNotNull('position_name')
                ->distinct()
                ->union(
                    Device::query()
                        ->select(
                            'report_time_departure as hour',
                            'department_id',
                            'position_name',
                            DB::raw('"checkout" as type')
                        )
                        ->join('divipoles', 'devices.divipole_id', '=', 'divipoles.id')
                        ->join('configurations as c', DB::raw('c.id'), '=', DB::raw('1'))
                        ->whereColumn('devices.work_shift_id', 'c.current_work_shift_id')
                        ->whereNotNull('report_time_departure')
                        ->whereNotNull('department_id')
                        ->whereNotNull('position_name')
                        ->distinct()
                )
                ->get();

            DB::transaction(function () use ($reportTimes): void {
                $this->clearFilterHoursTable();

                if ($reportTimes->isEmpty()) {
                    return;
                }

                $now = now();
                $filterHoursToInsert = $reportTimes
                    ->pluck('hour')
                    ->unique()
                    ->values()
                    ->map(static function (string $hour) use ($now): array {
                        return [
                            'hour' => $hour,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->all();

                FilterHours::query()->insert($filterHoursToInsert);

                /** @var array<string, int> $filterHoursByHour */
                $filterHoursByHour = FilterHours::query()
                    ->pluck('id', 'hour')
                    ->mapWithKeys(static fn ($id, $hour): array => [(string) $hour => (int) $id])
                    ->all();

                $pivotRows = $reportTimes
                    ->map(static function (object $item) use ($filterHoursByHour, $now): ?array {
                        $filterHourId = $filterHoursByHour[$item->hour] ?? null;

                        if ($filterHourId === null) {
                            return null;
                        }

                        return [
                            'filter_hours_id' => $filterHourId,
                            'department_id' => (int) $item->department_id,
                            'type' => (string) $item->type,
                            'position_name' => (string) $item->position_name,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->filter()
                    ->unique(static fn (array $row): string => implode('|', [
                        $row['filter_hours_id'],
                        $row['department_id'],
                        $row['type'],
                        $row['position_name'],
                    ]))
                    ->values()
                    ->all();

                if ($pivotRows !== []) {
                    FilterHoursDepartment::query()->insert($pivotRows);
                }
            });

            Cache::flush();
            Toast::info(__('Filter hours options synchronized successfully.'));
        } catch (\Exception $e) {
            Toast::error(__('There was an error synchronizing filter hours options. Please try again.'));
        }
    }

    public function clearFilterHoursTable(): void
    {
        Log::channel('config')->info('Clearing filter hours table by user ID: ' . auth()->id());
        DB::table('filter_hours_departments')->delete();
        DB::table('filter_hours')->delete();
    }
}
