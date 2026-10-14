@extends('gentelella::layouts.blank')

@section('title', __('Two-factor authentication'))

@section('content')
<x-gentelella::auth.card
    :title="__('Verify your identity')"
    :subtitle="__('Enter an authentication code or use one of your recovery codes.')">
    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf

        <div id="authentication-code-field" class="form-group">
            <label class="form-label" for="code">{{ __('Authentication code') }}</label>
            <input
                id="code"
                name="code"
                type="text"
                class="form-control"
                inputmode="numeric"
                autocomplete="one-time-code"
                autofocus
            >
            @error('code')<div class="form-error" role="alert">{{ $message }}</div>@enderror
        </div>

        <div id="recovery-code-field" class="form-group" hidden>
            <label class="form-label" for="recovery_code">{{ __('Recovery code') }}</label>
            <input
                id="recovery_code"
                name="recovery_code"
                type="text"
                class="form-control"
                autocomplete="one-time-code"
                disabled
            >
            @error('recovery_code')<div class="form-error" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="auth-actions">
            <button
                id="toggle-authentication-method"
                type="button"
                class="btn btn-ghost btn-sm"
                aria-controls="recovery-code-field"
                aria-expanded="false"
            >
                {{ __('Use a recovery code') }}
            </button>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;height:38px">
            {{ __('Sign in') }}
        </button>
    </form>
</x-gentelella::auth.card>

@push('scripts')
    <script>
        (() => {
            const toggle = document.getElementById('toggle-authentication-method');
            const codeField = document.getElementById('authentication-code-field');
            const recoveryField = document.getElementById('recovery-code-field');
            const codeInput = document.getElementById('code');
            const recoveryInput = document.getElementById('recovery_code');

            toggle.addEventListener('click', () => {
                const showRecovery = recoveryField.hidden;

                recoveryField.hidden = !showRecovery;
                recoveryInput.disabled = !showRecovery;
                codeField.hidden = showRecovery;
                codeInput.disabled = showRecovery;
                toggle.setAttribute('aria-expanded', String(showRecovery));
                toggle.setAttribute('aria-controls', showRecovery ? 'recovery-code-field' : 'authentication-code-field');
                toggle.textContent = showRecovery
                    ? @json(__('Use an authentication code'))
                    : @json(__('Use a recovery code'));
                (showRecovery ? recoveryInput : codeInput).focus();
            });
        })();
    </script>
@endpush
@endsection
