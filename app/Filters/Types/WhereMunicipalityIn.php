<?php

namespace App\Filters\Types;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Orchid\Filters\BaseHttpEloquentFilter;

class WhereMunicipalityIn extends BaseHttpEloquentFilter
{
    /**
     * Apply the filter to the Eloquent query builder.
     */
    public function run(Builder $builder): Builder
    {
        $query = $this->getHttpValue();
        $values = is_array($query)
            ? $query
            : Str::of($query)->explode(',');
        $values = collect($values)
            ->map(fn($v) => trim($v))
            ->filter()
            ->values();

        if ($values->isEmpty()) {
            return $builder;
        }
        return $builder->whereHas('divipole.municipality', function ($q) use ($values) {
            $q->whereIn('name', $values);
        });
    }
}
