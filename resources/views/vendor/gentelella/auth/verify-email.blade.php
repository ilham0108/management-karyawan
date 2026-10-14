@extends('gentelella::layouts.blank')

@section('title', __('Verify your email'))

@section('content')
<x-gentelella::auth.card
    :title="__('Verify your email address')"
    :subtitle="__('Check your inbox for the verification link. If you have not received it, request a new one below.')">
    @if (session('status') === 'verification-link-sent')
        <div class="auth-success" role="status">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;height:38px">
            {{ __('Resend verification email') }}
        </button>
    </form>

    <x-slot:footer>
        <a href="{{ route('profile.show') }}">{{ __('Edit profile') }}</a>
        <span aria-hidden="true"> · </span>
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">{{ __('Sign out') }}</button>
        </form>
    </x-slot>
</x-gentelella::auth.card>
@endsection
