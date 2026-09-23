# 🔑 Guide : Assigner le rôle Super Admin

Ce guide explique comment assigner le rôle **super_admin** à un utilisateur.

## 📋 Fichiers créés

1. **`app/Console/Commands/MakeSuperAdmin.php`** - Commande Artisan
2. **`make_super_admin.php`** - Script PHP standalone
3. **`make_user_4_super_admin.sql`** - Script SQL direct

---

## 🚀 Méthode 1 : Commande Artisan (Recommandée)

Une fois que votre environnement est correctement configuré :

```bash
php artisan user:make-super-admin 4
```

**Pour un autre utilisateur :**
```bash
php artisan user:make-super-admin {user_id}
```

---

## 💾 Méthode 2 : Script SQL (Pour production)

Sur votre serveur de production MySQL :

```bash
mysql -u votre_user -p votre_database < make_user_4_super_admin.sql
```

**Ou via la console MySQL :**
```bash
mysql -u votre_user -p votre_database
```

Puis copiez-collez le contenu du fichier `make_user_4_super_admin.sql`

---

## 🔧 Méthode 3 : Script PHP Standalone

Si votre environnement local est configuré :

```bash
php make_super_admin.php
```

---

## 🐚 Méthode 4 : Via Tinker

```bash
php artisan tinker
```

Puis dans Tinker :
```php
$user = App\Models\User::find(4);
$user->assignRole('super_admin');
echo "User {$user->name} is now super admin";
exit;
```

---

## ✅ Vérification

Pour vérifier que le rôle a été assigné :

**Via SQL :**
```sql
SELECT 
    u.id,
    u.name,
    u.email,
    r.name as role_name
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id
INNER JOIN roles r ON r.id = mhr.role_id
WHERE u.id = 4;
```

**Via Tinker :**
```php
$user = App\Models\User::find(4);
$user->getRoleNames(); // Affiche tous les rôles
$user->hasRole('super_admin'); // Retourne true/false
```

---

## 🗑️ Nettoyage

Une fois le rôle assigné, vous pouvez supprimer ces fichiers temporaires :

```bash
rm make_super_admin.php
rm make_user_4_super_admin.sql
rm SUPER_ADMIN_SETUP.md
```

La commande Artisan reste disponible : `app/Console/Commands/MakeSuperAdmin.php`

---

## 🎯 Résumé rapide pour l'utilisateur ID 4

**Sur le serveur de production (Himalaya) :**
```bash
mysql -u votre_user -p votre_database << 'EOF'
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', 4
FROM roles r WHERE r.name = 'super_admin'
ON DUPLICATE KEY UPDATE role_id = role_id;
EOF
```

---

## ❓ Problèmes courants

### Erreur : "could not find driver"
- Installez l'extension PHP PDO pour MySQL
- Vérifiez votre fichier `.env` (DB_CONNECTION devrait être 'mysql', pas 'sqlite')

### Le rôle 'super_admin' n'existe pas
```sql
INSERT INTO roles (name, guard_name, created_at, updated_at) 
VALUES ('super_admin', 'web', NOW(), NOW());
```

---

**Créé le :** 22 décembre 2024

