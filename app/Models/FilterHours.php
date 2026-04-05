<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Orchid\Screen\AsSource;

class FilterHours extends Model
{
    use HasFactory;
    use AsSource;

    protected static function booted(): void
    {
        static::saved(static function (): void {
            self::bumpFilterOptionsVersion();
        });

        static::deleted(static function (): void {
            self::bumpFilterOptionsVersion();
        });
    }

    private static function bumpFilterOptionsVersion(): void
    {
        $currentVersion = (int) Cache::get('filter_options_version', 1);
        $cacheKey = 'filter_hours:v' . $currentVersion;
        Cache::forget($cacheKey);
        Cache::forever('filter_options_version', $currentVersion + 1);
    }

    protected $fillable = [
        'hour',
    ];

    //cast the hour attribute to a Carbon instance
    protected $casts = [
        'hour' => 'datetime:H:i',
    ];
}
