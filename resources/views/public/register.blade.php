@extends('layouts.public')

@section('title', 'Créer un compte — EVON')

@push('styles')
<style>
/* ── Register Page Variables ────────────────────────────────────────── */
:root {
    --ev-green: #10b981;
    --ev-green-d: #059669;
    --ev-green-l: #d1fae5;
    --ev-dark: #0f172a;
    --ev-panel: #f8fafc;
    --ev-radius: 14px;
    --ev-input-h: 46px;
    --transition: 200ms cubic-bezier(.4,0,.2,1);
}

/* ── Layout ─────────────────────────────────────────────────────────── */
.reg-shell {
    display: flex;
    min-height: 100vh;
    font-family: 'Inter', system-ui, sans-serif;
}

/* ── Left Branding Panel ────────────────────────────────────────────── */
.reg-brand {
    position: sticky;
    top: 0;
    height: 100vh;
    width: 420px;
    flex-shrink: 0;
    background: linear-gradient(160deg, #0f172a 0%, #064e3b 60%, #065f46 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 2.5rem 2rem;
    overflow: hidden;
}

/* decorative circles */
.reg-brand::before,
.reg-brand::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    opacity: .07;
    pointer-events: none;
}
.reg-brand::before {
    width: 460px; height: 460px;
    background: #10b981;
    top: -120px; right: -140px;
}
.reg-brand::after {
    width: 340px; height: 340px;
    background: #34d399;
    bottom: -80px; left: -100px;
}

.reg-brand-logo {
    display: flex;
    align-items: center;
    gap: .75rem;
    text-decoration: none;
    position: relative;
    z-index: 1;
}
.reg-brand-logo-icon {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #10b981, #34d399);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.reg-brand-logo-text {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -.03em;
}

.reg-brand-hero {
    position: relative;
    z-index: 1;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 2rem 0;
}

.reg-brand-headline {
    font-size: 1.85rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.25;
    letter-spacing: -.03em;
    margin-bottom: 1rem;
}

.reg-brand-sub {
    font-size: .95rem;
    color: #6ee7b7;
    line-height: 1.6;
    margin-bottom: 2rem;
}

