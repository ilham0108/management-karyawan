@extends('gentelella::layouts.blank')

@section('title', __('Confirm password'))

@section('content')
<x-gentelella::auth.card
    :title="__('Confirm your password')"
    :subtitle="__('This is a secure area. Please confirm your password before continuing.')">
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="password">{{ __('Password') }}</label>
            <input
                id="password"
                name="password"
                type="password"
                class="form-control"
                autocomplete="current-password"
                required
                autofocus
            >
            @error('password')<div class="form-error" role="alert">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;height:38px">
            {{ __('Confirm') }}
        </button>
    </form>
</x-gentelella::auth.card>
@endsection
