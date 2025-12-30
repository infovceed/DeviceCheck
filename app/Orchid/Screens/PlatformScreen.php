<?php

declare(strict_types=1);

namespace App\Orchid\Screens;


use Orchid\Screen\TD;
use App\Models\Device;
use App\Models\Incident;
use Orchid\Screen\Screen;
use App\Models\Department;  
use Orchid\Screen\Repository;
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
    public function query(): iterable
    {
        $user    = auth()->user();
        $departmentID  = $user->hasAccess('platform.systems.dashboard.show-all')
            ? request('department', [])
            : [$user->department_id];
        $municipalities = request('municipality', []);
        $positions = request('position', []);
        $incidents = Device::getIncidentsOpen();
        $date = request('chart_date', now()->toDateString());
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

        $sumIn = $unreportedIn + $reportedIn;
        $pctPendingIn  = $sumIn ? round(($unreportedIn / $sumIn) * 100, 1) : 0;
        $pctReportedIn = $sumIn ? round(($reportedIn / $sumIn) * 100, 1) : 0;
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

        $sumOut = $unreportedOut + $reportedOut;
        $pctPendingOut  = $sumOut ? round(($unreportedOut / $sumOut) * 100, 1) : 0;
        $pctReportedOut = $sumOut ? round(($reportedOut / $sumOut) * 100, 1) : 0;
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
        //boton para recargar la pagina, si hay filtros aplicados, mantenerlos
        return [
            Link::make(__('Refresh'))
                ->icon('bs.arrow-clockwise')
                ->route('platform.main', request()->query()),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        $user=auth()->user();
        if(!$user->hasAccess('platform.systems.dashboard')) {
            return [Layout::view('platform.welcome_collaborator')];
        }
        $showAll=$user->hasAccess('platform.systems.dashboard.show-all');
        $data    = $this->query();
        $layouts = [
            Layout::columns([
                Layout::view('components.dashboard.stats',[
                    'title' => $data['stats']['totalRecords'] ?? 0,
                    'subtitle' =>__('Total Devices'),
                    'icon' => 'bs.phone',
                    'iconColor' => '#92D050',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => "{$data['stats']['percentageIn']}%",
                    'subtitle' => __('Devices Reported (Arrival)'),
                    'icon' => 'bs.phone-vibrate',
                    'iconColor' => '#002060',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => "{$data['stats']['percentageOut']}%",
                    'subtitle' => __('Devices Reported (Departure)'),
                    'icon' => 'bs.phone-vibrate',
                    'iconColor' => '#FF8805',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => $data['stats']['totalReportedIn'],
                    'subtitle' => __('Total Reported (Arrival)'),
                    'icon' => 'bs.phone-vibrate-fill',
                    'iconColor' => '#002060',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => $data['stats']['totalReportedOut'],
                    'subtitle' => __('Total Reported (Departure)'),
                    'icon' => 'bs.phone-vibrate-fill',
                    'iconColor' => '#FF8805',
                ]),
            ]),
        ];
        if($showAll) {
            $layouts[] = Layout::view('partials.auto-filter-enable');
            $chartFilters = new ChartFiltersLayout();
            $layouts[] = $chartFilters;
        }
        
        $deptLayout = ChartsLayout::make('departmentsChart', 'Reporte por departamento')
            ->description('Comparativa de dispositivos reportados por departamento.');
        $layouts[] = $deptLayout;
        $reportedInPct  = $data['stats']['percentageIn'] ?? 0;
        $pendingInPct   = 100 - $reportedInPct;
        $reportedOutPct = $data['stats']['percentageOut'] ?? 0;
        $pendingOutPct  = 100 - $reportedOutPct;

        $pieIn = PieChartLayout::make('checkinChart', __('Arrival'))
            ->description("<div class='d-flex fs-6'><div class='bg-grey mr-2 p-2 rounded-2'>".__('Pending') . ": {$pendingInPct}%</div><div class='bg-success mx-2 p-2 rounded-2'>".__('Reported') . ": {$reportedInPct}%</div></div>");
        $pieOut = PieChartLayout::make('checkoutChart', __('Departure'))
            ->description("<div class='d-flex fs-6'><div class='bg-grey mr-2 p-2 rounded-2'>".__('Pending') . ": {$pendingOutPct}%</div><div class='bg-success mx-2 p-2 rounded-2'>".__('Reported') . ": {$reportedOutPct}%</div></div>");

        $layouts[] = Layout::split([
            $pieIn,
            $pieOut,
        ])->ratio('50/50');

        if (isset($data['municipalitiesChart'])) {
            $munLayout = ChartsLayout::make('municipalitiesChart', 'Reporte por municipio')
                ->description('Totals, check-in and check-out by municipality');
            $layouts[] = $munLayout;
        }
        
        if (config('incidents.enabled')) {
            $layouts[] = Layout::split([
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
            ])->ratio('70/30');
        }
        return $layouts;
    }
}
