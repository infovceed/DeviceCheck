<?php

namespace App\Models;

use App\Filters\Types\WhereTimeIn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
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
        'report_time_arrival'   => WhereTimeIn::class,
        'report_time_departure' => WhereTimeIn::class,
    ];
}
