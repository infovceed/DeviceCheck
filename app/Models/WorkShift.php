<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class WorkShift extends Model
{
    use HasFactory;
    use Filterable;
    use AsSource;

    protected $fillable = [
        'name',
    ];

    protected $allowedFilters = [
        'id'   => Where::class,
        'name' => Like::class,
    ];
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
