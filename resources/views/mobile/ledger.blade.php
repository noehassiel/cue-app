@extends('mobile.layouts.app', ['title' => 'Ledger', 'showBack' => false])

@section('content')
<div
    class="screen-body"
    x-data="{
        token: '{{ $apiToken }}',
        apiBase: '{{ $apiBaseUrl }}',
        loading: true,
        transactions: [],
        workspace: null,
        currentMonth: new Date().toISOString().substring(0, 7),
        filter: 'all',

        async init() {
            await this.loadWorkspace();
        },

        async loadWorkspace() {
            const res = await fetch(this.apiBase + '/user', {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.status === 401) { window.location.href = '{{ route('mobile.login') }}'; return; }
            const json = await res.json();
            this.workspace = json.data.active_workspace;
            await this.loadTransactions();
        },

        async loadTransactions() {
            this.loading = true;
            let url = this.apiBase + '/workspaces/' + this.workspace.id + '/transactions?month=' + this.currentMonth + '&per_page=50';
            if (this.filter !== 'all') url += '&type=' + this.filter;
            const res = await fetch(url, {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const json = await res.json();
                this.transactions = json.data;
            }
            this.loading = false;
        },

        monthLabel() {
            const [y, m] = this.currentMonth.split('-');
            return new Date(y, m - 1).toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
        },

        prevMonth() {
            const d = new Date(this.currentMonth + '-01');
            d.setMonth(d.getMonth() - 1);
            this.currentMonth = d.toISOString().substring(0, 7);
            this.loadTransactions();
        },

        nextMonth() {
            const d = new Date(this.currentMonth + '-01');
            d.setMonth(d.getMonth() + 1);
            this.currentMonth = d.toISOString().substring(0, 7);
            this.loadTransactions();
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: this.workspace?.currency || 'MXN', minimumFractionDigits: 2 }).format(parseFloat(amount || 0));
        },

        formatDate(d) {
            return new Date(d).toLocaleDateString('es-MX', { month: 'short', day: 'numeric' });
        }
    }"
>
    <!-- Month navigator -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <button @click="prevMonth" style="background:none;border:none;font-size:22px;color:var(--color-primary);cursor:pointer;">‹</button>
        <span style="font-weight: 600; font-size: 15px; text-transform: capitalize;" x-text="monthLabel()"></span>
        <button @click="nextMonth" style="background:none;border:none;font-size:22px;color:var(--color-primary);cursor:pointer;">›</button>
    </div>

    <!-- Filter tabs -->
    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
        <button class="btn" :class="filter === 'all' ? 'btn-primary' : 'btn-secondary'" style="flex:1;padding:8px;" @click="filter='all';loadTransactions()">All</button>
        <button class="btn" :class="filter === 'income' ? 'btn-primary' : 'btn-secondary'" style="flex:1;padding:8px;" @click="filter='income';loadTransactions()">Income</button>
        <button class="btn" :class="filter === 'expense' ? 'btn-primary' : 'btn-secondary'" style="flex:1;padding:8px;" @click="filter='expense';loadTransactions()">Expenses</button>
    </div>

    <!-- Loading -->
    <div x-show="loading" x-cloak><div class="spinner"></div></div>

    <!-- Transaction list -->
    <div x-show="!loading" x-cloak>
        <template x-if="transactions.length === 0">
            <div class="card" style="text-align:center;color:var(--color-muted);padding:32px;">
                No transactions for this period.
            </div>
        </template>

        <div class="card" x-show="transactions.length > 0">
            <template x-for="tx in transactions" :key="tx.id">
                <div class="row">
                    <div style="flex:1;">
                        <div style="font-size:14px;font-weight:500;" x-text="tx.concept"></div>
                        <div style="font-size:12px;color:var(--color-muted);">
                            <span x-text="formatDate(tx.projected_date)"></span>
                            <span x-show="tx.category" style="margin-left:6px;background:var(--color-bg);padding:1px 6px;border-radius:4px;font-size:11px;" x-text="tx.category"></span>
                            <span x-show="!tx.confirmed_at" style="margin-left:6px;color:var(--color-warning);font-size:11px;">projected</span>
                        </div>
                    </div>
                    <span
                        style="font-size:14px;font-weight:600;margin-left:12px;"
                        :style="tx.type === 'income' ? 'color:var(--color-income)' : 'color:var(--color-expense)'"
                        x-text="(tx.type === 'income' ? '+' : '-') + formatCurrency(tx.amount)"
                    ></span>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
