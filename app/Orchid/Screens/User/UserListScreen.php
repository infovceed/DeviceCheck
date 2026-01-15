<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use Orchid\Screen\Screen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use App\Models\User;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Facades\Layout;
use App\Actions\Import\UserFileAction;
use Orchid\Screen\Actions\ModalToggle;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserListLayout;
use App\Http\Requests\User\ImportFileRequest;
use App\Orchid\Layouts\User\UserFiltersLayout;

class UserListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'users' => User::with(['roles', 'department'])
                ->filters(UserFiltersLayout::class)
                ->when(!auth()->user()->hasAccess("platform.systems.user.show-all"),
                    function($q){
                        $q->where('department_id', auth()->user()->department_id);
                    }
                )
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'User Management';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'A comprehensive list of all registered users, including their profiles and privileges.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.systems.users',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('platform.systems.users.create'),
            ModalToggle::make(__('Bulk upload'))
                ->modal('uploadModal')
                ->icon('cloud-upload')
                ->method('uploadTemplate')
                ->modalTitle(__('Bulk upload')),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            UserFiltersLayout::class,
            UserListLayout::class,

            Layout::modal('asyncEditUserModal', UserEditLayout::class)
                ->async('asyncGetUser'),
            Layout::modal('uploadModal', Layout::rows([
                Input::make('file')
                    ->type('file')
                    ->title(__('File'))
                    ->required()
                    ->accept('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),

                link::make('Download Template')
                    ->icon('download')
                    ->href('/templates/user_template.xlsx'),
            ]))->applyButton(__('Upload')),
        ];
    }

    /**
     * @return array
     */
    public function asyncGetUser(User $user): iterable
    {
        return [
            'user' => $user,
        ];
    }

    public function saveUser(Request $request, User $user): void
    {
        $request->validate([
            'user.email' => [
                'required',
                Rule::unique(User::class, 'email')->ignore($user),
            ],
        ]);

        $user->fill($request->input('user'))->save();

        Toast::info(__('User was saved.'));
    }
    public function uploadTemplate(ImportFileRequest $request)
    {

        UserFileAction::run(
            $request
        );
        Toast::info(__('file uploaded'));
    }

    public function remove(Request $request): void
    {
        User::findOrFail($request->get('id'))->delete();

        Toast::info(__('User was removed'));
    }
}
