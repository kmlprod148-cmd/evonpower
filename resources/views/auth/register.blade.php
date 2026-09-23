<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscription - EvonPower</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        :root {
            --ev-green: #10b981;
            --ev-green-d: #059669;
            --ev-dark: #0f172a;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .auth-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
        }
        .auth-header { text-align: center; margin-bottom: 1.75rem; }
        .auth-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px; height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--ev-green), #34d399);
            margin-bottom: 1rem;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--ev-dark);
            margin: 0 0 .25rem;
            letter-spacing: -.02em;
        }
        .auth-sub {
            color: #64748b;
            font-size: .9375rem;
            margin: 0;
        }
        .field { margin-bottom: 1rem; }
        .field label {
            display: block;
            font-size: .8125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: .375rem;
        }
        .field label .req { color: #ef4444; }
        .form-input {
            width: 100%;
            height: 46px;
            padding: 0 .875rem;
            border: 1.5px solid #e2e8f0;
            border-radius: .625rem;
            background: #f8fafc;
            font-size: .9375rem;
            color: var(--ev-dark);
            outline: none;
            transition: border-color .2s, background-color .2s, box-shadow .2s;
            font-family: inherit;
        }
        .form-input:focus {
            border-color: var(--ev-green);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, .15);
        }
        .form-input.invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .phone-input-group {
            display: flex;
            gap: .5rem;
        }
        .phone-prefix {
            display: flex;
            align-items: center;
            gap: .375rem;
            padding: 0 .75rem;
            height: 46px;
            border: 1.5px solid #e2e8f0;
            border-radius: .625rem;
            background: #f1f5f9;
            font-size: .9375rem;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }
        .phone-prefix img {
            width: 20px; height: auto;
            border-radius: 2px;
        }
        .phone-input-group .form-input { flex: 1; }
        .field-err {
            color: #ef4444;
            font-size: .75rem;
            margin-top: .375rem;
        }
        .btn-primary {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, var(--ev-green), var(--ev-green-d));
            color: #fff;
            font-weight: 700;
            font-size: .9375rem;
            border: 0;
            border-radius: .625rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            box-shadow: 0 4px 14px rgba(16, 185, 129, .35);
            transition: transform .15s, box-shadow .2s;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, .45);
        }
        .btn-primary:disabled { opacity: .65; cursor: not-allowed; transform: none; }
        .auth-footer {
            text-align: center;
            font-size: .875rem;
            color: #64748b;
            margin-top: 1.5rem;
        }
        .auth-footer a {
            color: var(--ev-green-d);
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer a:hover { text-decoration: underline; }
        .alert {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .75rem .875rem;
            border-radius: .5rem;
            font-size: .8125rem;
            margin-bottom: 1rem;
        }
        .alert-info { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-err  { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
        .alert ul { margin: .25rem 0 0; padding-left: 1rem; }
    </style>
</head>
<body>
    <main class="auth-card" role="main">
        <header class="auth-header">
            <div class="auth-logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="#fff">
                    <path d="M13 2L4.09 12.96a1 1 0 00.77 1.64H11L11 22l8.91-10.96a1 1 0 00-.77-1.64H13V2z"/>
                </svg>
            </div>
            <h1 class="auth-title">Créer votre compte</h1>
            <p class="auth-sub">Entrez votre nom et un numéro Marocain — nous vous enverrons un code de vérification.</p>
        </header>

        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-err">
                <div>
                    <strong>Veuillez corriger :</strong>
                    <ul>
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" novalidate id="regForm">
            @csrf

            <div class="field">
                <label for="name">Nom complet <span class="req">*</span></label>
                <input type="text" name="name" id="name"
                       value="{{ old('name') }}"
                       class="form-input @error('name') invalid @enderror"
                       placeholder="Votre nom"
                       autocomplete="name"
                       maxlength="255" required>
                @error('name') <p class="field-err">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="phone">Téléphone <span class="req">*</span></label>
                <div class="phone-input-group">
                    <span class="phone-prefix" aria-hidden="true">
                        <span>🇲🇦</span> +212
                    </span>
                    <input type="tel" name="phone_local" id="phone_local"
                           value="{{ old('phone_local') }}"
                           class="form-input @error('phone') invalid @enderror"
                           placeholder="6 12 34 56 78"
                           inputmode="numeric"
                           autocomplete="tel-national" required>
                </div>
                {{-- Country is pre-selected (hidden) and phone is normalised to E.164 on submit. --}}
                <input type="hidden" name="phone" id="phone" value="{{ old('phone') }}">
                <input type="hidden" name="country" value="MA">
                @error('phone') <p class="field-err">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
                Recevoir le code SMS
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </form>

        <p class="auth-footer">
            Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a>
        </p>
    </main>

    <script>
        // Normalise the local Moroccan phone (e.g. "0612345678" or "612345678") into E.164 ("+2126…")
        // before submitting. Keeps the visible field user-friendly while the controller validates +E.164.
        (function () {
            var form = document.getElementById('regForm');
            var local = document.getElementById('phone_local');
            var hidden = document.getElementById('phone');

            function toE164(raw) {
                var digits = (raw || '').replace(/\D+/g, '');
                if (digits.startsWith('212')) return '+' + digits;
                if (digits.startsWith('0'))   return '+212' + digits.substring(1);
                if (digits.length > 0)        return '+212' + digits;
                return '';
            }

            form.addEventListener('submit', function () {
                hidden.value = toE164(local.value);
                document.getElementById('submitBtn').disabled = true;
            });
        })();
    </script>
</body>
</html>
