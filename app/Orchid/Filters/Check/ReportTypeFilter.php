<?php

namespace App\Orchid\Filters\Check;

use App\Models\FilterHoursDepartment;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class ReportTypeFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Report Type';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [
            'filter.type',
        ];
    }

    /**
     * Apply to a given Eloquent query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {

        return $builder;
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        $filters = $this->request->input('filter', []);
        $options = FilterHoursDepartment::query()
            ->select('type')
            ->join('departments as d', 'filter_hours_departments.department_id', '=', 'd.id')
            ->when(!empty($filters['department']), function (Builder $query) use ($filters) {
                    $departments = is_array($filters['department']) ? $filters['department'] : array_map('trim', explode(',', (string) $filters['department']));
                    $departments = array_values(array_filter($departments, static fn ($d) => is_string($d) && trim($d) !== ''));
                if (!empty($departments)) {
                    $query->whereIn('d.name', $departments);
                }
            })
            ->when(!empty($filters['report_time']), function (Builder $query) use ($filters) {
                if (!empty($filters['report_time'])) {
                    $query->whereIn('filter_hours_departments.filter_hours_id', $filters['report_time']);
                }
            })
            ->distinct()
            ->pluck('type')
            ->mapWithKeys(static fn (string $type): array => [
                $type => match ($type) {
                    'checkin' => 'Llegada',
                    'checkout' => 'Salida',
                    default => $type,
                },
            ])
            ->toArray();

        return [
            Select::make('filter[type]')
                ->title(__('Report Type'))
                ->options($options)
                ->empty(__('All'))
                ->value($this->request->input('filter.type')),
        ];
    }
}
