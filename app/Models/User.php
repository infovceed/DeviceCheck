<?php

namespace App\Models;

use App\Models\Department;
use Orchid\Screen\AsSource;
use Orchid\Access\UserAccess;
use Orchid\Metrics\Chartable;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Access\UserInterface;
use App\Orchid\Presenters\UserPresenter;
use Illuminate\Notifications\Notifiable;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements UserInterface
{
    use AsSource, Chartable, Filterable, HasFactory, Notifiable, UserAccess;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'document',
        'department_id',
        'email',
        'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions'          => 'array',
        'email_verified_at'    => 'datetime',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
           'id'              => Where::class,
           'name'            => Like::class,
           'document'        => Like::class,
           'department_id'   => Where::class,
           'email'           => Like::class,
           'updated_at'      => WhereDateStartEnd::class,
           'created_at'      => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'document',
        'user.department.name',
        'email',
        'updated_at',
        'created_at',
    ];
    public static $rules = [
        'user.email' => [
            'nullable',
            'email',
            'unique:users,email',
        ],
        'user.name'     => ['required', 'alpha', 'min:3', 'max:255'],
        'user.document' => [
            'required',
            'unique:users,document',
            'numeric',
            'min:10',
            'max:9999999999',
        ],
        'user.department_id' => ['required', 'exists:departments,id'],
    ];
    /**
     * @return UserPresenter
     */
    public function presenter()
    {
        return new UserPresenter($this);
    }

    //relaciones
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
