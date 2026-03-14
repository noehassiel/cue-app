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
            --color-bg: #F9FAFB;
            --color-surface: #FFFFFF;
            --color-text: #111827;
            --color-muted: #6B7280;
            --color-border: #E5E7EB;
            --color-error: #EF4444;
            --radius-sm: 8px;
            --radius-md: 12px;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .auth-screen {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 48px 24px 32px;
        }
        .auth-logo {
            font-size: 32px;
            font-weight: 800;
            color: var(--color-primary);
            letter-spacing: -1px;
            margin-bottom: 8px;
        }
        .auth-tagline {
            font-size: 15px;
            color: var(--color-muted);
            margin-bottom: 40px;
        }
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--color-muted);
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 16px;
            color: var(--color-text);
            background: var(--color-surface);
            outline: none;
            transition: border-color 0.15s;
        }
        .form-input:focus { border-color: var(--color-primary); }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .btn:active { opacity: 0.75; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-primary { background: var(--color-primary); color: white; }
        .btn-block { width: 100%; }
        .error-msg {
            color: var(--color-error);
            font-size: 13px;
            margin-top: 4px;
        }
        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: var(--color-muted);
        }
        .auth-footer a {
            color: var(--color-primary);
            font-weight: 500;
            text-decoration: none;
        }
        [x-cloak] { display: none; }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')
    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @stack('scripts')
</body>
</html>
