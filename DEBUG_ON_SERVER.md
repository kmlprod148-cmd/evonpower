# Guide de débogage sur le serveur de production

## Fichiers à uploader sur le serveur

1. **app/Http/Middleware/SetLocaleMiddleware.php** - Middleware avec logs
2. **app/Http/Controllers/LocaleController.php** - Contrôleur avec logs
3. **routes/web.php** - Routes avec route de test

## Étapes de test

### 1. Uploader les fichiers et nettoyer le cache

```bash
# SSH dans le serveur
ssh evonpower@votre-serveur.com

# Aller dans le répertoire de l'application
cd /home/evonpower/devcharge.evonpower.com

# Après avoir uploadé les fichiers, exécuter:
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Vider le fichier de log
> storage/logs/laravel.log
```

### 2. Tester la route de débogage

```bash
# Accéder à la route de test dans votre navigateur:
# https://devcharge.evonpower.com/test-middleware-debug

# OU via curl:
curl https://devcharge.evonpower.com/test-middleware-debug

# Vérifier les logs:
tail -f storage/logs/laravel.log
```

**Ce que vous devriez voir:**
- `=== MIDDLEWARE EXECUTING ===` - Si le middleware s'exécute
- `[MIDDLEWARE-START]` - Début du middleware
- `[MIDDLEWARE] Before determineLocale` - Avant de déterminer la locale
- `TEST ROUTE HIT` - La route de test a été atteinte

**Si vous NE voyez PAS `=== MIDDLEWARE EXECUTING ===`:**
- Le middleware ne s'exécute pas du tout
- Il y a un problème de configuration dans `app/Http/Kernel.php`
- Le middleware plante avant le log

### 3. Tester le changement de langue

```bash
# Vider les logs
> storage/logs/laravel.log

# Dans le navigateur:
# 1. Aller sur https://devcharge.evonpower.com
# 2. Changer la langue vers English
# 3. Recharger la page

# Vérifier les logs:
tail -n 50 storage/logs/laravel.log | grep -E "MIDDLEWARE|AJAX"
```

**Ce que vous devriez voir (ordre chronologique):**
1. `[MIDDLEWARE] Before determineLocale` - Au chargement initial (session contient 'fr')
2. `[AJAX] setAjax called` - Quand vous changez la langue
3. `[AJAX] After Session facade save` - Session mise à jour avec 'en'
4. `[MIDDLEWARE] Before determineLocale` - Après le rechargement (session devrait contenir 'en')
5. `[MIDDLEWARE] Using Session` - Le middleware lit 'en' depuis la session

## Diagnostic selon les logs

### Cas 1: Aucun log [MIDDLEWARE]
**Problème:** Le middleware ne s'exécute pas
**Solution:** Vérifier `app/Http/Kernel.php` ligne 30

### Cas 2: [MIDDLEWARE] apparaît mais détermine toujours 'fr'
**Problème:** Le middleware utilise Accept-Language au lieu de la session
**Solution:** Le fix dans SetLocaleMiddleware.php devrait résoudre cela

### Cas 3: [MIDDLEWARE] lit 'en' mais l'app affiche 'fr'
**Problème:** Un autre middleware ou code écrase la locale
**Solution:** Chercher d'autres middlewares qui appellent `App::setLocale()`

## Commandes utiles

```bash
# Voir les middlewares actifs
php artisan route:list --columns=uri,name,middleware | grep language

# Voir toutes les routes avec middleware web
php artisan route:list --columns=uri,middleware | grep "web"

# Vérifier la configuration
php artisan config:show app.locale
php artisan config:show app.available_locales

# Voir les derniers logs en temps réel
tail -f storage/logs/laravel.log

# Filtrer seulement les logs de langue
tail -f storage/logs/laravel.log | grep -E "MIDDLEWARE|AJAX|locale"
```

## Fix attendu

Après le déploiement, le comportement devrait être:
1. Changement de langue → Session mise à jour
2. Rechargement de page → Middleware lit la session
3. App affiche la bonne langue
4. Navigation → Langue persiste (middleware lit toujours la session)

