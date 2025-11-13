@extends(config('platform.workspace', 'platform::workspace.compact'))

@section('aside')
    <div class="offcanvas offcanvas-end bg-primary" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
            <div class="offcanvas-header">
                <div class="offcanvas-title" id="offcanvasMenuLabel">
                    <a class="header-brand mb-3" href="{{ route(config('platform.index')) }}">
                        @includeFirst([config('platform.template.header'), 'platform::header'])
                    </a>
                </div>
                <button type="button" class="btn-close text-bg-light" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                
        
                @include('platform::partials.search')
        
                <ul class="nav flex-column mb-md-1 mb-auto ps-0">
                    {!! Dashboard::renderMenu() !!}
                </ul>       
                <footer class="mt-auto">
                    <div class="position-relative overflow-hidden" style="padding-bottom: 10px;">
                        @includeWhen(Auth::check(), 'platform::partials.profile')
                    </div>
                    <div class="mt-3">
                        @includeFirst([config('platform.template.footer'), 'platform::footer'])
                    </div>
                </footer>
            </div>
    </div>    
    <div class="container bg-primary py-3">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <a class="header-brand" href="{{ route(config('platform.index')) }}">
                        @includeFirst([config('platform.template.header'), 'platform::header'])
                    </a>
                    @if (Auth::check())                    
                    <div class="d-flex">
                        <div class="bg-primary position-relative overflow-hidden">                                
                            <div class="d-flex align-items-stretch lh-sm position-relative overflow-hidden">
                                <a href="{{ route(config('platform.profile', 'platform.profile')) }}" class="col-10 d-flex align-items-center me-3">
                                    @if($image = Auth::user()->presenter()->imageWhite())
                                        <img src="{{$image}}"  alt="{{ Auth::user()->presenter()->title()}}" class="thumb-md avatar me-3" type="image/*">
                                    @endif

                                    <small class="d-flex flex-column lh-1 col-9">
                                        <span class="text-ellipsis text-white">{{Auth::user()->presenter()->title()}}</span>
                                        <span class="text-ellipsis text-muted">{{Auth::user()->presenter()->subTitle()}}</span>
                                        <span class="text-ellipsis text-muted">{{Auth::user()->department ? Auth::user()->department->name : '' }}</span>
                                    </small>
                                </a>
                            </div>
                        </div>
                        <div class="mx-3">
                            <x-orchid-notification/>
                        </div>
                        <button data-controller="button" data-turbo="true" class="d-none d-md-block btn text-white" type="submit" form="post-form" formnovalidate="true" formaction="{{ route('platform.logout') }}">
                            <x-orchid-icon path="bs.box-arrow-left" class="icon-logout"/>
                            {{-- <span class="ms-2">{{ __('Sign out') }}</span> --}}
                        </button>
                        <button class="btn btn-outline-light mx-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
                            <x-orchid-icon path="bs.list" class="icon-menu"/>
                        </button>                        
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('workspace')
    @if(Breadcrumbs::has())
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb px-4 mb-2">
                <x-tabuna-breadcrumbs
                    class="breadcrumb-item"
                    active="active"
                />
            </ol>
        </nav>
    @endif

    <div class="order-last order-md-0 command-bar-wrapper">
        <div class="@hasSection('navbar') @else d-none d-md-block @endif layout d-md-flex align-items-center">
            <header class="d-none d-md-block col-xs-12 col-md p-0 me-3">
                <h1 class="m-0 fw-light h3 text-black">@yield('title')</h1>
                <small class="text-muted" title="@yield('description')">@yield('description')</small>
            </header>
            <nav class="col-xs-12 col-md-auto ms-md-auto p-0">
                <ul class="nav command-bar justify-content-sm-end justify-content-start d-flex align-items-center gap-2 flex-wrap-reverse flex-sm-nowrap">
                    @yield('navbar')
                </ul>
            </nav>
        </div>
    </div>

    @include('platform::partials.alert')
    @yield('content')
@endsection
