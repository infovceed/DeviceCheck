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
            ? request('department', 'all')
            : $user->department_id;
        $incidents = Device::getIncidentsOpen();

        [$labels, $valuesTotal, $valuesReported] = Department::getChartData($departmentID);
        return [
            'departmentsChart' => [
                ['labels' => $labels, 'name' => 'Total',            'values' => $valuesTotal],
                ['labels' => $labels, 'name' => 'Total reportados', 'values' => $valuesReported],
            ],
            'stats' => [
                'totalRecords' => array_sum($valuesTotal),
                'totalReported' => array_sum($valuesReported),
                'percentage' => array_sum($valuesTotal) ? round((array_sum($valuesReported) / array_sum($valuesTotal)) * 100, 2) : 0,
            ],
            'departmentID' => $departmentID,
            'incidents' => $incidents,
        ];
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
                    'subtitle' =>__('Total Dispositivos'),
                    'icon' => 'bs.phone',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => "{$data['stats']['percentage']}%",
                    'subtitle' => 'Dispositivos Reportados',
                    'icon' => 'bs.phone-vibrate',
                ]),
                Layout::view('components.dashboard.stats',[
                    'title' => $data['stats']['totalReported'],
                    'subtitle' => 'Total Dispositivos Reportados',
                    'icon' => 'bs.phone-vibrate-fill',
                ]),
            ]),
        ];
        if($showAll) {
            $chartFilters = new ChartFiltersLayout();
            $layouts[] = $chartFilters;
        }
        $deptLayout = ChartsLayout::make('departmentsChart', 'Reporte por departamento')
            ->description('Comparativa de empaques reportados por departamento.');


        $layouts[] = $data['departmentID'] === 'all'
            ? $deptLayout
            : Layout::split([$deptLayout, $corpLayout])->ratio('30/70');

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
