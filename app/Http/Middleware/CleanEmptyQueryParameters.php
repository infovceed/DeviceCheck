<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\FilterHoursDepartment;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanEmptyQueryParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $original = $request->query();
        $cleaned = self::cleanArray($original);
        $cleaned = self::normalizeCheckFilters($cleaned);
        $cleaned = self::cleanArray($cleaned);

        if ($cleaned !== $original) {
            $baseUrl = $request->url();
            $queryString = http_build_query($cleaned);
            $targetUrl = $queryString !== '' ? $baseUrl . '?' . $queryString : $baseUrl;

            return redirect()->to($targetUrl);
        }

        return $next($request);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function cleanArray(array $input): array
    {
        $output = [];

        foreach ($input as $key => $value) {
            $cleaned = self::cleanValue($value);

            if ($cleaned === null) {
                continue;
            }

            $output[$key] = $cleaned;
        }

        return $output;
    }

    private static function cleanValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        if (is_array($value)) {
            if ($value === []) {
                return null;
            }

            if (array_is_list($value)) {
                $cleanedList = [];

                foreach ($value as $item) {
                    $cleanedItem = self::cleanValue($item);

                    if ($cleanedItem === null) {
                        continue;
                    }

                    $cleanedList[] = $cleanedItem;
                }

                return $cleanedList === [] ? null : $cleanedList;
            }

            $cleanedAssoc = [];

            foreach ($value as $k => $v) {
                $cleanedV = self::cleanValue($v);

                if ($cleanedV === null) {
                    continue;
                }

                $cleanedAssoc[$k] = $cleanedV;
            }

            return $cleanedAssoc === [] ? null : $cleanedAssoc;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function normalizeCheckFilters(array $input): array
    {
        $filters = $input['filter'] ?? null;
        if (! is_array($filters)) {
            return $input;
        }

        $normalizedReportTime = self::normalizeReportTimeByType($filters);
        if ($normalizedReportTime === null) {
            return $input;
        }

        $input['filter']['report_time'] = $normalizedReportTime;

        return $input;
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<string>|null
     */
    private static function normalizeReportTimeByType(array $filters): ?array
    {
        $reportTimeIds = array_values(array_filter(
            array_map(
                static fn (string $value): int => (int) $value,
                self::normalizeList($filters['report_time'] ?? null)
            ),
            static fn (int $value): bool => $value > 0
        ));
        $types = self::normalizeList($filters['type'] ?? null);

        if ($reportTimeIds === [] || count($types) !== 1) {
            return null;
        }

        $selectedType = $types[0];
        if (! in_array($selectedType, ['checkin', 'checkout'], true)) {
            return null;
        }

        $departments = self::normalizeList($filters['department'] ?? null);
        $positions = self::normalizeList($filters['position_name'] ?? null);

        $validIds = FilterHoursDepartment::query()
            ->whereIn('filter_hours_id', $reportTimeIds)
            ->where('type', $selectedType)
            ->when($departments !== [], function (Builder $query) use ($departments) {
                $query
                    ->join('departments', 'filter_hours_departments.department_id', '=', 'departments.id')
                    ->whereIn('departments.name', $departments);
            })
            ->when($positions !== [], function (Builder $query) use ($positions) {
                $query->whereIn('filter_hours_departments.position_name', $positions);
            })
            ->distinct()
            ->pluck('filter_hours_id')
            ->map(static fn (int|string $value): string => (string) ((int) $value))
            ->all();

        return array_values(array_filter(
            array_map(static fn (int $value): string => (string) $value, $reportTimeIds),
            static fn (string $value): bool => in_array($value, $validIds, true)
        ));
    }

    /**
     * @return list<string>
     */
    private static function normalizeList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value)
            ? $value
            : explode(',', (string) $value);

        return array_values(array_unique(array_filter(
            array_map(static fn ($item): string => trim((string) $item), $values),
            static fn (string $item): bool => $item !== ''
        )));
    }
}
