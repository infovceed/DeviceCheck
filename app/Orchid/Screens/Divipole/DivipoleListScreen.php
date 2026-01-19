<?php

namespace App\Orchid\Screens\Divipole;

use App\Models\User;
use Orchid\Screen\TD;
use App\Models\Divipole;
use Orchid\Screen\Screen;
use App\Models\Department;
use App\Models\Municipality;
use Illuminate\Http\Request;
use App\Traits\ComponentsTrait;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Fields\Relation;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\ModalToggle;
use App\Orchid\Filters\TerritoryFilter;

class DivipoleListScreen extends Screen
{
    use ComponentsTrait;
    /**
     * Base permission required to access this screen.
     */
    public $permission = 'platform.systems.divipoles';
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {

        $divipoles = Divipole::query()
                ->filters([TerritoryFilter::class])
                ->defaultSort('id', 'asc')
                ->with(['department', 'municipality', 'users'])
                ->paginate();

        return [
            'divipoles' => $divipoles,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Divipole';
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
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $canEdit = auth()->user()?->hasAccess('platform.systems.divipoles.edit');

        $columns = [
            TD::make('id', 'ID')
                ->sort()
                ->align(TD::ALIGN_CENTER)
                ->render(function (Divipole $divipole) use ($canEdit) {
                    if ($canEdit) {
                        return ModalToggle::make((string) $divipole->id)
                            ->modal('editDivipoleModal')
                            ->method('saveDivipole')
                            ->asyncParameters(['divipole' => $divipole->id]);
                    }
                    return (string) $divipole->id;
                }),
            TD::make('code', __('Code'))
                ->sort()
                ->align(TD::ALIGN_CENTER)
                ->filter(TD::FILTER_TEXT),
            TD::make('department.code', __('Department Code'))
                ->sort()
                ->align(TD::ALIGN_CENTER)
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Divipole $divipole) => $divipole->department->code),
            TD::make('department.name', __('Department'))
                ->sort()
                ->filter(TD::FILTER_TEXT),
            TD::make('municipality.code', __('Municipality Code'))
                ->sort()
                ->align(TD::ALIGN_CENTER)
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Divipole $divipole) => $divipole->municipality->code),
            TD::make('municipality.name', __('Municipality'))
                ->sort()
                ->filter(TD::FILTER_TEXT),
            TD::make('position_name', __('Position'))
                ->sort()
                ->filter(TD::FILTER_TEXT),
            TD::make('operative', __('Operative'))
                ->sort()
                ->filter(
                    Relation::make('operative')
                        ->fromModel(User::class, 'name')
                        ->applyScope('agents')
                        ->multiple()
                )
                ->filterValue(function ($value) {
                    if (is_array($value)) {
                        $names = User::findMany($value)->pluck('name')->toArray();
                        return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 10, '...'), $names));
                    }
                })
                ->render(fn(Divipole $divipole) => $divipole->users->pluck('name')->join(', ') ?: $this->badge([
                    'text'  => __('No operative assigned'),
                    'color' => 'warning',
                ])),
            TD::make('position_address', __('Address'))
                ->sort()
                ->filter(TD::FILTER_TEXT),
            TD::make('created_at', __('Created'))
                ->sort()
                ->render(fn (Divipole $divipole) => $divipole->created_at->format('Y-m-d H:i:s')),
        ];

        if ($canEdit) {
            $columns[] = TD::make('actions', __('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->render(function (Divipole $divipole) {
                    return ModalToggle::make(__('Edit'))
                        ->modal('editDivipoleModal')
                        ->method('saveDivipole')
                        ->asyncParameters(['divipole' => $divipole->id]);
                });
        }

        return [
            Layout::table('divipoles', $columns),
            Layout::modal('editDivipoleModal', Layout::rows([
                Input::make('divipole.id')
                    ->type('hidden'),
                Relation::make('divipole.department_id')
                    ->title(__('Department'))
                    ->fromModel(Department::class, 'name')
                    ->required(),
                Relation::make('divipole.municipality_id')
                    ->title(__('Municipality'))
                    ->fromModel(Municipality::class, 'name')
                    ->displayAppend('label')
                    ->required(),
                Input::make('divipole.code')
                    ->title(__('Code'))
                    ->required(),
                Input::make('divipole.position_name')
                    ->title(__('Position'))
                    ->required(),
                Input::make('divipole.position_address')
                    ->title(__('Address'))
                    ->required(),
                Relation::make('users')
                    ->title(__('Operative'))
                    ->fromModel(User::class, 'name')
                    ->applyScope('agents')
                    ->multiple(),
            ]))
                ->title(__('Edit Divipole'))
                ->applyButton(__('Save'))
                ->async('asyncGetDivipole'),
        ];
    }

    /**
     * Load divipole data into the modal asynchronously.
     */
    public function asyncGetDivipole(Divipole $divipole): array
    {
        return [
            'divipole' => $divipole,
            'users'    => $divipole->users->pluck('id')->toArray(),
        ];
    }

    /**
     * Save changes from modal.
     */
    public function saveDivipole(Request $request)
    {
        abort_unless($request->user()?->hasAccess('platform.systems.divipoles.edit'), 403);

        $id   = (int) $request->input('divipole.id');
        $dep  = (int) $request->input('divipole.department_id');
        $mun  = (int) $request->input('divipole.municipality_id');
        $code = (string) $request->input('divipole.code');
        $name = (string) $request->input('divipole.position_name');
        $addr = (string) $request->input('divipole.position_address');

        $usersInput = $request->input('users', []);
        if (!is_array($usersInput)) {
            $usersInput = [$usersInput];
        }
        $users = collect($usersInput)
            ->filter(fn($v) => is_numeric($v))
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $request->validate([
            'divipole.department_id'    => ['required', 'integer', 'exists:departments,id'],
            'divipole.municipality_id'  => ['required', 'integer', 'exists:municipalities,id'],
            'divipole.code'             => ['required', 'string', 'max:50'],
            'divipole.position_name'    => ['required', 'string', 'max:255'],
            'divipole.position_address' => ['required', 'string', 'max:500'],
        ]);
        
        $divipole                   = Divipole::findOrFail($id);
        $divipole->department_id    = $dep;
        $divipole->municipality_id  = $mun;
        $divipole->code             = $code;
        $divipole->position_name    = $name;
        $divipole->position_address = $addr;
        $divipole->updated_by       = $request->user()->id;
        $divipole->save();

        // Sync agents in pivot
        $existingIds = User::findMany($users)->pluck('id')->all();
        $divipole->users()->sync($existingIds);

        Toast::info(__('Divipole updated'));
    }
}
