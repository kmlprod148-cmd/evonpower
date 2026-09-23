# 🔧 Corrections des Routes - 23 Décembre 2025

## 📋 Résumé des problèmes identifiés

### 1. Route `locale.switch` non définie
**Fichier**: `resources/views/components/mobile-floating-widgets.blade.php` (ligne 56)

**Problème**: Utilisation de `route('locale.switch')` alors que la route correcte est `route('language.switch')`

**Solution**: Remplacement de `locale.switch` par `language.switch`

---

### 2. Routes en double dans `routes/web.php`

#### Doublon #1: `transactions.show`
**Lignes concernées**: 882 et 890

**Problème**: La route `transactions.show` est définie deux fois
- Ligne 882: Dans le premier groupe `transactions` (TransactionHistoryController)
- Ligne 890: Dans le deuxième groupe `transactions` (TransactionController)

**Solution**: La ligne 890 a été commentée pour éviter le doublon

#### Doublon #2: `transactions.export`
**Lignes concernées**: 891 et 1912

**Problème**: La route `transactions.export` est définie deux fois
- Ligne 891: `Route::get('/export/csv', ...)` dans un groupe `transactions`
- Ligne 1912: `Route::get('/export', ...)` dans un autre groupe `transactions`

**Solution**: La ligne 891 a été commentée (la ligne 1912 est conservée)

#### Doublon #3: `transactions.history`
**Lignes concernées**: 867 et 1907

**Problème**: La route `transactions.history` est définie deux fois
- Ligne 867: Route vers TransactionHistoryController
- Ligne 1907: Route vers une closure qui retourne une vue

**Solution**: Les lignes 1907-1909 ont été commentées

---

## 📁 Fichiers modifiés

### 1. `resources/views/components/mobile-floating-widgets.blade.php`
```php
// AVANT (ligne 56)
<a href="{{ route('locale.switch', $locale) }}"

// APRÈS (ligne 56)
<a href="{{ route('language.switch', $locale) }}"
```

### 2. `routes/web.php`
```php
// LIGNES 888-894: Routes commentées pour éviter les doublons
Route::prefix('transactions')->name('transactions.')->middleware(['auth', \App\Http\Middleware\TransactionAccessMiddleware::class])->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('index');
    // Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show'); // DOUBLON - Commenté
    // Route::get('/export/csv', [TransactionController::class, 'export'])->name('export'); // DOUBLON - Commenté
    Route::get('/api/balance', [TransactionController::class, 'balance'])->name('balance');
    Route::get('/api/recent', [TransactionController::class, 'recent'])->name('recent');
});

// LIGNES 1906-1913: Route history commentée
Route::prefix('transactions')->middleware('auth')->group(function () {
    // Route::get('/history', function () { // DOUBLON - Commenté
    //     return view('transactions.history');
    // })->name('transactions.history');
    Route::get('/api', [App\Http\Controllers\TransactionController::class, 'api'])->name('transactions.api');
    Route::get('/summary', [App\Http\Controllers\TransactionController::class, 'summary'])->name('transactions.summary');
    Route::get('/export', [App\Http\Controllers\TransactionController::class, 'export'])->name('transactions.export');
});
```

---

## 🚀 Procédure de déploiement

### Option 1: Script automatisé (RECOMMANDÉ)

1. Transférer les fichiers vers le serveur:
```bash
# Sur votre machine locale
scp resources/views/components/mobile-floating-widgets.blade.php evonpower@himalaya:/home/evonpower/devcharge.evonpower.com/resources/views/components/
scp routes/web.php evonpower@himalaya:/home/evonpower/devcharge.evonpower.com/routes/
scp deploy-fix-routes.sh evonpower@himalaya:/home/evonpower/devcharge.evonpower.com/
```

2. Exécuter le script sur le serveur:
```bash
# Se connecter au serveur
ssh evonpower@himalaya

# Aller dans le répertoire
cd /home/evonpower/devcharge.evonpower.com

# Rendre le script exécutable
chmod +x deploy-fix-routes.sh

# Exécuter le script
bash deploy-fix-routes.sh
```

