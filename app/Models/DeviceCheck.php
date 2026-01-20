<?php

namespace App\Models;

use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DeviceCheck extends Model
{
    use HasFactory,Filterable,AsSource;

    /**
     * Automatically set the "time" column to the HH:MM:SS of creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (DeviceCheck $model) {
            $model->time = now()->format('H:i:s');
        });
    }

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'distance',
        'type',
        'time',
    ];

    /**
     * Attributes to append when model is converted to array/json.
     * These are virtual attributes provided by accessors below.
     *
     * @var array
     */
    protected $appends = [
        'report_date_formatted',
        'arrival_time_formatted',
        'report_time_formatted',
        'time_difference_minutes',
    ];

    /**
     * Cast 'created_at' to Carbon (Eloquent already does this) and
     * optionally cast 'distance' to float for calculations.
     */
    protected $casts = [
        'distance' => 'float',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function divipole()
    {
        return $this->belongsTo(Divipole::class);
    }

    public static function saveReport(array $deviceData):Device
    {
        $device = Device::query()
                    ->where('imei', $deviceData['imei'])
                ->first(['id']);
        if (!$device) {
            throw new \Exception("Device not found", 1);
            
        }
        self::create([
            'device_id' => $device->id,
            'latitude'  => $deviceData['lat'],
            'longitude' => $deviceData['lon'],
            'type'      => $deviceData['tipo'],
        ]);
        return $device;
    }

    /**
     * Formatted report date (localized, capitalized) e.g. "Martes 19 de noviembre de 2025".
     */
    public function getReportDateFormattedAttribute(): ?string
    {
        if (! $this->created_at) {
            return null;
        }

        $formatted = Carbon::parse($this->created_at)
            ->locale(app()->getLocale() ?: 'es')
            ->isoFormat('dddd D [de] MMMM [de] YYYY');

        $first = mb_substr($formatted, 0, 1, 'UTF-8');
        $rest  = mb_substr($formatted, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8') . $rest;
    }

    /**
     * Arrival time formatted as 12-hour string with am/pm (e.g. 07:17:05 am).
     */
    public function getArrivalTimeFormattedAttribute(): ?string
    {
        if (empty($this->time)) {
            return null;
        }

        // time stored as H:i:s — parse with Carbon and format
        try {
            return Carbon::createFromFormat('H:i:s', $this->time)->format('h:i:s a');
        } catch (\Exception $e) {
            return $this->time;
        }
    }

    /**
     * Report time (from related device) formatted as 12-hour string.
     */
    public function getReportTimeFormattedAttribute(): ?string
    {
        if (! $this->relationLoaded('device') || empty($this->device->report_time)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i:s', $this->device->report_time)->format('h:i:s a');
        } catch (\Exception $e) {
            return $this->device->report_time;
        }
    }

    /**
     * Time difference in minutes between device.report_time and this->time,
     * returned as string with two decimals (e.g. "2.50").
     */
    public function getTimeDifferenceMinutesAttribute(): ?string
    {
        if (empty($this->time) || ! $this->relationLoaded('device') || empty($this->device->report_time)) {
            return null;
        }

        try {
            $report = Carbon::createFromFormat('H:i:s', $this->device->report_time);
            $check  = Carbon::createFromFormat('H:i:s', $this->time);
            $seconds = abs($report->diffInSeconds($check));
            $minutes = $seconds / 60;
            return number_format($minutes, 2);
        } catch (\Exception $e) {
            return null;
        }
    }
}
