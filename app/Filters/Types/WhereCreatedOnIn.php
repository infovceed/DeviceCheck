<?php

namespace App\Filters\Types;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Orchid\Filters\BaseHttpEloquentFilter;

class WhereCreatedOnIn extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $query = $this->getHttpValue();
        $values = is_array($query)
            ? $query
            : Str::of($query)->explode(',');

        $dates = collect($values)
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v))
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return $builder;
        }

        // Importante: se filtra por created_on (DATE indexable) en vez de DATE(created_at)
        // Esto asume que el view/tabla expone una columna created_on.
        return $builder->whereIn('created_on', $dates->all());
    }
}
