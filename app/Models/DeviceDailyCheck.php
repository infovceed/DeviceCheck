<?php

namespace App\Models;

use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\WhereIn;
use App\Filters\Types\WhereDateIn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceDailyCheck extends Model
{
    use HasFactory,AsSource, Filterable;
    protected $allowedFilters = [
        'department'    => WhereIn::class,
        'municipality'  => WhereIn::class,
        'check_day'     => WhereDateIn::class,
    ];
}
