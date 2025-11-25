<?php

namespace App\Filters\Types;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\BaseHttpEloquentFilter;

/**
 * Filter distances relative to 500 meters.
 *
 * Acceptable HTTP values (via filter[distance]):
 * - "gt" | "greater" | "1" => distance > 500 m (stored as > 0.5 km)
 * - "le" | "lte" | "less" | "0" => distance <= 500 m (<= 0.5 km)
 * - numeric (meters) => compares values greater than given meters
 */
class WhereDistance500 extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $value = $this->getHttpValue();
        // If an array is passed, take first element
        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null || $value === '') {
            return $builder;
        }

        $value = (string) $value;
        $lower = strtolower($value);

        if (in_array($lower, ['gt', 'greater', '1'], true)) {
            $builder = $builder->where('distance', '>', 0.5);
        } elseif (in_array($lower, ['le', 'lte', 'less', '0'], true)) {
            $builder = $builder->where('distance', '<=', 0.5);
        } elseif (is_numeric($value)) {
            $km = floatval($value) / 1000.0;
            $builder = $builder->where('distance', '>', $km);
        }

        return $builder;
    }
}
