# Guía de instalación — Nokia 1100 System
## Cambios generados (checklist pendiente)

---

## 1. Ejecutar la migración SQL (PRIMERO)

Importá `migration_seguridad.sql` en tu base de datos MySQL/MariaDB:

```bash
mysql -u root -p nokia1100 < migration_seguridad.sql
```

Esto agrega:
- Columnas `is_active`, `fecha_baja`, `motivo_baja` en la tabla `usuario`
- Tabla `audit_log` para registrar acciones sensibles

---

## 2. Copiar el helper de auditoría

```
audit.php  →  config/audit.php
```

---

## 3. Reemplazar archivos PHP

| Archivo generado          | Destino en el proyecto                        |
|---------------------------|-----------------------------------------------|
| `auth_login.php`          | `modules/auth/auth.php`                       |
| `delete_user.php`         | `modules/admin/delete_user.php`               |
| `users.php`               | `modules/admin/users.php`                     |
| `audit_log.php`           | `modules/admin/audit_log.php` *(nuevo)*       |
| `new_sale.php`            | `modules/sales/new_sale.php`                  |
| `process_registration.php`| `modules/auth/process_registration.php`       |

---

## 4. Agregar enlace "Auditoría" al sidebar admin

En `classes/Layout.php`, dentro del array `$links` de `renderAdminSidebar()`, agregá:

```php
['id' => 'auditoria', 'url' => BASE_URL . '/modules/admin/audit_log.php',
 'icon' => 'security', 'label' => 'Auditoría'],
```

---

## 5. Usuarios existentes sin verificar

Los usuarios existentes en la BD tienen `verificado = 0`.
Para habilitarlos sin que tengan que verificar email, ejecutá:

```sql
UPDATE usuario SET verificado = 1 WHERE verificado = 0;
```

O dejalo así si querés forzar la verificación (recomendado para producción).

---

## Checklist cubierto por estos archivos

| Ítem                                       | Estado  |
|--------------------------------------------|---------|
| Sin alerts nativos del navegador            | ✅ Reemplazados por showToast/showConfirmModal |
| ABM de usuarios con baja lógica            | ✅ Soft delete + restaurar en users.php       |
| Validación de email por envío de correo    | ✅ Login bloquea si `verificado = 0`          |
| Seguridad y Auditoría                      | ✅ Tabla audit_log + logging en login/ventas  |
| Validación de unicidad (DNI)               | ✅ Agregado en process_registration.php       |
| Validación de formato email                | ✅ filter_var en process_registration.php     |
| Exclusión del propio usuario al editar     | ✅ delete_user bloquea self-disable           |
