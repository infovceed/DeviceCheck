<?php

namespace App\Filters\Types;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Orchid\Filters\BaseHttpEloquentFilter;

class WhereDateIn extends BaseHttpEloquentFilter
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
        return $builder->where(function ($q) use ($values) {
            $values->each(fn($value) =>
                $q->orWhereDate($this->column, $value)
            );
        });
    }
}
