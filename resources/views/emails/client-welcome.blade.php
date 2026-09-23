@component('mail::message')
# Bienvenue chez EVON !

Merci de créer votre compte client EVON. Nous sommes ravis de vous compter parmi nos utilisateurs.

## Vos informations de connexion

**Email:** {{ $client->email }}
**Mot de passe temporaire:** {{ $tempPassword }}

Veuillez changer votre mot de passe lors de votre première connexion.

## Votre véhicule

**Marque:** {{ $vehicle->make }}
**Modèle:** {{ $vehicle->model }}
**Immatriculation:** {{ $vehicle->registration }}
@if($vehicle->connector_type)
**Type de connecteur:** {{ $vehicle->connector_type }}
@endif

## Prochaines étapes

1. Connectez-vous à votre espace client avec les identifiants ci-dessus
2. Changez votre mot de passe
3. Ajoutez des véhicules supplémentaires si nécessaire
4. Commandez votre tag OCPP pour démarrer vos sessions de charge

## Accéder à votre espace client

Vous pouvez accéder à votre espace client à l'adresse suivante :
[{{ route('login') }}]({{ route('login') }})

Si vous avez des questions, n'hésitez pas à nous contacter.

Merci d'utiliser EVON !

@component('mail::subcopy')
Cet email a été envoyé à {{ $client->email }}
@endcomponent
@endcomponent
