@extends('mobile.layouts.app', ['title' => 'Settings', 'showBack' => false])

@section('content')
<div
    class="screen-body"
    x-data="{
        token: '{{ $apiToken }}',
        apiBase: '{{ $apiBaseUrl }}',
        user: null,
        workspaces: [],
        activeWorkspaceId: null,
        loading: true,
        logoutUrl: '{{ route('mobile.logout') }}',

        async init() {
            const [userRes, wsRes] = await Promise.all([
                fetch(this.apiBase + '/user', {
                    headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
                }),
                fetch(this.apiBase + '/workspaces', {
                    headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
                }),
            ]);

            if (userRes.status === 401) { window.location.href = '{{ route('mobile.login') }}'; return; }

            const userJson = await userRes.json();
            this.user = userJson.data.user;

            if (wsRes.ok) {
                const wsJson = await wsRes.json();
                this.workspaces = wsJson.data ?? [];
                const stored = localStorage.getItem('active_workspace_id');
                this.activeWorkspaceId = stored && this.workspaces.find(w => w.id === stored)
                    ? stored
                    : (this.workspaces[0]?.id ?? null);
            }

            this.loading = false;
        },

        switchWorkspace(id) {
            this.activeWorkspaceId = id;
            localStorage.setItem('active_workspace_id', id);
        },

        async logout() {
            try {
                await fetch(this.apiBase + '/auth/logout', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
                });
            } catch (e) {
                // Proceed with local logout regardless
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.logoutUrl;

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name=csrf-token]').content;

            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        }
    }"
>
    <div x-show="loading" x-cloak><div class="spinner"></div></div>

    <div x-show="!loading" x-cloak>
        <!-- Profile card -->
        <div class="card" style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:48px;height:48px;border-radius:50%;background:var(--color-primary);display:flex;align-items:center;justify-content:center;color:white;font-size:20px;font-weight:700;" x-text="(user?.name || '?')[0].toUpperCase()"></div>
            <div>
                <div style="font-size:16px;font-weight:600;" x-text="user?.name"></div>
                <div style="font-size:13px;color:var(--color-muted);" x-text="user?.email"></div>
            </div>
        </div>

        <!-- Workspace switcher -->
        <template x-if="workspaces.length > 1">
            <div>
                <div class="section-title">Workspace</div>
                <div class="card" style="padding:0;">
                    <template x-for="ws in workspaces" :key="ws.id">
                        <div
                            class="row"
                            style="padding:14px 16px;cursor:pointer;border-bottom:1px solid var(--color-border);"
                            @click="switchWorkspace(ws.id)"
                        >
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div
                                    style="width:32px;height:32px;border-radius:8px;background:var(--color-primary);display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:700;"
                                    x-text="(ws.name || '?')[0].toUpperCase()"
                                ></div>
                                <div>
                                    <div style="font-size:14px;font-weight:500;" x-text="ws.name"></div>
                                    <div style="font-size:12px;color:var(--color-muted);" x-text="ws.currency"></div>
                                </div>
                            </div>
                            <div x-show="ws.id === activeWorkspaceId" style="color:var(--color-primary);font-size:18px;">✓</div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- App info -->
        <div class="section-title">App</div>
        <div class="card">
            <div class="row">
                <span style="font-size:14px;">Version</span>
                <span style="font-size:14px;color:var(--color-muted);">1.0.0</span>
            </div>
            <div class="row">
                <span style="font-size:14px;">API</span>
                <span style="font-size:12px;color:var(--color-muted);" x-text="apiBase"></span>
            </div>
        </div>

        <!-- Logout -->
        <div class="section-title">Account</div>
        <div class="card">
            <button
                class="btn btn-danger btn-block"
                @click="logout"
            >
                Sign out
            </button>
        </div>
    </div>
</div>
@endsection
