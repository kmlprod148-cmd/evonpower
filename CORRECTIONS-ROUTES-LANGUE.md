# 🔧 Corrections des Routes et Système de Langue

## 📋 Résumé des problèmes corrigés

### 1. Erreur `Route [locale.switch] not defined`
**Problème :** Une vue utilisait `route('locale.switch')` mais la route s'appelait `language.switch`

**Solution :** Correction dans `resources/views/components/mobile-floating-widgets.blade.php`
- Ligne 56 : `route('locale.switch')` → `route('language.switch')`

### 2. Conflit de routes `transactions.export` 
**Problème :** Deux routes différentes utilisaient le même nom, causant une erreur lors de `php artisan route:cache`

**Solution :** Commenté les routes en double dans `routes/web.php`
- Lignes 889-894 : Routes redondantes commentées
- Lignes 1906-1913 : Route `transactions.history` commentée (doublon)

---

## 🚀 Déploiement sur le serveur de production

### Étape 1 : Transférer les fichiers modifiés

#### Option A : Via Git (Recommandé)
```bash
# Sur votre machine locale (Windows)
git add resources/views/components/mobile-floating-widgets.blade.php
git add routes/web.php
git commit -m "Fix: Correction route locale.switch et suppression doublons transactions"
git push origin main

# Sur le serveur
ssh evonpower@himalaya
cd /home/evonpower/devcharge.evonpower.com
git pull origin main
```

#### Option B : Via SCP (Alternative)
```bash
# Sur votre machine locale (Windows PowerShell)
scp resources/views/components/mobile-floating-widgets.blade.php evonpower@himalaya:/home/evonpower/devcharge.evonpower.com/resources/views/components/
scp routes/web.php evonpower@himalaya:/home/evonpower/devcharge.evonpower.com/routes/
```

#### Option C : Édition manuelle (Dernier recours)
```bash
# Sur le serveur
ssh evonpower@himalaya
cd /home/evonpower/devcharge.evonpower.com

# Sauvegarder les fichiers actuels
cp resources/views/components/mobile-floating-widgets.blade.php resources/views/components/mobile-floating-widgets.blade.php.backup
cp routes/web.php routes/web.php.backup

# Éditer mobile-floating-widgets.blade.php
nano resources/views/components/mobile-floating-widgets.blade.php
# Ligne 56 : Remplacer route('locale.switch' par route('language.switch'
# Ctrl+O pour sauvegarder, Ctrl+X pour quitter

# Les modifications de routes/web.php peuvent être appliquées plus tard si nécessaire
```

### Étape 2 : Vider tous les caches
```bash
cd /home/evonpower/devcharge.evonpower.com

# Vider le cache des vues (CRITIQUE!)
php artisan view:clear

# Vider les autres caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Supprimer manuellement la vue compilée problématique
rm -f storage/framework/views/cdb79df02c0994186c7c8a39abc12142.php

# Reconstruire les caches optimisés
php artisan config:cache

# Tester le cache des routes (peut échouer si routes en double subsistent)
php artisan route:cache
```

### Étape 3 : Redémarrer les services (si nécessaire)
```bash
# PHP-FPM (ajuster la version selon votre configuration)
sudo systemctl restart php8.1-fpm

# OU simplement recharger
sudo systemctl reload php8.1-fpm

# Nginx
sudo systemctl reload nginx
```

### Étape 4 : Vérifier les logs
```bash
# Surveiller les logs en temps réel
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Vérifier les dernières erreurs
tail -100 storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "error\|exception"
```

---

## ⚠️ Problème du locale "es" (Espagnol)

### Symptôme
Les logs montrent :
```
[2025-12-23 01:45:39] production.INFO: [APPSERVICEPROVIDER] Locale set {"locale":"es","source":"url"}
```

### Diagnostic
Le système tente d'utiliser le locale "es" mais il n'est probablement pas configuré dans votre application.

### Solutions

