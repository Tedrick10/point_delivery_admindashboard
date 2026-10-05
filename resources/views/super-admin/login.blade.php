<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('message.sa_super_admin') }} — {{ appCopy('admin', 'title') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}">
    <link href="{{ asset('frontend-website/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sa-ink: #14110F;
            --sa-accent: #FE6F07;
            --sa-accent-dark: #E05F00;
            --sa-accent-light: #FF8A3D;
            --sa-glow: rgba(254, 111, 7, 0.22);
            --sa-soft: rgba(254, 111, 7, 0.1);
            --sa-muted: rgba(20, 17, 15, 0.55);
            --sa-line: rgba(20, 17, 15, 0.1);
        }
        * { box-sizing: border-box; }
        body.sa-login {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', 'Noto Sans Myanmar', system-ui, sans-serif;
            color: var(--sa-ink);
            background:
                radial-gradient(ellipse 90% 70% at 50% -10%, rgba(254, 111, 7, 0.18), transparent 55%),
                radial-gradient(ellipse 45% 40% at 100% 100%, rgba(254, 111, 7, 0.08), transparent 50%),
                linear-gradient(180deg, #F7F4F0 0%, #FFFFFF 55%, #F3F0EB 100%);
            display: grid;
            place-items: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        body.sa-login::before,
        body.sa-login::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none;
            z-index: 0;
        }
        body.sa-login::before {
            width: 380px;
            height: 380px;
            top: -100px;
            left: -60px;
            background: rgba(254, 111, 7, 0.18);
        }
        body.sa-login::after {
            width: 300px;
            height: 300px;
            bottom: -80px;
            right: -40px;
            background: rgba(254, 111, 7, 0.12);
        }

        .sa-shell {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            animation: saRise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes saRise {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }

        body.sa-login .sa-card {
            background: #fff;
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 24px;
            padding: 2rem 1.75rem 1.5rem;
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.65) inset,
                0 24px 56px rgba(20, 17, 15, 0.08);
            position: relative;
            overflow: hidden;
        }
        body.sa-login .sa-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--sa-accent-dark), var(--sa-accent), var(--sa-accent-light));
        }

        .sa-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1.35rem;
            text-align: center;
        }
        .sa-mark {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: #FE6F07;
            color: #fff;
            font-size: 1.15rem;
            box-shadow: 0 10px 24px var(--sa-glow);
        }
        .sa-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--sa-accent);
            margin: 0;
        }
        .sa-title {
            font-size: 1.7rem;
            text-align: center;
            margin: 0.15rem 0 0;
            letter-spacing: -0.03em;
            font-weight: 700;
            line-height: 1.15;
            color: var(--sa-ink);
        }
        .sa-sub {
            text-align: center;
            color: var(--sa-muted);
            font-size: 0.9rem;
            margin: 0.45rem 0 0;
            line-height: 1.5;
            max-width: 32ch;
        }

        .sa-field { margin-bottom: 0.9rem; }
        .sa-field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #374151;
            letter-spacing: 0.01em;
        }
        .sa-input-wrap {
            position: relative;
        }
        .sa-field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 1;
        }
        .sa-field input {
            width: 100%;
            height: 50px;
            border: 1px solid var(--sa-line);
            border-radius: 14px;
            padding: 0 1rem 0 2.55rem;
            font-size: 0.9375rem;
            background: #f8fafc;
            color: var(--sa-ink);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }
        .sa-field input::placeholder { color: #9ca3af; }
        .sa-field input:hover { border-color: rgba(15, 23, 42, 0.18); }
        .sa-field input:focus {
            outline: none;
            background: #fff;
            border-color: var(--sa-accent);
            box-shadow: 0 0 0 4px var(--sa-glow);
        }
        .sa-input-wrap input.has-toggle {
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

        .sa-remember {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0.15rem 0 1.15rem;
            font-size: 0.84rem;
            color: var(--sa-muted);
            cursor: pointer;
            user-select: none;
        }
        .sa-remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: var(--sa-accent);
            cursor: pointer;
        }

        .sa-btn {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 14px;
            background: #FE6F07;
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
            transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow:
                0 10px 26px var(--sa-glow);
        }
        .sa-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.03);
            box-shadow: 0 14px 32px var(--sa-glow);
            color: #fff;
        }
        .sa-btn:active { transform: translateY(0); }

        .sa-foot {
            margin-top: 1.35rem;
            padding-top: 1.15rem;
            border-top: 1px solid var(--sa-line);
            text-align: center;
            font-size: 0.82rem;
            color: var(--sa-muted);
        }
        .sa-foot a {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-left: 0.2rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: var(--sa-soft);
            color: var(--sa-accent-dark);
            font-weight: 700;
            text-decoration: none;
            transition: background 0.15s ease, transform 0.15s ease;
        }
        .sa-foot a:hover {
            background: rgba(254, 111, 7, 0.16);
            transform: translateY(-1px);
        }

        body.sa-login .alert {
            border: none;
            border-radius: 14px;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        body.sa-login .alert-danger {
            background: linear-gradient(180deg, #fff5f5 0%, #ffe8e8 100%);
            color: #9f1239;
            box-shadow: inset 0 0 0 1px rgba(225, 29, 72, 0.14);
        }

        @media (max-width: 480px) {
            body.sa-login .sa-card {
                padding: 1.65rem 1.25rem 1.25rem;
                border-radius: 20px;
            }
            .sa-title { font-size: 1.45rem; }
        }
    </style>
</head>
<body class="sa-login">
    <div class="sa-shell">
        <div class="sa-card">
            <div class="sa-brand">
                <div class="sa-mark" aria-hidden="true">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <p class="sa-eyebrow">{{ __('message.sa_network_control') }}</p>
                    <h1 class="sa-title">{{ __('message.sa_super_admin') }}</h1>
                    <p class="sa-sub">{{ __('message.sa_login_sub') }}</p>
                </div>
            </div>

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
                    <div class="sa-input-wrap">
                        <i class="fas fa-envelope sa-field-icon" aria-hidden="true"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="superadmin@admin.com">
                    </div>
                </div>
                <div class="sa-field">
                    <label for="password">{{ __('message.password') }}</label>
                    <div class="sa-input-wrap">
                        <i class="fas fa-lock sa-field-icon" aria-hidden="true"></i>
                        <input id="password" type="password" name="password" class="password has-toggle" required autocomplete="current-password" placeholder="Enter your password">
                        <i class="sa-toggle-password fas fa-eye-slash" role="button" tabindex="0" aria-label="Show password"></i>
                    </div>
                </div>
                <label class="sa-remember">
                    <input type="checkbox" name="remember" value="1"> {{ __('message.sa_remember_me') }}
                </label>
                <button type="submit" class="sa-btn">{{ __('message.sa_enter_panel') }}</button>
            </form>
            <div class="sa-foot">
                {{ __('message.sa_branch_admin_q') }}
                <a href="{{ route('admin-login') }}">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    {{ __('message.sa_admin_hub_login') }}
                </a>
            </div>
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
