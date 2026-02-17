<?php

namespace App\Orchid\Screens\ConfigSystem;

use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Illuminate\Http\Request;
use App\Models\Configuration;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Facades\DB;
use Orchid\Support\Facades\Layout;
use Illuminate\Console\Application;
use Symfony\Component\Process\Process;
use App\Actions\Import\DivipoleFileAction;
use App\Actions\Import\DevicesFileAction;
use App\Actions\Import\DepartmentFileAction;
use App\Actions\Import\MunicipalityFileAction;

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
                ->type(Color::PRIMARY())
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
                        ->type(Color::DEFAULT())
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
                        ->type(Color::DEFAULT())
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
                        ->type(Color::DEFAULT())
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
                        ->type(Color::DEFAULT())
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
     * Save the divipole file
     *
     * @return \Illuminate\Http\RedirectResponse
     */

    public function saveConfig(Request $request,$fill): void
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
        $data=$request->all();
        $data['userId']=$request->user()->id;
        DepartmentFileAction::dispatch($data);
        Toast::info(__('Configuration saved'));

    }
    public function clearDepartmentsTable()
    {
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
        $data=$request->all();
        $data['userId']=$request->user()->id;
        MunicipalityFileAction::dispatch($data);
        Toast::info(__('Configuration saved'));
    }
    public function clearMunicipalitiesTable(): void
    {
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
        $data=$request->all();
        $data['route']=$request->route()->getName();
        $data['file']=$request->file('file');
        $data['userId']=$request->user()->id;
        DivipoleFileAction::dispatch($data);
        Toast::info(__('Configuration saved'));

    }
    public function clearDivipoleTable(): void
    {
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
        $data=$request->all();
        $data['route']=$request->route()->getName();
        $data['file']=$request->file('file');
        $data['userId']=$request->user()->id;
        DevicesFileAction::dispatch($data);
        Toast::info(__('Configuration saved'));

    }
    public function clearDeviceTable(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('devices')->truncate();
        DB::statement('ALTER TABLE devices AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    public function clearIncidentsTable(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('incidents')->truncate();
        DB::statement('ALTER TABLE incidents AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    public function clearDeviceChecksTable(): void
    {
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
}
