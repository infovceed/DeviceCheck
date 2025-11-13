<div class="bg-white rounded-top shadow-sm mb-4 rounded-bottom">

    <div class="row g-0">
        <div class="col col-lg-7 mt-6 p-4">

            <h2 class="text-dark fw-light lh-lg">
                {{ __('Hello, It’s Great to See You!') }}
            </h2>

            <p>
                {{ __('Start your journey with platform. Select one option below to get started.') }}
        </div>
        <div class="d-none d-lg-block col align-self-center text-end text-muted p-4 opacity-25">
            <!-- APP_URL -->
            <img src="{{ config('app.url') }}/assets/img/logo.png" class="img-fluid" alt="logo">
        </div>
    </div>

    <div class="row bg-light m-0 p-md-4 p-3 border-top rounded-bottom g-md-5">

        <div class="col-md-6 my-2">
            <h3 class="text-muted fw-light lh-lg d-flex align-items-center">
                <x-orchid-icon path="bs.people"/>

                <span class="ms-3 text-dark"> {{__('Create new users')}}</span>
            </h3>
            <p class="ms-md-5 ps-md-1">
                {{__('Create a new user account for a new employee or a new customer.')}} 

                <button class="btn btn-primary mt-3">
                    <a href="{{ route('platform.systems.users.create') }}"
                       class="text-white">{{__('Click here to create a new user.')}}
                    </a>
                </button>
            </p>
        </div>
        <div class="col-md-6 my-2">
            <h3 class="text-muted fw-light lh-lg d-flex align-items-center">
                <x-orchid-icon path="bs.shield-lock"/>

                <span class="ms-3 text-dark">{{ __('Access roles') }}</span>
            </h3>
            <p class="ms-md-5 ps-md-1">
                {{ __("Create, edit or delete roles to manage user permissions.")." " }}

                <button class="btn btn-primary mt-3">
                    <a href="{{ route('platform.systems.roles.create') }}"
                       class="text-white">{{ __('Click here to create a new role.') }}
                    </a>
                </button>
            </p>
        </div>
        <div class="col-md-6 my-2">
            <h3 class="text-muted fw-light lh-lg d-flex align-items-center">
                <x-orchid-icon path="bs.rocket"/>

                <span class="ms-3 text-dark">{{ __('Access projects') }}</span>
            </h3>
            <p class="ms-md-5 ps-md-1">
                {{ __("Create, edit or delete projects")." " }}

                <button class="btn btn-primary mt-3">
                    <a href="{{ route('platform.systems.projects') }}"
                    class="text-white">{{ __('Click here to access projects.') }}
                    </a>
                </button>
            </p>
        </div>
        <div class="col-md-6 my-2">
            <h3 class="text-muted fw-light lh-lg d-flex align-items-center">
                <x-orchid-icon path="bs.columns-gap"/>

                <span class="ms-3 text-dark">{{ __('Create a new status') }}</span>
            </h3>
            <p class="ms-md-5 ps-md-1">
                {{ __("Create, edit or delete status for your task.")." " }}

                <button class="btn btn-primary mt-3">
                    <a href="{{ route('platform.resource.create','status-resources') }}"
                       class="text-white">{{ __('Click here to create a new status.') }}
                    </a>
                </button>
            </p>
        </div>
    </div>
</div>
