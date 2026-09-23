<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Redirection vers CMI — Abonnement</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="now">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f0f4ff}
        .card{text-align:center;background:#fff;padding:2.5rem 2rem;border-radius:12px;box-shadow:0 4px 24px rgba(99,102,241,.15);max-width:420px;width:90%}
        .logo{font-size:2rem;margin-bottom:1rem}
        h2{color:#1e293b;font-size:1.1rem;margin-bottom:.5rem}
        p{color:#64748b;font-size:.875rem;margin-bottom:.5rem}
        .amount{font-size:1.5rem;font-weight:700;color:#6366f1;margin:.75rem 0}
        .spinner{border:4px solid #e0e7ff;border-top:4px solid #6366f1;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;margin:1rem auto}
        @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
        .secure{display:flex;align-items:center;justify-content:center;gap:.4rem;color:#94a3b8;font-size:.75rem;margin-top:1rem}
        .secure svg{width:14px;height:14px}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">🔒</div>
        <h2>Redirection vers la passerelle CMI...</h2>
        <div class="amount">{{ number_format($subscription->amount_paid, 2, ',', ' ') }} €</div>
        <p>Abonnement : {{ $subscription->subscriptionPlan->name ?? 'Plan' }}</p>
        <div class="spinner"></div>
        <p>Veuillez ne pas fermer cette page.</p>
        <div class="secure">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Paiement 3D Secure sécurisé par CMI
        </div>
    </div>

    <form name="pay_form" id="pay_form" method="POST" action="{{ $paymentUrl }}">
        @foreach($paymentData as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') }}" />
        @endforeach
    </form>

    <script>
    (function() {
        var submitted = false;
        var form = document.getElementById('pay_form');

        function submit() {
            if (submitted || !form) return;
            var required = ['clientid','amount','oid','hash','HASH'];
            var missing = required.filter(function(f){ return !form.querySelector('input[name="'+f+'"]'); });
            if (missing.length === required.length) {
                alert('Erreur: formulaire de paiement invalide. Veuillez réessayer.');
                return;
            }
            submitted = true;
            form.submit();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function(){ setTimeout(submit, 100); }, {once:true});
        } else {
            setTimeout(submit, 100);
        }

        // Fallback after 3s
        setTimeout(function(){ if (!submitted) submit(); }, 3000);
    })();
    </script>
</body>
</html>
