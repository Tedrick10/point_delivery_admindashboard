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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('frontend-website/assets/css/animations.css') }}">
    @if(mighty_language_direction() == 'rtl')
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif
    <style>
        :root {
            --site-color: {{ $themeColor }};
            --site-color-dark: color-mix(in srgb, var(--site-color) 80%, #000);
            --site-color-soft: color-mix(in srgb, var(--site-color) 10%, #fff);
            --site-color-glow: color-mix(in srgb, var(--site-color) 22%, transparent);
        }

        * { box-sizing: border-box; }

        body.admin-login-page {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #111827;
            background:
                radial-gradient(circle at 15% 20%, var(--site-color-glow), transparent 42%),
                radial-gradient(circle at 85% 80%, color-mix(in srgb, var(--site-color) 14%, transparent), transparent 40%),
                linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            padding: 2.5rem 2rem 2rem;
            box-shadow:
                0 1px 2px rgba(16, 24, 40, 0.04),
                0 12px 40px rgba(16, 24, 40, 0.08);
        }

        .login-card__brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .login-card__app-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.02em;
        }

        .login-card__title {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin: 0 0 0.4rem;
            text-align: center;
            color: #0f172a;
        }

        .login-card__subtitle {
            margin: 0 0 1.75rem;
            text-align: center;
            color: #64748b;
            font-size: 0.9375rem;
            line-height: 1.6;
        }

        .login-field {
            margin-bottom: 1rem;
        }

        .login-field label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input {
            width: 100%;
            height: 48px;
            padding: 0 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            font-size: 0.9375rem;
            color: #0f172a;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .login-input::placeholder {
            color: #94a3b8;
        }

        .login-input:focus {
            outline: none;
            border-color: var(--site-color);
            box-shadow: 0 0 0 4px var(--site-color-glow);
        }

        .login-input-wrap .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .login-input-wrap .toggle-password:hover {
            color: var(--site-color);
        }

        .login-input-wrap .login-input {
            padding-right: 2.75rem;
        }

        .login-meta {
            display: flex;
            justify-content: flex-end;
            margin: 0.15rem 0 1.35rem;
        }

        .login-meta a {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--site-color);
            text-decoration: none;
        }

        .login-meta a:hover {
            color: var(--site-color-dark);
        }

        .login-submit {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: var(--site-color);
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
            box-shadow: 0 8px 20px var(--site-color-glow);
        }

        .login-submit:hover {
            background: var(--site-color-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px var(--site-color-glow);
        }

        .modal.show .modal-dialog {
            animation: pdsModalIn 0.38s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .login-submit:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            font-size: 0.8125rem;
            color: #94a3b8;
        }

        .forgotModal-modalcontent {
            border: none;
            border-radius: 18px;
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
            border: 1px solid #e2e8f0;
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
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.25rem 1.5rem;
                border-radius: 20px;
            }

            .login-card__title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body class="admin-login-page">

    <div class="login-card hero-animate-in">
        <div class="login-card__brand">
            <div class="login-card__app-name">{{ SettingData('app_content', 'app_name') }}</div>
        </div>

        <h1 class="login-card__title">{{ __('message.login') }}</h1>
        <p class="login-card__subtitle">{{ __('message.Please_enter_your_login_credentials') }}</p>

        <x-auth-session-status class="mb-3" :status="session('status')" />
        <x-auth-validation-errors class="mb-3" :errors="$errors" />

        <form method="POST" action="{{ route('login.store') }}" data-toggle="validator">
            @csrf
            <input type="hidden" name="admin_login" value="admin_login">

            <div class="login-field">
                <label for="loginEmail">{{ __('message.email') }}</label>
                <input type="email"
                       class="login-input"
                       id="loginEmail"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="admin@example.com"
                       required
                       autofocus>
            </div>

            <div class="login-field">
                <label for="loginPassword">{{ __('message.password') }}</label>
                <div class="login-input-wrap">
                    <input type="password"
                           name="password"
                           id="loginPassword"
                           class="login-input password"
                           placeholder="Enter your password"
                           required
                           autocomplete="current-password">
                    <i class="toggle-password fas fa-eye-slash forgot-togglePassword"></i>
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
            toggle.addEventListener('click', function () {
                const input = this.closest('.login-input-wrap').querySelector('.password');
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                this.classList.toggle('fa-eye-slash', !isPassword);
                this.classList.toggle('fa-eye', isPassword);
            });
        });
    </script>
</body>
</html>
