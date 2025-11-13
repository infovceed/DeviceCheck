<?php

namespace App\Models;

use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Municipality extends Model
{
    use HasFactory,AsSource, Filterable;

    protected $fillable = [
        'id',
        'code',
        'name',
    ];


    //sort
    protected $allowedSorts = [
        'id',
        'code',
        'name',
        'created_at',
    ];

    //filters
    protected $allowedFilters = [
        'id'         => Where::class,
        'code'       => Like::class,
        'name'       => Like::class,
        'created_at' => Where::class,
    ];
    public function scopeMyMunicipality(Builder $query): Builder
    {
        return $query;
    }
}
