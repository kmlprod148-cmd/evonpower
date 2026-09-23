<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - EvonPower</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        :root {
            color-scheme: dark;
            --ink: #eef7ff;
            --muted: rgba(226, 237, 246, .72);
            --line: rgba(178, 222, 238, .22);
            --panel: rgba(7, 17, 28, .74);
            --panel-strong: rgba(9, 21, 34, .92);
            --teal: #35f4c6;
            --cyan: #66d9ff;
            --lime: #b7f264;
            --amber: #ffcc66;
            --rose: #ff6f9a;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
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
            z-index: 1;
        }

        .auth-shell {
            position: relative;
            isolation: isolate;
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 460px);
            align-items: stretch;
        }

        .scene-stage {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        #model3d {
            width: 100%;
            height: 100%;
            display: block;
        }

        .brand-rail {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100vh;
            padding: clamp(1.25rem, 4vw, 3rem);
            pointer-events: none;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            width: fit-content;
            padding: .65rem .8rem .65rem .65rem;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 999px;
            background: rgba(5, 13, 22, .45);
            backdrop-filter: blur(18px);
            box-shadow: 0 16px 50px rgba(0, 0, 0, .28);
            pointer-events: auto;
        }

        .brand-mark img {
            width: 2.4rem;
            height: 2.4rem;
            object-fit: contain;
            border-radius: 999px;
            background: rgba(255, 255, 255, .92);
            padding: .25rem;
        }

        .brand-mark span {
            display: block;
            font-size: .78rem;
            color: var(--muted);
            line-height: 1.1;
        }

        .brand-mark strong {
            display: block;
            color: #ffffff;
            font-size: .98rem;
            line-height: 1.1;
        }

        .hero-copy {
            max-width: min(45rem, 62vw);
            margin-bottom: clamp(1rem, 6vh, 4.8rem);
            text-shadow: 0 12px 42px rgba(0, 0, 0, .56);
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            margin-bottom: 1rem;
            padding: .45rem .7rem;
            color: #d8fbff;
            font-size: .8rem;
            font-weight: 700;
            border: 1px solid rgba(102, 217, 255, .28);
            border-radius: 999px;
            background: rgba(4, 13, 22, .42);
            backdrop-filter: blur(14px);
        }

        .hero-kicker::before {
            content: "";
            width: .52rem;
            height: .52rem;
            border-radius: 50%;
            background: var(--teal);
            box-shadow: 0 0 16px var(--teal);
        }

        .hero-copy h1 {
            margin: 0;
            max-width: 9ch;
            color: #ffffff;
            font-size: clamp(3.1rem, 6.8vw, 6.3rem);
            line-height: .92;
            font-weight: 850;
        }

        .hero-copy p {
            max-width: 39rem;
            margin: 1.35rem 0 0;
            color: rgba(238, 247, 255, .76);
            font-size: clamp(1rem, 1.55vw, 1.28rem);
            line-height: 1.65;
        }

        .telemetry-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(8rem, 1fr));
            gap: .75rem;
            max-width: 44rem;
            margin-top: 1.8rem;
        }

        .telemetry-item {
            min-width: 0;
            padding: .9rem 1rem;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: .5rem;
            background: rgba(4, 13, 22, .48);
            backdrop-filter: blur(16px);
        }

        .telemetry-item span {
            display: block;
            color: rgba(238, 247, 255, .62);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .telemetry-item strong {
            display: block;
            margin-top: .28rem;
            color: #ffffff;
            font-size: clamp(1rem, 1.9vw, 1.35rem);
        }

        .auth-panel-wrap {
            position: relative;
            z-index: 3;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 4vw, 2rem);
        }

        .auth-panel {
            width: min(100%, 28.75rem);
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: .5rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, .035)),
                var(--panel);
            backdrop-filter: blur(28px);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .48);
            overflow: hidden;
        }

        .panel-accent {
            height: .28rem;
            background: linear-gradient(90deg, var(--teal), var(--cyan), var(--amber), var(--rose));
        }

        .panel-body {
            padding: clamp(1.25rem, 4vw, 2rem);
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.65rem;
        }

        .panel-header h2 {
            margin: 0;
            color: #ffffff;
            font-size: clamp(1.55rem, 3vw, 2.05rem);
            line-height: 1.05;
            font-weight: 820;
        }

        .panel-header p {
            margin: .55rem 0 0;
            color: var(--muted);
            line-height: 1.5;
            font-size: .94rem;
        }

        .security-state {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .42rem .58rem;
            border: 1px solid rgba(183, 242, 100, .32);
            border-radius: 999px;
            color: #ecffd5;
            background: rgba(183, 242, 100, .1);
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .security-state::before {
            content: "";
            width: .44rem;
            height: .44rem;
            border-radius: 50%;
            background: var(--lime);
            box-shadow: 0 0 14px rgba(183, 242, 100, .75);
        }

        .field-group {
            margin-bottom: 1rem;
        }

        .field-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .45rem;
        }

        .field-top label,
        .remember-row label {
            color: rgba(238, 247, 255, .82);
            font-size: .85rem;
            font-weight: 730;
        }

        .input-shell {
            position: relative;
        }

        .input-shell::before {
            content: "";
            position: absolute;
            left: .95rem;
            top: 50%;
            width: .52rem;
            height: .52rem;
            transform: translateY(-50%);
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 16px rgba(102, 217, 255, .65);
            opacity: .85;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            min-height: 3.25rem;
            padding: .92rem 1rem .92rem 2.15rem;
            border: 1px solid rgba(206, 231, 240, .18);
            border-radius: .45rem;
            color: #ffffff;
            background: rgba(1, 8, 14, .52);
            outline: none;
            font-size: 1rem;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .form-input::placeholder {
            color: rgba(226, 237, 246, .38);
        }

        .form-input:focus {
            border-color: rgba(53, 244, 198, .72);
            background: rgba(3, 12, 20, .72);
            box-shadow: 0 0 0 4px rgba(53, 244, 198, .12), 0 0 32px rgba(53, 244, 198, .16);
        }

        .form-error {
            margin: .5rem 0 0;
            color: #ffc1d1;
            font-size: .82rem;
            line-height: 1.4;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 1.05rem 0 1.25rem;
        }

        .remember-row {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
        }

        .remember-row input {
            width: 1.05rem;
            height: 1.05rem;
            margin: 0;
            accent-color: #35f4c6;
        }

        .text-link {
            color: #9fecff;
            font-size: .86rem;
            font-weight: 760;
            text-decoration: none;
        }

        .text-link:hover,
        .text-link:focus {
            color: #ffffff;
            text-decoration: underline;
        }

        .primary-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 3.25rem;
            border: 0;
            border-radius: .45rem;
            color: #051018;
            background: linear-gradient(90deg, var(--teal), #8cf7ff 47%, var(--lime));
            font-size: 1rem;
            font-weight: 850;
            cursor: pointer;
            box-shadow: 0 16px 36px rgba(53, 244, 198, .22);
            transition: transform .18s, box-shadow .18s, filter .18s;
        }

        .primary-button:hover,
        .primary-button:focus-visible {
            transform: translateY(-1px);
            filter: saturate(1.05);
            box-shadow: 0 20px 46px rgba(53, 244, 198, .3);
        }

        .register-line {
            margin: 1.15rem 0 0;
            color: rgba(226, 237, 246, .68);
            font-size: .9rem;
            text-align: center;
        }

        .panel-footer {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-direction: column;
            gap: .65rem;
            padding: 1rem clamp(1.25rem, 4vw, 2rem);
            border-top: 1px solid var(--line);
            background: rgba(2, 10, 17, .28);
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: .85rem;
        }

        .panel-footer small {
            color: rgba(226, 237, 246, .45);
            font-size: .75rem;
        }

        .staff-link {
            font-size: .8rem;
            color: rgba(255, 204, 102, .85);
        }

        .staff-link:hover { color: #ffe1a4; }

        @media (max-width: 1100px) {
            .auth-shell {
                grid-template-columns: minmax(0, 1fr);
            }

            .brand-rail {
                min-height: auto;
                padding-bottom: 0;
            }

            .hero-copy {
                max-width: 44rem;
                margin-top: 4rem;
                margin-bottom: 0;
            }

            .auth-panel-wrap {
                min-height: auto;
                align-items: flex-start;
                justify-content: flex-start;
                padding-top: 2rem;
                padding-bottom: 2.5rem;
            }
        }

        @media (max-width: 760px) {
            body {
                background:
                    linear-gradient(128deg, rgba(53, 244, 198, .13), transparent 40%),
                    linear-gradient(155deg, #07111c 0%, #102234 55%, #07151f 100%);
            }

            .auth-shell { min-height: 100svh; }

            .brand-rail { min-height: 31svh; padding: 1rem; }

            .hero-copy { max-width: 100%; margin-top: 2.15rem; }
            .hero-copy h1 { max-width: 8.5ch; font-size: clamp(2.35rem, 12vw, 3.5rem); }
            .hero-copy p { display: none; }

            .telemetry-strip { display: none; }

            .auth-panel-wrap { padding: .85rem; }
            .auth-panel { width: 100%; }
            .panel-header { display: block; }
            .security-state { margin-top: .85rem; }

            .form-options { align-items: flex-start; flex-direction: column; gap: .75rem; }

            .panel-footer { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="scene-stage" aria-hidden="true">
        <canvas id="model3d"></canvas>
    </div>

    <main class="auth-shell">
        <section class="brand-rail" aria-label="EvonPower">
            <a class="brand-mark" href="{{ url('/') }}" aria-label="EvonPower">
                <img src="{{ asset('images/evon-logo.png') }}" alt="">
                <span>
                    <strong>EvonPower</strong>
                    Recharge intelligente
                </span>
            </a>

            <div class="hero-copy">
                <div class="hero-kicker">Espace client</div>
                <h1>Smart EV Control</h1>
                <p>Rechargez votre véhicule, suivez vos sessions et gérez votre solde depuis un seul espace.</p>

                <div class="telemetry-strip" aria-label="Highlights">
                    <div class="telemetry-item">
                        <span>Réseau</span>
                        <strong>OCPP en direct</strong>
                    </div>
                    <div class="telemetry-item">
                        <span>Paiement</span>
                        <strong>Stripe + CMI</strong>
                    </div>
                    <div class="telemetry-item">
                        <span>Suivi</span>
                        <strong>Sessions live</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-panel-wrap" aria-label="Connexion client">
            <div class="auth-panel">
                <div class="panel-accent"></div>
                <div class="panel-body">
                    <header class="panel-header">
                        <div>
                            <h2>Connexion client</h2>
                            <p>Saisissez votre numéro de téléphone pour recevoir un code SMS.</p>
                        </div>
                        <div class="security-state">Lien sécurisé</div>
                    </header>

                    @if (session('status'))
                        <p class="form-error">{{ session('status') }}</p>
                    @endif

                    <form id="loginForm" method="POST" action="{{ route('login.customer') }}">
                        @csrf
                        <input type="hidden" name="login_method" value="customer">

                        <div class="field-group">
                            <div class="field-top">
                                <label for="phone">Numéro de téléphone</label>
                            </div>
                            <div class="input-shell">
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    autocomplete="tel"
                                    inputmode="tel"
                                    value="{{ old('phone') }}"
                                    class="form-input"
                                    placeholder="+212612345678"
                                    required
                                >
                            </div>
                            @error('phone')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-options">
                            <div class="remember-row">
                                <input id="remember_me" name="remember" type="checkbox">
                                <label for="remember_me">Se souvenir de moi</label>
                            </div>
                        </div>

                        <button type="submit" class="primary-button">Recevoir le code SMS</button>
                    </form>

                    <p class="register-line">
                        Nouveau client ?
                        <a href="{{ route('register') }}" class="text-link">Créer un compte</a>
                    </p>
                </div>

                <footer class="panel-footer">
                    <div class="footer-links">
                        <a href="#" class="text-link">Aide</a>
                        <a href="#" class="text-link">FAQ</a>
                    </div>
                    {{-- Same path as the customer login (no subdomain, no separate URL), just a `?staff=1` marker that AuthenticatedSessionController::create() reads to render auth.login-staff instead of this view. Path-only URL keeps the link host-agnostic. --}}
                    <a href="{{ route('login', ['staff' => 1], false) }}" class="staff-link">Vous êtes administrateur, opérateur, intégrateur ou partenaire ? &rarr;</a>
                    <small>&copy; {{ date('Y') }} EvonPower</small>
                </footer>
            </div>
        </section>
    </main>

    <script src="{{ asset('js/mobile-csrf-fix.js') }}"></script>
    <script type="module" src="{{ asset('build/assets/login3d-CVRpZX5U.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('loginForm');
            form?.addEventListener('submit', function () {
                const phoneInput = document.getElementById('phone');
                if (phoneInput) {
                    phoneInput.value = normalizePhone(phoneInput.value);
                }
            });
        });

        function normalizePhone(raw) {
            const value = (raw || '').trim();
            const digits = value.replace(/\D+/g, '');
            if (value.startsWith('+')) return '+' + digits;
            if (digits.startsWith('00')) return '+' + digits.slice(2);
            if (digits.startsWith('212')) return '+' + digits;
            if (digits.startsWith('0')) return '+212' + digits.slice(1);
            if (digits.length > 0) return '+212' + digits;
            return value;
        }
    </script>
</body>
</html>
