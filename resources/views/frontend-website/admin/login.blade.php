<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ mighty_language_direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('message.login') }} — {{ SettingData('app_content', 'app_name') ?? config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}">
    <link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"/>
    <link href="{{ asset('frontend-website/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('frontend-website/assets/css/animations.css') }}">
    @if(mighty_language_direction() == 'rtl')
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif
    <style>
        :root {
            --site-color: {{ $themeColor }};
            --site-color-dark: color-mix(in srgb, var(--site-color) 78%, #000);
            --site-color-soft: color-mix(in srgb, var(--site-color) 10%, #fff);
            --site-color-glow: color-mix(in srgb, var(--site-color) 22%, transparent);
            --login-ink: #14110f;
            --login-muted: #7a7168;
            --login-line: rgba(20, 17, 15, 0.09);
        }

        * { box-sizing: border-box; }

        body.admin-login-page {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', 'Noto Sans Myanmar', system-ui, sans-serif;
            color: var(--login-ink);
            background:
                radial-gradient(ellipse 90% 70% at 50% -10%, color-mix(in srgb, var(--site-color) 45%, transparent), transparent 55%),
                radial-gradient(ellipse 50% 40% at 100% 100%, color-mix(in srgb, var(--site-color) 18%, transparent), transparent 55%),
                radial-gradient(ellipse 40% 35% at 0% 80%, rgba(255, 255, 255, 0.06), transparent 50%),
                linear-gradient(160deg, #120e0b 0%, #1f1510 42%, #2c1a12 100%);
            display: grid;
            place-items: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        body.admin-login-page::before,
        body.admin-login-page::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
            z-index: 0;
        }
        body.admin-login-page::before {
            width: 420px;
            height: 420px;
            top: -120px;
            right: -80px;
            background: color-mix(in srgb, var(--site-color) 28%, transparent);
        }
        body.admin-login-page::after {
            width: 320px;
            height: 320px;
            bottom: -100px;
            left: -60px;
            background: rgba(255, 180, 100, 0.12);
        }

        .login-shell {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            animation: loginRise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes loginRise {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }

        .login-card {
            background: #fff;
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 24px;
            padding: 2rem 1.75rem 1.5rem;
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.65) inset,
                0 24px 56px rgba(0, 0, 0, 0.32);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--site-color-dark), var(--site-color), #ffb347);
        }

        .login-card__brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1.35rem;
            text-align: center;
        }

        .login-card__mark {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, var(--site-color) 0%, var(--site-color-dark) 100%);
            color: #fff;
            font-size: 1.15rem;
            box-shadow: 0 10px 24px var(--site-color-glow);
        }

        .login-card__app-name {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--site-color);
        }

        .login-card__title {
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin: 0.15rem 0 0;
            color: var(--login-ink);
            line-height: 1.15;
        }

        .login-card__subtitle {
            margin: 0.45rem 0 0;
            color: var(--login-muted);
            font-size: 0.9rem;
            line-height: 1.5;
            max-width: 28ch;
        }

        .login-field {
            margin-bottom: 0.9rem;
        }

        .login-field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #564e47;
            margin-bottom: 0.4rem;
            letter-spacing: 0.01em;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input-wrap > .login-field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a89f96;
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 1;
        }

        .login-input {
            width: 100%;
            height: 50px;
            padding: 0 1rem 0 2.55rem;
            border: 1px solid var(--login-line);
            border-radius: 14px;
            background: #faf8f6;
            font-size: 0.9375rem;
            color: var(--login-ink);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .login-input::placeholder {
            color: #b0a79e;
        }

        .login-input:hover {
            border-color: rgba(20, 17, 15, 0.16);
        }

        .login-input:focus {
            outline: none;
            background: #fff;
            border-color: var(--site-color);
            box-shadow: 0 0 0 4px var(--site-color-glow);
        }

        .login-input-wrap .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #948b82;
            cursor: pointer;
            font-size: 0.9rem;
            transition: color 0.2s ease;
            z-index: 2;
        }

        .login-input-wrap .toggle-password:hover {
            color: var(--site-color);
        }

        .login-input-wrap .login-input.has-toggle {
            padding-right: 2.75rem;
        }

        .login-meta {
            display: flex;
            justify-content: flex-end;
            margin: 0.1rem 0 1.15rem;
        }

        .login-meta a {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--site-color);
            text-decoration: none;
        }

        .login-meta a:hover {
            color: var(--site-color-dark);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .login-submit {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--site-color-dark) 0%, var(--site-color) 55%, var(--site-color) 100%);
            color: #fff;
            font-size: 0.98rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow:
                0 10px 26px var(--site-color-glow),
                inset 0 1px 0 rgba(255, 255, 255, 0.28);
        }

        .login-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.03);
            box-shadow: 0 14px 32px var(--site-color-glow);
        }

        .login-submit:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 1.35rem;
            padding-top: 1.15rem;
            border-top: 1px solid var(--login-line);
            text-align: center;
            font-size: 0.78rem;
            color: var(--login-muted);
        }

        .login-card .alert {
            border: none;
            border-radius: 14px;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        .login-card .alert-danger {
            background: linear-gradient(180deg, #fff8f2 0%, #ffefe6 100%);
            color: #9f1239;
            box-shadow: inset 0 0 0 1px rgba(225, 29, 72, 0.12);
        }

        .login-card .font-medium.text-sm.text-green-600,
        .login-card .login-status-ok {
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 14px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
            color: #047857 !important;
            box-shadow: inset 0 0 0 1px rgba(5, 150, 105, 0.16);
        }

        .forgotModal-modalcontent {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }

        .forgotModal-modalcontent .modal-header {
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .forgotModal-modalcontent .modal-body {
            padding: 0 1.5rem 1.5rem;
        }

        .forgotModal-form {
            height: 48px;
            border-radius: 12px;
            border: 1px solid #f0e6dc;
        }

        .forgotModal-form:focus {
            border-color: var(--site-color);
            box-shadow: 0 0 0 4px var(--site-color-glow);
        }

        .forgot-submit-btn {
            background: var(--site-color) !important;
            border-radius: 10px;
            padding: 0.55rem 1.25rem !important;
        }

        .forgot-cancle-btn {
            border-radius: 10px;
            border: 1px solid #f0e6dc;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 1.65rem 1.25rem 1.25rem;
                border-radius: 20px;
            }

            .login-card__title {
                font-size: 1.45rem;
            }
        }
    </style>
</head>

<body class="admin-login-page">

    <div class="login-shell">
        <div class="login-card">
            <div class="login-card__brand">
                <div class="login-card__mark" aria-hidden="true">
                    <i class="fas fa-store"></i>
                </div>
                <div>
                    <div class="login-card__app-name">{{ SettingData('app_content', 'app_name') }}</div>
                    <h1 class="login-card__title">Branch Admin</h1>
                    <p class="login-card__subtitle">{{ __('message.Please_enter_your_login_credentials') }}</p>
                </div>
            </div>

            <x-auth-session-status class="mb-3" :status="session('status')" />
            <x-auth-validation-errors class="mb-3" :errors="$errors" />

            <form method="POST" action="{{ route('login.store') }}" data-toggle="validator">
                @csrf
                <input type="hidden" name="admin_login" value="admin_login">

                <div class="login-field">
                    <label for="loginEmail">{{ __('message.email') }}</label>
                    <div class="login-input-wrap">
                        <i class="fas fa-envelope login-field-icon" aria-hidden="true"></i>
                        <input type="email"
                               class="login-input"
                               id="loginEmail"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="admin@example.com"
                               required
                               autofocus>
                    </div>
                </div>

                <div class="login-field">
                    <label for="loginPassword">{{ __('message.password') }}</label>
                    <div class="login-input-wrap">
                        <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                        <input type="password"
                               name="password"
                               id="loginPassword"
                               class="login-input password has-toggle"
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <i class="toggle-password fas fa-eye-slash forgot-togglePassword" role="button" tabindex="0" aria-label="Show password"></i>
                    </div>
                </div>

                <div class="login-meta">
                    <a href="{{ route('auth.recover-password') }}"
                       data-bs-toggle="modal"
                       data-bs-target="#forgotModal">{{ __('message.forgot_password') }}</a>
                </div>

                <button type="submit" class="login-submit">{{ __('message.login') }}</button>
            </form>

            <div class="login-footer">
                &copy; {{ date('Y') }} {{ SettingData('app_content', 'app_name') }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content forgotModal-modalcontent">
                <div class="modal-header border-bottom-0">
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="forgotModalModalLabel">{{ __('message.forgot_password') }}</h5>
                        <p class="mt-2 mb-0 text-muted small">{{ __('message.reset_your_password_and_regain_access_with_ease') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body forgotModal-modalbody">
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label fw-semibold">{{ __('message.email') }}</label>
                            <input type="email" name="email" class="form-control forgotModal-form" id="forgotEmail" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn forgot-cancle-btn" data-bs-dismiss="modal">{{ __('message.cancel') }}</button>
                            <button type="submit" class="btn text-white forgot-submit-btn">{{ __('message.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('frontend-website/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('frontend-website/assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('frontend-website/assets/js/bootstrap.min.js') }}"></script>
    <script>
        document.querySelectorAll('.forgot-togglePassword').forEach((toggle) => {
            const flip = () => {
                const input = toggle.closest('.login-input-wrap').querySelector('.password');
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                toggle.classList.toggle('fa-eye-slash', !isPassword);
                toggle.classList.toggle('fa-eye', isPassword);
                toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            };
            toggle.addEventListener('click', flip);
            toggle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    flip();
                }
            });
        });
    </script>
</body>
</html>
