# 🚀 Déploiement rapide - Serveur de production

## ⚡ Commandes à exécuter (copier-coller)

```bash
# 1. Se connecter au serveur
ssh evonpower@himalaya

# 2. Aller dans le répertoire du projet
cd /home/evonpower/devcharge.evonpower.com

# 3. Récupérer les modifications
git pull origin main

# 4. Rendre le script exécutable
chmod +x deploy-production.sh

# 5. Exécuter le script de déploiement
./deploy-production.sh
```

---

## OU commandes manuelles

Si vous préférez exécuter les commandes manuellement :

```bash
# Nettoyer tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Tester la compilation des routes
php artisan route:cache

# Si la commande ci-dessus échoue, nettoyer le cache
php artisan route:clear

# Redémarrer les workers
php artisan queue:restart

# Redémarrer PHP-FPM (adapter selon votre version)
sudo systemctl restart php8.2-fpm
```

---

## 🧪 Tests après déploiement

1. **Ouvrir l'application** : https://devcharge.evonpower.com
2. **Tester le changement de langue** (français ↔ anglais ↔ arabe)
3. **Vérifier les logs** :
   ```bash
   tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log | grep "ERROR"
   ```
   Résultat attendu : **aucune erreur**

---

## ❓ En cas de problème

### Si l'erreur `locale.switch` persiste

Le fichier `mobile-floating-widgets.blade.php` n'a pas été mis à jour. Vérifier :

```bash
grep "locale.switch" resources/views/components/mobile-floating-widgets.blade.php

# Ne devrait RIEN retourner (vide)
```

Si le grep retourne quelque chose, le fichier n'est pas à jour.

### Si l'erreur de routes dupliquées persiste

```bash
# Vérifier que les modifications sont présentes
grep "api.admin.notifications.unread" routes/api.php
grep "export.csv" routes/admin.php

# Nettoyer à nouveau
php artisan route:clear
php artisan cache:clear
```

### Restaurer la sauvegarde

```bash
# Trouver le dossier de sauvegarde
ls -lt | grep backups_

# Restaurer (remplacer XXXXXX par le timestamp)
rm -rf routes
cp -r backups_XXXXXX_XXXXXX/routes .

# Nettoyer les caches
php artisan cache:clear
php artisan route:clear
```

---

**Durée estimée** : 2-3 minutes  
**Temps d'arrêt** : Aucun (déploiement à chaud)


