<?php

namespace App\Filters\Types;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Orchid\Filters\BaseHttpEloquentFilter;

class WhereTimeIn extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $query = $this->getHttpValue();
        $values = is_array($query)
            ? $query
            : Str::of((string) $query)->explode(',');

        $times = collect($values)
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values();

        if ($times->isEmpty()) {
            return $builder;
        }

        $column = $this->column;

        return $builder->where(function (Builder $queryBuilder) use ($times, $column): void {
            $times->each(static function (string $value) use ($queryBuilder, $column): void {
                $queryBuilder->orWhere($column, '=', $value);
            });
        });
    }
}
