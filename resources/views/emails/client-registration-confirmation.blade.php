@component('mail::message')
# Confirmation de votre inscription - EVON

Merci de votre inscription chez EVON ! Nous sommes ravis de vous compter parmi nos utilisateurs.

## Vos informations de connexion

**Email:** {{ $client->email }}

Veuillez vous connecter avec le mot de passe que vous avez choisi lors de la création de votre compte.

## Prochaines étapes

1. Connectez-vous à votre espace client
2. Ajoutez des véhicules supplémentaires si nécessaire
3. Commandez votre tag OCPP pour démarrer vos sessions de charge

## Accéder à votre espace client

[Se connecter]({{ route('login') }})

Si vous avez des questions, n'hésitez pas à nous contacter.

Merci d'utiliser EVON !

@component('mail::subcopy')
Cet email a été envoyé à {{ $client->email }}
@endcomponent
@endcomponent
