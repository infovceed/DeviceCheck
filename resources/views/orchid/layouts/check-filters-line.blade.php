<div class="g-0 bg-white rounded mb-3">
    <div class="row align-items-center p-4" data-controller="filter">
        @foreach ($filters as $filter)
            @php
                $isReportTimeHidden = $filter instanceof \App\Orchid\Filters\Check\ReportTimeFilter;
                $isDepartmentHidden = $filter instanceof \App\Orchid\Filters\Check\DepartmentFilter;
                $isHiddenFilter = $isReportTimeHidden || $isDepartmentHidden;
            @endphp

            <div
                @class([
                    'd-none' => $isHiddenFilter,
                    'col-sm-auto col-md mb-3 align-self-start' => ! $isHiddenFilter,
                ])
                @if (! $isHiddenFilter)
                    style="min-width: 200px;"
                @endif
            >
                {!! $filter->render() !!}
            </div>
        @endforeach

        <div class="col-sm-auto ms-auto text-end">
            <div class="btn-group">
                <button data-action="filter#clear" class="btn btn-default">
                    <x-orchid-icon class="me-1" path="bs.arrow-repeat"/> {{ __('Reset') }}
                </button>
                <button type="submit" form="filters" class="btn btn-default">
                    <x-orchid-icon class="me-1" path="bs.funnel"/> {{ __('Apply') }}
                </button>
            </div>
        </div>
    </div>
</div>
