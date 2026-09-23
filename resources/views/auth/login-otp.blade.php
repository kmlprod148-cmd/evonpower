<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Code de connexion - EvonPower</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        :root {
            color-scheme: dark;
            --ink: #eef7ff;
            --muted: rgba(226, 237, 246, .72);
            --line: rgba(178, 222, 238, .22);
            --teal: #35f4c6;
            --lime: #b7f264;
            --panel: rgba(7, 17, 28, .82);
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 1rem;
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                linear-gradient(118deg, rgba(53, 244, 198, .14), transparent 32%),
                linear-gradient(292deg, rgba(255, 204, 102, .1), transparent 36%),
                linear-gradient(132deg, #07111c 0%, #0f2030 39%, #07151f 67%, #111827 100%);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .03) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .65), rgba(0, 0, 0, .08));
        }

        .otp-card {
            position: relative;
            width: min(100%, 28rem);
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: .5rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, .035)),
                var(--panel);
            backdrop-filter: blur(24px);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .48);
            overflow: hidden;
        }

        .accent {
            height: .28rem;
            background: linear-gradient(90deg, var(--teal), #66d9ff, var(--lime));
        }

        .body {
            padding: clamp(1.25rem, 5vw, 2rem);
        }

        h1 {
            margin: 0;
            color: #ffffff;
            font-size: clamp(1.55rem, 4vw, 2rem);
            line-height: 1.08;
            letter-spacing: 0;
        }

        p {
            margin: .75rem 0 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .phone-pill {
            display: inline-flex;
            margin-top: 1rem;
            padding: .45rem .7rem;
            border: 1px solid rgba(53, 244, 198, .3);
            border-radius: 999px;
            color: #d8fff5;
            background: rgba(53, 244, 198, .1);
            font-size: .86rem;
            font-weight: 800;
        }

        .alert {
            margin-top: 1rem;
            padding: .75rem .85rem;
            border-radius: .45rem;
            font-size: .85rem;
            line-height: 1.4;
        }

        .alert-info { color: #d8fff5; background: rgba(53, 244, 198, .1); border: 1px solid rgba(53, 244, 198, .24); }
        .alert-dev { color: #fff0ce; background: rgba(255, 204, 102, .12); border: 1px solid rgba(255, 204, 102, .28); }
        .field-error { color: #ffc1d1; font-size: .83rem; margin-top: .55rem; }

        label {
            display: block;
            margin-top: 1.35rem;
            color: rgba(238, 247, 255, .82);
            font-size: .85rem;
            font-weight: 730;
        }

        .otp-input {
            width: 100%;
            min-height: 3.35rem;
            margin-top: .45rem;
            padding: .75rem 1rem;
            border: 1px solid rgba(206, 231, 240, .18);
            border-radius: .45rem;
            color: #ffffff;
            background: rgba(1, 8, 14, .52);
            outline: none;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: .45em;
            text-align: center;
            text-indent: .45em;
        }

        .otp-input:focus {
            border-color: rgba(53, 244, 198, .72);
            box-shadow: 0 0 0 4px rgba(53, 244, 198, .12), 0 0 32px rgba(53, 244, 198, .16);
        }

        .primary-button {
            width: 100%;
            min-height: 3.15rem;
            margin-top: 1rem;
            border: 0;
            border-radius: .45rem;
            color: #051018;
            background: linear-gradient(90deg, var(--teal), #8cf7ff 47%, var(--lime));
            font-size: 1rem;
            font-weight: 850;
            cursor: pointer;
        }

        .secondary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }

        .link-button,
        .text-link {
            color: #9fecff;
            font-size: .86rem;
            font-weight: 760;
            text-decoration: none;
        }

        .link-button {
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        @media (max-width: 420px) {
            .secondary-row {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="otp-card" role="main">
        <div class="accent"></div>
        <section class="body">
            <h1>Code de connexion</h1>
            <p>Entrez le code SMS recu pour ouvrir votre espace client.</p>
            <div class="phone-pill">{{ $phone }}</div>

            @if (session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            @if (session('dev_otp_code'))
                <div class="alert alert-dev">
                    <strong>Code de test :</strong> {{ session('dev_otp_code') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.customer.verify') }}" id="otpForm" novalidate>
                @csrf
                <label for="code">Code a 6 chiffres</label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    class="otp-input"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    required
                    autofocus
                >
                @error('code')
                    <p class="field-error">{{ $message }}</p>
                @enderror

                <button type="submit" class="primary-button" id="verifyBtn">Verifier et se connecter</button>
            </form>

            <div class="secondary-row">
                <form method="POST" action="{{ route('login.customer.resend') }}">
                    @csrf
                    <button type="submit" class="link-button">Renvoyer le code</button>
                </form>
                <a href="{{ route('login') }}" class="text-link">Changer de numero</a>
            </div>
        </section>
    </main>

    <script>
        (function () {
            var code = document.getElementById('code');
            var form = document.getElementById('otpForm');
            var button = document.getElementById('verifyBtn');

            code.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                if (this.value.length === 6) {
                    form.submit();
                }
            });

            form.addEventListener('submit', function () {
                button.disabled = true;
            });
        })();
    </script>
</body>
</html>
