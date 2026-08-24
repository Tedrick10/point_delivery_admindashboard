<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('message.sa_super_admin') }} — {{ SettingData('app_content', 'app_name') ?? config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}">
    <link href="{{ asset('frontend-website/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"/>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root {
            --sa-ink: #0c1222;
            --sa-accent: #0d9488;
            --sa-accent-dark: #0f766e;
            --sa-glow: rgba(13, 148, 136, 0.22);
            --sa-paper: #f4f7f6;
            --sa-border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body.sa-login {
            margin: 0;
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            color: var(--sa-ink);
            background:
                radial-gradient(ellipse at 10% 0%, var(--sa-glow), transparent 50%),
                radial-gradient(ellipse at 90% 100%, rgba(15, 23, 42, 0.08), transparent 45%),
                linear-gradient(165deg, #0c1222 0%, #134e4a 48%, #0f766e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
        }
        .sa-login-lang { position: absolute; top: 1.25rem; right: 1.25rem; }
        .sa-lang { position: relative; }
        .sa-lang__btn {
            display: inline-flex; align-items: center; gap: 0.4rem; min-height: 34px;
            padding: 0.3rem 0.65rem 0.3rem 0.4rem; border: 1px solid rgba(255,255,255,0.25);
            border-radius: 999px; background: rgba(255,255,255,0.12); color: #fff;
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; cursor: pointer;
            backdrop-filter: blur(8px);
        }
        .sa-lang__flag {
            width: 20px; height: 20px; border-radius: 999px; overflow: hidden;
            display: grid; place-items: center; background: #f1f5f9;
        }
        .sa-lang__flag img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .sa-lang__chevron { font-size: 0.55rem; opacity: 0.8; }
        .sa-lang__menu {
            position: absolute; top: calc(100% + 0.4rem); right: 0; z-index: 40; width: 220px;
            padding: 0.45rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff;
            color: var(--sa-ink); box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
        }
        .sa-lang__menu-head {
            padding: 0.35rem 0.55rem 0.5rem; font-size: 0.68rem; font-weight: 800;
            letter-spacing: 0.06em; text-transform: uppercase; color: #94a3b8;
        }
        .sa-lang__list { list-style: none; margin: 0; padding: 0; }
        .sa-lang__item {
            display: flex; align-items: center; gap: 0.55rem; padding: 0.5rem 0.55rem;
            border-radius: 10px; color: inherit; text-decoration: none;
        }
        .sa-lang__item:hover { background: #f1f5f9; }
        .sa-lang__item.is-active { background: #ecfdf5; }
        .sa-lang__label { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.05rem; }
        .sa-lang__label strong { font-size: 0.84rem; font-weight: 700; color: #0f172a; }
        .sa-lang__label small { font-size: 0.7rem; color: #94a3b8; }
        .sa-lang__check { color: #0d9488; font-size: 0.75rem; }
        .sa-lang__empty { padding: 0.65rem; font-size: 0.8rem; color: #94a3b8; text-align: center; }
        .sa-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255,255,255,0.96);
            border-radius: 20px;
            padding: 2.25rem 2rem 1.75rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.28);
            animation: saRise 0.55s ease both;
        }
        @keyframes saRise {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: none; }
        }
        .sa-eyebrow {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--sa-accent);
            margin-bottom: 0.4rem;
            text-align: center;
        }
        .sa-title {
            font-family: 'Instrument Serif', Georgia, serif;
            font-size: 2rem;
            text-align: center;
            margin: 0 0 0.35rem;
            letter-spacing: -0.02em;
        }
        .sa-sub {
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .sa-field { margin-bottom: 1rem; }
        .sa-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: #334155;
        }
        .sa-field input {
            width: 100%;
            height: 48px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0 0.95rem;
            font-size: 0.95rem;
        }
        .sa-field input:focus {
            outline: none;
            border-color: var(--sa-accent);
            box-shadow: 0 0 0 4px var(--sa-glow);
        }
        .sa-input-wrap {
            position: relative;
        }
        .sa-input-wrap input {
            padding-right: 2.75rem;
        }
        .sa-toggle-password {
            position: absolute;
            right: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.95rem;
            line-height: 1;
            z-index: 2;
        }
        .sa-toggle-password:hover { color: var(--sa-accent-dark); }
        .sa-btn {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--sa-accent), var(--sa-accent-dark));
            color: #fff;
            font-weight: 700;
            margin-top: 0.5rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .sa-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px var(--sa-glow);
            color: #fff;
        }
        .sa-foot {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .sa-foot a { color: var(--sa-accent-dark); font-weight: 600; text-decoration: none; }
        .alert { border-radius: 12px; font-size: 0.875rem; }
    </style>
</head>
<body class="sa-login">
    <div class="sa-login-lang">
        @include('super-admin.partials.language-switcher')
    </div>
    <div class="sa-card">
        <div class="sa-eyebrow">{{ __('message.sa_network_control') }}</div>
        <h1 class="sa-title">{{ __('message.sa_super_admin') }}</h1>
        <p class="sa-sub">{{ __('message.sa_login_sub') }}</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('super-admin.login.store') }}">
            @csrf
            <div class="sa-field">
                <label for="email">{{ __('message.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="superadmin@admin.com">
            </div>
            <div class="sa-field">
                <label for="password">{{ __('message.password') }}</label>
                <div class="sa-input-wrap">
                    <input id="password" type="password" name="password" class="password" required autocomplete="current-password">
                    <i class="sa-toggle-password fas fa-eye-slash" role="button" tabindex="0" aria-label="Show password"></i>
                </div>
            </div>
            <label class="d-flex align-items-center gap-2 mb-2" style="font-size:0.85rem;color:#64748b;">
                <input type="checkbox" name="remember" value="1"> {{ __('message.sa_remember_me') }}
            </label>
            <button type="submit" class="sa-btn">{{ __('message.sa_enter_panel') }}</button>
        </form>
        <div class="sa-foot">
            {{ __('message.sa_branch_admin_q') }} <a href="{{ route('admin-login') }}">{{ __('message.sa_admin_hub_login') }}</a>
        </div>
    </div>
    <script>
        document.querySelectorAll('.sa-toggle-password').forEach(function (toggle) {
            function flip() {
                var input = toggle.closest('.sa-input-wrap').querySelector('.password');
                var isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                toggle.classList.toggle('fa-eye-slash', !isPassword);
                toggle.classList.toggle('fa-eye', isPassword);
                toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            }
            toggle.addEventListener('click', flip);
            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    flip();
                }
            });
        });
    </script>
</body>
</html>
