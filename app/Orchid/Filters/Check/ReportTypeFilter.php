<?php

namespace App\Orchid\Filters\Check;

use Orchid\Screen\Field;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Illuminate\Database\Eloquent\Builder;

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
        $selectedHourIds = $this->normalizeSelectedHourIds($this->request->input('filter.report_time_ids'));

        if ($selectedHourIds === []) {
            $selectedHourIds = $this->normalizeSelectedHourIds($this->request->input('filter.report_time'));
        }

        $fields = [
            Select::make('filter[type]')
                ->title(__('Report Type'))
                ->options([
                    'checkin'  => __('Arrival'),
                    'checkout' => __('Departure'),
                ])
                ->empty(__('All'))
                ->value($this->request->input('filter.type')),
        ];

        foreach ($selectedHourIds as $hourId) {
            $fields[] = Input::make('filter[report_time_ids][]')
                ->type('hidden')
                ->value((string) $hourId);
        }

        return $fields;
    }

    /**
     * @param mixed $value
     * @return array<int, int>
     */
    private function normalizeSelectedHourIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value)
            ? $value
            : array_map('trim', explode(',', (string) $value));

        $normalized = array_filter(
            array_map(static fn ($item): string => trim((string) $item), $values),
            static fn (string $item): bool => $item !== '' && ctype_digit($item) && (int) $item > 0
        );

        return array_values(array_unique(array_map(static fn (string $item): int => (int) $item, $normalized)));
    }
}
