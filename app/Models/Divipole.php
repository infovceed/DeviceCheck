<?php

namespace App\Models;

use App\Models\User;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use App\Filters\Types\WhereUserIn;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Divipole extends Model
{
    use HasFactory,Filterable,AsSource;

    protected $fillable = [
        'code',
        'department_id',
        'municipality_id',
        'position_name',
        'position_address',
        'is_backup',
        'created_by',
        'updated_by',


    ];

    //filters
    protected $allowedFilters = [
        'id'              => Where::class,
        'code'            => Where::class,
        'operative'       => WhereUserIn::class,
        'position_name'   => Like::class,
        'position_address'=> Like::class,
        'created_at'      => Where::class,
    ];


    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'divipoles_users', 'divipole_id', 'user_id');
    }

    public static function totalRecords(int|string $departmentId = 'all', float $minutes = 0.5): int
    {
        $cacheKey = "divipoles.total.{$departmentId}";
        return Cache::remember(
            $cacheKey,
            now()->addMinutes($minutes),
            fn() => self::query()
                ->when($departmentId !== 'all', fn($query) => $query->where('department_id', $departmentId))
                ->count()
        );
    }

    public function getLabelAttribute(): string
    {
        $department  = $this->department->name ?? '';
        $municipality = $this->municipality->name ?? '';
        $position     = $this->position_name ?? '';
        return "{$this->code} - {$department} - {$municipality} - {$position}";
    }
}
