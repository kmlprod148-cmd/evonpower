@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.my_profile') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> {{ __('messages.edit') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>{{ __('messages.personal_info') }}</h5>
                            <p><strong>{{ __('messages.name') }} :</strong> {{ auth()->user()->name ?? __('messages.not_defined') }}</p>
                            <p><strong>{{ __('messages.email') }} :</strong> {{ auth()->user()->email ?? __('messages.not_defined') }}</p>
                            <p><strong>{{ __('messages.user_role') }} :</strong> {{ auth()->user()->roles->first()->name ?? __('messages.user') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>{{ __('messages.quick_actions') }}</h5>
                            <a href="{{ route('profile.password') }}" class="btn btn-outline-primary">{{ __('messages.change_password') }}</a>
                            <a href="{{ route('profile.notifications') }}" class="btn btn-outline-secondary">{{ __('messages.notifications') }}</a>
                            <a href="{{ route('profile.preferences') }}" class="btn btn-outline-info">{{ __('messages.preferences') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
