<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Confirmé - EvonPower</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        
        .email-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 30px;
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success-icon .icon {
            display: inline-block;
            width: 60px;
            height: 60px;
            background: #10b981;
            border-radius: 50%;
            line-height: 60px;
            font-size: 30px;
            color: white;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .message {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .details-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .details-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #374151;
        }
        
        .detail-value {
            color: #1f2937;
            font-weight: 600;
        }
        
        .amount-highlight {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }
        
        .amount-value {
            font-size: 24px;
            font-weight: 700;
            color: #166534;
        }
        
        .next-steps {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .next-steps h3 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
            color: #1e40af;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
        
        .cta-button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }
        
        .cta-button:hover {
            background: #2563eb;
        }
        
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🎉 Paiement Confirmé !</h1>
            <p>Votre réservation EvonPower est maintenant active</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="success-icon">
                <span class="icon">✓</span>
            </div>
            
            <div class="greeting">
                Bonjour {{ $user->name ?? 'Cher client' }},
            </div>
            
            <div class="message">
                Excellente nouvelle ! Votre paiement a été traité avec succès et votre réservation de borne de recharge est maintenant confirmée.
            </div>
            
            <!-- Montant payé -->
            <div class="amount-highlight">
                <div style="color: #6b7280; font-size: 14px; margin-bottom: 5px;">Montant payé</div>
                <div class="amount-value">{{ number_format($amount, 2) }} {{ $currency ?? 'EUR' }}</div>
            </div>
            
            <!-- Détails de la réservation -->
            <div class="details-card">
                <div class="details-title">
                    📋 Détails de votre réservation
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Numéro de réservation</span>
                    <span class="detail-value">#{{ $reservation->id }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Borne de recharge</span>
                    <span class="detail-value">{{ $chargingPoint->name ?? 'Borne EvonPower' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Adresse</span>
                    <span class="detail-value">{{ $chargingPoint->address ?? 'Adresse non disponible' }}</span>
                </div>
                
                @if($reservationDate)
                <div class="detail-row">
                    <span class="detail-label">Date de début</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($reservationDate)->format('d/m/Y H:i') }}</span>
                </div>
                @endif
                
                @if($reservationDuration)
                <div class="detail-row">
                    <span class="detail-label">Durée prévue</span>
                    <span class="detail-value">{{ $reservationDuration }} minutes</span>
                </div>
                @endif
                
                @if($reservationEnergy)
                <div class="detail-row">
                    <span class="detail-label">Énergie prévue</span>
                    <span class="detail-value">{{ $reservationEnergy }} kWh</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">Méthode de paiement</span>
                    <span class="detail-value">
                        @if($paymentMethod === 'cmi')
                            🏦 CMI
                        @elseif($paymentMethod === 'stripe')
                            💳 Carte bancaire
                        @else
                            💰 {{ ucfirst($paymentMethod) }}
                        @endif
                    </span>
                </div>
            </div>
            
            <!-- Prochaines étapes -->
            <div class="next-steps">
                <h3>📝 Prochaines étapes</h3>
                <ul>
                    <li>Présentez-vous à la borne à l'heure prévue</li>
                    <li>Utilisez votre code de réservation pour démarrer la charge</li>
                    <li>Gardez ce reçu comme preuve de paiement</li>
                    <li>Contactez-nous en cas de problème</li>
                </ul>
            </div>
            
            <!-- Bouton d'action -->
            <div style="text-align: center;">
                <a href="{{ route('dashboard') }}" class="cta-button">
                    Voir mes réservations
                </a>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
                <p><strong>Besoin d'aide ?</strong></p>
                <p>Si vous avez des questions concernant votre réservation, n'hésitez pas à nous contacter :</p>
                <p>📧 Email : support@evonpower.com<br>
                📞 Téléphone : +212 5XX XXX XXX</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} EvonPower. Tous droits réservés.</p>
            <p>
                <a href="{{ route('dashboard') }}">Tableau de bord</a> | 
                <a href="{{ route('contact') }}">Contact</a> | 
                <a href="{{ route('privacy') }}">Confidentialité</a>
            </p>
        </div>
    </div>
</body>
</html>
