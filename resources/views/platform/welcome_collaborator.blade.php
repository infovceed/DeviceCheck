<div class="bg-white rounded-top shadow-sm mb-4 rounded-bottom">

    <div class="row g-0">
        {{-- boton para acceder a empaques --}}
        <div class="col-md-2 p-3">
            <a
                href="{{ route('platform.systems.Devices') }}"
                class="btn btn-primary btn-lg w-100 py-4 rounded-4"
                style="font-size: 1.25rem;"
            >
                <x-orchid-icon
                path="bs.box-seam"
                class="d-inline mx-2 fs-2"
                />
                {{ __('Access Devices') }}
            </a>
        </div>

    </div>

    <div class="row bg-light m-0 p-md-4 p-3 border-top rounded-bottom g-md-5">

            {{ __("Access the options of interest to you from the left side menu.") }}
    </div>
</div>
