@extends('mobile.layouts.app', ['title' => 'Debts', 'showBack' => false])

@section('content')
<div
    class="screen-body"
    x-data="{
        token: '{{ $apiToken }}',
        apiBase: '{{ $apiBaseUrl }}',
        loading: true,
        debts: [],
        upcoming: [],
        workspace: null,
        activeTab: 'debts',

        async init() {
            const res = await fetch(this.apiBase + '/user', {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.status === 401) { window.location.href = '{{ route('mobile.login') }}'; return; }
            const json = await res.json();
            this.workspace = json.data.active_workspace;
            await Promise.all([this.loadDebts(), this.loadUpcoming()]);
        },

        async loadDebts() {
            const res = await fetch(this.apiBase + '/workspaces/' + this.workspace.id + '/debts', {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const json = await res.json();
                this.debts = json.data;
            }
            this.loading = false;
        },

        async loadUpcoming() {
            const res = await fetch(this.apiBase + '/workspaces/' + this.workspace.id + '/debts/upcoming?days=60', {
                headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const json = await res.json();
                this.upcoming = json.data;
            }
        },

        totalRemaining() {
            return this.debts.reduce((sum, d) => sum + parseFloat(d.remaining_amount || 0), 0);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: this.workspace?.currency || 'MXN', minimumFractionDigits: 2 }).format(parseFloat(amount || 0));
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('es-MX', { month: 'short', day: 'numeric' });
        },

        daysUntil(d) {
            const diff = Math.ceil((new Date(d) - new Date()) / (1000 * 60 * 60 * 24));
            if (diff < 0) return 'overdue';
            if (diff === 0) return 'today';
            return diff + 'd';
        },

        urgencyColor(d) {
            const diff = Math.ceil((new Date(d) - new Date()) / (1000 * 60 * 60 * 24));
            if (diff <= 3) return 'var(--color-expense)';
            if (diff <= 7) return 'var(--color-warning)';
            return 'var(--color-muted)';
        }
    }"
>
    <div x-show="loading" x-cloak><div class="spinner"></div></div>

    <div x-show="!loading" x-cloak>
        <!-- Total remaining -->
        <div class="card" style="background:var(--color-primary);color:white;margin-bottom:16px;">
            <div style="font-size:12px;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;opacity:0.8;margin-bottom:4px;">Total Remaining</div>
            <div style="font-size:28px;font-weight:800;letter-spacing:-0.5px;" x-text="formatCurrency(totalRemaining())"></div>
            <div style="font-size:12px;opacity:0.7;margin-top:4px;" x-text="debts.length + ' active debt' + (debts.length !== 1 ? 's' : '')"></div>
        </div>

        <!-- Tabs -->
        <div style="display:flex;gap:8px;margin-bottom:16px;">
            <button class="btn" :class="activeTab === 'debts' ? 'btn-primary' : 'btn-secondary'" style="flex:1;padding:8px;" @click="activeTab='debts'">All Debts</button>
            <button class="btn" :class="activeTab === 'upcoming' ? 'btn-primary' : 'btn-secondary'" style="flex:1;padding:8px;position:relative;" @click="activeTab='upcoming'">
                Upcoming
                <span x-show="upcoming.length > 0" style="position:absolute;top:-4px;right:-4px;background:var(--color-expense);color:white;border-radius:50%;width:16px;height:16px;font-size:10px;display:flex;align-items:center;justify-content:center;" x-text="upcoming.length"></span>
            </button>
        </div>

        <!-- Debts list -->
        <div x-show="activeTab === 'debts'">
            <template x-if="debts.length === 0">
                <div class="card" style="text-align:center;color:var(--color-muted);padding:32px;">
                    No active debts. 🎉
                </div>
            </template>

            <template x-for="debt in debts" :key="debt.id">
                <div class="card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                        <div>
                            <div style="font-size:15px;font-weight:600;" x-text="debt.name"></div>
                            <div style="font-size:12px;color:var(--color-muted);margin-top:2px;" x-text="debt.remaining_installments + ' of ' + debt.total_installments + ' installments left'"></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:14px;font-weight:700;color:var(--color-expense);" x-text="formatCurrency(debt.remaining_amount)"></div>
                            <div style="font-size:11px;color:var(--color-muted);" x-text="formatCurrency(debt.installment_amount) + '/mo'"></div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="progress-bar">
                        <div class="progress-fill" :style="'width:' + (debt.paid_installments / debt.total_installments * 100) + '%'"></div>
                    </div>

                    <template x-if="debt.next_installment">
                        <div style="margin-top:8px;font-size:12px;color:var(--color-muted);">
                            Next: <span x-text="formatDate(debt.next_installment?.due_date)"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Upcoming payments -->
        <div x-show="activeTab === 'upcoming'">
            <template x-if="upcoming.length === 0">
                <div class="card" style="text-align:center;color:var(--color-muted);padding:32px;">
                    No payments due in the next 60 days.
                </div>
            </template>

            <div class="card" x-show="upcoming.length > 0">
                <template x-for="installment in upcoming" :key="installment.id">
                    <div class="row">
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:500;" x-text="installment.debt?.name"></div>
                            <div style="font-size:12px;color:var(--color-muted);">
                                Installment <span x-text="installment.installment_number"></span> · <span x-text="formatDate(installment.due_date)"></span>
                            </div>
                        </div>
                        <div style="text-align:right;margin-left:12px;">
                            <div style="font-size:14px;font-weight:600;color:var(--color-expense);" x-text="formatCurrency(installment.amount)"></div>
                            <div style="font-size:11px;font-weight:500;" :style="'color:' + urgencyColor(installment.due_date)" x-text="daysUntil(installment.due_date)"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
