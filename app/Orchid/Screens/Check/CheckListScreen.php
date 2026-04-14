<?php

namespace App\Orchid\Screens\Check;

use App\Exports\ChecksExport;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Jobs\RunReportDeviceDailyExport;
use App\Models\Check;
use App\Models\Department;
use App\Models\Device;
use App\Models\DeviceDailyCheck;
use App\Models\DeviceWithLocation;
use App\Models\FilterHours;
use App\Notifications\DashboardNotification;
use App\Orchid\Layouts\Check\CheckFiltersLayout;
use App\Orchid\Layouts\Check\CheckListLayout;
use App\Orchid\Layouts\Check\DepartmentSummaryLayout;
use App\Orchid\Layouts\Check\MissingDevicesLayout;
use App\Traits\ComponentsTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class CheckListScreen extends Screen
{
    use ComponentsTrait;

    protected $departments;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $filters = request()->query('filter', []);
        $selectedReportHourIds = $filters['report_time'] ?? [];
        foreach ($selectedReportHourIds as $key => $value) {
            if (!$value) {
                unset($selectedReportHourIds[$key]);
                unset($filters['report_time'][$key]);
                continue;
            }
            if (is_string($value)) {
                $selectedReportHourIds[$key] = (int) $value;
                $filters['report_time'][$key] = (int) $value;
            }
        }
        $data['departmentButtons']  = $this->buildDepartmentToggleButtons($filters);
        $data['filterHoursButtons'] = $this->buildFilterHoursToggleButtons($selectedReportHourIds);
        $filters['report_time']     = $this->resolveFilterHoursToTimes($selectedReportHourIds);

        if (isset($filters['type']) && $filters['type'] == 'checkin' && !empty($filters['report_time'])) {
            $addFilters['report_time_arrival'] = $filters['report_time'];
            $addFilters = array_merge($filters, $addFilters);
            $filters = $addFilters;
        } elseif (isset($filters['type']) && $filters['type'] == 'checkout' && !empty($filters['report_time'])) {
            $addFilters['report_time_departure'] = $filters['report_time'];
            $addFilters = array_merge($filters, $addFilters);
            $filters = $addFilters;
        }

        request()->merge(['filter' => $filters]);
        $data['checks'] = $this->checkList();

        // Summary of departments
        $showSummary = isset($filters['type']) && !empty($filters['type']) && isset($filters['created_at']) && !empty($filters['created_at']);
        if ($showSummary) {
            $this->showSummary($filters, $data);
        }
        //Table of missing devices
        $showMissing = !empty($filters['type']) && !empty($filters['created_at']);
        if ($showMissing) {
            $this->showMissing($filters, $data);
        }
        $filters['report_time'] = $selectedReportHourIds;
        request()->merge(['filter' => $filters]);
        return $data;
    }

    protected function checkList()
    {
        $perPage = request()->input('perPage', 15);

        return Check::query()
            ->filters()
            ->when(auth()->user()->hasAccess('platform.systems.devices.show-department'), function ($query,) {
                $departmentId = auth()->user()->department_id;
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->defaultSort('id', 'asc')
            ->paginate($perPage);
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Device Checks');
    }

    //permisos
    public function permission(): array
    {
        return [
            'platform.systems.device-check',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Add'))
                ->modal('createDeviceModal')
                ->icon('plus')
                ->method('create')
                ->canSee(auth()->user()->hasAccess('platform.systems.devices.create')),
            Button::make(__('Export'))
                ->icon('download')
                ->canSee(auth()->user()->hasAccess('platform.systems.report-download'))
                ->method('export', [
                    'filter' => request()->query('filter', []),
                ]),
            Button::make(__('Export totals'))
                ->icon('download')
                ->canSee(auth()->user()->hasAccess('platform.systems.report-download'))
                ->method('exportTotals', [
                    'filter' => request()->query('filter', []),
                ]),
            Link::make(__('Refresh'))
                ->icon('bs.arrow-clockwise')
                ->route('platform.systems.devices-check'),
            Link::make(__('Filter Hours'))
                ->icon('bs.funnel')
                ->route('platform.systems.devices-check.filter-hours')
                ->canSee(auth()->user()->hasAccess('platform.systems.device-check.filter-hours')),
        ];
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('platform.systems.report-download')) {
            Alert::error('You do not have permission to export.');
            return;
        }

        $filters = $request->input('filter', $request->query('filter', []));

        $user->notify(new DashboardNotification(
            __('Excel export started'),
            __('We are generating your Excel file. You will be notified when it is ready.'),
        ));

        $timestamp = now()->format('Ymd_His');
        $fileName  = "checks_{$timestamp}.xlsx";
        $disk      = 'public';
        $path      = "exports/{$fileName}";

        $ttl = (int) config('export.signed_url_ttl', 60 * 24);
        $downloadUrl = URL::temporarySignedRoute(
            'exports.download',
            now()->addMinutes($ttl),
            ['path' => $path]
        );
        Excel::queue(new ChecksExport($filters, $user), $path, $disk)
            ->chain([
                new NotifyUserOfCompletedExport($user, $downloadUrl, $fileName),
            ]);

        Toast::info(__('Your export has started. We will notify you when it finishes.'));
    }

    public function exportTotals(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('platform.systems.report-download')) {
            Alert::error('You do not have permission to export.');
            return;
        }

        $filters = $request->input('filter', $request->query('filter', []));
        $dates = $filters['created_at'] ?? null;

        if (is_string($dates)) {
            $dates = array_filter(array_map('trim', explode(',', $dates)));
        }

        $dates = array_values(array_filter((array) $dates, static fn ($d) => is_string($d) && trim($d) !== ''));

        if (empty($dates)) {
            Alert::error(__('Select one or more report dates to export totals.'));
            return;
        }

        $parsed = [];
        foreach ($dates as $date) {
            try {
                $parsed[] = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
            } catch (\Throwable $e) {
                logger()->error('Invalid date format for export totals: ' . $date, ['exception' => $e]);
            }
        }

        if (empty($parsed)) {
            Alert::error(__('Invalid report date selection.'));
            return;
        }

        $sorted = collect($parsed)
            ->sortBy(static fn (\Carbon\Carbon $date) => $date->timestamp)
            ->values();

        $start = $sorted->first()->copy()->startOfDay();
        $end = $sorted->last()->copy()->endOfDay();

        $user->notify(new DashboardNotification(
            __('Excel export started'),
            __('We are generating your Excel file. You will be notified when it is ready.'),
        ));

        $timestamp = now()->format('Ymd_His');
        $dateRange = $start->format('Ymd') . '_' . $end->format('Ymd');
        $fileName  = "device_daily_report_{$dateRange}_{$timestamp}.xlsx";
        $disk      = 'public';
        $path      = "exports/device_report/{$fileName}";

        $ttl = (int) config('export.signed_url_ttl', 60 * 24);
        $downloadUrl = URL::temporarySignedRoute(
            'exports.download',
            now()->addMinutes($ttl),
            ['path' => $path]
        );

        Bus::chain([
            new RunReportDeviceDailyExport($start->toDateString(), $end->toDateString(), $disk, $path),
            new NotifyUserOfCompletedExport($user, $downloadUrl, $fileName),
        ])->dispatch();

        Toast::info(__('Your export has started. We will notify you when it finishes.'));
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $layout[] = Layout::view('partials.auto-filter-enable');
        $layout[] = Layout::split([
            Layout::view('partials.check-department-buttons'),
            Layout::view('partials.check-filter-hours-buttons'),
        ])->ratio('50/50');
        $layout[] = Layout::view('partials.vertical-spacer');
        $layout[] = new CheckFiltersLayout();
        $layout[] = (new CheckListLayout())->title(__('Reported Devices'));
        $filters  = request()->query('filter', []);
        $showSummary = isset($filters['type']) && !empty($filters['type']) && isset($filters['created_at']) && !empty($filters['created_at']);
        if ($showSummary) {
            $layout[] = Layout::split([
                (new DepartmentSummaryLayout())->title(__('Department Summary')),
                (new MissingDevicesLayout())->title(__('Missing Devices')),
            ])->ratio('50/50');
        }

        return $layout;
    }


    public function create(Request $request): void
    {
        try {
            Toast::info(__('Device report was created successfully.'));
        } catch (\Exception $e) {
            Log::error($e);
            Alert::error(__('There was an error creating the Device report. Please try again.'));
            return;
        }
    }
    public function remove(Request $request): void
    {
        $device = Device::find($request->input('id'));
        $device->updated_by = null;
        $device->status = 0;
        $device->save();
        Toast::info(__('Device report was removed'));
    }

    /**
     * Build department summary DTOs from query rows.
     *
     * @param \Illuminate\Support\Collection $rows
     * @return \Illuminate\Support\Collection
     */
    protected function buildDepartmentSummary(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            $pending = max(0, $row->total - $row->reported);
            $pctReported = $row->total > 0 ? round(($row->reported / $row->total) * 100, 2) : 0;
            $pctPending = round(100 - $pctReported, 2);
            return (object) [
                'id'           => $row->id,
                'department'   => $row->name,
                'municipality' => $row->municipality,
                'total'        => (int) $row->total,
                'reported'     => (int) $row->reported,
                'pending'      => (int) $pending,
                'pct_reported' => $pctReported,
                'pct_pending'  => $pctPending,
            ];
        });
    }

    /**
     * Build department toggle buttons preserving current filters.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array{name: string, active: bool, url: string, bg: string, text: string, hover: string}>
     */
    protected function buildDepartmentToggleButtons(array $filters): array
    {
        $selectedDepartments = $this->normalizeDepartmentFilter($filters['department'] ?? null);
        $user = auth()->user();
        $cacheTtl = (int) config('cache.filter_options_ttl', 60);
        $cacheVersion = (int) Cache::get('filter_options_version', 1);
        $cacheKey = 'department_toggle_buttons:v' . $cacheVersion . ':' . md5(implode(',', $selectedDepartments) . '|' . $user->id);
        $departmentNames = Cache::remember($cacheKey, $cacheTtl, function () {
            return $this->getDepartments();
        });

        /** @var array<string, mixed> $baseQuery */
        $baseQuery = request()->query();
        unset($baseQuery['page'], $baseQuery['summaryPage'], $baseQuery['missingPage']);

        return array_map(function (string $departmentName) use ($selectedDepartments, $baseQuery): array {
            $colors = $this->resolveDepartmentButtonColors($departmentName);

            return [
                'name' => $departmentName,
                'active' => in_array($departmentName, $selectedDepartments, true),
                'url' => $this->buildDepartmentToggleUrl($baseQuery, $departmentName, $selectedDepartments),
                'bg' => $colors['bg'],
                'text' => $colors['text'],
                'hover' => $colors['hover'],
            ];
        }, $departmentNames);
    }

    /**
     * Resolve colors for a department button.
     *
     * @return array{bg: string, text: string, hover: string}
     */
    protected function resolveDepartmentButtonColors(string $departmentName): array
    {
        /** @var array<array-key, mixed> $map */
        $map = config('ui.department_button_colors', []);

        $normalizeKey = static function (string $value): string {
            $value = trim($value);
            if ($value === '') {
                return '';
            }

            // Normalize punctuation/spacing first (e.g. "D.C." -> "DC")
            $value = str_replace(['.', '\'', '’'], '', $value);
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

            $value = Str::upper(Str::ascii($value));

            // Keep only A-Z, 0-9 and spaces
            $value = preg_replace('/[^A-Z0-9 ]+/', '', $value) ?? $value;
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;

            return trim($value);
        };

        $default = [
            'bg' => '#ffffff',
            'text' => '#002060',
            'hover' => '#eaf0ff',
        ];

        $configuredDefault = $map['_default'] ?? null;
        if (is_array($configuredDefault)) {
            $default = array_merge($default, array_intersect_key($configuredDefault, $default));
        }

        /** @var array<string, array{bg?: string, text?: string, hover?: string}> $normalizedIndex */
        $normalizedIndex = [];
        foreach ($map as $key => $value) {
            if ($key === '_default' || ! is_array($value)) {
                continue;
            }

            $normalizedKey = $normalizeKey((string) $key);
            if ($normalizedKey === '') {
                continue;
            }

            // First match wins to keep behavior deterministic.
            $normalizedIndex[$normalizedKey] ??= $value;
        }

        $trimmed = trim($departmentName);
        $upper = function_exists('mb_strtoupper')
            ? mb_strtoupper($trimmed, 'UTF-8')
            : strtoupper($trimmed);

        $candidates = array_values(array_unique([$departmentName, $trimmed, $upper]));

        foreach ($candidates as $candidate) {
            $departmentConfig = $map[$candidate] ?? null;
            if (is_array($departmentConfig)) {
                return array_merge($default, array_intersect_key($departmentConfig, $default));
            }
        }

        $normalizedCandidate = $normalizeKey($departmentName);
        $departmentConfig = $normalizedCandidate !== '' ? ($normalizedIndex[$normalizedCandidate] ?? null) : null;
        if (is_array($departmentConfig)) {
            return array_merge($default, array_intersect_key($departmentConfig, $default));
        }
        return $default;
    }

    /**
     * @param array<string, mixed> $baseQuery
     */
    protected function buildDepartmentToggleUrl(array $baseQuery, string $departmentName, array $selectedDepartments): string
    {
        $query = $baseQuery;
        $updatedDepartments = $selectedDepartments;

        if (in_array($departmentName, $updatedDepartments, true)) {
            $updatedDepartments = array_values(array_filter(
                $updatedDepartments,
                static fn (string $department): bool => $department !== $departmentName
            ));
        } else {
            $updatedDepartments[] = $departmentName;
        }

        $query['filter'] = is_array($query['filter'] ?? null)
            ? $query['filter']
            : [];

        if (empty($updatedDepartments)) {
            unset($query['filter']['department']);
            if (empty($query['filter'])) {
                unset($query['filter']);
            }
        } else {
            $query['filter']['department'] = array_values(array_unique($updatedDepartments));
        }

        $queryString = http_build_query($query);

        return $queryString !== '' ? request()->url() . '?' . $queryString : request()->url();
    }

    /**
     * Normalize department filter values.
     *
     * @param mixed $department
     * @return array<int, string>
     */
    protected function normalizeDepartmentFilter(mixed $department): array
    {
        if ($department === null || $department === '') {
            return [];
        }

        $values = is_array($department)
            ? $department
            : array_map('trim', explode(',', (string) $department));

        return array_values(array_unique(array_filter(
            array_map(static fn ($value) => trim((string) $value), $values),
            static fn (string $value) => $value !== ''
        )));
    }

    public function showSummary(&$filters, &$data): void
    {
        $isEmptyDepartmentFilter = empty($filters['department']);
        if ($isEmptyDepartmentFilter) {
            $filters['department'] = $this->getDepartments();
        }
        if (!empty($filters['created_at']) && empty($filters['check_day'])) {
            $inputFilters = request()->input('filter', $filters);
            $inputFilters['check_day'] = $filters['created_at'];
            request()->merge(['filter' => $inputFilters]);
        }
        $query = DeviceDailyCheck::query()->filters();
        if (!empty($filters['type']) && $filters['type'] === 'checkin') {
            $query->select(
                'department as name',
                'municipality',
                DB::raw('COUNT(DISTINCT device_id) as total'),
                DB::raw('COUNT(DISTINCT CASE WHEN has_checkin > 0 THEN device_id END) as reported')
            );
        } else {
            $query->select(
                'department as name',
                'municipality',
                DB::raw('COUNT(DISTINCT device_id) as total'),
                DB::raw('COUNT(DISTINCT CASE WHEN has_checkout > 0 THEN device_id END) as reported')
            );
        }

        $query->groupBy('department', 'municipality')
            ->orderBy('department');
        $summaryPage = request()->input('summaryPage', 1);
        $paginated = $query->paginate(40, ['*'], 'summaryPage', $summaryPage);
        $paginated->appends(request()->except(['summaryPage']));
        $transformed = $this->buildDepartmentSummary(collect($paginated->items()));
        $paginated->setCollection($transformed);
        $data['departmentSummary'] = $paginated;
        if ($isEmptyDepartmentFilter) {
            unset($filters['department']);
        }
    }

    public function showMissing(&$filters, &$data): void
    {
        $dates = $filters['created_at'];
        if (is_string($dates)) {
            $dates = array_filter(array_map('trim', explode(',', $dates)));
        }
        $dates = array_unique((array)$dates);

        $type = is_array($filters['type']) ? reset($filters['type']) : $filters['type'];
        $deptNames = $filters['department'] ?? $this->getDepartments();
        $missingPage = request()->input('missingPage', 1);
        $missing = DeviceWithLocation::missingFor($deptNames, $type, $dates, $filters)
            ->paginate(15, ['*'], 'missingPage', $missingPage);
        $missing->appends(request()->except(['missingPage']));
        $data['missingDevices'] = $missing;
        $data['missingType']    = $type;
        $data['missingDates']   = $dates;
    }

    public function buildFilterHoursToggleButtons(array $selectedHours): array
    {
        $cacheTtl = (int) config('cache.filter_options_ttl', 60);
        $cacheKey = 'filter_hours:v' . (int) Cache::get('filter_options_version', 1);
        $filterHours = Cache::remember($cacheKey, $cacheTtl, function () {
            return FilterHours::query()
                ->orderBy('hour', 'asc')
                ->get();
        });

        /** @var array<string, mixed> $baseQuery */
        $baseQuery = request()->query();
        unset($baseQuery['page'], $baseQuery['summaryPage'], $baseQuery['missingPage']);
        return $filterHours->map(function (FilterHours $filterHour) use ($selectedHours, $baseQuery) {
            $isActive = in_array($filterHour->id, $selectedHours, true);
            return [
                'hour' => Carbon::parse($filterHour->hour)->format('h:i A'),
                'active' => $isActive,
                'url' => $this->buildFilterHoursToggleUrl($baseQuery, $filterHour->id, $selectedHours),
            ];
        })->toArray();
    }

    protected function buildFilterHoursToggleUrl(array $baseQuery, int $filterHourId, array $selectedHours): string
    {
        $query = $baseQuery;
        $updatedHours = $selectedHours;

        if (in_array($filterHourId, $updatedHours, true)) {
            $updatedHours = array_values(array_filter(
                $updatedHours,
                static fn (int $id): bool => $id !== $filterHourId
            ));
        } else {
            $updatedHours[] = $filterHourId;
        }

        $query['filter'] = is_array($query['filter'] ?? null)
            ? $query['filter']
            : [];

        if (empty($updatedHours)) {
            unset($query['filter']['report_time']);
            if (empty($query['filter'])) {
                unset($query['filter']);
            }
        } else {
            $query['filter']['report_time'] = array_values(array_unique($updatedHours));
        }

        $queryString = http_build_query($query);

        return $queryString !== '' ? request()->url() . '?' . $queryString : request()->url();
    }

    /**
     * @param mixed $reportTime
     * @return array<int, string>
     */
    protected function resolveFilterHoursToTimes(mixed $reportTime): array
    {
        return FilterHours::query()
            ->whereIn('id', $reportTime)
            ->get(['hour'])
            ->map(function (FilterHours $filterHour): string {
                return Carbon::parse((string) $filterHour->hour)->format('H:i:s');
            })
            ->values()
            ->all();
    }

    protected function getDepartments(): array
    {
        if ($this->departments !== null) {
            return $this->departments;
        }
        $user = auth()->user();
        $this->departments = Department::query()
            ->select('departments.name')
            ->join('divipoles', 'divipoles.department_id', '=', 'departments.id')
            ->join('devices as d', 'd.divipole_id', '=', 'divipoles.id')
            ->join('configurations as c', DB::raw('c.id'), '=', DB::raw('1'))
            ->whereColumn('d.work_shift_id', 'c.current_work_shift_id')
            ->when($user->hasAccess('platform.systems.devices.show-department'), function (Builder $query) use ($user) {
                $departmentId = $user?->department_id;

                if ($departmentId !== null) {
                    $query->where('departments.id', $departmentId);
                }
            })
            ->orderBy('departments.name', 'asc')
            ->distinct()
            ->pluck('name')
            ->toArray();
        return $this->departments;
    }
}
