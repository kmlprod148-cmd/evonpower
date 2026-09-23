<p align="center">
  <strong>EVON Power</strong><br>
  Solutions de recharge intelligentes
</p>

<p align="center">
  Plateforme de gestion de bornes de recharge pour véhicules électriques.<br>
  Sessions, réservations, solde et réseau, dans un seul espace.
</p>

<p align="center">
  <img alt="Auteur" src="https://img.shields.io/badge/Auteur-Kamal%20Zakoune-10B981">
  <img alt="Origine" src="https://img.shields.io/badge/Maroc-🇲🇦-047857">
  <img alt="Durée" src="https://img.shields.io/badge/Travail-1%20an-F59E0B">
  <img alt="Licence" src="https://img.shields.io/badge/Licence-GPL--3.0-3B82F6">
</p>

---

## À propos

**EVON Power** est une application web de pilotage d’un réseau de recharge électrique. Elle relie les bornes, les conducteurs et les opérateurs : démarrer ou arrêter une charge, suivre une session, réserver un créneau, et gérer un solde.

Le cœur technique parle **OCPP** avec le serveur **SteVe** : commandes à distance, événements de transaction, réconciliation des sessions et supervision des connecteurs. Autour, un back-office couvre les rôles, la tarification, le portefeuille, la facturation et les paiements.

Ce dépôt est un **projet personnel à but éducatif**, mené pendant **un an** par **Kamal Zakoune**, ingénieur logiciel au Maroc.

## Ce que la plateforme couvre

| Domaine | Contenu |
| --- | --- |
| Bornes | Points de charge, stations, connecteurs, statut en temps réel |
| Sessions | Démarrage et arrêt à distance, prépayé et postpayé, timeouts, réconciliation |
| Protocole | OCPP via SteVe (HTTP, webhooks, file de commandes, WebSocket) |
| Réservations | Créneaux, participants, lien avec la session de charge |
| Argent | Portefeuille, crédits, transactions, commissions, factures, retraits |
| Réseau | Hiérarchie admin, intégrateur, partenaire, groupe, opérateur |
| Accès | Rôles, permissions, authentification, OTP par SMS |
| Produit | Tableaux de bord, notifications, abonnements, profils business, QR code |
| Langues | Français, anglais, arabe (RTL), espagnol |

```text
Admin
 └── Intégrateur
      ├── Partenaire
      │    └── Groupe
      │         └── Borne
      └── Opérateur
```

## Pile technique

Construit avec **Laravel** (PHP), **Livewire**, **Inertia**, **Laravel Sanctum**, files d’attente et événements. L’interface s’appuie sur **Tailwind CSS**. Les paiements prévus dans la configuration incluent **Stripe** et **CMI**. Les SMS passent par un client **Infobip**. La documentation détaillée vit dans [`docs/`](docs/).

```text
app/            Modèles, services OCPP, contrôleurs, jobs, politiques
config/         Marque, SteVe, paiements, langues, permissions
database/       Migrations
docs/           Références fonctionnelles et techniques
```

## Auteur

**Kamal Zakoune** — ingénieur logiciel, Maroc.

EVON Power a été réalisé **uniquement en vibecoding** : le code a été produit en guidant des assistants d’IA, avec le jugement d’ingénierie, l’architecture et les décisions produit portés par l’auteur. Le projet a demandé **une année de travail**.

Contact : [kmldeveloper@gmail.com](mailto:kmldeveloper@gmail.com)

## Droits

- Projet **indépendant**, réalisé **à des fins éducatives**.
- **Aucune société** n’en détient les droits.
- **Kamal Zakoune** conserve **l’intégralité des droits d’usage** sur cette œuvre.

Le fichier [`LICENSE`](LICENSE) place la distribution du code sous **GNU GPL v3**. Cette licence n’attribue la propriété à aucune entreprise.

## Remerciement

À ma mère. Ce travail existe grâce à elle.

---

<p align="center">
  EVON Power · Kamal Zakoune · <a href="mailto:kmldeveloper@gmail.com">kmldeveloper@gmail.com</a>
</p>
