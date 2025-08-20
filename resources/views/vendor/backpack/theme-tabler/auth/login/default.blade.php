@extends(backpack_view('layouts.auth'))

@push('after_styles')
<style>
/* CSS inline untuk memastikan berfungsi */
.form-control:focus,
.form-control:hover {
    border-color: #FF0000 !important;
    box-shadow: 0 0 0 0.2rem rgba(255,0,0,.25) !important;
}

.btn-primary {
    background-color: #FF0000 !important;
    border-color: #FF0000 !important;
    color: #fff !important;
}

.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active {
    background-color: #CC0000 !important;
    border-color: #CC0000 !important;
}
</style>
@endpush

@section('content')
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4 display-6 auth-logo-container">
                {!! backpack_theme_config('project_logo') !!}
            </div>
            <div class="card card-md">
                <div class="card-body pt-0">
                    @include(backpack_view('auth.login.inc.form'))
                </div>
            </div>
            @if (config('backpack.base.registration_open'))
                <div class="text-center text-muted mt-4">
                    <a tabindex="6" href="{{ route('backpack.auth.register') }}">{{ trans('backpack::base.register') }}</a>
                </div>
            @endif
        </div>
    </div>
@endsection
