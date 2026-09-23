-- ============================================
-- Script pour assigner le rôle super_admin à l'utilisateur ID 4
-- ============================================

-- 1. Vérifier que l'utilisateur existe
SELECT 
    id, 
    name, 
    email, 
    role as 'role_colonne'
FROM users 
WHERE id = 4;

-- 2. Vérifier si le rôle super_admin existe
SELECT id, name FROM roles WHERE name = 'super_admin';

-- 3. Si le rôle n'existe pas, le créer (décommentez si nécessaire)
-- INSERT INTO roles (name, guard_name, created_at, updated_at) 
-- VALUES ('super_admin', 'web', NOW(), NOW());

-- 4. Assigner le rôle super_admin à l'utilisateur ID 4
-- (Cela n'échouera pas si le rôle est déjà assigné grâce à ON DUPLICATE KEY UPDATE)
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT 
    r.id,
    'App\\Models\\User',
    4
FROM roles r
WHERE r.name = 'super_admin'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- 5. Vérifier l'assignation
SELECT 
    u.id,
    u.name,
    u.email,
    r.name as role_name
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id
INNER JOIN roles r ON r.id = mhr.role_id
WHERE u.id = 4;

-- ============================================
-- FIN DU SCRIPT
-- ============================================

