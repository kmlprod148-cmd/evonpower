# 🔐 Configuration des Utilisateurs Admin et Super Admin

Ce guide explique comment créer rapidement les utilisateurs administrateurs avec toutes les permissions pour votre application EVON.

## 📋 Table des matières

- [Commande rapide](#commande-rapide)
- [Options disponibles](#options-disponibles)
- [Comptes créés](#comptes-créés)
- [Permissions](#permissions)
- [Exemples d'utilisation](#exemples-dutilisation)

---

## 🚀 Commande rapide

Pour créer tous les utilisateurs admin et super admin avec toutes les permissions :

```bash
php artisan seed:admin-users
```

Cette commande va :
1. ✅ Créer **toutes les permissions** du système (270+ permissions)
2. ✅ Créer tous les **rôles** nécessaires (super_admin, admin, integrator, etc.)
3. ✅ Créer un **Super Administrateur** avec TOUS les droits
4. ✅ Créer un **Administrateur** avec les droits admin
5. ✅ Créer des **utilisateurs de démonstration** pour chaque rôle

---

## ⚙️ Options disponibles

### Option `--fresh`
Supprime tous les utilisateurs, rôles et permissions existants avant de créer les nouveaux.

```bash
php artisan seed:admin-users --fresh
```

⚠️ **ATTENTION** : Cette option supprime toutes les données existantes !

### Option `--force`
Force l'exécution même en environnement de production.

```bash
php artisan seed:admin-users --force
```

### Combiner les options

```bash
php artisan seed:admin-users --fresh --force
```

---

## 👥 Comptes créés

### 👑 Super Administrateur

**Le compte avec TOUS les pouvoirs** :

- **Email** : `superadmin@evonpower.com`
- **Mot de passe** : `SuperAdmin2024!`
- **Rôle** : `super_admin`
- **Permissions** : **TOUTES** (*)
- **Peut** :
  - ✅ Gérer tous les utilisateurs, rôles et permissions
  - ✅ Accéder à toutes les fonctionnalités système
  - ✅ Effectuer des sauvegardes/restaurations
  - ✅ Se connecter en tant qu'autres utilisateurs
  - ✅ Tout modifier sans restriction

---

### 🛡️ Administrateur

**Le compte admin standard** :

- **Email** : `admin@evonpower.com`
- **Mot de passe** : `Admin2024!`
- **Rôle** : `admin`
- **Permissions** : Presque toutes (sauf super-admin)
- **Peut** :
  - ✅ Gérer les utilisateurs et les rôles
  - ✅ Gérer les intégrateurs, partenaires, opérateurs
  - ✅ Gérer les bornes de recharge et stations
  - ✅ Voir et gérer les transactions
  - ✅ Générer des rapports
  - ✅ Gérer les paramètres système
  - ❌ Ne peut PAS se connecter en tant qu'autres utilisateurs
  - ❌ Ne peut PAS faire de sauvegardes système

---

### 🎭 Utilisateurs de démonstration

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin Démo | `demo.admin@evonpower.com` | `Demo2024!` |
| Intégrateur | `integrator@evonpower.com` | `Integrator2024!` |
| Partenaire | `partner@evonpower.com` | `Partner2024!` |
| Opérateur | `operator@evonpower.com` | `Operator2024!` |
| Utilisateur | `user@evonpower.com` | `User2024!` |

---

## 🔑 Permissions

### Liste complète des permissions créées

Le seeder crée automatiquement **270+ permissions** organisées par catégorie :

#### 📊 Administration
- `admin_access`, `manage_all`
- `view_admin_dashboard`, `view_dashboard_stats`

#### 👤 Gestion des utilisateurs
- `view_users`, `create_users`, `edit_users`, `delete_users`
- `manage_users`, `activate_users`, `deactivate_users`

#### 🏢 Gestion des intégrateurs
- `view_integrators`, `create_integrators`, `edit_integrators`
- `delete_integrators`, `manage_integrators`

#### 🤝 Gestion des partenaires
- `view_partners`, `create_partners`, `edit_partners`
- `delete_partners`, `activate_partners`, `deactivate_partners`

#### ⚡ Gestion des bornes de recharge
- `view_charging_points`, `create_charging_points`
- `edit_charging_points`, `delete_charging_points`
- `manage_qr_codes`

#### 🏪 Gestion des stations
- `view_stations`, `create_stations`
- `edit_stations`, `delete_stations`

#### 👥 Gestion des groupes
- `view_groups`, `create_groups`
- `edit_groups`, `delete_groups`

#### 💰 Gestion des transactions
- `view_transactions`, `create_transactions`
- `edit_transactions`, `manage_transactions`

#### 💼 Profils business
- `view_business_profiles`, `create_business_profiles`
- `edit_business_profiles`, `delete_business_profiles`

#### 💵 Commissions et plans
- `view_commissions`, `manage_commissions`
- `view_pricing_plans`, `create_plans`

#### 📈 Rapports
- `view_reports`, `create_reports`
- `generate_reports`, `export_reports`

#### 💳 Paiements et wallet
- `view_wallet`, `manage_wallet`
- `view_payments`, `manage_payments`

#### 🔐 Sécurité et système
- `view_audit_logs`
- `manage_system`
- `backup_system`, `restore_system`
- `impersonate_users`

Et bien d'autres...

---

## 📝 Exemples d'utilisation

### Scénario 1 : Première installation

```bash
# Installation complète avec tous les utilisateurs
php artisan seed:admin-users
```

### Scénario 2 : Réinitialiser complètement

```bash
# Supprimer tous les utilisateurs et recréer
php artisan seed:admin-users --fresh

# Confirmer quand demandé
```

### Scénario 3 : Mise à jour en production

```bash
# Ajouter/mettre à jour les utilisateurs en production
php artisan seed:admin-users --force
```

### Scénario 4 : Reset complet en production

```bash
# Reset total en production (DANGEREUX!)
php artisan seed:admin-users --fresh --force
```

---

## 🔧 Utilisation programmatique

Vous pouvez aussi exécuter le seeder directement :

```bash
php artisan db:seed --class=CompleteAdminSuperAdminSeeder
```

---

## 🆘 Dépannage

### Problème : "Role already exists"

**Solution** : Utilisez l'option `--fresh` pour réinitialiser :

```bash
php artisan seed:admin-users --fresh
```

### Problème : "Permission already exists"

**Solution** : Le seeder utilise `updateOrCreate`, donc aucun problème normalement. Si l'erreur persiste :

```bash
php artisan cache:clear
php artisan config:clear
php artisan seed:admin-users
```

### Problème : "Cannot run in production"

**Solution** : Ajoutez l'option `--force` :

```bash
php artisan seed:admin-users --force
```

---

## ⚡ Workflow recommandé

### Développement local

```bash
# 1. Réinitialiser la base de données
php artisan migrate:fresh

# 2. Créer les utilisateurs admin
php artisan seed:admin-users

# 3. (Optionnel) Ajouter des données de démo
php artisan db:seed
```

### Déploiement en production

```bash
# 1. Exécuter les migrations
php artisan migrate --force

# 2. Créer les utilisateurs admin (si première installation)
php artisan seed:admin-users --force

# 3. Nettoyer le cache
php artisan optimize:clear
```

---

## 📊 Après l'exécution

Après avoir exécuté la commande, vous verrez un récapitulatif complet :

```
╔══════════════════════════════════════════════════════════════╗
║         🔐 IDENTIFIANTS CRÉÉS AVEC SUCCÈS                   ║
╚══════════════════════════════════════════════════════════════╝

┌──────────────────────────────────────────────────────────────┐
│ 👑 SUPER ADMINISTRATEUR (Tous les droits)                   │
├──────────────────────────────────────────────────────────────┤
│ Email    : superadmin@evonpower.com                          │
│ Password : SuperAdmin2024!                                   │
│ Rôle     : super_admin                                       │
│ Permissions : TOUTES (*)                                     │
└──────────────────────────────────────────────────────────────┘

📊 STATISTIQUES:
   • Permissions créées : 270+
   • Rôles créés        : 7
   • Utilisateurs créés : 7
```

---

## 🔒 Sécurité

### ⚠️ Recommandations importantes

1. **Changez les mots de passe** après la première connexion
2. **Ne partagez jamais** les identifiants super admin
3. **Utilisez le compte admin** pour les tâches quotidiennes
4. **Activez la 2FA** (authentification à deux facteurs) quand disponible
5. **Surveillez les logs** d'accès aux comptes admin

### 🔐 Changer les mots de passe par défaut

```php
// Dans tinker ou via l'interface
php artisan tinker

$user = User::where('email', 'superadmin@evonpower.com')->first();
$user->password = bcrypt('VotreNouveauMotDePasseSécurisé');
$user->save();
```

---

## 📚 Fichiers créés

- **Seeder** : `database/seeders/CompleteAdminSuperAdminSeeder.php`
- **Commande** : `app/Console/Commands/SeedAdminUsers.php`
- **Documentation** : `ADMIN_USERS_SETUP.md` (ce fichier)

---

## 🎯 Résumé rapide

```bash
# Commande principale
php artisan seed:admin-users

# Identifiants Super Admin
superadmin@evonpower.com / SuperAdmin2024!

# Identifiants Admin
admin@evonpower.com / Admin2024!
```

**C'est tout ! Vous êtes prêt à administrer votre application EVON ! 🚀**

---

## 📞 Support

Si vous rencontrez des problèmes, vérifiez :
- Les logs Laravel : `storage/logs/laravel.log`
- Les migrations ont été exécutées : `php artisan migrate:status`
- Spatie Permission est installé : `composer show spatie/laravel-permission`

---

**Dernière mise à jour** : Décembre 2024
**Version** : 1.0.0

