<div class="rounded bg-white mb-3 p-3">
    <div class="border-dashed d-flex align-items-center w-100 rounded overflow-hidden" style="min-height: 100px;">
       <div class="d-flex justify-content-center align-items-center w-100 px-3 gap-3">
            <div class="text-center">
                <h5 class="card-title fs-1">
                    {{ $title }}
                </h5>
                <h6 class="card-subtitle mb-2 text-muted">{{ $subtitle }}</h6>
            </div>
            <div>
                <div class="col-3 text-primary">
                    <x-orchid-icon path="{{ $icon }}" class="d-inline mx-1" style="font-size: 4rem;" />
                </div>
            </div>
        </div>
    </div>
</div>
