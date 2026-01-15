<?php

declare(strict_types=1);
namespace App\Orchid\Layouts\User;

use App\Models\User;
use Orchid\Screen\TD;
use App\Traits\DateTrait;
use App\Models\Department;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Persona;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Components\Cells\DateTimeSplit;

class UserListLayout extends Table
{
    use DateTrait;
    /**
     * @var string
     */
    public $target = 'users';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                    ->sort()
                    ->align(TD::ALIGN_CENTER),
            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (User $user) => new Persona($user->presenter())),

            TD::make('email', __('Email'))
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (User $user) => ModalToggle::make($user->email)
                    ->modal('asyncEditUserModal')
                    ->modalTitle($user->presenter()->title())
                    ->method('saveUser')
                    ->asyncParameters([
                        'user' => $user->id,
                    ])),
            TD::make('department_id', __('Department'))
                ->filter(
                    Relation::make('department')
                    ->fromModel(Department::class, 'name')
                    ->empty(),
                )
                ->filterValue(function ($value) {
                    return Department::where('id', $value)->first()?->name;
                })
                ->render(function (User $user){
                    $department =$user->department;
                    return $department?->name ?? __('N/A');
                }),
            TD::make('created_at', __('Created'))
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort()
                ->asComponent(DateTimeSplit::class, [
                    'value' =>fn($created_at)=>$this->setTimezone($created_at, config('app.timezone')),
                ]),

            TD::make('updated_at', __('Last edit'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->asComponent(DateTimeSplit::class, [
                    'value' =>fn($updated_at)=>$this->setTimezone($updated_at, config('app.timezone')),
                ]),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (User $user) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([

                        Link::make(__('Edit'))
                            ->route('platform.systems.users.edit', $user->id)
                            ->icon('bs.pencil'),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                            ->method('remove', [
                                'id' => $user->id,
                            ]),
                    ])),
        ];
    }
}
