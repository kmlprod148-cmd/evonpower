#!/bin/bash

################################################################################
# Script de déploiement des corrections - Production
# Date: 23 Décembre 2025
# 
# Ce script applique automatiquement toutes les corrections de routes
# et nettoie les caches sur le serveur de production
################################################################################

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Fonction pour vérifier le statut de la dernière commande
check_status() {
    if [ $? -eq 0 ]; then
        print_success "$1"
    else
        print_error "$2"
        exit 1
    fi
}

################################################################################
# DÉBUT DU SCRIPT
################################################################################

print_header "🚀 Déploiement des corrections de routes"

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    print_error "Ce script doit être exécuté depuis la racine du projet Laravel"
    exit 1
fi

print_success "Répertoire du projet validé"

################################################################################
# 1. SAUVEGARDE
################################################################################

print_header "📦 Création des sauvegardes"

BACKUP_DIR="backups_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Sauvegarder les routes
cp -r routes "$BACKUP_DIR/"
check_status "Routes sauvegardées" "Échec de la sauvegarde des routes"

# Sauvegarder les contrôleurs
cp -r app/Http/Controllers "$BACKUP_DIR/"
check_status "Contrôleurs sauvegardés" "Échec de la sauvegarde des contrôleurs"

print_info "Sauvegardes créées dans: $BACKUP_DIR"

################################################################################
# 2. RÉCUPÉRATION DES MODIFICATIONS
################################################################################

print_header "📥 Récupération des dernières modifications"

# Vérifier si git est disponible
if command -v git &> /dev/null; then
    print_info "Statut Git actuel:"
    git status --short
    
    echo ""
    read -p "Voulez-vous récupérer les dernières modifications depuis Git? (o/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[OoYy]$ ]]; then
        # Stasher les modifications locales
        if [ -n "$(git status --porcelain)" ]; then
            print_warning "Modifications locales détectées, création d'un stash..."
            git stash
            check_status "Stash créé" "Échec de la création du stash"
        fi
        
        # Pull
        git pull origin main
        check_status "Modifications récupérées depuis Git" "Échec du git pull"
    else
        print_info "Skip du git pull"
    fi
else
    print_warning "Git non disponible, skip de cette étape"
fi

################################################################################
# 3. NETTOYAGE DES CACHES
################################################################################

print_header "🧹 Nettoyage des caches"

print_info "Nettoyage du cache général..."
php artisan cache:clear
check_status "Cache général nettoyé" "Échec du nettoyage du cache"

print_info "Nettoyage du cache de configuration..."
php artisan config:clear
check_status "Cache de configuration nettoyé" "Échec du nettoyage de la config"

print_info "Nettoyage du cache des routes..."
php artisan route:clear
check_status "Cache des routes nettoyé" "Échec du nettoyage des routes"

print_info "Nettoyage du cache des vues..."
php artisan view:clear
check_status "Cache des vues nettoyé" "Échec du nettoyage des vues"

print_info "Nettoyage des caches optimisés..."
php artisan optimize:clear
check_status "Caches optimisés nettoyés" "Échec du nettoyage d'optimize"

################################################################################
# 4. VÉRIFICATION DES ROUTES
################################################################################

print_header "🔍 Vérification des routes"

print_info "Test de compilation du cache des routes..."
php artisan route:cache 2>&1

if [ $? -eq 0 ]; then
    print_success "Le cache des routes compile correctement !"
    ROUTE_CACHE_OK=true
else
    print_warning "Le cache des routes ne peut pas être compilé"
    print_info "L'application fonctionnera sans cache de routes"
    print_info "Nettoyage du cache de routes..."
    php artisan route:clear
    ROUTE_CACHE_OK=false
fi

################################################################################
# 5. RECRÉATION DES CACHES (OPTIONNEL)
################################################################################

if [ "$ROUTE_CACHE_OK" = true ]; then
    print_header "⚡ Recréation des caches optimisés"
    
    echo ""
    read -p "Voulez-vous recréer les caches optimisés ? (O/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        print_info "Création du cache de configuration..."
        php artisan config:cache
        check_status "Cache de configuration créé" "Échec de la création du cache de config"
        
        print_info "Création du cache des routes..."
        php artisan route:cache
        check_status "Cache des routes créé" "Échec de la création du cache des routes"
        
        print_info "Création du cache des vues..."
        php artisan view:cache
        check_status "Cache des vues créé" "Échec de la création du cache des vues"
    else
        print_info "Skip de la recréation des caches"
    fi
else
    print_warning "Les caches optimisés ne seront pas recréés car le cache des routes a échoué"
fi

################################################################################
# 6. PERMISSIONS
################################################################################

print_header "🔐 Vérification des permissions"

