<th @empty(!$width) width="{{$width}}" @endempty class="text-{{$align}}" data-column="{{ $slug }}">
    <div class="d-inline-flex align-items-center">

        @includeWhen($filter !== null, "platform::partials.layouts.filter", ['filter' => $filter])

        @if($sort)
            <a href="{{ $sortUrl }}"
               class="@if(!is_sort($column)) text-muted @endif">
                {!! $title !!}

                <x-orchid-popover :content="$popover"/>

                @if(is_sort($column))
                    @php $sortIcon = get_sort($column) === 'desc' ? 'bs.sort-down' : 'bs.sort-up' @endphp
                    <x-orchid-icon :path="$sortIcon"/>
                @endif
            </a>
        @else
            {!! $title !!}

            <x-orchid-popover :content="$popover"/>
        @endif
    </div>

    @if($filterString)
        @php
            $rawFilter = get_filter($column);
            $plainTitle = trim(strip_tags((string) $title)) ?: $column;

            $pluralLabel = null;
            if (isset($filter) && $filter !== null) {
                $pluralLabel = $filter->get('data-filter-plural');
            }
            $pluralLabel = $pluralLabel ?: config('filter_badge.plurals.' . $column);
            $pluralLabel = $pluralLabel ?: $plainTitle;

            $popoverContent = null;
            $displayText = (string) $filterString;
            $clearFilterColumn = in_array($column, ['report_time_arrival', 'report_time_departure'], true)
                ? 'report_time'
                : $column;

            if (is_array($rawFilter)) {
                $isRange = isset($rawFilter['start']) || isset($rawFilter['end']);

                if (! $isRange) {
                    $values = array_values(array_filter($rawFilter, fn ($v) => $v !== null && $v !== ''));

                    $labels = $values;
                    if (isset($filter) && $filter !== null) {
                        $options = $filter->get('options');

                        if (is_iterable($options)) {
                            $flatOptions = [];
                            foreach ($options as $key => $option) {
                                if (is_array($option)) {
                                    foreach ($option as $k => $v) {
                                        $flatOptions[(string) $k] = $v;
                                    }
                                } else {
                                    $flatOptions[(string) $key] = $option;
                                }
                            }

                            if (count($flatOptions) > 0) {
                                $labels = array_map(fn ($v) => $flatOptions[(string) $v] ?? $v, $values);
                            }
                        }
                    }

                    if (
                        isset($filter)
                        && $filter instanceof \Orchid\Screen\Fields\Relation
                        && $labels === $values
                        && count($values) > 0
                    ) {
                        try {
                            $modelClass = \Illuminate\Support\Facades\Crypt::decryptString($filter->get('relationModel'));
                            $key = \Illuminate\Support\Facades\Crypt::decryptString($filter->get('relationKey'));
                            $name = \Illuminate\Support\Facades\Crypt::decryptString($filter->get('relationName'));

                            $items = $modelClass::query()
                                ->whereIn($key, $values)
                                ->get([$key, $name])
                                ->pluck($name, $key)
                                ->mapWithKeys(fn ($v, $k) => [(string) $k => $v])
                                ->all();

                            if (is_array($items) && count($items) > 0) {
                                $labels = array_map(fn ($v) => $items[(string) $v] ?? $v, $values);
                            }
                        } catch (\Throwable $e) {
                            // Fallback silencioso
                        }
                    }

                    if ($labels === $values && count($values) > 0) {
                        $resolver = config('filter_badge.resolvers.' . $column);

                        if (is_array($resolver) && isset($resolver['model'], $resolver['key'], $resolver['label'])) {
                            try {
                                $modelClass = $resolver['model'];
                                $key = $resolver['key'];
                                $labelField = $resolver['label'];

                                $items = $modelClass::query()
                                    ->whereIn($key, $values)
                                    ->get([$key, $labelField])
                                    ->pluck($labelField, $key)
                                    ->mapWithKeys(fn ($v, $k) => [(string) $k => $v])
                                    ->all();

                                if (is_array($items) && count($items) > 0) {
                                    $labels = array_map(fn ($v) => $items[(string) $v] ?? $v, $values);
                                }
                            } catch (\Throwable $e) {
                                // Fallback silencioso
                            }
                        }
                    }

                    if ($column === 'report_time' && count($labels) > 0) {
                        $labels = array_map(function ($value) {
                            try {
                                return \Carbon\Carbon::parse((string) $value)->format('H:i:s');
                            } catch (\Throwable $e) {
                                return (string) $value;
                            }
                        }, $labels);
                    }

                    if (count($values) <= 2 && $labels !== $values) {
                        $displayText = implode(', ', array_map('strval', $labels));
                    }

                    if (count($labels) > 0) {
                        $popoverContent = implode(', ', array_map('strval', $labels));
                    }

                    if (count($values) > 2) {
                        $displayText = count($values) . ' ' . $pluralLabel;
                    }
                }
            }

            if ($popoverContent === null && mb_strlen($displayText) > 40) {
                $popoverContent = $displayText;
                $displayText = mb_strimwidth($displayText, 0, 40, '...');
            }
        @endphp

        <div data-controller="filter" class="mt-2">
            <a href="#"
               data-action="filter#clearFilter"
               data-filter="{{ $clearFilterColumn }}"
               class="badge bg-light border d-inline-flex align-items-center">
                @if($popoverContent !== null)
                    <span
                        data-controller="popover"
                        data-bs-toggle="popover"
                        data-bs-trigger="hover focus"
                        data-bs-placement="top"
                        data-bs-title="{{ $plainTitle }}"
                        data-bs-content="{{ $popoverContent }}"
                        title="{{ $popoverContent }}"
                    >{{ $displayText }}</span>
                @else
                    <span>{{ $displayText }}</span>
                @endif
                <x-orchid-icon path="bs.x-lg" class="ms-1"/>
            </a>
        </div>
    @endif
</th>
