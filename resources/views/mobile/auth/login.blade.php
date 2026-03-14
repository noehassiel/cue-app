@extends('mobile.layouts.auth')

@section('content')
<div
    class="auth-screen"
    x-data="{
        email: '',
        password: '',
        loading: false,
        error: '',
        apiBase: '{{ $apiBaseUrl }}',
        storeTokenUrl: '{{ route('mobile.storeToken') }}',
        biometricUrl: '{{ route('mobile.biometricLogin') }}',

        async login() {
            this.loading = true;
            this.error = '';

            try {
                const res = await fetch(this.apiBase + '/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        email: this.email,
                        password: this.password,
                        device_name: 'Cue Mobile',
                    }),
                });

                const json = await res.json();

                if (!res.ok) {
                    this.error = json.message || 'Invalid credentials.';
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.storeTokenUrl;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name=csrf-token]').content;

                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'token';
                tokenInput.value = json.data.token;

                form.appendChild(csrfInput);
                form.appendChild(tokenInput);
                document.body.appendChild(form);
                form.submit();

            } catch (e) {
                this.error = 'Connection error. Check your internet connection.';
            } finally {
                this.loading = false;
            }
        }
    }"
>
    <div class="auth-logo">Cue</div>
    <div class="auth-tagline">Your signal for what's coming</div>

    <div x-show="error" x-cloak style="background:#FEE2E2;border-radius:8px;padding:12px 14px;margin-bottom:16px;color:#991B1B;font-size:14px;" x-text="error"></div>

    <div class="form-group">
        <label class="form-label">Email</label>
        <input
            type="email"
            class="form-input"
            x-model="email"
            placeholder="you@example.com"
            autocomplete="email"
            inputmode="email"
        >
    </div>

    <div class="form-group">
        <label class="form-label">Password</label>
        <input
            type="password"
            class="form-input"
            x-model="password"
            placeholder="••••••••"
            autocomplete="current-password"
            @keydown.enter="login"
        >
    </div>

    <button
        class="btn btn-primary btn-block"
        style="margin-top: 8px;"
        @click="login"
        :disabled="loading || !email || !password"
    >
        <span x-show="!loading">Sign in</span>
        <span x-show="loading" x-cloak>Signing in…</span>
    </button>

    <div class="auth-footer" style="margin-top: 20px;">
        Don't have an account? <a href="{{ route('mobile.register') }}">Create one</a>
    </div>
</div>
@endsection
