<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
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

        if ($cleaned !== $original) {
            $baseUrl = $request->url();
            $queryString = http_build_query($cleaned);
            $targetUrl = $queryString !== '' ? $baseUrl.'?'.$queryString : $baseUrl;

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
}
