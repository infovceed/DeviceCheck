<?php

declare(strict_types=1);

namespace App\Orchid\Screens;


use Orchid\Screen\TD;
use App\Models\Device;
use Orchid\Screen\Screen;
use App\Models\Department;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use App\Orchid\Layouts\Dashboard\ChartsLayout;
use App\Orchid\Layouts\Dashboard\PieChartLayout;
use App\Orchid\Layouts\Dashboard\ChartFiltersLayout;

class PlatformScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    private array $viewData = [];
    public function query(): iterable
    {
        $user           = auth()->user();
        $departmentID   = $user->hasAccess('platform.systems.dashboard.show-all')
                        ? request('department', [])
                        : [$user->department_id];
        $municipalities = request('municipality', []);
        $positions      = request('position', []);
        $incidents      = Device::getIncidentsOpen();
        $date           = request('chart_date', now()->toDateString());
        [$labels, $valuesTotal, $valuesReported, $reportedCheckout] = Department::getChartData($date, $departmentID, $municipalities,$positions );

        $totalReportedIn  = array_sum($valuesReported);
        $totalReportedOut = array_sum($reportedCheckout);
        $totalDevicesSum  = array_sum($valuesTotal);
        $percentageIn     = $totalDevicesSum ? round(($totalReportedIn / $totalDevicesSum) * 100, 1) : 0;
        $percentageOut    = $totalDevicesSum ? round(($totalReportedOut / $totalDevicesSum) * 100, 1) : 0;

        $data = [
            'stats' => [
                'totalRecords'     => $totalDevicesSum,
                'totalReported'    => $totalReportedIn + $totalReportedOut,
                'totalReportedIn'  => $totalReportedIn,
                'totalReportedOut' => $totalReportedOut,
                'percentage'       => $totalDevicesSum ? round((($totalReportedIn + $totalReportedOut) / $totalDevicesSum) * 100, 2) : 0,
                'percentageIn'     => $percentageIn,
                'percentageOut'    => $percentageOut,
            ],
            'departmentID' => $departmentID,
            'incidents' => $incidents,
        ];

        $data['departmentsChart'] = [
            ['labels' => $labels, 'name' => __('Meta'),    'values' => $valuesTotal],
            ['labels' => $labels, 'name' => __('Arrival'),'values' => $valuesReported],
            ['labels' => $labels, 'name' => __('Check-out'),'values' => $reportedCheckout],
        ];
        $totalDevices  = array_sum($valuesTotal)??0;
        $reportedIn    = array_sum($valuesReported) ?? 0;
        $reportedOut   = array_sum($reportedCheckout) ?? 0;
        $unreportedIn  = max(0, $totalDevices - $reportedIn);
        $unreportedOut = max(0, $totalDevices - $reportedOut);
        $data['checkinChart'] = [
            [
                'labels' => [
                    __('Pending'),
                    __('Reported'),
                ],
                'name'   => __('Arrival'),
                'values' => [$unreportedIn, $reportedIn],
            ],
        ];
        $data['checkoutChart'] = [
            [
                'labels' => [
                    __('Pending'),
                    __('Reported'),
                ],
                'name'   => __('Check-out'),
                'values' => [$unreportedOut, $reportedOut],
            ],
        ];

        if (is_array($departmentID) && count($departmentID) === 1 && $departmentID[0]) {
            [$mlabels, $mtotal, $mcheckin, $mcheckout] = Department::getMunicipalityChartData(
                $date,
                (int)$departmentID[0],
                $municipalities,
                $positions
            );

            $data['municipalitiesChart'] = [
                ['labels' => $mlabels, 'name' => 'Meta',       'values' => $mtotal],
                ['labels' => $mlabels, 'name' => __('Arrival'),   'values' => $mcheckin],
                ['labels' => $mlabels, 'name' => __('Check-out'),  'values' => $mcheckout],
            ];
        }
        $this->viewData = $data;
        return $data;
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'MANAGE YOUR DEVICES';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Welcome to your devices report application.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Refresh'))
                ->icon('bs.arrow-clockwise')
                ->route('platform.main', request()->query())
                ->attributes(['data-turbo' => 'false']),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        $user = auth()->user();
        if (!$user->hasAccess('platform.systems.dashboard')) {
            return [Layout::view('platform.welcome_collaborator')];
        }

        $showAll = $user->hasAccess('platform.systems.dashboard.show-all');
        $showRealTime = $user->hasAccess('platform.systems.dashboard.realtime');
        $data = $this->viewData;

        if (!$showRealTime) {
            return $this->buildStaticLayouts($data, $showAll);
        }

        return $this->buildRealtimeLayouts($data, $showAll);
    }

    protected function buildStaticLayouts(array $data, bool $showAll): array
    {
        $layouts = [
            $this->buildStatsColumns($data['stats'] ?? []),
        ];

        if ($showAll) {
            $layouts = array_merge($layouts, $this->buildChartFilters());
        }

        $layouts[] = ChartsLayout::make('departmentsChart', 'Reporte por departamento')
            ->description('Comparativa de dispositivos reportados por departamento.');

        $layouts[] = $this->buildStaticPies($data['stats'] ?? []);

        if (isset($data['municipalitiesChart'])) {
            $layouts[] = ChartsLayout::make('municipalitiesChart', 'Reporte por municipio')
                ->description('Totals, check-in and check-out by municipality');
        }

        $layouts = array_merge($layouts, $this->buildIncidentsSection());

        return $layouts;
    }

    protected function buildRealtimeLayouts(array $data, bool $showAll): array
    {
        $wsUrl = $this->getWsUrl();

        $layouts = [
            $this->buildRealtimeStatsColumns($data['stats'] ?? [], $wsUrl),
        ];

        if ($showAll) {
            $layouts = array_merge($layouts, $this->buildChartFilters());
        }

        $layouts[] = Layout::view('components.dashboard.departments-realtime', [
            'initial' => $data['departmentsChart'] ?? [],
            'wsUrl' => $wsUrl,
        ]);

        $layouts[] = $this->buildRealtimePies($data['stats'] ?? [], $wsUrl);

        if (isset($data['municipalitiesChart'])) {
            $layouts[] = Layout::view('components.dashboard.municipalities-realtime', [
                'initial' => $data['municipalitiesChart'] ?? [],
                'wsUrl' => $wsUrl,
            ]);
        }

        return $layouts;
    }

    protected function buildStatsColumns(array $stats)
    {
        return Layout::columns([
            Layout::view('components.dashboard.stats', [
                'title' => $stats['totalRecords'] ?? 0,
                'subtitle' => __('Total Devices'),
                'icon' => 'bs.phone',
                'iconColor' => '#92D050',
            ]),
            Layout::view('components.dashboard.stats', [
                'title' => ($stats['percentageIn'] ?? 0) . '%',
                'subtitle' => __('Devices Reported (Arrival)'),
                'icon' => 'bs.phone-vibrate',
                'iconColor' => '#002060',
            ]),
            Layout::view('components.dashboard.stats', [
                'title' => ($stats['percentageOut'] ?? 0) . '%',
                'subtitle' => __('Devices Reported (Departure)'),
                'icon' => 'bs.phone-vibrate',
                'iconColor' => '#FF8805',
            ]),
            Layout::view('components.dashboard.stats', [
                'title' => $stats['totalReportedIn'] ?? 0,
                'subtitle' => __('Total Reported (Arrival)'),
                'icon' => 'bs.phone-vibrate-fill',
                'iconColor' => '#002060',
            ]),
            Layout::view('components.dashboard.stats', [
                'title' => $stats['totalReportedOut'] ?? 0,
                'subtitle' => __('Total Reported (Departure)'),
                'icon' => 'bs.phone-vibrate-fill',
                'iconColor' => '#FF8805',
            ]),
        ]);
    }

    protected function buildChartFilters(): array
    {
        return [
            Layout::view('partials.auto-filter-enable'),
            new ChartFiltersLayout(),
        ];
    }

    protected function buildStaticPies(array $stats)
    {
        $reportedInPct = $stats['percentageIn'] ?? 0;
        $pendingInPct = 100 - $reportedInPct;
        $reportedOutPct = $stats['percentageOut'] ?? 0;
        $pendingOutPct = 100 - $reportedOutPct;

        $pieIn = PieChartLayout::make('checkinChart', __('Arrival'))
            ->colors(['#DBDBDB', '#002060'])
            ->description("<div class='d-flex fs-6'><div class='bg-grey mr-2 p-2 rounded-2'>" . __('Pending') . ": {$pendingInPct}%</div><div class='bg-primary mx-2 p-2 rounded-2'>" . __('Reported') . ": {$reportedInPct}%</div></div>");

        $pieOut = PieChartLayout::make('checkoutChart', __('Departure'))
            ->colors(['#DBDBDB', '#FF8805'])
            ->description("<div class='d-flex fs-6'><div class='bg-grey mr-2 p-2 rounded-2'>" . __('Pending') . ": {$pendingOutPct}%</div><div class='bg-warning mx-2 p-2 rounded-2'>" . __('Reported') . ": {$reportedOutPct}%</div></div>");

        return Layout::split([
            $pieIn,
            $pieOut,
        ])->ratio('50/50');
    }

    protected function buildRealtimeStatsColumns(array $stats, ?string $wsUrl)
    {
        return Layout::columns([
            Layout::view('components.dashboard.realtime-stats-card', [
                'metric' => 'totalRecords',
                'value' => $stats['totalRecords'] ?? 0,
                'subtitle' => __('Total Devices'),
                'icon' => 'phone',
                'iconColor' => '#92D050',
                'wsUrl' => $wsUrl,
            ]),
            Layout::view('components.dashboard.realtime-stats-card', [
                'metric' => 'percentageIn',
                'value' => $stats['percentageIn'] ?? 0,
                'subtitle' => __('Devices Reported (Arrival)'),
                'icon' => 'phone-vibrate',
                'iconColor' => '#002060',
                'wsUrl' => $wsUrl,
            ]),
            Layout::view('components.dashboard.realtime-stats-card', [
                'metric' => 'percentageOut',
                'value' => $stats['percentageOut'] ?? 0,
                'subtitle' => __('Devices Reported (Departure)'),
                'icon' => 'phone-vibrate',
                'iconColor' => '#FF8805',
                'wsUrl' => $wsUrl,
            ]),
            Layout::view('components.dashboard.realtime-stats-card', [
                'metric' => 'totalReportedIn',
                'value' => $stats['totalReportedIn'] ?? 0,
                'subtitle' => __('Total Reported (Arrival)'),
                'icon' => 'phone-vibrate-fill',
                'iconColor' => '#002060',
                'wsUrl' => $wsUrl,
            ]),
            Layout::view('components.dashboard.realtime-stats-card', [
                'metric' => 'totalReportedOut',
                'value' => $stats['totalReportedOut'] ?? 0,
                'subtitle' => __('Total Reported (Departure)'),
                'icon' => 'phone-vibrate-fill',
                'iconColor' => '#FF8805',
                'wsUrl' => $wsUrl,
            ]),
        ]);
    }

    protected function buildRealtimePies(array $stats, ?string $wsUrl)
    {
        $initialIn = [
            'pending' => max(0, ($stats['totalRecords'] ?? 0) - ($stats['totalReportedIn'] ?? 0)),
            'reported' => $stats['totalReportedIn'] ?? 0,
        ];

        $initialOut = [
            'pending' => max(0, ($stats['totalRecords'] ?? 0) - ($stats['totalReportedOut'] ?? 0)),
            'reported' => $stats['totalReportedOut'] ?? 0,
        ];

        return Layout::split([
            Layout::view('components.dashboard.pie-realtime', [
                'mode' => 'arrival',
                'title' => __('Arrival'),
                'wsUrl' => $wsUrl,
                'initial' => $initialIn,
            ]),
            Layout::view('components.dashboard.pie-realtime', [
                'mode' => 'departure',
                'title' => __('Departure'),
                'wsUrl' => $wsUrl,
                'initial' => $initialOut,
            ]),
        ])->ratio('50/50');
    }

    protected function buildIncidentsSection(): array
    {
        if (!config('incidents.enabled')) {
            return [];
        }

        return [
            Layout::split([
                Layout::table('incidents', [
                    TD::make('department_name', __('Department')),
                    TD::make('municipality_name', __('Municipality')),
                    TD::make('position_name', __('Position')),
                    TD::make('tel', __('Mobile')),
                    TD::make('Device_id', __('Write'))
                        ->render(fn($inc) => Link::make('')
                            ->route('platform.systems.incidents', ['device' => $inc->device_id])
                            ->icon('bs.pencil')
                        ),
                ])->title(__('Incidents')),
                Layout::view('components.dashboard.empty'),
            ])->ratio('70/30'),
        ];
    }

    protected function getWsUrl(): ?string
    {
        return config('services.websocket.url') ?? null;
    }
}
