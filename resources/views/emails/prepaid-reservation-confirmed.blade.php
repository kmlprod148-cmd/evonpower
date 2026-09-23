@component('mail::message')
# Confirmation de votre réservation

Merci pour votre réservation EVON ! Votre paiement a été confirmé avec succès.

## Détails de la réservation

**Numéro de réservation:** #{{ $reservation->id }}

**Borne:** {{ $reservation->chargingPoint->name ?? 'N/A' }}

**Date de réservation:** {{ $reservation->created_at->format('d/m/Y H:i') }}

**Montant payé:** {{ number_format($reservation->prepaid_amount ?? $reservation->amount, 2) }} EUR

**Type de recharge:** {{ $reservation->reservation_type === 'kwh' ? 'Énergie (kWh)' : 'Durée (minutes)' }}
**Quantité:** {{ $reservation->reservation_value }}

## Prochaines étapes

1. **Rendez-vous à la borne** indiquer dans l'application
2. **Scannez le code QR** ou entrez votre token d'activation
3. **Connectez votre véhicule** et la recharge démarrera automatiquement
4. **Credit non utilisé:** Le montant non consommé vous sera automatiquement remboursé

## Code d'activation

@if($reservation->activation_token)
Votre token d'activation: **{{ $reservation->activation_token }}**
@endif

Si vous avez des questions, n'hésitez pas à nous contacter.

Merci d'utiliser EVON !

@component('mail::subcopy')
Cet email a été envoyé à {{ $reservation->guest_email ?? 'votre adresse email' }}
@endcomponent
@endcomponent
