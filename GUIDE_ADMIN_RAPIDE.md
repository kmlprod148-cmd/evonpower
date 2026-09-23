# 🚀 Guide Rapide - Utilisateurs Admin

## En 30 secondes ⚡

```bash
php artisan seed:admin-users
```

C'est tout ! 🎉

---

## 🔑 Identifiants créés

### Super Admin (TOUS les droits)
```
Email    : superadmin@evonpower.com
Password : SuperAdmin2024!
```

### Admin (Droits d'administration)
```
Email    : admin@evonpower.com
Password : Admin2024!
```

---

## 📝 Autres commandes utiles

### Réinitialiser tout
```bash
php artisan seed:admin-users --fresh
```
⚠️ Supprime tous les utilisateurs existants !

### Forcer en production
```bash
php artisan seed:admin-users --force
```

---

## 🎭 Comptes de test disponibles

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin Démo | demo.admin@evonpower.com | Demo2024! |
| Intégrateur | integrator@evonpower.com | Integrator2024! |
| Partenaire | partner@evonpower.com | Partner2024! |
| Opérateur | operator@evonpower.com | Operator2024! |
| Utilisateur | user@evonpower.com | User2024! |

---

## ✅ Ce qui est créé automatiquement

- ✅ **270+ permissions** (toutes les permissions système)
- ✅ **7 rôles** (super_admin, admin, integrator, partner, operator, client, user)
- ✅ **1 Super Admin** avec TOUS les droits
- ✅ **1 Admin** avec droits d'administration
- ✅ **5 utilisateurs de test** (un par rôle)

---

## 🔐 Sécurité

> ⚠️ **IMPORTANT** : Changez les mots de passe après la première connexion !

---

## 📖 Documentation complète

Voir le fichier `ADMIN_USERS_SETUP.md` pour tous les détails.

---

**Version** : 1.0.0 | **Dernière mise à jour** : Décembre 2024

