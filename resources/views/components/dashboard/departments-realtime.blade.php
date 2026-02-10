@php
    $props = [
        'initialSeries' => $initial ?? [],
        'wsUrl' => $wsUrl ?? null,
        'title' => __('Reporte por departamento'),
    ];
@endphp

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/ts/app.tsx'])
@endif

<div id="orchid-departments-realtime" data-props='@json($props)'></div>
