@php
    $invalidCredentials = collect($errors->get('email'))->unique()->first();
@endphp

<div class="mb-3">

    <label class="form-label" for="email">
        {{ __('Email address') }}
    </label>

    <input
        id="email"
        name="email"
        type="email"
        class="form-control"
        value="{{ old('email') }}"
        required
        tabindex="1"
        autofocus
        autocomplete="email"
        inputmode="email"
        placeholder="{{ __('Enter your email') }}"
    >

    @if($invalidCredentials)
        <div class="invalid-feedback d-block">
            <small>{{ $invalidCredentials }}</small>
        </div>
    @endif
</div>

<div class="mb-3">
    <label class="form-label w-100" for="password">
        {{ __('Password') }}
    </label>

    <div class="input-group">
        <input
            id="password"
            name="password"
            type="password"
            class="form-control"
            required
            autocomplete="current-password"
            tabindex="2"
            placeholder="{{ __('Enter your password') }}"
        >
    </div>
</div>

<div class="row align-items-center">
    <div class="col-md-6 col-xs-12">
        <label class="form-check">
            <input type="hidden" name="remember">
            <input type="checkbox" name="remember" value="true"
                   class="form-check-input" {{ !old('remember') || old('remember') === 'true'  ? 'checked' : '' }}>
            <span class="form-check-label"> {{__('Remember Me')}}</span>
        </label>
    </div>
    <div class="col-md-6 col-xs-12">
        <button id="button-login" type="submit" class="btn btn-default btn-block" tabindex="3">
            <x-orchid-icon path="bs.box-arrow-in-right" class="small me-2"/>
            {{__('Login')}}
        </button>
    </div>
</div>
