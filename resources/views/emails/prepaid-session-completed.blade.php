@component('mail::message')
# Session de recharge terminée

Votre session de recharge EVON est terminée. Voici le récapitulatif :

## Détails de la session

**Numéro de réservation:** #{{ $reservation->id }}

**Borne:** {{ $reservation->chargingPoint->name ?? 'N/A' }}

**Date de début:** {{ $reservation->start_time->format('d/m/Y H:i') }}
**Date de fin:** {{ $reservation->end_time->format('d/m/Y H:i') }}

## Consommation

**Énergie consommée:** {{ number_format($reservation->actual_energy ?? 0, 2) }} kWh
**Durée:** {{ $reservation->actual_duration ?? 0 }} minutes

**Coût total:** {{ number_format($reservation->actual_cost ?? 0, 2) }} EUR
**Montant payé:** {{ number_format($reservation->prepaid_amount ?? $reservation->amount, 2) }} EUR

@if($refundAmount > 0)
## Remboursement

Un montant de **{{ number_format($refundAmount, 2) }} EUR** vous sera remboursé sur votre moyen de paiement.

Le remboursement sera effectué dans un délai de 5 à 10 jours ouvrables.
@else
Le montant payé correspond à votre consommation. Aucun remboursement n'est nécessaire.
@endif

Merci d'avoir utilisé EVON !

@component('mail::subcopy')
Cet email a été envoyé à {{ $reservation->guest_email ?? 'votre adresse email' }}
@endcomponent
@endcomponent
