@extends('mobile.layouts.auth')

@section('content')
<div
    class="auth-screen"
    x-data="{
        name: '',
        email: '',
        password: '',
        passwordConfirmation: '',
        loading: false,
        error: '',
        fieldErrors: {},
        apiBase: '{{ $apiBaseUrl }}',
        storeTokenUrl: '{{ route('mobile.storeToken') }}',

        async register() {
            this.loading = true;
            this.error = '';
            this.fieldErrors = {};

            try {
                const res = await fetch(this.apiBase + '/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        name: this.name,
                        email: this.email,
                        password: this.password,
                        password_confirmation: this.passwordConfirmation,
                    }),
                });

                const json = await res.json();

                if (!res.ok) {
                    this.error = json.message || 'Registration failed.';
                    this.fieldErrors = json.errors || {};
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
    <div class="auth-tagline">Start tracking your cash flow</div>

    <div x-show="error" x-cloak style="background:#FEE2E2;border-radius:8px;padding:12px 14px;margin-bottom:16px;color:#991B1B;font-size:14px;" x-text="error"></div>

    <div class="form-group">
        <label class="form-label">Full name</label>
        <input type="text" class="form-input" x-model="name" placeholder="Your name" autocomplete="name">
        <p class="error-msg" x-show="fieldErrors.name" x-text="(fieldErrors.name || [])[0]"></p>
    </div>

    <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-input" x-model="email" placeholder="you@example.com" inputmode="email">
        <p class="error-msg" x-show="fieldErrors.email" x-text="(fieldErrors.email || [])[0]"></p>
    </div>

    <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" class="form-input" x-model="password" placeholder="Min 8 characters">
        <p class="error-msg" x-show="fieldErrors.password" x-text="(fieldErrors.password || [])[0]"></p>
    </div>

    <div class="form-group">
        <label class="form-label">Confirm password</label>
        <input type="password" class="form-input" x-model="passwordConfirmation" placeholder="Repeat password" @keydown.enter="register">
    </div>

    <button
        class="btn btn-primary btn-block"
        style="margin-top: 8px;"
        @click="register"
        :disabled="loading || !name || !email || !password || !passwordConfirmation"
    >
        <span x-show="!loading">Create account</span>
        <span x-show="loading" x-cloak>Creating account…</span>
    </button>

    <div class="auth-footer">
        Already have an account? <a href="{{ route('mobile.login') }}">Sign in</a>
    </div>
</div>
@endsection
