# Instructions de Déploiement - Fix Changement de Langue

## Fichiers modifiés à déployer:

1. `app/Http/Middleware/SetLocaleMiddleware.php` - Fix du middleware pour prioriser la session
2. `app/Http/Controllers/LocaleController.php` - Ajout de logs de débogage  
3. `routes/language.php` - Ajout du groupe middleware 'web' (optionnel car non utilisé)

## Commandes de déploiement:

```bash
# Sur votre serveur de production (SSH)
cd /home/evonpower/devcharge.evonpower.com

# 1. Récupérer les changements depuis Git (si vous utilisez Git)
git pull origin main

# OU copier les fichiers manuellement via FTP/SCP

# 2. Vider les caches Laravel
php artisan config:clear
php artisan route:clear  
php artisan cache:clear
php artisan view:clear

# 3. Redémarrer PHP-FPM ou le serveur web
sudo systemctl restart php8.2-fpm  # Ajustez la version PHP
# OU
sudo systemctl restart apache2
# OU  
sudo systemctl restart nginx
```

## Test après déploiement:

1. Accédez à https://devcharge.evonpower.com
2. Changez la langue vers English
3. Vérifiez si la langue persiste après le rechargement
4. Vérifiez les logs: `tail -f storage/logs/laravel.log` - Vous devriez voir [MIDDLEWARE-START]

## Fix Principal:

Le middleware `SetLocaleMiddleware` a été modifié pour:
- NE PLUS utiliser `Accept-Language` du navigateur (qui forçait toujours 'fr')
- TOUJOURS prioriser la session en premier
- Mieux gérer les cas où la colonne `locale` n'existe pas dans la table users

