@if($device)
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">{{ __('Device information') }}</h5>
                        <span
                            class="badge badge-pill bg-{{ $device->status_incidents==1?"danger":"info" }}"
                            data-controller="popover"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-delay-show="300"
                            data-bs-content={{__('Incident Status') }}>
                            {{ ucfirst($device->status_incidents==1? __('Opened') : __('Closed')) }}
                        </span>
                    </div>
                </div>
                <div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column my-2">
                    {{-- colocar datos de la divipole --}}
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <span><strong>{{ __('Department') }}:</strong> {{ $device->divipole->department->name }}</span><br>
                                <span><strong>{{ __('Municipality') }}:</strong> {{ $device->divipole->municipality->name }}</span><br>
                                <span><strong>{{ __('Position') }}:</strong> {{ $device->divipole->position_name }}</span><br>
                                <span><strong>{{ __('Address') }}:</strong> {{ $device->divipole->position_address }}</span><br>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <span><strong>{{ __('IMEI') }}:</strong> {{ $device->imei }}</span><br>
                                <span><strong>{{ __('Key') }}:</strong> {{ $device->device_key }}</span><br>
                                <span><strong>{{ __('Sequential') }}:</strong> {{ $device->sequential }}</span><br>
                                <span><strong>{{ __('Status') }}:</strong> {{ ucfirst($device->status? __('Reported') : __('Not Reported')) }}</span><br>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="col-lg-12">
                    <h5 class="card-title">Description</h5>
                    <div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column my-2">
                        <span>Device not found</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

