<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cue</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --color-primary: #6366F1;
            --color-primary-light: #EEF2FF;
            --color-bg: #F9FAFB;
            --color-surface: #FFFFFF;
            --color-text: #111827;
            --color-muted: #6B7280;
            --color-border: #E5E7EB;
            --color-income: #10B981;
            --color-expense: #EF4444;
            --color-warning: #F59E0B;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .screen {
            min-height: 100vh;
            padding-bottom: 80px; /* space for bottom nav */
            overflow-y: auto;
        }
        .screen-header {
            background: var(--color-surface);
            padding: 16px 20px;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .screen-header h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        .screen-body { padding: 16px 20px; }
        .card {
            background: var(--color-surface);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .card-title {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-muted);
            margin-bottom: 8px;
        }
        .amount {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .amount.income { color: var(--color-income); }
        .amount.expense { color: var(--color-expense); }
        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--color-border);
        }
        .row:last-child { border-bottom: none; }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-income { background: #D1FAE5; color: #065F46; }
        .badge-expense { background: #FEE2E2; color: #991B1B; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .btn:active { opacity: 0.75; }
        .btn-primary { background: var(--color-primary); color: white; }
        .btn-secondary { background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-border); }
        .btn-danger { background: #FEE2E2; color: var(--color-expense); }
        .btn-block { width: 100%; }
        .section-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 20px 0 8px;
        }
        .progress-bar {
            height: 6px;
            background: var(--color-border);
            border-radius: 100px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-fill {
            height: 100%;
            background: var(--color-primary);
            border-radius: 100px;
            transition: width 0.3s ease;
        }
        [x-cloak] { display: none; }
        .spinner {
            width: 32px; height: 32px;
            border: 3px solid var(--color-border);
            border-top-color: var(--color-primary);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin: 40px auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
    @stack('styles')
</head>
<body>
    <native-top-bar
        title="{{ $title ?? 'Cue' }}"
        :show-navigation-icon="{{ isset($showBack) && $showBack ? 'true' : 'false' }}"
        background-color="#FFFFFF"
        text-color="#111827"
        elevation="0"
    />

    <div class="screen">
        @yield('content')
    </div>

    <native-bottom-nav active-color="#6366F1">
        <native-bottom-nav-item
            id="dashboard"
            icon="house.fill"
            url="{{ route('mobile.dashboard') }}"
            label="Dashboard"
            :active="{{ request()->routeIs('mobile.dashboard') ? 'true' : 'false' }}"
        />
        <native-bottom-nav-item
            id="ledger"
            icon="list.bullet.rectangle"
            url="{{ route('mobile.ledger') }}"
            label="Ledger"
            :active="{{ request()->routeIs('mobile.ledger') ? 'true' : 'false' }}"
        />
        <native-bottom-nav-item
            id="funds"
            icon="folder.fill"
            url="{{ route('mobile.funds') }}"
            label="Funds"
            :active="{{ request()->routeIs('mobile.funds') ? 'true' : 'false' }}"
        />
        <native-bottom-nav-item
            id="debts"
            icon="creditcard.fill"
            url="{{ route('mobile.debts') }}"
            label="Debts"
            :active="{{ request()->routeIs('mobile.debts') ? 'true' : 'false' }}"
        />
        <native-bottom-nav-item
            id="settings"
            icon="gear"
            url="{{ route('mobile.settings') }}"
            label="Settings"
            :active="{{ request()->routeIs('mobile.settings') ? 'true' : 'false' }}"
        />
    </native-bottom-nav>

    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @stack('scripts')
</body>
</html>
