@push('head')
    <meta name="robots" content="noindex"/>
    <meta name="google" content="notranslate">
    <link href="/favicon.ico" id="favicon" rel="icon">

    <!-- For Safari on iOS -->
    <meta name="theme-color" content="#21252a">
@endpush

<div class="h2 d-flex align-items-center">
    <p class="my-0 {{ auth()->check() ? 'd-block' : '' }}">
        {{ config('app.name') }}
        <small class="align-top opacity">Software</small>
    </p>
</div>