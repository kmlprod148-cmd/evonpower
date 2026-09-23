# 🔧 Troubleshooting - Lien "Gestion Crédits" dans la Sidebar

## ✅ Vérification de la Configuration

### 1. **Le lien existe dans le code**

**Fichier:** `resources/views/layouts/partials/evon-sidebar.blade.php`  
**Lignes:** 105-112

```php
[
    'label' => 'Gestion Crédits',
    'route' => 'admin.credit-recharges.dashboard',
    'icon' => 'coins',
    'badge' => CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->count(),
    'badge_class' => 'bg-yellow-500',
    'permission' => 'admin'
]
```

### 2. **L'icône existe**

**Fichier:** `resources/views/components/icons/lucide-coins.blade.php`  
✅ Confirmé présent

### 3. **La route existe**

**Route:** `admin.credit-recharges.dashboard`  
**URL:** `/admin/credit-recharges/dashboard`

## 🔍 Diagnostic - Pourquoi le lien n'apparaît pas ?

### Cause 1: Fichier non sauvegardé
**Symptôme:** Modifications dans l'éditeur mais pas appliquées  
**Solution:** 
```bash
# Sauvegarder le fichier dans l'éditeur (Ctrl+S)
```

### Cause 2: Cache Laravel
**Symptôme:** Anciennes vues en cache  
**Solution:**
```bash
php artisan view:clear
php artisan cache:clear
php artisan config:clear
```

### Cause 3: Permission insuffisante
**Symptôme:** User n'est pas admin  
**Vérification:**
```php
// Dans tinker
php artisan tinker
>>> auth()->user()->hasRole('admin')
>>> auth()->user()->roles->pluck('name')
```

**Solution:**
```php
// Ajouter le rôle admin
php artisan tinker
>>> $user = User::find(1); // Votre ID
>>> $user->assignRole('admin');
```

### Cause 4: Section Administration masquée
**Symptôme:** `$isAdmin` retourne false  
**Vérification:**
```php
'visible' => $isAdmin,  // Doit être true
```

### Cause 5: Model CreditRecharge non importé
**Symptôme:** Erreur lors du calcul du badge  
**Vérification:** Ligne 2 du fichier sidebar
```php
use App\Models\CreditRecharge;
```

## 🚀 Solution Rapide - Étapes à Suivre

### Étape 1: Vérifier l'import du Model
```bash
# Ouvrir le fichier
resources/views/layouts/partials/evon-sidebar.blade.php
```

**Ajouter en haut du fichier (ligne 2):**
```php
@php
use App\Models\CreditRecharge;
@endphp
```

### Étape 2: Vider les caches
```bash
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Étape 3: Vérifier le rôle admin
```bash
php artisan tinker
```

```php
$user = auth()->user();
$user->hasRole('admin'); // Doit retourner true

// Si false, ajouter le rôle:
$user->assignRole('admin');
```

### Étape 4: Rafraîchir le navigateur
```
Ctrl + Shift + R (hard refresh)
ou
Ctrl + F5
```

## 📝 Code Complet à Vérifier

### Début du fichier sidebar (lignes 1-5)
```php
@php
use App\Models\CreditRecharge;
@endphp

