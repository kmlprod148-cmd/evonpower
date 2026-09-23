# 🚀 Seed des Utilisateurs Admin et Super Admin

## ⚡ Commande rapide

```bash
php artisan seed:admin-users
```

---

## 🎯 Ce qui est créé

### 👥 Utilisateurs

| Rôle | Email | Mot de passe | Permissions |
|------|-------|--------------|-------------|
| **Super Admin** | `superadmin@evonpower.com` | `SuperAdmin2024!` | **TOUTES** (wildcard *) |
| **Admin** | `admin@evonpower.com` | `Admin2024!` | Presque toutes |
| Admin Démo | `demo.admin@evonpower.com` | `Demo2024!` | Admin |
| Intégrateur | `integrator@evonpower.com` | `Integrator2024!` | Intégrateur |
| Partenaire | `partner@evonpower.com` | `Partner2024!` | Partenaire |
| Opérateur | `operator@evonpower.com` | `Operator2024!` | Opérateur |
| Utilisateur | `user@evonpower.com` | `User2024!` | Utilisateur |

### 🔐 Permissions créées

**270+ permissions** réparties en :
- 👤 Gestion des utilisateurs (20+)
- 🏢 Gestion des intégrateurs (15+)
- 🤝 Gestion des partenaires (20+)
- ⚡ Gestion des bornes (25+)
- 🏪 Gestion des stations (15+)
- 👥 Gestion des groupes (15+)
- 💰 Gestion des transactions (20+)
- 💼 Profils business (25+)
- 💵 Commissions (15+)
- 📈 Rapports (20+)
- Et bien plus...

### 🎭 Rôles créés

7 rôles avec hiérarchie :
1. `super_admin` - Niveau 5 (tous les droits)
2. `admin` - Niveau 4
3. `integrator` - Niveau 3
4. `partner` - Niveau 2
5. `operator` - Niveau 1
6. `client` - Niveau 0
7. `user` - Niveau 0

---

## 📝 Options

```bash
# Création simple
php artisan seed:admin-users

# Réinitialisation complète (⚠️ supprime tout)
php artisan seed:admin-users --fresh

# En production
php artisan seed:admin-users --force

# Les deux combinés
php artisan seed:admin-users --fresh --force
```

---

## 📂 Fichiers créés

### Code source
- ✅ `database/seeders/CompleteAdminSuperAdminSeeder.php` - Seeder principal
- ✅ `app/Console/Commands/SeedAdminUsers.php` - Commande Artisan

### Documentation
- ✅ `ADMIN_USERS_SETUP.md` - Documentation complète (EN)
- ✅ `GUIDE_ADMIN_RAPIDE.md` - Guide rapide (FR)
- ✅ `COMMANDES_SEED.txt` - Référence des commandes (FR)
- ✅ `README_SEED.md` - Ce fichier

---

## 🔐 Sécurité

> ⚠️ **IMPORTANT** : Changez TOUJOURS les mots de passe par défaut après la première connexion !

### Changer un mot de passe

```bash
php artisan tinker
```

```php
$user = User::where('email', 'superadmin@evonpower.com')->first();
$user->password = bcrypt('VotreNouveauMotDePasseSécurisé123!');
$user->save();
```

---

## 💡 Workflow recommandé

### Développement local

```bash
# 1. Reset de la base de données
php artisan migrate:fresh

# 2. Créer les utilisateurs admin
php artisan seed:admin-users

# 3. (Optionnel) Autres seeders
php artisan db:seed
```

### Production

```bash
# 1. Migrations
php artisan migrate --force

# 2. Créer les utilisateurs admin (première fois seulement)
php artisan seed:admin-users --force

# 3. Cache
php artisan optimize
```

---

## 🎯 Différences Super Admin vs Admin

| Fonctionnalité | Super Admin | Admin |
|----------------|-------------|-------|
| Gérer utilisateurs | ✅ | ✅ |
| Gérer rôles/permissions | ✅ | ✅ |
| Gérer intégrateurs | ✅ | ✅ |
| Gérer bornes | ✅ | ✅ |
| Voir transactions | ✅ | ✅ |
| Générer rapports | ✅ | ✅ |
| **Se connecter en tant qu'autre** | ✅ | ❌ |
| **Sauvegarder/Restaurer système** | ✅ | ❌ |
| **Accès root système** | ✅ | ❌ |

> 💡 **Conseil** : Utilisez le compte Admin pour les tâches quotidiennes et réservez le Super Admin pour les opérations critiques.

---

## 🆘 Dépannage

| Problème | Solution |
|----------|----------|
| "Role already exists" | `php artisan seed:admin-users --fresh` |
| "Cannot run in production" | `php artisan seed:admin-users --force` |
| Erreurs de cache | `php artisan cache:clear && php artisan config:clear` |
| Permissions manquantes | Ré-exécuter le seeder |

---

## 📊 Vérification

Après l'exécution, vérifiez :

```bash
# Voir les utilisateurs créés
php artisan tinker
User::count()
User::pluck('email')

# Voir les rôles
Role::count()
Role::pluck('name')

# Voir les permissions
Permission::count()
```

---

## 🎉 C'est tout !

Vous êtes maintenant prêt à administrer votre application EVON !

**Prochaines étapes** :
1. ✅ Exécuter `php artisan seed:admin-users`
2. ✅ Se connecter avec `superadmin@evonpower.com`
3. ✅ Changer le mot de passe
4. ✅ Commencer à utiliser l'application

---

**Version** : 1.0.0  
**Date** : Décembre 2024  
**Auteur** : EVON Power Team