print_info "Correction des permissions sur storage..."
chmod -R 775 storage bootstrap/cache 2>/dev/null
if [ $? -eq 0 ]; then
    print_success "Permissions corrigées"
else
    print_warning "Impossible de modifier les permissions (peut nécessiter sudo)"
fi

################################################################################
# 7. REDÉMARRAGE DES SERVICES
################################################################################

print_header "🔄 Redémarrage des services"

# Queue workers
if command -v php artisan queue:restart &> /dev/null; then
    print_info "Redémarrage des queue workers..."
    php artisan queue:restart
    check_status "Queue workers redémarrés" "Échec du redémarrage des workers"
else
    print_info "Pas de queue workers à redémarrer"
fi

# PHP-FPM (optionnel, nécessite sudo)
echo ""
read -p "Voulez-vous redémarrer PHP-FPM ? (nécessite sudo) (o/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[OoYy]$ ]]; then
    print_info "Tentative de redémarrage de PHP-FPM..."
    
    # Détecter la version de PHP
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    
    # Essayer différentes commandes de redémarrage
    if sudo systemctl restart php${PHP_VERSION}-fpm 2>/dev/null; then
        print_success "PHP-FPM redémarré (systemctl)"
    elif sudo service php${PHP_VERSION}-fpm restart 2>/dev/null; then
        print_success "PHP-FPM redémarré (service)"
    elif sudo service php-fpm restart 2>/dev/null; then
        print_success "PHP-FPM redémarré (service php-fpm)"
    else
        print_warning "Impossible de redémarrer PHP-FPM automatiquement"
        print_info "Redémarrez-le manuellement avec: sudo systemctl restart php${PHP_VERSION}-fpm"
    fi
else
    print_info "Skip du redémarrage de PHP-FPM"
fi

################################################################################
# 8. TESTS POST-DÉPLOIEMENT
################################################################################

print_header "🧪 Tests post-déploiement"

# Test 1: Vérifier les routes de langue
print_info "Test des routes de langue..."
LANG_ROUTE_COUNT=$(php artisan route:list --name=language 2>/dev/null | grep -c "language")
if [ "$LANG_ROUTE_COUNT" -gt 0 ]; then
    print_success "Routes de langue trouvées ($LANG_ROUTE_COUNT routes)"
else
    print_warning "Aucune route de langue trouvée"
fi

# Test 2: Vérifier les routes admin
print_info "Test des routes admin..."
ADMIN_ROUTE_COUNT=$(php artisan route:list --name=admin 2>/dev/null | grep -c "admin")
if [ "$ADMIN_ROUTE_COUNT" -gt 0 ]; then
    print_success "Routes admin trouvées ($ADMIN_ROUTE_COUNT routes)"
else
    print_warning "Aucune route admin trouvée"
fi

# Test 3: Vérifier les logs d'erreur récents
print_info "Vérification des logs d'erreur..."
LOG_FILE="storage/logs/laravel-$(date +%Y-%m-%d).log"
if [ -f "$LOG_FILE" ]; then
    ERROR_COUNT=$(tail -100 "$LOG_FILE" | grep -c "ERROR" || echo "0")
    if [ "$ERROR_COUNT" -eq 0 ]; then
        print_success "Aucune erreur récente dans les logs"
    else
        print_warning "$ERROR_COUNT erreur(s) trouvée(s) dans les logs récents"
        print_info "Vérifiez les logs avec: tail -50 $LOG_FILE"
    fi
else
    print_info "Pas de fichier de log pour aujourd'hui"
fi

################################################################################
# RÉSUMÉ
################################################################################

print_header "📊 Résumé du déploiement"

echo -e "${GREEN}✓${NC} Sauvegardes créées dans: ${BLUE}$BACKUP_DIR${NC}"
echo -e "${GREEN}✓${NC} Caches nettoyés"

if [ "$ROUTE_CACHE_OK" = true ]; then
    echo -e "${GREEN}✓${NC} Cache des routes compilé avec succès"
else
    echo -e "${YELLOW}⚠${NC} Cache des routes non activé (application fonctionne sans)"
fi

echo -e "\n${BLUE}ℹ${NC} Pour restaurer la sauvegarde en cas de problème:"
echo -e "   ${YELLOW}rm -rf routes && cp -r $BACKUP_DIR/routes .${NC}"
echo -e "   ${YELLOW}rm -rf app/Http/Controllers && cp -r $BACKUP_DIR/app/Http/Controllers .${NC}"

echo -e "\n${BLUE}ℹ${NC} Pour surveiller les logs:"
echo -e "   ${YELLOW}tail -f storage/logs/laravel-\$(date +%Y-%m-%d).log${NC}"

print_header "✅ Déploiement terminé avec succès !"

exit 0


