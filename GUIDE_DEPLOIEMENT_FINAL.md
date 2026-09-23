# Guide de Déploiement Final - Fix Changement de Langue

## ✅ Fichier à déployer

**UN SEUL fichier:** `resources/views/components/direct-language-switcher.blade.php`

## 📋 Étapes de déploiement (CRITIQUES)

### 1. Upload du fichier

**Via FTP/FileZilla/WinSCP:**
```
Fichier local: c:\Users\kmlpr\Desktop\EVON\NEW-EVON-APP\resources\views\components\direct-language-switcher.blade.php

Destination serveur: /home/evonpower/devcharge.evonpower.com/resources/views/components/direct-language-switcher.blade.php

⚠️ IMPORTANT: Remplacez le fichier existant!
```

### 2. Vider les caches (SSH)

```bash
ssh evonpower@votre-serveur
cd /home/evonpower/devcharge.evonpower.com

# Vider TOUS les caches
php artisan view:clear
php artisan config:clear  
php artisan cache:clear
php artisan route:clear

# Vider aussi le cache OPcache si disponible
php artisan optimize:clear

# Vider les logs pour un test propre
> storage/logs/laravel.log

echo "✅ Caches vidés!"
```

### 3. Test dans le navigateur

1. **Fermez COMPLÈTEMENT votre navigateur** (tous les onglets)
2. **Rouvrez le navigateur**
3. **Videz le cache navigateur:** Ctrl+Shift+Delete → Cocher "Cache" + "Cookies" → Effacer
4. **Ouvrez la console JavaScript:** F12 → Onglet "Console"
5. **Accédez à:** https://devcharge.evonpower.com

### 4. Vérifications dans la console

Vous DEVEZ voir dans la console:
```
[LOCALE-PERSISTENCE] Script loaded
[LOCALE-PERSISTENCE] Current lang: fr
```

Si vous ne voyez PAS ces messages:
- Le fichier n'est pas déployé OU
- Le cache n'est pas vidé OU  
- Vous regardez la mauvaise console

### 5. Test de changement de langue

1. Dans la console, gardez un œil sur les messages
2. Cliquez sur le sélecteur de langue
3. Choisissez "English"
4. Vous DEVEZ voir dans la console:
   ```
   [LANGUAGE-SWITCH] Redirecting to: https://devcharge.evonpower.com/quelque-chose?lang=en
   ```
5. La page SE RECHARGE
6. L'URL DOIT contenir `?lang=en`
7. La console DOIT afficher:
   ```
   [LOCALE-PERSISTENCE] Script loaded
   [LOCALE-PERSISTENCE] Current lang: en
   ```
8. L'interface DOIT être en anglais

### 6. Test de persistence

1. Cliquez sur un lien (Partners, Dashboard, etc.)
2. Dans la console, vous DEVEZ voir:
   ```
   [LOCALE-PERSISTENCE] Updated link: https://...?lang=en
   ```
3. L'URL de la nouvelle page DOIT contenir `?lang=en`
4. L'interface DOIT rester en anglais

## 🐛 Dépannage

### Problème: Aucun message dans la console

**Cause:** Le fichier n'est pas déployé ou le cache n'est pas vidé

**Solution:**
```bash
# Sur le serveur, vérifier que le fichier contient bien le nouveau code:
grep "LOCALE-PERSISTENCE" /home/evonpower/devcharge.evonpower.com/resources/views/components/direct-language-switcher.blade.php

# Vous devez voir plusieurs lignes contenant "LOCALE-PERSISTENCE"
# Si rien ne s'affiche, le fichier n'est pas déployé!

# Re-vider les caches:
php artisan view:clear && php artisan cache:clear
```

### Problème: Messages dans la console mais pas de rechargement

**Cause:** Erreur JavaScript qui bloque le rechargement

**Solution:**
1. Regardez la console pour des erreurs en rouge
2. Copiez l'erreur complète
3. Le fix dépendra de l'erreur spécifique

### Problème: Rechargement OK mais URL sans ?lang=

**Cause:** Le code de construction d'URL a échoué

**Solution:** Ouvrez la console et tapez:
```javascript
let locale = 'en';
let newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?lang=' + locale;
console.log(newUrl);
```
Si vous voyez l'URL correcte, le problème est ailleurs.

### Problème: URL avec ?lang=en mais interface toujours en français

**Cause:** Le middleware ne lit pas le paramètre ?lang=

**Solution:** Vérifier que SetLocaleMiddleware est actif:
```bash
# Sur le serveur:
grep -n "SetLocaleMiddleware" /home/evonpower/devcharge.evonpower.com/app/Http/Kernel.php

# Vous devez voir une ligne comme:
# 30:            \App\Http\Middleware\SetLocaleMiddleware::class,
```

Si la ligne est commentée (//), décommentez-la et re-videz les caches.

## ✅ Succès attendu

Après le déploiement correct:

1. ✅ Console affiche les messages [LOCALE-PERSISTENCE]
2. ✅ Changement de langue → Rechargement avec ?lang=en
3. ✅ Interface en anglais après rechargement
4. ✅ Navigation → URLs contiennent toujours ?lang=en
5. ✅ Langue persiste sur toutes les pages

## 📞 Si ça ne fonctionne toujours pas

Fournissez-moi:
1. Capture d'écran de la console JavaScript
2. L'URL complète après avoir cliqué sur "English"
3. Résultat de: `grep "LOCALE-PERSISTENCE" /home/.../resources/views/components/direct-language-switcher.blade.php`
4. Les dernières 30 lignes des logs: `tail -n 30 storage/logs/laravel.log`

