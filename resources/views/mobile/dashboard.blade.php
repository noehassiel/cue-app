@extends('mobile.layouts.app', ['title' => 'Dashboard', 'showBack' => false])

@section('content')
<div
    class="screen-body"
    x-data="{
        token: '{{ $apiToken }}',
        apiBase: '{{ $apiBaseUrl }}',
        loading: true,
        error: '',
        data: null,
        workspace: null,

        async init() {
            await this.loadUser();
        },

        async loadUser() {
            this.loading = true;
            try {
                const res = await fetch(this.apiBase + '/user', {
                    headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
                });
                if (res.status === 401) {
                    window.location.href = '{{ route('mobile.login') }}';
                    return;
                }
                const json = await res.json();
                this.workspace = json.data.active_workspace;
                if (this.workspace) {
                    await this.loadDashboard();
                }
            } catch (e) {
                this.error = 'Could not load data.';
            } finally {
                this.loading = false;
            }
        },

        async loadDashboard() {
            const res = await fetch(this.apiBase + '/workspaces/' + this.workspace.id + '/dashboard', {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const json = await res.json();
                this.data = json.data;
            }
        },

        formatCurrency(amount) {
            if (!amount) return '$0.00';
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: this.workspace?.currency || 'MXN', minimumFractionDigits: 2 }).format(parseFloat(amount));
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('es-MX', { month: 'short', day: 'numeric' });
        }
    }"
>
    <!-- Loading state -->
    <div x-show="loading" x-cloak>
        <div class="spinner"></div>
    </div>

    <!-- Error state -->
    <div x-show="!loading && error" x-cloak>
        <div class="card" style="color: var(--color-expense); text-align: center; padding: 24px;" x-text="error"></div>
    </div>

    <!-- Main content -->
    <div x-show="!loading && data" x-cloak>

        <!-- Workspace name -->
        <div style="font-size: 13px; color: var(--color-muted); margin-bottom: 16px;" x-text="workspace?.name"></div>

        <!-- Balance cards -->
        <div class="card" style="background: var(--color-primary); color: white;">
            <div style="font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; margin-bottom: 4px;">Available Balance</div>
            <div style="font-size: 32px; font-weight: 800; letter-spacing: -0.5px;" x-text="formatCurrency(data?.operational_balance)"></div>
            <div style="font-size: 12px; opacity: 0.7; margin-top: 4px;">Excluding allocated funds</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="card" style="margin-bottom: 0;">
                <div class="card-title">Projected</div>
                <div style="font-size: 20px; font-weight: 700; color: var(--color-primary);" x-text="formatCurrency(data?.projected_balance)"></div>
            </div>
            <div class="card" style="margin-bottom: 0;">
                <div class="card-title">Funds</div>
                <div style="font-size: 20px; font-weight: 700;" x-text="formatCurrency(data?.total_funds_allocated)"></div>
            </div>
        </div>

        <!-- This month -->
        <div class="card">
            <div class="card-title">This Month</div>
            <div class="row">
                <span style="color: var(--color-muted); font-size: 14px;">Income</span>
                <span class="amount income" style="font-size: 16px; font-weight: 600;" x-text="formatCurrency(data?.monthly_summary?.income)"></span>
            </div>
            <div class="row">
                <span style="color: var(--color-muted); font-size: 14px;">Expenses</span>
                <span class="amount expense" style="font-size: 16px; font-weight: 600;" x-text="formatCurrency(data?.monthly_summary?.expenses)"></span>
            </div>
            <div class="row">
                <span style="font-weight: 600; font-size: 14px;">Net</span>
                <span style="font-size: 16px; font-weight: 700;" :style="parseFloat(data?.monthly_summary?.net || 0) >= 0 ? 'color: var(--color-income)' : 'color: var(--color-expense)'" x-text="formatCurrency(data?.monthly_summary?.net)"></span>
            </div>
        </div>

        <!-- Upcoming payments -->
        <template x-if="data?.upcoming_payments?.length > 0">
            <div>
                <div class="section-title">Upcoming payments</div>
                <div class="card">
                    <template x-for="payment in (data?.upcoming_payments || []).slice(0, 5)" :key="payment.id">
                        <div class="row">
                            <div>
                                <div style="font-size: 14px; font-weight: 500;" x-text="payment.debt?.name || payment.concept"></div>
                                <div style="font-size: 12px; color: var(--color-muted);" x-text="formatDate(payment.due_date)"></div>
                            </div>
                            <span style="font-weight: 600; font-size: 14px; color: var(--color-expense);" x-text="formatCurrency(payment.amount)"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Active debts count -->
        <div class="card" x-show="data?.active_debts_count > 0">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 14px; color: var(--color-muted);">Active debts</span>
                <span style="font-size: 18px; font-weight: 700;" x-text="data?.active_debts_count"></span>
            </div>
        </div>
    </div>
</div>
@endsection
