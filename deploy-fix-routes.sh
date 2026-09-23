#!/bin/bash

# Script de déploiement pour corriger les routes en double
# Usage: bash deploy-fix-routes.sh

echo "==================================="
echo "Déploiement des corrections"
echo "==================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/home/evonpower/devcharge.evonpower.com"

echo -e "${YELLOW}📁 Répertoire de l'application: ${APP_DIR}${NC}"
echo ""

# Vérifier si on est dans le bon répertoire
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}❌ Erreur: Le répertoire $APP_DIR n'existe pas${NC}"
    exit 1
fi

cd "$APP_DIR" || exit 1

echo -e "${GREEN}✅ Répertoire trouvé${NC}"
echo ""

# Étape 1: Sauvegarder les fichiers actuels
echo "📦 Étape 1/5: Sauvegarde des fichiers actuels..."
cp resources/views/components/mobile-floating-widgets.blade.php resources/views/components/mobile-floating-widgets.blade.php.backup-$(date +%Y%m%d-%H%M%S) 2>/dev/null || true
cp routes/web.php routes/web.php.backup-$(date +%Y%m%d-%H%M%S) 2>/dev/null || true
echo -e "${GREEN}✅ Sauvegarde terminée${NC}"
echo ""

# Étape 2: Appliquer les corrections
echo "🔧 Étape 2/5: Application des corrections..."

# Correction 1: mobile-floating-widgets.blade.php
echo "   → Correction de mobile-floating-widgets.blade.php..."
if grep -q "route('locale.switch'" resources/views/components/mobile-floating-widgets.blade.php 2>/dev/null; then
    sed -i "s/route('locale\.switch'/route('language.switch'/g" resources/views/components/mobile-floating-widgets.blade.php
    echo -e "${GREEN}      ✓ Fichier corrigé${NC}"
else
    echo -e "${YELLOW}      ⚠ Pas de correction nécessaire ou fichier déjà corrigé${NC}"
fi

echo ""

# Étape 3: Vider tous les caches
echo "🧹 Étape 3/5: Nettoyage des caches..."
php artisan view:clear
echo -e "${GREEN}   ✓ Cache des vues vidé${NC}"

php artisan cache:clear
echo -e "${GREEN}   ✓ Cache d'application vidé${NC}"

php artisan config:clear
echo -e "${GREEN}   ✓ Cache de configuration vidé${NC}"

php artisan route:clear
echo -e "${GREEN}   ✓ Cache des routes vidé${NC}"

echo ""

# Étape 4: Supprimer le fichier de vue compilé problématique
echo "🗑️  Étape 4/5: Suppression des vues compilées problématiques..."
if [ -f "storage/framework/views/cdb79df02c0994186c7c8a39abc12142.php" ]; then
    rm -f storage/framework/views/cdb79df02c0994186c7c8a39abc12142.php
    echo -e "${GREEN}   ✓ Vue compilée supprimée${NC}"
else
    echo -e "${YELLOW}   ⚠ Vue compilée déjà supprimée${NC}"
fi

echo ""

# Étape 5: Recompiler les caches (facultatif)
echo "⚡ Étape 5/5: Recompilation des caches optimisés..."
php artisan config:cache 2>/dev/null
echo -e "${GREEN}   ✓ Configuration mise en cache${NC}"

# Tester le cache des routes
echo "   → Test du cache des routes..."
if php artisan route:cache 2>/dev/null; then
    echo -e "${GREEN}   ✓ Routes mises en cache${NC}"
else
    echo -e "${RED}   ✗ Erreur lors de la mise en cache des routes${NC}"
    echo -e "${YELLOW}   ℹ Le cache des routes n'a pas été créé mais l'application fonctionnera${NC}"
fi

echo ""
echo "==================================="
echo -e "${GREEN}✅ Déploiement terminé !${NC}"
echo "==================================="
echo ""
echo "📝 Prochaines étapes:"
echo "   1. Vérifiez les logs: tail -f storage/logs/laravel-\$(date +%Y-%m-%d).log"
echo "   2. Testez le changement de langue sur le site"
echo "   3. Surveillez les erreurs dans les logs"
echo ""
echo "🔄 En cas de problème, restaurez les sauvegardes:"
echo "   - resources/views/components/mobile-floating-widgets.blade.php.backup-*"
echo "   - routes/web.php.backup-*"
echo ""