#### Solution 1 : Ajouter le support de l'espagnol
```bash
# 1. Créer le répertoire de langue espagnole
mkdir -p resources/lang/es

# 2. Copier les fichiers de traduction depuis une autre langue
cp -r resources/lang/fr/* resources/lang/es/

# 3. Ajouter "es" dans config/app.php
```

Éditez `config/app.php` :
```php
'available_locales' => [
    'fr' => 'Français',
    'en' => 'English',
    'ar' => 'العربية',
    'es' => 'Español',  // Ajouter cette ligne
],
```

#### Solution 2 : Bloquer le locale "es" (Si non désiré)
Éditez `app/Http/Middleware/SetLocaleMiddleware.php` ou votre middleware de locale :
```php
public function handle($request, Closure $next)
{
    $locale = $request->segment(1);
    
    // Liste blanche des locales autorisées
    $allowedLocales = ['fr', 'en', 'ar'];
    
    if (in_array($locale, $allowedLocales)) {
        app()->setLocale($locale);
        session(['locale' => $locale]);
    } else {
        // Rediriger vers la langue par défaut si locale non autorisé
        app()->setLocale(config('app.locale', 'fr'));
    }
    
    return $next($request);
}
```

---

## ✅ Vérification post-déploiement

### 1. Tester le changement de langue
- Accédez à votre site : https://devcharge.evonpower.com
- Cliquez sur le sélecteur de langue (widgets flottants mobile)
- Changez entre Français, English, et Arabe
- Vérifiez qu'il n'y a pas d'erreur 500

### 2. Vérifier les routes
```bash
# Lister toutes les routes "transactions"
php artisan route:list --path=transactions

# Vérifier qu'il n'y a pas de doublons
php artisan route:list | grep "transactions.export"

# Doit montrer une seule route transactions.export
```

### 3. Vérifier les logs
```bash
# Vérifier qu'il n'y a plus d'erreurs
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log | grep "locale.switch"

# Ne devrait rien retourner
```

---

## 📁 Fichiers modifiés

1. **resources/views/components/mobile-floating-widgets.blade.php**
   - Ligne 56 : Correction du nom de route

2. **routes/web.php**
   - Lignes 889-891 : Routes `transactions.show` et `transactions.export` commentées (doublons)
   - Lignes 1907-1909 : Route `transactions.history` commentée (doublon)

---

## 🔄 Rollback (En cas de problème)

Si quelque chose ne fonctionne pas :

```bash
# Restaurer les fichiers de sauvegarde
cd /home/evonpower/devcharge.evonpower.com

cp resources/views/components/mobile-floating-widgets.blade.php.backup resources/views/components/mobile-floating-widgets.blade.php
cp routes/web.php.backup routes/web.php

# Vider les caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Redémarrer PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## 📝 Notes importantes

1. **Cache des vues** : Le problème principal était causé par des vues Blade compilées en cache qui contenaient l'ancienne référence `locale.switch`

2. **Routes en double** : Laravel ne peut pas mettre en cache les routes s'il y a des noms de routes en double

3. **Locale "es"** : Vérifiez d'où vient cette requête (URL, cookie, session) et décidez si vous voulez supporter l'espagnol ou le bloquer

4. **Production** : Toujours faire une sauvegarde avant de modifier des fichiers en production

---

## 🎉 État actuel (basé sur vos logs)

✅ Le système fonctionne avec le locale "fr" (Français)
✅ Plus d'erreur `Route [locale.switch] not defined`
⚠️ Le locale "es" (Espagnol) est sollicité mais peut-être pas configuré

---

## 🆘 Support

Si vous rencontrez des problèmes :
1. Vérifiez les logs Laravel : `storage/logs/laravel-YYYY-MM-DD.log`
2. Vérifiez les logs Nginx : `/var/log/nginx/error.log`
3. Vérifiez les logs PHP-FPM : `/var/log/php8.1-fpm.log`
4. Testez en mode debug : Changez `APP_DEBUG=true` dans `.env` temporairement

---

**Date de création :** 23 décembre 2025
**Version :** 1.0
**Statut :** ✅ Corrections appliquées localement, en attente de déploiement sur production

