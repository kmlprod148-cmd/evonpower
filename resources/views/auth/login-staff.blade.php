<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Back-office EvonPower</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f6f9;
            --bg-panel: #ffffff;
            --ink: #0e1726;
            --ink-soft: #4a5365;
            --muted: #8892a6;
            --line: #e2e6ee;
            --line-strong: #cdd3e0;
            --accent: #1f3a8a;
            --accent-soft: #eef1fb;
            --warn: #b56500;
            --warn-soft: #fdf2d9;
            --danger: #b3261e;
            --field-bg: #fafbfc;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .shell {
            width: min(100%, 25rem);
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin-bottom: 1.2rem;
            color: var(--ink-soft);
            font-size: .82rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .brand-row img {
            width: 1.6rem;
            height: 1.6rem;
            object-fit: contain;
            border-radius: .35rem;
            background: var(--accent-soft);
            padding: .2rem;
        }

        .surface-chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-left: auto;
            padding: .25rem .55rem;
            border: 1px solid var(--warn);
            background: var(--warn-soft);
            color: var(--warn);
            font-size: .68rem;
            border-radius: .3rem;
        }

        .surface-chip::before {
            content: "";
            width: .42rem;
            height: .42rem;
            border-radius: 50%;
            background: var(--warn);
        }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--line);
            border-radius: .55rem;
            box-shadow: 0 1px 3px rgba(14, 23, 38, .04), 0 12px 30px rgba(14, 23, 38, .06);
            overflow: hidden;
        }

        .panel-body {
            padding: 1.85rem 1.85rem 1.5rem;
        }

        .panel-body h1 {
            margin: 0 0 .3rem;
            font-size: 1.32rem;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.01em;
        }

        .panel-body .lede {
            margin: 0 0 1.5rem;
            color: var(--ink-soft);
            font-size: .9rem;
            line-height: 1.55;
        }

        .field-group {
            margin-bottom: 1rem;
        }

        .field-group label {
            display: block;
            margin-bottom: .35rem;
            color: var(--ink);
            font-size: .82rem;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            min-height: 2.85rem;
            padding: .7rem .85rem;
            border: 1px solid var(--line-strong);
            border-radius: .4rem;
            color: var(--ink);
            background: var(--field-bg);
            outline: none;
            font-size: .95rem;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        .form-input:focus {
            border-color: var(--accent);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(31, 58, 138, .12);
        }

        .form-error {
            margin: .4rem 0 0;
            color: var(--danger);
            font-size: .8rem;
        }

        .form-status {
            margin: 0 0 1rem;
            padding: .65rem .85rem;
            border-radius: .4rem;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: .85rem;
        }

        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1rem 0 1.25rem;
            font-size: .85rem;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            color: var(--ink-soft);
        }

        .remember input { accent-color: var(--accent); }

        .text-link {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        .primary-button {
            width: 100%;
            min-height: 2.85rem;
            border: 0;
            border-radius: .4rem;
            color: #ffffff;
            background: var(--accent);
            font-size: .95rem;
            font-weight: 600;
            letter-spacing: .01em;
            cursor: pointer;
            transition: background .15s, transform .1s;
        }

        .primary-button:hover { background: #16306e; }
        .primary-button:active { transform: translateY(1px); }

        .panel-footer {
            padding: 1rem 1.85rem;
            background: #fafbfc;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: .8rem;
            text-align: center;
        }

        .panel-footer a { color: var(--ink-soft); text-decoration: none; }
        .panel-footer a:hover { color: var(--accent); text-decoration: underline; }

        .legal {
            margin-top: 1rem;
            text-align: center;
            color: var(--muted);
            font-size: .75rem;
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="brand-row">
            <img src="{{ asset('images/evon-logo.png') }}" alt="">
            <span>EvonPower</span>
            <span class="surface-chip">Espace back-office</span>
        </div>

        <div class="panel">
            <div class="panel-body">
                <h1>Connexion administrative</h1>
                <p class="lede">Reservé aux administrateurs, opérateurs, intégrateurs et partenaires. Les clients utilisent <a href="{{ route('login') }}" class="text-link">l'accès client</a>.</p>

                @if (session('status'))
                    <p class="form-status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('login.admin') }}" autocomplete="off">
                    @csrf
                    <input type="hidden" name="login_method" value="admin">

                    <div class="field-group">
                        <label for="email">Adresse e-mail professionnelle</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="form-input"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus
                        >
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field-group">
                        <label for="password">Mot de passe</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-input"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="options-row">
                        <label class="remember">
                            <input type="checkbox" name="remember">
                            Garder ma session active
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-link">Mot de passe oublié ?</a>
                        @endif
                    </div>

                    <button type="submit" class="primary-button">Se connecter</button>
                </form>
            </div>

            <footer class="panel-footer">
                Accès tracé et audité. Toute activité est journalisée.
            </footer>
        </div>

        <p class="legal">&copy; {{ date('Y') }} EvonPower</p>
    </main>
</body>
</html>
