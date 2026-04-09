@php
    /** @var array<int, array{name: string, active: bool, url: string, bg?: string, text?: string, hover?: string}> $departmentButtons */
    $departmentButtons = $departmentButtons ?? [];
@endphp

@if (!empty($departmentButtons))
    <div class="bg-white rounded shadow-sm p-3 mb-3">
        <div class="d-flex flex-nowrap align-items-center overflow-auto py-2" style="gap: 0.5rem;">
            <span class="text-muted small me-2">{{ __('Departments') }}:</span>
            @foreach ($departmentButtons as $button)
                <a
                    href="{{ $button['url'] }}"
                    class="btn btn-sm text-nowrap rounded-pill department-toggle-btn {{ $button['active'] ? 'department-toggle-btn--active' : '' }}"
                    style="
                        flex-shrink: 0;
                        --dept-bg: {{ $button['bg'] ?? '#ffffff' }};
                        --dept-text: {{ $button['text'] ?? '#002060' }};
                        --dept-hover-bg: {{ $button['hover'] ?? '#eaf0ff' }};
                    "
                    >
                    {{ $button['name'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
