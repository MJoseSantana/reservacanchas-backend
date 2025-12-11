-- Script para verificar datos de usuarios y canchas

-- 1. Ver todos los usuarios con rol 'dueno'
SELECT id, nombre, apellido, email, rol, estado, created_at
FROM usuarios
WHERE rol = 'dueno'
ORDER BY created_at DESC;

-- 2. Ver todas las canchas con su dueño
SELECT 
    c.id AS cancha_id,
    c.nombre AS cancha_nombre,
    c.dueno_id,
    u.nombre AS dueno_nombre,
    u.email AS dueno_email,
    u.rol AS dueno_rol,
    u.estado AS dueno_estado,
    c.estado AS cancha_estado,
    c.created_at AS cancha_creada
FROM canchas c
LEFT JOIN usuarios u ON c.dueno_id = u.id
ORDER BY c.created_at DESC;

-- 3. Contar canchas por dueño
SELECT 
    u.id AS usuario_id,
    u.nombre,
    u.email,
    u.rol,
    u.estado,
    COUNT(c.id) AS total_canchas
FROM usuarios u
LEFT JOIN canchas c ON c.dueno_id = u.id
WHERE u.rol = 'dueno'
GROUP BY u.id, u.nombre, u.email, u.rol, u.estado
ORDER BY total_canchas DESC;

-- 4. Buscar canchas huérfanas (sin dueño válido)
SELECT 
    c.id AS cancha_id,
    c.nombre AS cancha_nombre,
    c.dueno_id,
    c.estado
FROM canchas c
WHERE c.dueno_id IS NULL 
   OR NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.id = c.dueno_id);

-- 5. Ver estructura de la tabla canchas
SELECT 
    column_name,
    data_type,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'canchas'
ORDER BY ordinal_position;
