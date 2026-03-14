@extends('mobile.layouts.auth')

@section('content')
<div
    class="auth-screen"
    style="align-items: center; justify-content: center; text-align: center;"
    x-data="{
        biometricId: '{{ $biometricId }}',
        dashboardUrl: '{{ route('mobile.dashboard') }}',
        loginUrl: '{{ route('mobile.login') }}',

        init() {
            window.addEventListener('native:biometric:completed', (e) => {
                const data = e.detail;
                if (data.id === this.biometricId && data.result === 'success') {
                    window.location.href = this.dashboardUrl;
                } else if (data.result !== 'success') {
                    window.location.href = this.loginUrl;
                }
            });
        }
    }"
>
    <div style="font-size: 64px; margin-bottom: 24px;">🔒</div>
    <div class="auth-logo">Cue</div>
    <p style="color: var(--color-muted); margin-top: 12px; font-size: 15px;">
        Authenticate with Face ID or fingerprint to continue
    </p>

    <div class="auth-footer" style="margin-top: 32px;">
        <a href="{{ route('mobile.login') }}">Use password instead</a>
    </div>
</div>
@endsection
