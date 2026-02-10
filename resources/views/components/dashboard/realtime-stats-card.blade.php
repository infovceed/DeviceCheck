@php
    $props = [
        'metric' => $metric,
        'value' => $value ?? null,
        'subtitle' => $subtitle ?? null,
        'icon' => $icon ?? null,
        'iconColor' => $iconColor ?? null,
        'wsUrl' => $wsUrl ?? null,
    ];
@endphp

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/ts/app.tsx'])
@endif

<div class="realtime-stats-card" data-props='@json($props)'></div>