/* EV Charging Illustration (CSS) */
.reg-ev-art {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    position: relative;
}
.reg-ev-art-station {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
}
.ev-pole {
    width: 52px;
    border-radius: 10px 10px 0 0;
    background: linear-gradient(180deg, #34d399, #10b981);
    height: 100px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ev-pole::after {
    content: '';
    position: absolute;
    bottom: 0;
    width: 62px;
    height: 6px;
    background: #065f46;
    border-radius: 3px;
}
.ev-cable {
    position: absolute;
    right: -16px;
    top: 60%;
    width: 20px;
    height: 2px;
    background: #6ee7b7;
    border-radius: 1px;
}
.ev-car {
    flex: 1;
    height: 60px;
    background: linear-gradient(135deg, #1e3a5f, #1e40af);
    border-radius: 12px 20px 4px 4px;
    position: relative;
    overflow: hidden;
}
.ev-car::before {
    content: '';
    position: absolute;
    top: 6px; left: 10px; right: 25px; bottom: 14px;
    background: rgba(255,255,255,.1);
    border-radius: 8px 16px 0 0;
}
.ev-car::after {
    content: '';
    position: absolute;
    bottom: 0; left: 8px;
    width: 12px; height: 12px;
    background: #1e293b;
    border-radius: 50%;
    box-shadow: 26px 0 0 #1e293b;
}
.ev-charge-dots {
    display: flex;
    gap: 4px;
    margin-top: .75rem;
    justify-content: center;
}
.ev-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #10b981;
    animation: ev-pulse 1.4s ease-in-out infinite;
}
.ev-dot:nth-child(2) { animation-delay: .2s; }
.ev-dot:nth-child(3) { animation-delay: .4s; }
.ev-dot:nth-child(4) { animation-delay: .6s; }
@keyframes ev-pulse { 0%,100%{ opacity:.25; transform:scale(.8); } 50%{ opacity:1; transform:scale(1.1); } }

/* Benefits */
.reg-benefits {
    display: flex;
    flex-direction: column;
    gap: .85rem;
    position: relative;
    z-index: 1;
}
.reg-benefit {
    display: flex;
    align-items: center;
    gap: .75rem;
    color: #a7f3d0;
    font-size: .875rem;
}
.reg-benefit-icon {
    width: 32px; height: 32px;
    background: rgba(16,185,129,.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #34d399;
}

/* Brand footer */
.reg-brand-footer {
    position: relative;
    z-index: 1;
    font-size: .75rem;
    color: #4b7261;
}

/* ── Right Form Panel ────────────────────────────────────────────────── */
.reg-form-panel {
    flex: 1;
    min-width: 0;
    background: #fff;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.reg-form-inner {
    max-width: 560px;
    width: 100%;
    margin: 0 auto;
    padding: 3rem 2rem 4rem;
}

/* Charging context banner */
.reg-context-banner {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .875rem 1rem;
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
    border-radius: 10px;
    color: #065f46;
    font-size: .875rem;
    margin-bottom: 1.5rem;
}

/* Form header */
.reg-form-heading {
    margin-bottom: 2rem;
}
.reg-form-heading h1 {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -.04em;
    line-height: 1.2;
    margin-bottom: .375rem;
}
.reg-form-heading p {
    color: #64748b;
    font-size: .9375rem;
}

/* Step progress bar */
.reg-steps {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 2.25rem;
}
.reg-step {
    display: flex;
    align-items: center;
    flex: 1;
    cursor: pointer;
}
.reg-step-bubble {
    width: 28px; height: 28px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    font-weight: 700;
    color: #94a3b8;
    flex-shrink: 0;
    transition: all var(--transition);
}
.reg-step.active .reg-step-bubble {
    border-color: var(--ev-green);
    background: var(--ev-green);
    color: #fff;
}
.reg-step.done .reg-step-bubble {
    border-color: var(--ev-green);
    background: var(--ev-green-l);
    color: var(--ev-green-d);
}
.reg-step-label {
    font-size: .7rem;
    font-weight: 600;
    color: #94a3b8;
    margin-left: .4rem;
    white-space: nowrap;
    transition: color var(--transition);
}
.reg-step.active .reg-step-label { color: var(--ev-green-d); }
.reg-step.done .reg-step-label { color: var(--ev-green-d); }
.reg-step-line {
    flex: 1;
    height: 2px;
    background: #e2e8f0;
    margin: 0 .5rem;
    border-radius: 1px;
    transition: background var(--transition);
}
.reg-step-line.done { background: var(--ev-green); }

/* Section cards */
.reg-section {
    display: none;
    animation: fadeUp 220ms ease both;
}
.reg-section.active { display: block; }
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.reg-section-header {
    display: flex;
    align-items: center;
    gap: .625rem;
    margin-bottom: 1.25rem;
}
.reg-section-icon {
    width: 36px; height: 36px;
    border-radius: 9px;
    background: var(--ev-green-l);
    color: var(--ev-green-d);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.reg-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}
.reg-section-badge {
    margin-left: auto;
    font-size: .7rem;
    font-weight: 600;
    color: #94a3b8;
    background: #f1f5f9;
    padding: .2rem .6rem;
    border-radius: 99px;
}

/* Field group */
.reg-grid { display: grid; gap: 1rem; }
.reg-grid-2 { grid-template-columns: 1fr 1fr; }
.reg-grid-full { grid-column: 1 / -1; }

/* Floating label inputs */
.reg-field {
    position: relative;
}
.reg-field label {
    display: block;
    font-size: .8125rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: .375rem;
}
.reg-field label .req { color: #ef4444; margin-left: 2px; }
.reg-field label .opt {
    font-weight: 400;
    color: #94a3b8;
    font-size: .75rem;
    margin-left: .25rem;
}
.reg-input {
    width: 100%;
    height: var(--ev-input-h);
    padding: 0 .875rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    color: #0f172a;
    font-size: .9375rem;
    font-family: inherit;
    outline: none;
    transition: all var(--transition);
    box-sizing: border-box;
}
.reg-input:focus {
    border-color: var(--ev-green);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(16,185,129,.12);
}
.reg-input.is-error { border-color: #ef4444; background: #fff5f5; }
.reg-input.is-ok {
    border-color: var(--ev-green);
    background: #f0fdf8;
    padding-right: 2.5rem;
}
.reg-select {
    width: 100%;
    height: var(--ev-input-h);
    padding: 0 .875rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    color: #0f172a;
    font-size: .9375rem;
    font-family: inherit;
    outline: none;
    appearance: none;
    cursor: pointer;
    transition: all var(--transition);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' d='m5 8 5 5 5-5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right .75rem center;
    background-size: 18px;
}
.reg-select:focus {
    border-color: var(--ev-green);
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(16,185,129,.12);
}

/* Input with icon suffix */
.reg-field-icon {
    position: relative;
}
.reg-field-icon .reg-input { padding-right: 2.75rem; }
.reg-field-icon .field-suffix {
    position: absolute;
    right: .875rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    display: flex;
    align-items: center;
}

/* Field status text */
.reg-field-msg {
    font-size: .75rem;
    margin-top: .35rem;
    min-height: 1rem;
}
.reg-field-msg.ok  { color: var(--ev-green-d); }
.reg-field-msg.err { color: #ef4444; }
.reg-field-msg.hint { color: #94a3b8; }

/* Password strength */
.pw-strength-bar {
    height: 3px;
    border-radius: 2px;
    background: #e2e8f0;
    margin-top: .5rem;
    overflow: hidden;
}
.pw-strength-fill {
    height: 100%;
    border-radius: 2px;
    transition: width .4s ease, background .3s;
    width: 0;
}

/* Connector type pills */
.connector-pills {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: .375rem;
}
.connector-pill {
    padding: .4rem .9rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 99px;
    font-size: .8rem;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all var(--transition);
    background: #f8fafc;
}
.connector-pill:hover { border-color: var(--ev-green); color: var(--ev-green-d); }
.connector-pill.selected {
    border-color: var(--ev-green);
    background: var(--ev-green-l);
    color: var(--ev-green-d);
}
.connector-pill input { display: none; }

/* Collapsible optional section */
.reg-optional-toggle {
    display: flex;
    align-items: center;
    gap: .5rem;
    cursor: pointer;
    user-select: none;
    padding: .75rem 0;
    border-top: 1px dashed #e2e8f0;
    color: #64748b;
    font-size: .875rem;
    font-weight: 500;
    margin-top: .5rem;
}
.reg-optional-toggle:hover { color: var(--ev-green-d); }
.reg-optional-toggle svg { transition: transform .25s; }
.reg-optional-toggle.open svg { transform: rotate(180deg); }
.reg-optional-body {
    display: none;
    padding-top: 1rem;
}
.reg-optional-body.open { display: block; animation: fadeUp 200ms ease; }

/* Terms */
.reg-terms {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: border-color var(--transition);
}
.reg-terms:hover { border-color: var(--ev-green); }
.reg-terms-checkbox {
    width: 18px; height: 18px;
    border: 2px solid #cbd5e1;
    border-radius: 4px;
    flex-shrink: 0;
    margin-top: 1px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition);
    cursor: pointer;
}
.reg-terms input[type="checkbox"]:checked + .reg-terms-label .reg-terms-checkbox,
.reg-terms-cb:checked ~ .reg-terms-checkbox-visual {
    border-color: var(--ev-green);
    background: var(--ev-green);
}

/* Nav buttons */
.reg-nav {
    display: flex;
    gap: .75rem;
    margin-top: 1.75rem;
}
.btn-ev-primary {
    flex: 1;
    height: 48px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    font-weight: 700;
    font-size: .9375rem;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    transition: all var(--transition);
    box-shadow: 0 4px 14px rgba(16,185,129,.35);
}
.btn-ev-primary:hover {
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 6px 20px rgba(16,185,129,.45);
    transform: translateY(-1px);
}
.btn-ev-primary:active { transform: translateY(0); }
.btn-ev-primary:disabled {
    opacity: .6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
.btn-ev-ghost {
    height: 48px;
    padding: 0 1.25rem;
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    font-size: .9375rem;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .375rem;
    transition: all var(--transition);
}
.btn-ev-ghost:hover { background: #e2e8f0; color: #0f172a; }

/* Login link */
.reg-login-link {
    text-align: center;
    font-size: .875rem;
    color: #64748b;
    margin-top: 1.5rem;
}
.reg-login-link a {
    color: var(--ev-green-d);
    font-weight: 600;
    text-decoration: none;
}
.reg-login-link a:hover { text-decoration: underline; }

/* Alert messages */
.reg-alert {
    display: flex;
    align-items: flex-start;
    gap: .625rem;
    padding: .875rem 1rem;
    border-radius: 10px;
    font-size: .875rem;
    margin-bottom: 1.25rem;
}
.reg-alert.info { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
.reg-alert.err  { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }

/* ── Mobile ────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .reg-brand { display: none; }
    .reg-form-inner { padding: 2rem 1.25rem 3rem; }
    .reg-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .reg-form-heading h1 { font-size: 1.4rem; }
    .reg-steps { gap: 0; }
    .reg-step-label { display: none; }
}
</style>
@endpush

@section('content')
<div class="reg-shell">

    {{-- ═══════════════════ LEFT — BRANDING PANEL ═══════════════════ --}}
    <aside class="reg-brand">
        {{-- Logo --}}
        <a href="{{ url('/') }}" class="reg-brand-logo">
            <div class="reg-brand-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" width="24" height="24">
                    <path d="M13 2L4.09 12.96a1 1 0 00.77 1.64H11L11 22l8.91-10.96a1 1 0 00-.77-1.64H13V2z"
                          fill="#fff" stroke="#fff" stroke-width=".5"/>
                </svg>
            </div>
            <span class="reg-brand-logo-text">EVON</span>
        </a>

        {{-- Hero --}}
        <div class="reg-brand-hero">
            <p class="reg-brand-headline">Rechargez malin,<br>roulez serein.</p>
            <p class="reg-brand-sub">Accédez à tout le réseau de bornes EVON avec un seul compte.</p>

            {{-- Mini EV Art --}}
            <div class="reg-ev-art">
                <div class="reg-ev-art-station">
                    <div class="ev-pole" style="position:relative">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                            <path d="M13 2L4.09 12.96a1 1 0 00.77 1.64H11L11 22l8.91-10.96a1 1 0 00-.77-1.64H13V2z" fill="#fff"/>
                        </svg>
                        <span class="ev-cable"></span>
                    </div>
                    <div class="ev-car" style="flex:1;height:56px"></div>
                </div>
                <div class="ev-charge-dots">
                    <div class="ev-dot"></div>
                    <div class="ev-dot"></div>
                    <div class="ev-dot"></div>
                    <div class="ev-dot"></div>
                </div>
            </div>

            {{-- Benefits --}}
            <div class="reg-benefits">
                <div class="reg-benefit">
                    <div class="reg-benefit-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span>Localisez les bornes disponibles en temps réel</span>
                </div>
                <div class="reg-benefit">
                    <div class="reg-benefit-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                            <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4z"/>
                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span>Paiement sécurisé, factures automatiques</span>
                </div>
                <div class="reg-benefit">
                    <div class="reg-benefit-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span>Démarrez une session en un scan de QR</span>
                </div>
            </div>
        </div>

        <p class="reg-brand-footer">&copy; {{ date('Y') }} EVON Power &mdash; Recharge électrique intelligente</p>
    </aside>

    {{-- ═══════════════════ RIGHT — FORM PANEL ═══════════════════════ --}}
    <main class="reg-form-panel">
        <div class="reg-form-inner">

            {{-- Charging context banner --}}
            @if(!empty($pending_charging_point_id) || session('pending_charging_point_id'))
            <div class="reg-context-banner">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2" flex-shrink="0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>Créez votre compte pour finaliser votre réservation. Vous serez redirigé automatiquement.</span>
            </div>
            @endif

            @if(session('info'))
            <div class="reg-alert info">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="flex-shrink:0;margin-top:1px">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                {{ session('info') }}
            </div>
            @endif

            @if($errors->any())
            <div class="reg-alert err">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="flex-shrink:0;margin-top:1px">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong>Veuillez corriger les erreurs ci-dessous :</strong>
                    <ul style="margin-top:.25rem;padding-left:1rem">
                        @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            {{-- Heading --}}
            <div class="reg-form-heading">
                <h1>Créer votre compte</h1>
                <p>Rejoignez EVON et accédez aux bornes de recharge partout.</p>
            </div>

            {{-- Step progress --}}
            <div class="reg-steps" id="regSteps">
                <div class="reg-step active" data-step="1" onclick="goStep(1)">
                    <div class="reg-step-bubble" id="sb1">1</div>
                    <span class="reg-step-label">Informations</span>
                </div>
                <div class="reg-step-line" id="sl1"></div>
                <div class="reg-step" data-step="2" onclick="goStep(2)">
                    <div class="reg-step-bubble" id="sb2">2</div>
                    <span class="reg-step-label">Sécurité</span>
                </div>
                <div class="reg-step-line" id="sl2"></div>
                <div class="reg-step" data-step="3" onclick="goStep(3)">
                    <div class="reg-step-bubble" id="sb3">3</div>
                    <span class="reg-step-label">Véhicule</span>
                </div>
            </div>

            {{-- ─── FORM ─── --}}
            <form method="POST" action="{{ route('register') }}" id="regForm" novalidate>
                @csrf

                {{-- ════ STEP 1: Personal Info ════ --}}
                <div class="reg-section active" id="step1">
                    <div class="reg-section-header">
                        <div class="reg-section-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="reg-section-title">Informations personnelles</span>
                    </div>

                    <div class="reg-grid reg-grid-2">
                        {{-- Last name --}}
                        <div class="reg-field">
                            <label for="name">Nom <span class="req">*</span></label>
                            <input type="text" name="name" id="name"
                                   value="{{ old('name') }}"
                                   class="reg-input @error('name') is-error @enderror"
                                   placeholder="Votre nom de famille"
                                   autocomplete="family-name" required>
                            @error('name')<p class="reg-field-msg err">{{ $message }}</p>@enderror
                        </div>

                        {{-- First name --}}
                        <div class="reg-field">
                            <label for="first_name">Prénom <span class="opt">(optionnel)</span></label>
                            <input type="text" name="first_name" id="first_name"
                                   value="{{ old('first_name') }}"
                                   class="reg-input"
                                   placeholder="Votre prénom"
                                   autocomplete="given-name">
                        </div>

                        {{-- Email --}}
                        <div class="reg-field reg-grid-full">
                            <label for="email">Adresse e-mail <span class="req">*</span></label>
                            <div class="reg-field-icon">
                                <input type="email" name="email" id="email"
                                       value="{{ old('email') }}"
                                       class="reg-input @error('email') is-error @enderror"
                                       placeholder="vous@exemple.com"
                                       autocomplete="email" required>
                                <span class="field-suffix" id="email-icon" style="display:none"></span>
                            </div>
                            <p class="reg-field-msg" id="email-status"></p>
                            @error('email')<p class="reg-field-msg err">{{ $message }}</p>@enderror
                        </div>

                        {{-- Phone --}}
                        <div class="reg-field">
                            <label for="phone">Téléphone <span class="opt">(optionnel)</span></label>
                            <input type="tel" name="phone" id="phone"
                                   value="{{ old('phone') }}"
                                   class="reg-input"
                                   placeholder="+212 6 00 00 00 00"
                                   autocomplete="tel">
                        </div>

                        {{-- City --}}
                        <div class="reg-field">
                            <label for="city">Ville <span class="opt">(optionnel)</span></label>
                            <input type="text" name="city" id="city"
                                   value="{{ old('city') }}"
                                   class="reg-input"
                                   placeholder="Votre ville"
                                   autocomplete="address-level2">
                        </div>

                        {{-- Address --}}
                        <div class="reg-field reg-grid-full">
                            <label for="address">Adresse <span class="opt">(optionnel)</span></label>
                            <input type="text" name="address" id="address"
                                   value="{{ old('address') }}"
                                   class="reg-input"
                                   placeholder="Rue, numéro..."
                                   autocomplete="street-address">
                        </div>

                        {{-- hidden fields --}}
                        <input type="hidden" name="postal_code" id="postal_code" value="{{ old('postal_code') }}">
                        <input type="hidden" name="country" id="country" value="{{ old('country', 'Maroc') }}">
                    </div>

                    <div class="reg-nav">
                        <button type="button" class="btn-ev-primary" onclick="nextStep(1)">
                            Continuer
                            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ════ STEP 2: Password + Terms ════ --}}
                <div class="reg-section" id="step2">
                    <div class="reg-section-header">
                        <div class="reg-section-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="reg-section-title">Sécurité du compte</span>
                    </div>

                    <div class="reg-grid">
                        {{-- Password --}}
                        <div class="reg-field">
                            <label for="password">Mot de passe <span class="req">*</span></label>
                            <div class="reg-field-icon">
                                <input type="password" name="password" id="password"
                                       class="reg-input @error('password') is-error @enderror"
                                       placeholder="Minimum 8 caractères"
                                       autocomplete="new-password" required>
                                <span class="field-suffix" id="togglePw" title="Afficher/masquer" style="cursor:pointer">
                                    <svg id="eyeShow" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg id="eyeHide" viewBox="0 0 20 20" fill="currentColor" width="18" height="18" style="display:none">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="pw-strength-bar">
                                <div class="pw-strength-fill" id="pwBar"></div>
                            </div>
                            <p class="reg-field-msg" id="pw-strength-label" style="color:#94a3b8"></p>
                            @error('password')<p class="reg-field-msg err">{{ $message }}</p>@enderror
                        </div>

                        {{-- Confirm password --}}
                        <div class="reg-field">
                            <label for="password_confirmation">Confirmer le mot de passe <span class="req">*</span></label>
                            <div class="reg-field-icon">
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="reg-input"
                                       placeholder="Répétez votre mot de passe"
                                       autocomplete="new-password" required>
                                <span class="field-suffix" id="togglePwc" style="cursor:pointer">
                                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            </div>
                            <p class="reg-field-msg" id="pw-match-msg"></p>
                        </div>

                        {{-- Terms --}}
                        <div>
                            <label style="display:flex;align-items:flex-start;gap:.75rem;cursor:pointer" id="termsLabel">
                                <input type="checkbox" name="terms" value="1" id="terms"
                                       style="position:absolute;opacity:0;width:0;height:0" required>
                                <span id="termsBox" style="
                                    width:18px;height:18px;flex-shrink:0;margin-top:1px;
                                    border:2px solid #cbd5e1;border-radius:4px;
                                    display:flex;align-items:center;justify-content:center;
                                    transition:all .2s;background:#fff;cursor:pointer">
                                    <svg id="termsCheck" viewBox="0 0 12 12" fill="none" width="10" height="10" style="display:none">
                                        <path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span style="font-size:.875rem;color:#475569;line-height:1.5">
                                    J'accepte les
                                    <a href="#" style="color:var(--ev-green-d);font-weight:600" target="_blank">conditions générales d'utilisation</a>
                                    et la
                                    <a href="{{ route('privacy-policy') }}" style="color:var(--ev-green-d);font-weight:600" target="_blank">politique de confidentialité</a>.
                                </span>
                            </label>
                            @error('terms')<p class="reg-field-msg err" style="margin-top:.375rem">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="reg-nav">
                        <button type="button" class="btn-ev-ghost" onclick="goStep(1)">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                            </svg>
                            Retour
                        </button>
                        <button type="button" class="btn-ev-primary" onclick="nextStep(2)">
                            Continuer
                            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ════ STEP 3: Vehicle + OCPP (optional) + Submit ════ --}}
                <div class="reg-section" id="step3">
                    <div class="reg-section-header">
                        <div class="reg-section-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-2a4 4 0 00-4-4h-2V5a1 1 0 00-1-1H3z"/>
                            </svg>
                        </div>
                        <span class="reg-section-title">Votre véhicule</span>
                        <span class="reg-section-badge">Optionnel</span>
                    </div>

                    <div class="reg-grid reg-grid-2">
                        <div class="reg-field">
                            <label for="vehicle_make">Marque <span class="opt">(optionnel)</span></label>
                            <input type="text" name="vehicle_make" id="vehicle_make"
                                   value="{{ old('vehicle_make') }}"
                                   class="reg-input" placeholder="Tesla, Renault, BMW…">
                        </div>
                        <div class="reg-field">
                            <label for="vehicle_model">Modèle <span class="opt">(optionnel)</span></label>
                            <input type="text" name="vehicle_model" id="vehicle_model"
                                   value="{{ old('vehicle_model') }}"
                                   class="reg-input" placeholder="Model 3, Zoé, iX3…">
                        </div>
                        <div class="reg-field">
                            <label for="vehicle_registration">Immatriculation <span class="opt">(optionnel)</span></label>
                            <input type="text" name="vehicle_registration" id="vehicle_registration"
                                   value="{{ old('vehicle_registration') }}"
                                   class="reg-input" placeholder="AB-123-CD">
                            <p class="reg-field-msg hint" id="registration-status"></p>
                        </div>
                        <div class="reg-field">
                            <label for="vehicle_year">Année <span class="opt">(optionnel)</span></label>
                            <input type="number" name="vehicle_year" id="vehicle_year"
                                   value="{{ old('vehicle_year') }}"
                                   min="2000" max="{{ date('Y') + 1 }}"
                                   class="reg-input" placeholder="{{ date('Y') }}">
                        </div>
                        <div class="reg-field">
                            <label for="vehicle_battery_capacity">Batterie (kWh) <span class="opt">(optionnel)</span></label>
                            <input type="text" name="vehicle_battery_capacity" id="vehicle_battery_capacity"
                                   value="{{ old('vehicle_battery_capacity') }}"
                                   class="reg-input" placeholder="50, 75, 100…">
                        </div>
                        <div class="reg-field">
                            <label>Type connecteur <span class="opt">(optionnel)</span></label>
                            <select name="vehicle_connector_type" id="vehicle_connector_type" class="reg-select">
                                <option value="">Sélectionner…</option>
                                @foreach(\App\Models\Vehicle::connectorTypes() as $value => $label)
                                    <option value="{{ $value }}" {{ old('vehicle_connector_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- OCPP Tag - collapsible --}}
                    <div class="reg-optional-toggle" id="ocppToggle" onclick="toggleOcpp()">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" id="ocppArrow">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <span>J'ai déjà un tag OCPP</span>
                        <span style="margin-left:auto;font-size:.7rem;font-weight:400;color:#94a3b8">Optionnel</span>
                    </div>
                    <div class="reg-optional-body" id="ocppBody">
                        <div class="reg-field">
                            <label for="existing_ocpp_tag">Code-barres / numéro de série</label>
                            <input type="text" name="existing_ocpp_tag" id="existing_ocpp_tag"
                                   value="{{ old('existing_ocpp_tag') }}"
                                   class="reg-input"
                                   placeholder="Entrez le code de votre tag OCPP">
                            <p class="reg-field-msg hint" id="ocpp-tag-status">
                                Laissez vide si vous n'avez pas de tag. Vous pourrez en commander un depuis votre espace client.
                            </p>
                        </div>
                    </div>

                    <div class="reg-nav" style="margin-top:2rem">
                        <button type="button" class="btn-ev-ghost" onclick="goStep(2)">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                            </svg>
                            Retour
                        </button>
                        <button type="submit" class="btn-ev-primary" id="submitBtn">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Créer mon compte
                        </button>
                    </div>
                </div>

            </form>

            {{-- Login link --}}
            <p class="reg-login-link">
                Déjà inscrit ?
                <a href="{{ route('login') }}">Se connecter</a>
            </p>

        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
(function() {
    // ── Step navigation ──────────────────────────────────────────────
    let currentStep = {{ $errors->any() ? 1 : 1 }};

    function showStep(n) {
        document.querySelectorAll('.reg-section').forEach(s => s.classList.remove('active'));
        document.getElementById('step' + n).classList.add('active');
        currentStep = n;

        // Update step bubbles + lines
        for (let i = 1; i <= 3; i++) {
            const step = document.querySelector(`.reg-step[data-step="${i}"]`);
            const sb = document.getElementById('sb' + i);
            step.classList.remove('active', 'done');
            if (i < n) {
                step.classList.add('done');
                sb.innerHTML = '<svg viewBox="0 0 12 12" fill="none" width="12" height="12"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            } else if (i === n) {
                step.classList.add('active');
                sb.textContent = i;
            } else {
                sb.textContent = i;
            }
        }
        for (let i = 1; i <= 2; i++) {
            const line = document.getElementById('sl' + i);
            if (i < n) line.classList.add('done');
            else line.classList.remove('done');
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.goStep = showStep;

    window.nextStep = function(from) {
        if (from === 1) {
            // Validate name + email
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            let ok = true;
            if (!name.value.trim()) {
                name.classList.add('is-error');
                name.focus();
                ok = false;
            } else { name.classList.remove('is-error'); }
            if (!email.value.trim() || !email.value.includes('@')) {
                email.classList.add('is-error');
                if (ok) email.focus();
                ok = false;
            } else { email.classList.remove('is-error'); }
            if (ok) showStep(2);
        } else if (from === 2) {
            const pw  = document.getElementById('password');
            const pwc = document.getElementById('password_confirmation');
            const terms = document.getElementById('terms');
            let ok = true;
            if (!pw.value || pw.value.length < 8) {
                pw.classList.add('is-error');
                pw.focus();
                ok = false;
            } else { pw.classList.remove('is-error'); }
            if (pwc.value !== pw.value) {
                pwc.classList.add('is-error');
                document.getElementById('pw-match-msg').textContent = 'Les mots de passe ne correspondent pas.';
                document.getElementById('pw-match-msg').className = 'reg-field-msg err';
                ok = false;
            }
            if (!terms.checked) {
                document.getElementById('termsBox').style.borderColor = '#ef4444';
                ok = false;
            }
            if (ok) showStep(3);
        }
    };

    // Jump to error step on page load
    @if($errors->any())
    window.addEventListener('DOMContentLoaded', function() {
        const pwErr = {{ $errors->has('password') ? 'true' : 'false' }};
        const termsErr = {{ $errors->has('terms') ? 'true' : 'false' }};
        const vehErr = {{ ($errors->has('vehicle_make') || $errors->has('vehicle_model') || $errors->has('vehicle_registration')) ? 'true' : 'false' }};
        if (vehErr) { showStep(3); }
        else if (pwErr || termsErr) { showStep(2); }
        else { showStep(1); }
    });
    @endif

    // ── Password toggle ───────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const togglePw = document.getElementById('togglePw');
        const pwInput  = document.getElementById('password');
        const eyeShow  = document.getElementById('eyeShow');
        const eyeHide  = document.getElementById('eyeHide');
        if (togglePw) {
            togglePw.addEventListener('click', function() {
                const isText = pwInput.type === 'text';
                pwInput.type = isText ? 'password' : 'text';
                eyeShow.style.display = isText ? '' : 'none';
                eyeHide.style.display = isText ? 'none' : '';
            });
        }

        const togglePwc = document.getElementById('togglePwc');
        const pwcInput  = document.getElementById('password_confirmation');
        if (togglePwc) {
            togglePwc.addEventListener('click', function() {
                pwcInput.type = pwcInput.type === 'text' ? 'password' : 'text';
            });
        }

        // ── Password strength ─────────────────────────────────────────
        const pwBar   = document.getElementById('pwBar');
        const pwLabel = document.getElementById('pw-strength-label');
        const levels  = [
            { pct: 0,   color: '#e2e8f0', label: '' },
            { pct: 25,  color: '#ef4444', label: 'Trop court' },
            { pct: 50,  color: '#f59e0b', label: 'Faible' },
            { pct: 75,  color: '#3b82f6', label: 'Moyen' },
            { pct: 100, color: '#10b981', label: 'Fort' },
        ];
        function checkPwStrength(pw) {
            let score = 0;
            if (pw.length >= 8)   score++;
            if (pw.length >= 12)  score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;
            return Math.min(4, score);
        }
        pwInput.addEventListener('input', function() {
            const score = this.value ? checkPwStrength(this.value) : 0;
            const lvl   = levels[score];
            pwBar.style.width    = lvl.pct + '%';
            pwBar.style.background = lvl.color;
            pwLabel.textContent  = lvl.label;
            pwLabel.style.color  = lvl.color;
        });

        // ── Password confirm match ─────────────────────────────────────
        const matchMsg = document.getElementById('pw-match-msg');
        function checkMatch() {
            if (!pwcInput.value) { matchMsg.textContent = ''; return; }
            if (pwcInput.value === pwInput.value) {
                matchMsg.textContent = '✓ Les mots de passe correspondent';
                matchMsg.className   = 'reg-field-msg ok';
                pwcInput.classList.remove('is-error');
                pwcInput.classList.add('is-ok');
            } else {
                matchMsg.textContent = 'Les mots de passe ne correspondent pas';
                matchMsg.className   = 'reg-field-msg err';
                pwcInput.classList.remove('is-ok');
                pwcInput.classList.add('is-error');
            }
        }
        pwcInput.addEventListener('input', checkMatch);
        pwInput.addEventListener('input', function() { if (pwcInput.value) checkMatch(); });

        // ── Custom checkbox (terms) ────────────────────────────────────
        const termsInput = document.getElementById('terms');
        const termsBox   = document.getElementById('termsBox');
        const termsCheck = document.getElementById('termsCheck');
        document.getElementById('termsLabel').addEventListener('click', function(e) {
            if (e.target.tagName === 'A') return;
            termsInput.checked = !termsInput.checked;
            if (termsInput.checked) {
                termsBox.style.borderColor = '#10b981';
                termsBox.style.background  = '#10b981';
                termsCheck.style.display   = '';
            } else {
                termsBox.style.borderColor = '#cbd5e1';
                termsBox.style.background  = '#fff';
                termsCheck.style.display   = 'none';
            }
        });

        // ── Email async check ─────────────────────────────────────────
        const emailInput  = document.getElementById('email');
        const emailStatus = document.getElementById('email-status');
        let emailTimer;
        emailInput.addEventListener('blur', function() {
            clearTimeout(emailTimer);
            const val = this.value;
            if (!val || !val.includes('@')) { emailStatus.textContent = ''; return; }
            emailTimer = setTimeout(() => {
                fetch('{{ route("check-email") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ email: val })
                })
                .then(r => r.json())
                .then(d => {
                    emailStatus.textContent  = d.message;
                    emailStatus.className    = 'reg-field-msg ' + (d.available ? 'ok' : 'err');
                    emailInput.classList.toggle('is-ok',    d.available);
                    emailInput.classList.toggle('is-error', !d.available);
                })
                .catch(() => {});
            }, 500);
        });

        // ── Vehicle registration check ────────────────────────────────
        const regInput  = document.getElementById('vehicle_registration');
        const regStatus = document.getElementById('registration-status');
        let regTimer;
        regInput.addEventListener('blur', function() {
            clearTimeout(regTimer);
            const val = this.value;
            if (!val) { regStatus.textContent = ''; return; }
            regTimer = setTimeout(() => {
                fetch('{{ route("check-vehicle-registration") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ registration: val })
                })
                .then(r => r.json())
                .then(d => {
                    regStatus.textContent = d.message;
                    regStatus.className   = 'reg-field-msg ' + (d.available ? 'ok' : 'err');
                })
                .catch(() => {});
            }, 500);
        });

        // ── OCPP Tag check ────────────────────────────────────────────
        const ocppInput  = document.getElementById('existing_ocpp_tag');
        const ocppStatus = document.getElementById('ocpp-tag-status');
        let ocppTimer;
        ocppInput.addEventListener('blur', function() {
            clearTimeout(ocppTimer);
            const val = this.value;
            if (!val) {
                ocppStatus.textContent = 'Laissez vide si vous n\'avez pas de tag.';
                ocppStatus.className   = 'reg-field-msg hint';
                return;
            }
            ocppStatus.textContent = 'Vérification…';
            ocppTimer = setTimeout(() => {
                fetch('{{ route("check-ocpp-tag") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ ocpp_tag: val })
                })
                .then(r => r.json())
                .then(d => {
                    ocppStatus.textContent = d.message;
                    ocppStatus.className   = 'reg-field-msg ' + (d.available ? 'ok' : 'err');
                })
                .catch(() => {});
            }, 500);
        });

        // ── Submit feedback ───────────────────────────────────────────
        document.getElementById('regForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="spin" viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke-linecap="round"/></svg> Création en cours…';
        });
    });

    // ── OCPP toggle ───────────────────────────────────────────────────
    window.toggleOcpp = function() {
        const body   = document.getElementById('ocppBody');
        const toggle = document.getElementById('ocppToggle');
        const open   = body.classList.toggle('open');
        toggle.classList.toggle('open', open);
        document.getElementById('ocppArrow').style.transform = open ? 'rotate(180deg)' : '';
    };

}());
</script>
<style>
.spin { animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush
