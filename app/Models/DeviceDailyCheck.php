<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereIn;
use Orchid\Screen\AsSource;

class DeviceDailyCheck extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $allowedFilters = [
        'department'    => WhereIn::class,
        'municipality'  => WhereIn::class,
        'position_name' => WhereIn::class,
        'check_day'     => WhereIn::class,
        'report_time_arrival'   => Where::class,
        'report_time_departure' => Where::class,
    ];
}
