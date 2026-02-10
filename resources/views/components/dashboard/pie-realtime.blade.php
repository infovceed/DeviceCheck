@php
    $props = [
        'mode' => $mode ?? 'arrival',
        'title' => $title ?? ($mode === 'departure' ? __('Departure') : __('Arrival')),
        'wsUrl' => $wsUrl ?? null,
        'initial' => $initial ?? null,
    ];
@endphp

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/ts/app.tsx'])
@endif

<div class="realtime-pie" data-props='@json($props)'></div>