<aside class="evon-sidebar">
```

### Section Administration (lignes 88-114)
```php
$menuSections = [
    // Section: Administration (Admin only)
    [
        'title' => 'Administration',
        'visible' => $isAdmin,
        'items' => [
            [
                'label' => __('messages.monitoring'),
                'route' => 'monitoring.index',
                'icon' => 'activity',
                'permission' => 'admin'
            ],
            [
                'label' => __('messages.users'),
                'route' => 'admin.users.index',
                'icon' => 'users',
                'permission' => 'admin'
            ],
            [
                'label' => 'Gestion Crédits',
                'route' => 'admin.credit-recharges.dashboard',
                'icon' => 'coins',
                'badge' => CreditRecharge::where('status', 'pending')
                            ->where('payment_method', 'offline')
                            ->count(),
                'badge_class' => 'bg-yellow-500',
                'permission' => 'admin'
            ],
        ]
    ],
```

## 🧪 Tests de Vérification

### Test 1: Route existe
```bash
php artisan route:list | grep credit-recharges
```

**Résultat attendu:**
```
GET|HEAD  admin/credit-recharges/dashboard ... admin.credit-recharges.dashboard
```

### Test 2: Icône existe
```bash
ls resources/views/components/icons/lucide-coins.blade.php
```

**Résultat attendu:**
```
resources/views/components/icons/lucide-coins.blade.php
```

### Test 3: User est admin
```bash
php artisan tinker
```

```php
auth()->user()->hasRole('admin')
// true ✅
```

### Test 4: Badge fonctionne
```bash
php artisan tinker
```

```php
use App\Models\CreditRecharge;
CreditRecharge::where('status', 'pending')
    ->where('payment_method', 'offline')
    ->count()
// Retourne un nombre (0 ou plus) ✅
```

## 🔧 Fix Complet - Script Automatique

Créez un fichier `fix-sidebar-credit-link.sh`:

```bash
#!/bin/bash

echo "🔧 Fixing Sidebar Credit Link..."

# 1. Clear all caches
echo "📦 Clearing caches..."
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 2. Verify route exists
echo "🔍 Checking route..."
php artisan route:list | grep "admin.credit-recharges.dashboard"

# 3. Verify icon exists
echo "🎨 Checking icon..."
ls resources/views/components/icons/lucide-coins.blade.php

# 4. Optimize
echo "⚡ Optimizing..."
php artisan optimize

echo "✅ Done! Please refresh your browser (Ctrl+Shift+R)"
```

**Exécuter:**
```bash
chmod +x fix-sidebar-credit-link.sh
./fix-sidebar-credit-link.sh
```

## 📸 Résultat Attendu

### Desktop - Sidebar Expanded
```
┌─────────────────────────────────────┐
│ 📊 Administration                   │
├─────────────────────────────────────┤
│ 📈 Monitoring                       │
│ 👥 Users                            │
│ 💰 Gestion Crédits            [3]  │ ← Doit apparaître ici
├─────────────────────────────────────┤
```

### Desktop - Sidebar Collapsed
```
┌─────┐
│ 📈  │
│ 👥  │
│ 💰  │ ← Doit apparaître ici
│ [3] │
└─────┘
```

## 🆘 Si le problème persiste

### Option 1: Vérifier les logs
```bash
tail -f storage/logs/laravel.log
```

### Option 2: Mode debug
Dans `.env`:
```env
APP_DEBUG=true
```

Rafraîchir la page et vérifier les erreurs.

### Option 3: Recréer le lien manuellement

Ouvrir `resources/views/layouts/partials/evon-sidebar.blade.php`

**Trouver la section Administration (ligne ~88)**

**S'assurer que ce code existe:**
```php
[
    'label' => 'Gestion Crédits',
    'route' => 'admin.credit-recharges.dashboard',
    'icon' => 'coins',
    'badge' => \App\Models\CreditRecharge::where('status', 'pending')
                ->where('payment_method', 'offline')
                ->count(),
    'badge_class' => 'bg-yellow-500',
    'permission' => 'admin'
],
```

**Note:** Utiliser `\App\Models\CreditRecharge` avec le backslash complet si l'import ne fonctionne pas.

### Option 4: Vérifier le middleware

Dans `routes/web.php`:
```php
Route::prefix('admin/credit-recharges')
    ->name('admin.credit-recharges.')
    ->middleware(['role:admin|super-admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminCreditRechargeController::class, 'dashboard'])
            ->name('dashboard');
    });
```

## ✅ Checklist Finale

- [ ] Fichier sidebar sauvegardé
- [ ] Model CreditRecharge importé
- [ ] Cache vidé
- [ ] User a le rôle admin
- [ ] Route existe et fonctionne
- [ ] Icône coins existe
- [ ] Badge se calcule sans erreur
- [ ] Navigateur rafraîchi (hard refresh)
- [ ] Section Administration visible
- [ ] Permission 'admin' vérifiée

## 📞 Support Supplémentaire

Si après toutes ces étapes le lien n'apparaît toujours pas:

1. **Vérifier la console navigateur** (F12)
   - Erreurs JavaScript ?
   - Erreurs de chargement ?

2. **Vérifier l'inspecteur** (F12 > Elements)
   - Le HTML du lien est-il présent mais caché ?
   - Classes CSS appliquées ?

3. **Tester avec un autre user admin**
   - Créer un nouveau user
   - Lui donner le rôle admin
   - Se connecter et vérifier

4. **Vérifier la base de données**
   ```sql
   SELECT * FROM roles WHERE name = 'admin';
   SELECT * FROM model_has_roles WHERE role_id = 1;
   ```

---

## 🎉 Confirmation de Succès

Une fois le lien visible, vous devriez voir:

✅ Lien "Gestion Crédits" dans la section Administration  
✅ Icône 💰 (coins) visible  
✅ Badge jaune avec le nombre de demandes  
✅ Cliquable et redirige vers `/admin/credit-recharges/dashboard`  
✅ Tooltip en mode collapsed  

**Le système est maintenant opérationnel ! 🚀**

---

**Besoin d'aide ? Consultez la documentation complète dans:**
- `docs/ADMIN_CREDIT_BALANCE_SYSTEM.md`
- `docs/ADMIN_SIDEBAR_CREDIT_LINK.md`

