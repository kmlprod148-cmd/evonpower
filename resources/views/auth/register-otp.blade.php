<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vérification SMS - EvonPower</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        :root { --ev-green: #10b981; --ev-green-d: #059669; --ev-dark: #0f172a; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0; min-height: 100vh;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            display: flex; align-items: center; justify-content: center;
            padding: 1rem;
        }
        .auth-card {
            background: #fff; border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
            padding: 2.5rem 2rem; width: 100%; max-width: 440px;
        }
        .auth-header { text-align: center; margin-bottom: 1.5rem; }
        .auth-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 56px; height: 56px; border-radius: 14px;
            background: linear-gradient(135deg, var(--ev-green), #34d399);
            margin-bottom: 1rem; color: #fff;
        }
        .auth-title { font-size: 1.5rem; font-weight: 800; color: var(--ev-dark); margin: 0 0 .25rem; letter-spacing: -.02em; }
        .auth-sub { color: #64748b; font-size: .9375rem; margin: 0; }
        .phone-pill {
            display: inline-flex; align-items: center; gap: .375rem;
            margin-top: .5rem; padding: .25rem .625rem;
            background: #ecfdf5; color: #065f46;
            border-radius: 999px; font-size: .8125rem; font-weight: 600;
        }
        .otp-input {
            width: 100%; height: 58px;
            font-size: 1.5rem; font-weight: 700; letter-spacing: .5em;
            text-align: center; text-indent: .25em;
            border: 1.5px solid #e2e8f0; border-radius: .75rem;
            background: #f8fafc; color: var(--ev-dark);
            outline: none;
            transition: border-color .2s, background-color .2s, box-shadow .2s;
            margin: 1rem 0 .5rem;
        }
        .otp-input:focus {
            border-color: var(--ev-green); background: #fff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, .15);
        }
        .otp-input.invalid { border-color: #ef4444; background: #fef2f2; }
        .field-err { color: #ef4444; font-size: .8125rem; margin: 0 0 .5rem; text-align: center; }
        .btn-primary {
            width: 100%; height: 48px;
            background: linear-gradient(135deg, var(--ev-green), var(--ev-green-d));
            color: #fff; font-weight: 700; font-size: .9375rem;
            border: 0; border-radius: .625rem; cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, .35);
            transition: transform .15s, box-shadow .2s;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(16, 185, 129, .45); }
        .btn-primary:disabled { opacity: .65; cursor: not-allowed; transform: none; }
        .resend-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 1.25rem; font-size: .8125rem; color: #64748b;
        }
        .resend-btn {
            background: none; border: 0; padding: 0;
            color: var(--ev-green-d); font-weight: 600; font-size: .8125rem;
            cursor: pointer; text-decoration: underline;
        }
        .resend-btn:disabled { color: #94a3b8; cursor: not-allowed; text-decoration: none; }
        .alert {
            padding: .75rem .875rem; border-radius: .5rem;
            font-size: .8125rem; margin-bottom: 1rem;
        }
        .alert-info { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-err  { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
        .alert-dev  { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; font-family: ui-monospace, monospace; }
        .back-link {
            display: inline-block; margin-top: 1rem;
            color: #64748b; font-size: .8125rem; text-decoration: none;
        }
        .back-link:hover { color: var(--ev-dark); }
    </style>
</head>
<body>
    <main class="auth-card" role="main">
        <header class="auth-header">
            <div class="auth-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/>
                </svg>
            </div>
            <h1 class="auth-title">Vérifiez votre numéro</h1>
            <p class="auth-sub">Nous avons envoyé un code à 6 chiffres à</p>
            <div class="phone-pill">{{ $phone }}</div>
        </header>

        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @if (session('dev_otp_code'))
            <div class="alert alert-dev">
                <strong>Code de test (mode dev) :</strong> {{ session('dev_otp_code') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.verify') }}" id="otpForm" novalidate>
            @csrf
            <label for="code" style="display:block;text-align:center;font-size:.8125rem;font-weight:600;color:#334155">
                Code de vérification
            </label>
            <input type="text" name="code" id="code"
                   class="otp-input @error('code') invalid @enderror"
                   inputmode="numeric"
                   pattern="[0-9]{6}"
                   maxlength="6"
                   autocomplete="one-time-code"
                   placeholder="••••••"
                   required autofocus>
            @error('code') <p class="field-err">{{ $message }}</p> @enderror

            <button type="submit" class="btn-primary" id="verifyBtn">Vérifier et créer mon compte</button>
        </form>

        <div class="resend-row">
            <span>Pas reçu&nbsp;?</span>
            <form method="POST" action="{{ route('register.resend') }}" style="display:inline">
                @csrf
                <button type="submit" class="resend-btn" id="resendBtn">Renvoyer le code</button>
            </form>
        </div>

        <a href="{{ route('register') }}" class="back-link">← Modifier mon numéro</a>
    </main>

    <script>
        (function () {
            // Auto-submit when 6 digits are typed.
            var code = document.getElementById('code');
            var form = document.getElementById('otpForm');
            code.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                if (this.value.length === 6) form.submit();
            });
            form.addEventListener('submit', function () {
                document.getElementById('verifyBtn').disabled = true;
            });

            // 30-second cooldown countdown on the resend button.
            var btn = document.getElementById('resendBtn');
            var seconds = 30;
            btn.disabled = true;
            var orig = btn.textContent;
            var timer = setInterval(function () {
                btn.textContent = orig + ' (' + seconds + 's)';
                if (--seconds < 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = orig;
                }
            }, 1000);
        })();
    </script>
</body>
</html>
