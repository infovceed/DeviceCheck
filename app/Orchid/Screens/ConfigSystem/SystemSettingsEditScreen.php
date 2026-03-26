<?php

namespace App\Orchid\Screens\ConfigSystem;

use App\Actions\Import\DepartmentFileAction;
use App\Actions\Import\DevicesFileAction;
use App\Actions\Import\DivipoleFileAction;
use App\Actions\Import\MunicipalityFileAction;
use App\Models\Configuration;
use App\Models\WorkShift;
use Illuminate\Console\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Symfony\Component\Process\Process;

class SystemSettingsEditScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $configuration = Configuration::first();
        return [
            'department_attachment'   => $configuration?->attachment()->where('attachments.id', $configuration->department_file)->get(),
            'municipality_attachment' => $configuration?->attachment()->where('attachments.id', $configuration->municipality_file)->get(),
            'divipole_attachment'     => $configuration?->attachment()->where('attachments.id', $configuration->divipole_file)->get(),
            'Devices_attachment'      => $configuration?->attachment()->where('attachments.id', $configuration->Devices_file)->get(),
            'current_work_shift_id'   => $configuration?->current_work_shift_id,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Settings');
    }


    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Clear cache'))
                ->icon('bs.arrow-counterclockwise')
                ->method('clearCache'),
            Button::make(__('Clear database'))
                ->type(Color::PRIMARY)
                ->icon('bs.database')
                ->confirm(__('This action will truncate incidents, Devices, divipoles, departments and municipalities tables. Do you want to proceed?'))
                ->method('clearDatabase'),
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
            Layout::block(
                Layout::rows([
                    Select::make('current_work_shift_id')
                        ->title(__('Current work shift'))
                        ->options(WorkShift::query()->pluck('name', 'id'))
                        ->empty(__('Select a work shift'))
                        ->required(),
                ])
            )->title(__('Work shift configuration'))
              ->description(__('Select the current work shift. This will be used to filter device checks on the dashboard and reports.'))
              ->commands(
                  Button::make(__('Save'))
                      ->type(Color::DEFAULT)
                      ->icon('check')
                      ->method('saveWorkShiftConfig')
              ),
            Layout::block(Layout::rows([
                Upload::make('department_attachment')
                    ->acceptedFiles('.xlsx')
                    ->placeholder(__('Upload your file'))
                    ->title(__('Drop your departments file here'))
                    ->help(__('File format for departments .xlsx'))
                    ->maxFiles(1),
                Link::make('Download Template')
                    ->icon('download')
                    ->href('/templates/department.xlsx')
            ]))
                ->title(__('Upload departments'))
                ->description(__('Settings for the departments upload'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::DEFAULT)
                        ->icon('check')
                        ->method('saveDepartments')
                ),
            Layout::block(Layout::rows([
                Upload::make('municipality_attachment')
                    ->acceptedFiles('.xlsx')
                    ->placeholder(__('Upload your file'))
                    ->title(__('Drop your municipalities file here'))
                    ->help(__('File format for municipalities .xlsx'))
                    ->maxFiles(1),
                Link::make('Download Template')
                    ->icon('download')
                    ->href('/templates/municipality.xlsx')
            ]))
                ->title(__('Upload municipalities'))
                ->description(__('Settings for the municipalities upload'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::DEFAULT)
                        ->icon('check')
                        ->method('saveMunicipalities')
                ),
            Layout::block(Layout::rows([
                Upload::make('divipole_attachment')
                    ->acceptedFiles('.xlsx')
                    ->placeholder(__('Upload your file'))
                    ->help(__('File format for divipole .xlsx'))
                    ->maxFiles(1)
                    ->horizontal(),
                Link::make('Download Template')
                    ->icon('download')
                    ->href('/templates/divipole.xlsx')
            ]))
                ->title(__('Upload divipole'))
                ->description(__('If you upload this file, the divipoles table will be reset, and the new data will be imported. You must make sure that the configurations for departments, municipalities, and corporations have been previously uploaded.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::DEFAULT)
                        ->icon('check')
                        ->method('saveDivipole')
                ),
            Layout::block(Layout::rows([
                Upload::make('Devices_attachment')
                    ->acceptedFiles('.xlsx')
                    ->placeholder(__('Upload your file'))
                    ->help(__('File format for Devices .xlsx'))
                    ->maxFiles(1)
                    ->horizontal(),
                Link::make('Download Template')
                    ->icon('download')
                    ->href('/templates/Devices.xlsx')
            ]))
                ->title(__('Upload Devices'))
                ->description(__('If you upload this file, the Devices table will be reset, and the new data will be imported. You must make sure that the configurations for departments, municipalities, and corporations have been previously uploaded.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::DEFAULT)
                        ->icon('check')
                        ->method('saveDevices')
                ),
        ];
    }
    /**
     * Clear system caches
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache()
    {
        Process::fromShellCommandline(
            Application::formatCommandString('optimize:clear') . ' > /dev/null 2>&1 &',
            base_path(),
            null,
            null,
            null
        )->run();
        Toast::info(__('Cache cleared'));
        return redirect()->back();
    }

    /**
     * Save the configuration and sync uploaded attachments.
     */

    public function saveConfig(Request $request, $fill): void
    {
        DB::beginTransaction();
            $configuration = Configuration::first();
        if (!$configuration) {
            $configuration = new Configuration();
        }
            $configuration->fill($fill);
            $configuration->save();
            $attachments = $this->getAttachments($request);
            $configuration->attachment()->sync($attachments);
        DB::commit();
        Log::channel('config')->info('Configuration saved with data: ' . json_encode($fill));
    }

    public function saveDepartments(Request $request): void
    {
        $request->validate([
            'department_attachment' => 'required|array|min:1',
        ]);
        $this->saveConfig($request, [
            'department_file' => $request->input('department_attachment.0'),
        ]);
        $this->clearDepartmentsTable();
        $data = $request->all();
        $data['userId'] = $request->user()->id;
        DepartmentFileAction::dispatch($data);
        Log::channel('config')->info('Department file action dispatched by user ID: ' . auth()->id());
        Toast::info(__('Configuration saved'));
    }
    public function clearDepartmentsTable()
    {
        Log::channel('config')->info('Clearing departments table by user ID: ' . auth()->id());
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('departments')->truncate();
        DB::statement('ALTER TABLE departments AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function saveMunicipalities(Request $request): void
    {
        $request->validate([
            'municipality_attachment' => 'required|array|min:1',
        ]);
        $this->saveConfig($request, [
            'municipality_file' => $request->input('municipality_attachment.0'),
        ]);
        $this->clearMunicipalitiesTable();
        $data = $request->all();
        $data['userId'] = $request->user()->id;
        MunicipalityFileAction::dispatch($data);
        Log::channel('config')->info('Municipality file action dispatched by user ID: ' . auth()->id());
        Toast::info(__('Configuration saved'));
    }
    public function clearMunicipalitiesTable(): void
    {
        Log::channel('config')->info('Clearing municipalities table by user ID: ' . auth()->id());
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('municipalities')->truncate();
        DB::statement('ALTER TABLE municipalities AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function saveDivipole(Request $request): void
    {
        $request->validate([
            'department_attachment'   => 'required|array|min:1',
            'municipality_attachment' => 'required|array|min:1',
            'divipole_attachment'     => 'required|array|min:1',
        ]);
        $this->saveConfig($request, [
            'divipole_file'     => $request->input('divipole_attachment.0'),
            'department_file'   => $request->input('department_attachment.0'),
            'municipality_file' => $request->input('municipality_attachment.0'),
        ]) ;
        $this->clearDivipoleTable();
        $data = $request->all();
        $data['route'] = $request->route()->getName();
        $data['file'] = $request->file('file');
        $data['userId'] = $request->user()->id;
        DivipoleFileAction::dispatch($data);
        Log::channel('config')->info('Divipole file action dispatched by user ID: ' . auth()->id());
        Toast::info(__('Configuration saved'));
    }
    public function clearDivipoleTable(): void
    {
        Log::channel('config')->info('Clearing divipoles table by user ID: ' . auth()->id());
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('divipoles')->truncate();
        DB::statement('ALTER TABLE divipoles AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function saveDevices(Request $request): void
    {
        $request->validate([
            'department_attachment'   => 'required|array|min:1',
            'municipality_attachment' => 'required|array|min:1',
            'divipole_attachment'     => 'required|array|min:1',
            'Devices_attachment'     => 'required|array|min:1',
        ]);
        $this->saveConfig($request, [
            'divipole_file'     => $request->input('divipole_attachment.0'),
            'department_file'   => $request->input('department_attachment.0'),
            'municipality_file' => $request->input('municipality_attachment.0'),
            'Devices_file'     => $request->input('Devices_attachment.0'),
        ]) ;
        $this->clearDeviceTable();
        $data = $request->all();
        $data['route'] = $request->route()->getName();
        $data['file'] = $request->file('file');
        $data['userId'] = $request->user()->id;
        DevicesFileAction::dispatch($data);
        Log::channel('config')->info('Devices file action dispatched by user ID: ' . auth()->id());
        Toast::info(__('Configuration saved'));
    }
    public function clearDeviceTable(): void
    {
        Log::channel('config')->info('Clearing devices table by user ID: ' . auth()->id());
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('devices')->truncate();
        DB::statement('ALTER TABLE devices AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    public function clearIncidentsTable(): void
    {
        Log::channel('config')->info('Clearing incidents table by user ID: ' . auth()->id());
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('incidents')->truncate();
        DB::statement('ALTER TABLE incidents AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    public function clearDeviceChecksTable(): void
    {
        Log::channel('config')->info('Clearing device_checks table by user ID: ' . auth()->id());
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('device_checks')->truncate();
        DB::statement('ALTER TABLE device_checks AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    /**
     * Clear the database tables related to the import
     *
     * @return \Illuminate\Http\RedirectResponse
     */

    public function clearDatabase()
    {

        $this->clearIncidentsTable();
        $this->clearDeviceChecksTable();
        $this->clearDeviceTable();
        $this->clearDivipoleTable();
        $this->clearDepartmentsTable();
        $this->clearMunicipalitiesTable();
        Log::channel('config')->info('Database cleared by user ID: ' . auth()->id());
        return redirect()->back();
    }
    public function getAttachments($request): array
    {
        return array_merge(
            $request->input('attachment', []),
            $request->input('department_attachment', []),
            $request->input('municipality_attachment', []),
            $request->input('divipole_attachment', []),
            $request->input('Devices_attachment', []),
        );
    }

    public function saveWorkShiftConfig(Request $request): void
    {
        $request->validate([
            'current_work_shift_id' => 'required|exists:work_shifts,id',
        ]);
        Log::channel('config')->info('Saving work shift configuration with work shift ID: ' . $request->input('current_work_shift_id') . ' by user ID: ' . auth()->id());
        $this->saveConfig($request, [
            'current_work_shift_id' => $request->input('current_work_shift_id'),
        ]);
        Toast::info(__('Configuration saved'));
    }
}
