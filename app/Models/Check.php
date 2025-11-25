<?php

namespace App\Models;

use App\Models\Device;
use App\Models\Divipole;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereIn;
use App\Filters\Types\WhereDateIn;
use App\Filters\Types\WhereDistance500;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Check extends Model
{
    use HasFactory,Filterable,AsSource;

    

    /**
     * Virtual attributes appended to array / JSON.
     *
     * @var array
     */
    protected $appends = [
        'time_difference_minutes',
    ];

    protected $casts = [
        'time'        => 'datetime:H:i:s a',
        'report_time' => 'datetime:H:i:s a',
        'distance'    => 'float',
    ];

    protected $allowedFilters = [
        'department'    => WhereIn::class,
        'municipality'  => WhereIn::class,
        'position_name' => WhereIn::class,
        'tel'           => WhereIn::class,
        'device_key'    => WhereIn::class,
        'type'          => Like::class,
        'code'          => Where::class,
        'created_at'    => WhereDateIn::class,
        'distance'      => WhereDistance500::class,
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function divipole()
    {
        return $this->belongsTo(Divipole::class);
    }


    public function getTimeDifferenceMinutesAttribute(): ?string
    {
        if (empty($this->time) || empty($this->report_time)) {
            return null;
        }

        try {
            $report = Carbon::parse($this->report_time);
            $check  = Carbon::parse($this->time);
            $seconds = abs($report->diffInSeconds($check));
            $minutes = $seconds / 60;
            return number_format($minutes, 2);
        } catch (\Exception $e) {
            return null;
        }
    }
}
