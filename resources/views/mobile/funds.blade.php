@extends('mobile.layouts.app', ['title' => 'Funds', 'showBack' => false])

@section('content')
<div
    class="screen-body"
    x-data="{
        token: '{{ $apiToken }}',
        apiBase: '{{ $apiBaseUrl }}',
        loading: true,
        funds: [],
        workspace: null,

        async init() {
            const res = await fetch(this.apiBase + '/user', {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.status === 401) { window.location.href = '{{ route('mobile.login') }}'; return; }
            const json = await res.json();
            this.workspace = json.data.active_workspace;
            await this.loadFunds();
        },

        async loadFunds() {
            this.loading = true;
            const res = await fetch(this.apiBase + '/workspaces/' + this.workspace.id + '/funds', {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const json = await res.json();
                this.funds = json.data;
            }
            this.loading = false;
        },

        totalAllocated() {
            return this.funds.reduce((sum, f) => sum + parseFloat(f.current_balance || 0), 0);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: this.workspace?.currency || 'MXN', minimumFractionDigits: 2 }).format(parseFloat(amount || 0));
        },

        progressWidth(fund) {
            if (!fund.target_amount || parseFloat(fund.target_amount) === 0) return '0%';
            return Math.min(100, (parseFloat(fund.current_balance) / parseFloat(fund.target_amount)) * 100) + '%';
        },

        formatDate(d) {
            if (!d) return null;
            return new Date(d).toLocaleDateString('es-MX', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }"
>
    <div x-show="loading" x-cloak><div class="spinner"></div></div>

    <div x-show="!loading" x-cloak>
        <!-- Total allocated -->
        <div class="card" style="background:var(--color-primary);color:white;margin-bottom:16px;">
            <div style="font-size:12px;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;opacity:0.8;margin-bottom:4px;">Total Allocated</div>
            <div style="font-size:28px;font-weight:800;letter-spacing:-0.5px;" x-text="formatCurrency(totalAllocated())"></div>
            <div style="font-size:12px;opacity:0.7;margin-top:4px;" x-text="funds.length + ' fund' + (funds.length !== 1 ? 's' : '')"></div>
        </div>

        <!-- Empty state -->
        <template x-if="funds.length === 0">
            <div class="card" style="text-align:center;color:var(--color-muted);padding:32px;">
                No funds yet. Create your first savings goal from the web app.
            </div>
        </template>

        <!-- Fund cards -->
        <template x-for="fund in funds" :key="fund.id">
            <div class="card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div x-show="fund.color" :style="'width:10px;height:10px;border-radius:50%;background:' + fund.color"></div>
                        <span style="font-size:15px;font-weight:600;" x-text="fund.name"></span>
                    </div>
                    <span style="font-size:14px;font-weight:700;color:var(--color-primary);" x-text="formatCurrency(fund.current_balance)"></span>
                </div>

                <template x-if="fund.target_amount">
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--color-muted);margin-bottom:4px;">
                            <span x-text="parseFloat(fund.progress_percentage || 0).toFixed(1) + '% of goal'"></span>
                            <span x-text="formatCurrency(fund.target_amount)"></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" :style="'width:' + progressWidth(fund)"></div>
                        </div>
                    </div>
                </template>

                <template x-if="fund.target_date">
                    <div style="font-size:12px;color:var(--color-muted);margin-top:6px;">
                        Goal by <span x-text="formatDate(fund.target_date)"></span>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
@endsection
