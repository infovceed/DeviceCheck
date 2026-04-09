@php
    /** @var array<int, array{name: string, active: bool, url: string}> $filterHoursButtons */
    $filterHoursButtons = $filterHoursButtons ?? [];
@endphp

@if (!empty($filterHoursButtons))
    <div class="bg-white rounded shadow-sm p-3 mb-3">
        <span class="text-muted small me-2">{{ __('Hours') }}:</span>
        <div class="d-flex flex-nowrap align-items-center py-2" style="gap: 0.5rem;">
            @foreach ($filterHoursButtons as $button)
                <a
                    href="{{ $button['url'] }}"
                    class="btn btn-sm text-nowrap rounded-pill department-toggle-btn {{ $button['active'] ? 'department-toggle-btn--active' : '' }}"
                    style="flex-shrink: 0;"
                >
                    {{ $button['hour'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
