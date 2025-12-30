<?php

namespace App\Orchid\Screens\Incident;

use App\Models\Device;
use App\Models\Incident;
use App\Models\User;
use Orchid\Screen\Screen;
use Illuminate\Http\Request;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\Upload;
use App\Notifications\NewMessage;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Facades\DB;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\ModalToggle;

class IncidentListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        if (!config('incidents.enabled')) {
            abort(404);
        }
        $incidents = Incident::where('device_id', request()->route('device'))->get();
        $isOpen = Device::find(request()->route('device'))->status_incidents === 1;
        return [
            'incidents' => $incidents ?: [] ,
            'isOpen' => $isOpen,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        if (!config('incidents.enabled')) {
            abort(404);
        }
        $device = Device::find(request()->route('device'));
        return __('Incidents for :code', ['code' => $device->divipole->code]);
    }
    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        if (!config('incidents.enabled')) {
            return [];
        }
        return [
            ModalToggle::make(__('Add'))
                ->modal('messageModal')
                ->method('create')
                ->icon('pencil')
                ->canSee($this->query()['isOpen']),
            Button::make('Open Incident')
                ->icon('check-circle')
                ->canSee(!$this->query()['isOpen'])
                ->method('openIncident')
                ->confirm(__('You will open this incident.')),
            Button::make('Close Incident')
                ->icon('close')
                ->canSee($this->query()['isOpen'])
                ->method('closeIncident')
                ->confirm(__('You will close this incident.')),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        if (!config('incidents.enabled')) {
            return [];
        }
        $data     = $this->query();
        $messages = $data['incidents'] ?? [];
        return [
            Layout::modal('messageModal', Layout::rows([
                Upload::make('device.attachments')
                ->maxFiles(5),
                Quill::make('device.message')
                    ->toolbar(['text', 'color', 'header', 'list', 'format']),
            ]))->title(__('New Message'))
               ->applyButton(__('Save'))
               ->withoutCloseButton(),
            Layout::view('components.device.description', [
                'device' => Device::find(request()->route('device')),
            ]),
            Layout::view('components.messages.layout', [
                'messages' => $messages,
            ])
        ];
    }

    public function create(Request $request)
    {
        DB::beginTransaction();
            $device = Device::find(request()->route('device'));
            $userId = auth()->user()->id;
            $messageDevice = new Incident();
            $messageDevice->message = $request->input('device.message');
            $messageDevice->user_id = $userId;
            $messageDevice->device_id = $device->id;
            $messageDevice->save();
            $messageDevice->attachment()->sync($request->input('device.attachments', []));
            $myUser = auth()->user();
            $superUser=User::where('id',1)->first();
            $superUser->notify(new NewMessage($device));

            $users = User::where('department_id', $myUser->department_id)
                ->where('id', '!=', $myUser->id)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'COORDINADOR');
                })
                ->get();
            foreach ($users as $user) {
                if(!$user->hasAccess('platform.systems.incidents.receive-notification')) {
                    continue;
                }
                $user->notify(new NewMessage($device));
            }
        DB::commit();
    }

    public function openIncident(Request $request)
    {
        if (!config('incidents.enabled')) {
            abort(404);
        }
        $Device = Device::find(request()->route('device'));
        $Device->status_incidents = 1;
        $Device->save();
        Toast::info(__('Incident opened successfully.'));
    }
    public function closeIncident(Request $request)
    {
        if (!config('incidents.enabled')) {
            abort(404);
        }
        $Device = Device::find(request()->route('device'));
        $Device->status_incidents = 2;
        $Device->save();
        Toast::info(__('Incident closed successfully.'));
    }
}
