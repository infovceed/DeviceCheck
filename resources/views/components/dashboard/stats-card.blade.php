@php
    $props = [
        'title' => $title,
        'subtitle' => $subtitle,
        'icon' => $icon,
        'iconColor' => $iconColor,
    ];
@endphp

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/ts/app.tsx'])
@endif

<div class="stats-card" data-props='@json($props)'></div>