---

### Option 2: Déploiement manuel

#### Étape 1: Transférer et corriger mobile-floating-widgets.blade.php
```bash
ssh evonpower@himalaya
cd /home/evonpower/devcharge.evonpower.com

# Sauvegarder l'ancien fichier
cp resources/views/components/mobile-floating-widgets.blade.php resources/views/components/mobile-floating-widgets.blade.php.backup

# Éditer le fichier
nano resources/views/components/mobile-floating-widgets.blade.php
# Remplacer ligne 56: locale.switch → language.switch
# Ctrl+O pour sauvegarder, Ctrl+X pour quitter
```

#### Étape 2: Corriger routes/web.php
```bash
# Sauvegarder l'ancien fichier
cp routes/web.php routes/web.php.backup

# Éditer le fichier
nano routes/web.php
# Commenter les lignes 890, 891 et 1907-1909 comme indiqué ci-dessus
# Ctrl+O pour sauvegarder, Ctrl+X pour quitter
```

#### Étape 3: Vider les caches
```bash
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### Étape 4: Supprimer les vues compilées problématiques
```bash
rm -f storage/framework/views/cdb79df02c0994186c7c8a39abc12142.php
```

#### Étape 5: Reconstruire les caches (optionnel)
```bash
php artisan config:cache
php artisan route:cache
```

---

### Option 3: Déploiement via Git (si configuré)

```bash
# Sur votre machine locale
git add resources/views/components/mobile-floating-widgets.blade.php
git add routes/web.php
git commit -m "Fix: Correction des routes en double et locale.switch"
git push origin main

# Sur le serveur
cd /home/evonpower/devcharge.evonpower.com
git pull origin main
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
```

---

## ✅ Vérification post-déploiement

### 1. Vérifier qu'il n'y a plus d'erreurs
```bash
# Surveiller les logs en temps réel
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

### 2. Tester le changement de langue
- Accéder au site web sur mobile
- Ouvrir les widgets flottants
- Tester le changement de langue
- Vérifier qu'aucune erreur n'apparaît

### 3. Tester les routes de transactions
- Accéder à `/transactions`
- Accéder à `/transactions/history`
- Tester l'export: `/transactions/export`

### 4. Vérifier que le cache des routes fonctionne
```bash
php artisan route:list | grep transactions
```

---

## 🔄 Restauration en cas de problème

Si vous rencontrez des problèmes après le déploiement:

```bash
cd /home/evonpower/devcharge.evonpower.com

# Restaurer mobile-floating-widgets.blade.php
cp resources/views/components/mobile-floating-widgets.blade.php.backup resources/views/components/mobile-floating-widgets.blade.php

# Restaurer web.php
cp routes/web.php.backup routes/web.php

# Vider les caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📝 Notes importantes

1. **Cache des vues**: Le fichier `storage/framework/views/cdb79df02c0994186c7c8a39abc12142.php` est la version compilée de la vue qui contenait l'erreur. Il DOIT être supprimé.

2. **Cache des routes**: Si `php artisan route:cache` échoue, l'application fonctionnera quand même sans le cache des routes (légèrement plus lent).

3. **Locale "es"**: Les logs montrent que quelqu'un tente d'accéder au locale "es" (espagnol). Vérifiez si c'est voulu et si "es" est bien configuré dans `config/app.php`.

4. **Tests**: Après le déploiement, testez particulièrement:
   - Le changement de langue sur mobile
   - L'accès aux transactions
   - L'export des transactions

---

## 📞 Support

En cas de problème persistant:
1. Consultez les logs: `tail -f storage/logs/laravel-$(date +%Y-%m-%d).log`
2. Vérifiez les routes: `php artisan route:list | grep -E "(locale|language|transactions)"`
3. Vérifiez la configuration: `php artisan config:show app`

---

**Date de création**: 23 Décembre 2025  
**Environnement**: Production (devcharge.evonpower.com)  
**Serveur**: himalaya

