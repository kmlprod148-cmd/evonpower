# 🚀 Guide de déploiement des corrections - 23 Décembre 2025

## 📊 Résumé des corrections

### ✅ Corrections de routes (doublons éliminés)

| Problème | Fichier modifié | Action |
|----------|-----------------|--------|
| `admin.notifications.api.unread` dupliquée | `routes/api.php` | Renommée en `api.admin.notifications.unread` |
| `admin.users.*` dupliquées | `routes/web.php` | Groupe de routes commenté (déjà dans `admin.php`) |
| `admin.transactions.export` dupliquée | `routes/admin.php` | Renommée en `admin.transactions.export.csv` |

### ✅ Correction de route invalide

| Problème | Fichier modifié | Action |
|----------|-----------------|--------|
| Route `/language/2?lang=fr` (404) | `routes/language.php` | Contrainte supprimée pour accepter tous les formats |
| | `app/Http/Controllers/LocaleController.php` | Détection et redirection des IDs numériques |

---

## 🔧 Commandes de déploiement sur le serveur

Connectez-vous au serveur de production et exécutez ces commandes **dans l'ordre** :

### 1️⃣ Sauvegarder l'état actuel

```bash
# Se connecter au serveur
ssh evonpower@himalaya.evonpower.com

# Aller dans le répertoire de l'application
cd /home/evonpower/devcharge.evonpower.com

# Créer une sauvegarde
cp -r routes routes_backup_$(date +%Y%m%d_%H%M%S)
cp -r app/Http/Controllers app/Http/Controllers_backup_$(date +%Y%m%d_%H%M%S)
```

### 2️⃣ Récupérer les dernières modifications

```bash
# Stasher les modifications locales si nécessaire
git stash

# Récupérer les dernières modifications
git pull origin main

# Ou si vous utilisez une autre branche
# git pull origin votre-branche
```

### 3️⃣ Nettoyer TOUS les caches

```bash
# Nettoyer les caches Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Nettoyer le cache OPcache (si activé)
php artisan optimize:clear
```

### 4️⃣ Recréer les caches (OPTIONNEL - pour les performances)

```bash
# Recréer les caches optimisés
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> ⚠️ **IMPORTANT** : Si `route:cache` échoue avec une erreur de doublon, **NE PAS** recréer le cache. L'application fonctionnera sans cache de routes.

### 5️⃣ Vérifier les permissions

```bash
# S'assurer que les permissions sont correctes
chmod -R 775 storage bootstrap/cache
chown -R evonpower:evonpower storage bootstrap/cache
```

### 6️⃣ Redémarrer les services

```bash
# Redémarrer les workers de queue si vous en utilisez
php artisan queue:restart

# Redémarrer PHP-FPM (adapter selon votre configuration)
sudo systemctl restart php8.2-fpm
# OU
sudo service php-fpm restart
```

---

## 🧪 Tests après déploiement

### Test 1 : Vérifier qu'il n'y a plus d'erreur de routes

```bash
# Tester la compilation du cache des routes
php artisan route:cache

# Devrait afficher : "Routes cached successfully."
```

### Test 2 : Vérifier les logs

```bash
# Surveiller les logs en temps réel
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# OU vérifier les dernières erreurs
tail -100 storage/logs/laravel-$(date +%Y-%m-%d).log | grep "ERROR"
```

### Test 3 : Tester le changement de langue

1. Ouvrir l'application dans un navigateur
2. Cliquer sur le sélecteur de langue
3. Changer de langue plusieurs fois
4. Vérifier qu'il n'y a **pas d'erreur 404**
5. Vérifier que la langue change correctement

### Test 4 : Tester les routes admin

1. Se connecter en tant qu'administrateur
2. Accéder aux pages suivantes :
   - `/admin/users` (liste des utilisateurs)
   - `/admin/transactions` (liste des transactions)
   - `/admin/transactions/export/csv` (export CSV)
3. Vérifier qu'aucune erreur n'apparaît

---

## 📝 Fichiers modifiés (pour référence)

### Routes
- `routes/api.php` (ligne 110)
- `routes/web.php` (lignes 1081-1090 commentées)
- `routes/admin.php` (ligne 47)
- `routes/language.php` (ligne 28)

### Contrôleurs
- `app/Http/Controllers/LocaleController.php` (méthode `switch()`)

---

## 🔍 Vérification rapide des corrections

### Vérifier que les modifications sont présentes

```bash
# Vérifier la route API renommée
grep "api.admin.notifications.unread" routes/api.php

# Vérifier que les routes users sont commentées dans web.php
grep -A 5 "// Users - COMMENTÉ" routes/web.php

# Vérifier la route admin.transactions.export.csv
grep "export.csv" routes/admin.php

# Vérifier la gestion des IDs numériques dans LocaleController
grep "is_numeric" app/Http/Controllers/LocaleController.php
```

---

## ⚠️ En cas de problème

### Si l'application ne démarre pas après le déploiement

```bash
# 1. Vérifier les logs d'erreur
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log

# 2. Nettoyer à nouveau tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Ne PAS utiliser route:cache si ça échoue
# L'application fonctionne sans cache de routes
```

### Si les modifications ne sont pas prises en compte

```bash
# 1. Vérifier que les fichiers ont bien été mis à jour
git log --oneline -5
git status

# 2. Forcer le rechargement des vues
php artisan view:clear
rm -rf storage/framework/views/*

# 3. Redémarrer PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Restaurer la sauvegarde en cas de problème majeur

```bash
# Restaurer les fichiers de routes
rm -rf routes
cp -r routes_backup_XXXXXX_XXXXXX routes

# Restaurer les contrôleurs
rm -rf app/Http/Controllers
cp -r app/Http/Controllers_backup_XXXXXX_XXXXXX app/Http/Controllers

# Nettoyer les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 📞 Support

En cas de problème persistant après le déploiement, vérifier :

1. Les logs Laravel : `storage/logs/laravel-YYYY-MM-DD.log`
2. Les logs PHP-FPM : `/var/log/php8.2-fpm.log`
3. Les logs Nginx/Apache : `/var/log/nginx/error.log`

---

**Date de création** : 23 Décembre 2025  
**Version** : 1.0  
**Testé localement** : ✅ Oui  
**Cache de routes compile** : ✅ Oui (sans erreurs)


