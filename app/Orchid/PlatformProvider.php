<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        $hasAccessDashboard = auth()->user()->hasAccess('platform.systems.dashboard');
        $namePrincipal = $hasAccessDashboard ? __('Dashboard') : __('Home');
        $iconPrincipal = $hasAccessDashboard ? 'bs.speedometer' : 'bs.house-door';
        return [

            Menu::make($namePrincipal)
                ->icon($iconPrincipal)
                ->route('platform.main')
                ->title(__('System Management')),
            Menu::make(__('Devices'))
                ->icon('bs.phone-vibrate-fill')
                ->route('platform.systems.devices')
                ->permission('platform.systems.devices'),
            Menu::make(__('Device Checks'))
                ->icon('bs.geo-alt-fill')
                ->route('platform.systems.devices-check')
                ->permission('platform.systems.device-check'),
            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),
            Menu::make(__('Roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles')
                ->divider(),

            Menu::make(__('Settings'))
                ->icon('bs.gear')
                ->route('platform.settings')
                ->permission('platform.settings')
                ->title(__('System Settings')),
            Menu::make(__('Departments'))
                ->icon('bs.geo')
                ->route('platform.systems.departments')
                ->permission('platform.systems.departments'),
            Menu::make(__('Municipalities'))
                ->icon('bs.geo-alt')
                ->route('platform.systems.municipalities')
                ->permission('platform.systems.municipalities'),
            Menu::make(__('Divipoles'))
                ->icon('bs.globe-americas')
                ->route('platform.systems.divipoles')
                ->permission('platform.systems.divipoles')
                ->divider(),

        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users'))
                ->addPermission('platform.systems.report-download', __('Report Download'))
                ->addPermission('platform.systems.devices', __('Devices'))
                ->addPermission('platform.systems.device-check', __('Device Checks'))

                ->addPermission('platform.settings', __('Settings'))
                ->addPermission('platform.systems.departments', __('Departments'))
                ->addPermission('platform.systems.municipalities', __('Municipalities'))
                ->addPermission('platform.systems.divipoles', __('Divipoles')),

            ItemPermission::group(__('Divipole'))
                ->addPermission('platform.systems.divipoles.edit', __('Edit Divipoles')),
            ItemPermission::group(__('Devices'))
                ->addPermission('platform.systems.devices.edit', __('Edit Devices'))
                ->addPermission('platform.systems.devices.show-all', __('Show all Devices')),
            ItemPermission::group(__('Dashboard'))
                ->addPermission('platform.systems.dashboard', __('Dashboard'))
                ->addPermission('platform.systems.dashboard.realtime', __('Real-time Data'))
                ->addPermission('platform.systems.dashboard.show-all', __('Show all')),
            ItemPermission::group(__('Users'))
                ->addPermission('platform.systems.user.create', __('Create'))
                ->addPermission('platform.systems.user.edit', __('Edit'))
                ->addPermission('platform.systems.user.show-all', __('Show all users')),
                //->addPermission('platform.systems.user.delete', __('Delete')),
            ...(config('incidents.enabled') ? [
                ItemPermission::group(__('Incidents'))
                    ->addPermission('platform.systems.incidents.receive-notification', __('Receive Notification'))
                    ->addPermission('platform.systems.incidents.report', __('Report Incident'))
            ] : [])

        ];
    }
}
