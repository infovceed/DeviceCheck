@php
    /** @var array<int, array{name: string, active: bool, url: string}> $departmentButtons */
    $departmentButtons = $departmentButtons ?? [];
@endphp

@if (!empty($departmentButtons))
    <div class="bg-white rounded shadow-sm p-3 mb-3">
        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
            <span class="text-muted small me-2">{{ __('Departments') }}:</span>
            @foreach ($departmentButtons as $button)
                <a
                    href="{{ $button['url'] }}"
                    class="btn btn-sm department-toggle-btn {{ $button['active'] ? 'department-toggle-btn--active' : '' }}"
                >
                    {{ $button['name'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
