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
        $percentageIn     = $totalDevicesSum ? round(($totalReportedIn / $totalDevicesSum) * 100, 2) : 0;
        $percentageOut    = $totalDevicesSum ? round(($totalReportedOut / $totalDevicesSum) * 100, 2) : 0;

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
            ['labels' => [__('Pending'), __('Reported')], 'name' => __('Arrival'), 'values' => [$unreportedIn, $reportedIn]],
        ];

        $data['checkoutChart'] = [
            ['labels' => [__('Pending'), __('Reported')], 'name' => __('Check-out'), 'values' => [$unreportedOut, $reportedOut]],
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
        return [];
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
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => "{$data['stats']['percentageIn']}%",
                    'subtitle' => __('Devices Reported (Arrival)'),
                    'icon' => 'bs.phone-vibrate',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => "{$data['stats']['percentageOut']}%",
                    'subtitle' => __('Devices Reported (Departure)'),
                    'icon' => 'bs.phone-vibrate',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => $data['stats']['totalReportedIn'],
                    'subtitle' => __('Total Reported (Arrival)'),
                    'icon' => 'bs.phone-vibrate-fill',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => $data['stats']['totalReportedOut'],
                    'subtitle' => __('Total Reported (Departure)'),
                    'icon' => 'bs.phone-vibrate-fill',
                ]),
            ]),
        ];
        if($showAll) {
            // Activa auto-aplicación de filtros solo en esta vista
            $layouts[] = Layout::view('partials.auto-filter-enable');
            $chartFilters = new ChartFiltersLayout();
            $layouts[] = $chartFilters;
        }
        
        $deptLayout = ChartsLayout::make('departmentsChart', 'Reporte por departamento')
            ->description('Comparativa de dispositivos reportados por departamento.');
        $layouts[] = $deptLayout;
        $pieIn = PieChartLayout::make('checkinChart', __('Arrival'))
            ->description(__('Total devices vs reported (Arrival)'));
        $pieOut = PieChartLayout::make('checkoutChart', __('Departure'))
            ->description(__('Total devices vs reported (Departure)'));

        $layouts[] = Layout::split([
            $pieIn,
            $pieOut,
        ])->ratio('50/50');

        if (isset($data['municipalitiesChart'])) {
            $munLayout = ChartsLayout::make('municipalitiesChart', 'Reporte por municipio')
                ->description('Totales, check-in y check-out por municipio.');
            $layouts[] = $munLayout;
        }
        
         $layouts[] = Layout::split([
            Layout::table('incidents', [
                TD::make('department_name', __('Department')),
                TD::make('Device_id', __('Write'))
                    ->render(fn($inc) => Link::make('')
                        ->route('platform.systems.incidents', ['device' => $inc->Device_id])
                        ->icon('bs.pencil')
                    ),
            ])->title(__('Incidents')),
            Layout::view('components.dashboard.empty'),
        ])->ratio('30/70');
        return $layouts;
    }
}
