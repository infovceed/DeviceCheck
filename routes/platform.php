<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tabuna\Breadcrumbs\Trail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use App\Orchid\Screens\Device\DeviceListScreen;
use App\Orchid\Screens\Incident\IncidentListScreen;
use App\Orchid\Screens\Check\CheckListScreen;
use App\Orchid\Screens\Department\DepartmentListScreen;
use App\Orchid\Screens\Municipality\MunicipalityListScreen;
use App\Orchid\Screens\ConfigSystem\SystemSettingsEditScreen;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Main
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));


Route::screen('settings', SystemSettingsEditScreen::class)
    ->name('platform.settings')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Settings'), route('platform.settings')));


// Platform > System > departaments
Route::group(['prefix' => 'departaments'], function () {
    // Platform > System > departaments > Department
    Route::screen('/', DepartmentListScreen::class)
        ->name('platform.systems.departments')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('platform.index')
            ->push(__('Departments'), route('platform.systems.departments')));
});
// Platform > System > municipalities
Route::group(['prefix' => 'municipalities'], function () {
    // Platform > System > municipalities > Municipality
    Route::screen('/', MunicipalityListScreen::class)
        ->name('platform.systems.municipalities')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('platform.index')
            ->push(__('Municipalities'), route('platform.systems.municipalities')));
});


// Platform > System > Divipoles
Route::group(['prefix' => 'divipoles'], function () {
    // Platform > System > divipoles > Divipole
    Route::screen('/', \App\Orchid\Screens\Divipole\DivipoleListScreen::class)
        ->name('platform.systems.divipoles')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('platform.index')
            ->push(__('Divipoles'), route('platform.systems.divipoles')));
});

// Platform > System > Devices
Route::group(['prefix' => 'devices'], function () {
    // Platform > System > Devices > Device
    Route::screen('/', DeviceListScreen::class)
        ->name('platform.systems.devices')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('platform.index')
            ->push(__('Devices'), route('platform.systems.devices')));
});

// Platform > System > Device Checks
Route::group(['prefix' => 'device-checks'], function () {
    // Platform > System > Device Checks > Device Check
    Route::screen('/', CheckListScreen::class)
        ->name('platform.systems.devices-check')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('platform.index')
            ->push(__('Device Checks'), route('platform.systems.devices-check')));
});

if (config('incidents.enabled')) {
    Route::screen('devices/{device}/incidents', IncidentListScreen::class)
        ->name('platform.systems.incidents')
        ->breadcrumbs(fn (Trail $trail, $device) => $trail
            ->parent('platform.systems.devices', $device)
            ->push($device, route('platform.systems.incidents', ['device' => $device])));
}

// Override logout to redirect to login page
Route::post('logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('platform.login');
})->name('platform.logout');


