<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Redirection vers CMI - Recharge de crédit</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="now">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Redirection vers la passerelle de paiement CMI...</h2>
        <div class="spinner"></div>
        <p>Veuillez patienter, vous allez être redirigé automatiquement.</p>
        <p style="color: #666; font-size: 0.9rem;">Recharge de crédit: {{ number_format($recharge->amount, 2) }} {{ $recharge->currency }}</p>
    </div>

    <form name="pay_form" id="pay_form" method="POST" action="{{ $paymentUrl }}">
        @foreach($paymentData as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ htmlspecialchars($value, ENT_QUOTES, 'UTF-8') }}" />
        @endforeach
    </form>

    <script type="text/javascript">
        // CRITIQUE : S'assurer que le formulaire POST est soumis automatiquement
        // CMI exige une requête HTTP POST, pas une redirection GET
        (function() {
            let formSubmitted = false;
            const form = document.getElementById('pay_form');
            
            // Vérifier que le formulaire existe
            if (!form) {
                console.error('❌ ERREUR CRITIQUE: Formulaire CMI introuvable!');
                alert('Erreur: Le formulaire de paiement n\'a pas pu être chargé. Veuillez réessayer.');
                return;
            }
            
            // Vérifier que l'action du formulaire est correcte
            const actionUrl = form.getAttribute('action');
            if (!actionUrl || actionUrl === '') {
                console.error('❌ ERREUR CRITIQUE: L\'URL d\'action du formulaire est vide!');
                alert('Erreur: L\'URL de paiement n\'est pas configurée. Veuillez contacter le support.');
                return;
            }
            
            console.log('✅ Formulaire CMI détecté, action:', actionUrl);
            console.log('✅ Méthode:', form.getAttribute('method'));
            console.log('✅ Nombre de champs:', form.querySelectorAll('input[type="hidden"]').length);
            
            // Fonction de soumission sécurisée
            function submitForm() {
                if (formSubmitted) {
                    console.warn('⚠️ Formulaire déjà soumis, ignore...');
                    return;
                }
                
                if (!form) {
                    console.error('❌ Formulaire CMI introuvable lors de la soumission');
                    return;
                }
                
                formSubmitted = true;
                
                // Vérifier que tous les champs requis sont présents
                const requiredFields = ['clientid', 'amount', 'oid', 'HASH'];
                const missingFields = [];
                requiredFields.forEach(function(field) {
                    const input = form.querySelector('input[name="' + field + '"]');
                    if (!input || !input.value) {
                        missingFields.push(field);
                    }
                });
                
                if (missingFields.length > 0) {
                    console.error('❌ Champs manquants dans le formulaire:', missingFields);
                    alert('Erreur: Des champs requis sont manquants dans le formulaire de paiement. Veuillez réessayer.');
                    formSubmitted = false;
                    return;
                }
                
                console.log('🚀 Soumission du formulaire POST vers CMI...');
                
                // Désactiver le formulaire pour éviter les soumissions multiples
                form.style.display = 'none';
                
                // Soumettre le formulaire POST
                try {
                    form.submit();
                    console.log('✅ Formulaire POST soumis avec succès');
                } catch (e) {
                    console.error('❌ Erreur lors de la soumission du formulaire CMI:', e);
                    formSubmitted = false;
                    form.style.display = 'block';
                    alert('Erreur lors de la soumission du formulaire. Veuillez réessayer.');
                }
            }
            
            // Soumettre dès que le DOM est prêt
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(submitForm, 50);
                }, { once: true });
            } else if (document.readyState === 'interactive') {
                setTimeout(submitForm, 100);
            } else {
                setTimeout(submitForm, 50);
            }
            
            // Protection supplémentaire
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (formSubmitted) {
                        console.warn('⚠️ Tentative de soumission multiple bloquée');
                        e.preventDefault();
                        return false;
                    }
                    formSubmitted = true;
                    console.log('✅ Soumission du formulaire confirmée');
                });
            }
            
            // Fallback : forcer la soumission après 2 secondes
            setTimeout(function() {
                if (!formSubmitted && form) {
                    console.warn('⚠️ Timeout: Forcer la soumission du formulaire');
                    submitForm();
                }
            }, 2000);
        })();
    </script>
</body>
</html>

