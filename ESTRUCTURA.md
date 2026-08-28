# Nokia 1100 — Mapa del Proyecto

Guía de referencia: qué hace cada carpeta, qué archivos contiene y cómo se relacionan entre sí.
Última actualización: 2026-06-26

---

## Estructura general

```
nokia1100/
├── api/                  → Endpoints AJAX (llamados desde JS del frontend)
├── assets/               → Recursos estáticos globales (compartidos entre módulos)
│   ├── css/              → CSS global + estilos de páginas standalone
│   ├── img/              → Imágenes del sistema
│   └── js/               → JS global + scripts de páginas standalone
├── classes/              → Clases PHP reutilizables del sistema
├── config/               → Configuración de conexión a BD y entorno
├── database/             → Scripts SQL y scripts de migración
├── includes/             → Middlewares y fragmentos PHP reutilizables
├── modules/              → Módulos funcionales (arquitectura vertical slicing)
│   ├── admin/            → Gestión de usuarios y administración
│   ├── auth/             → Autenticación (login, registro, reset)
│   ├── clients/          → Gestión de clientes
│   ├── employee/         → Dashboard del empleado
│   ├── inventory/        → Inventario de productos
│   ├── sales/            → Ventas y estadísticas
│   ├── suppliers/        → Proveedores y compras
│   └── workshop/         → Taller de reparaciones
├── PHPMailer-master/     → Librería para envío de emails
├── forgot_password.php   → Página de recuperación de contraseña
├── index.php             → Pantalla de login / registro
└── track.php             → Redirección al portal de seguimiento
```

---

## Arquitectura: Vertical Slicing

El proyecto sigue un patrón de **vertical slicing**: cada módulo contiene todo lo que necesita para funcionar (controladores, modelos, vistas, JS y CSS propios).

```
modules/<modulo>/
├── Controllers/          → Lógica de negocio
├── Models/               → Consultas a la base de datos
├── Views/                → HTML/PHP de las vistas
├── *.js                  → JavaScript específico del módulo
└── *.css                 → CSS específico del módulo
```

Los únicos assets que permanecen en `assets/` son los **globales** (compartidos por todo el sistema):
- `assets/css/style.css` — Estilos base de todo el dashboard
- `assets/js/tailwind_config.js` — Configuración de Tailwind (global)
- `assets/js/main.js` — Interacciones generales del sistema
- `assets/js/export-helper.js` — Exportación a Excel/PDF
- `assets/js/filtros.js` — Filtros de búsqueda globales
- `assets/js/sileo-toaster.bundle.js` — Componente de notificaciones
- `assets/css/login.css` — Estilo de la página de login (standalone)
- `assets/css/forgot_password.css` — Estilo de recuperación de contraseña (standalone)
- `assets/css/auth_verify.css` — Estilo de verificación (standalone)

---

## Detalle por componente

### `api/` — Endpoints AJAX

| Archivo | Función |
|---|---|
| `cambiar_estado.php` | Toggle activo/inactivo de productos en inventario |
| `check_stock.php` | Validar stock disponible en tiempo real (usado en ventas) |
| `create_product.php` | Crear producto nuevo desde formulario AJAX |
| `get_purchase_history.php` | Obtener historial de compras de proveedores |

---

### `assets/` — Recursos Globales

#### `assets/css/`
| Archivo | Usado en |
|---|---|
| `style.css` | Todo el dashboard (cargado por `Layout.php`) |
| `login.css` | `index.php` (login/registro) |
| `forgot_password.css` | `forgot_password.php` |
| `auth_verify.css` | Verificación de cuenta |

#### `assets/js/`
| Archivo | Función |
|---|---|
| `tailwind_config.js` | Paleta de colores de Tailwind (cargado globalmente) |
| `main.js` | Interacciones del sistema (sidebar, toasts, confirmaciones) |
| `export-helper.js` | Exportar tablas a Excel y PDF |
| `filtros.js` | Filtros de búsqueda en tablas |
| `sileo-toaster.bundle.js` | Componente de notificaciones toast |
| `login.js` | Animación del slider login/registro en `index.php` |

---

### `classes/` — Clases Globales

| Archivo | Función |
|---|---|
| `Layout.php` | Renderiza `<head>`, sidebars admin/empleado, footer. Incluye control de acceso por módulos. |

---

### `config/` — Configuración

| Archivo | Función |
|---|---|
| `db.php` | Conexión a la BD (mysqli). Define `BASE_URL` y constantes de entorno. |

---

### `database/` — Scripts de BD

| Archivo | Cuándo usar |
|---|---|
| `bd_nokia1100.sql` | Importar BD completa con datos de ejemplo |
| `nokia1100_estructura.sql` | Solo la estructura de tablas (sin datos) |
| `migration_seguridad.sql` | Migración de columnas de seguridad (`modulos_permitidos`, etc.) |
| `run_migration.php` | Ejecutar la migración de `modulos_permitidos` vía web |
| `setup_taller.php` | Crear tablas del módulo Taller si no existen |

---

### `includes/` — Middlewares PHP

| Archivo | Función |
|---|---|
| `auth.php` | Protege rutas de admin/empleado. Redirige al login si no hay sesión. |
| `filtros.php` | Funciones de filtrado y sanitización de datos. |

---

### `modules/` — Módulos Funcionales

#### `modules/admin/`
| Archivo | Función |
|---|---|
| `Controllers/AdminController.php` | Gestión de usuarios del sistema |
| `Models/AdminModel.php` | Consultas de usuarios |
| `Views/users.php` | Listado, alta, edición y desactivación de usuarios |
| `admin_users.js` | Modal de agregar/editar usuario, permisos de módulos |
| `admin_users.css` | Estilos de la vista de usuarios |
| `users.php` | Entrypoint del módulo |
| `dashboard.php` | Dashboard del administrador |

#### `modules/auth/`
| Archivo | Función |
|---|---|
| `Controllers/AuthController.php` | Login, logout, registro, recuperación de contraseña |
| `Models/AuthModel.php` | Consultas de autenticación y creación de usuarios |
| `Views/reset.php` | Formulario de nueva contraseña (con token) |
| `reset_password.css` | Estilos del formulario de reset |
| `auth.php` | Procesa el login |
| `logout.php` | Cierra la sesión |
| `process_registration.php` | Procesa el registro |
| `index.php?action=forgot_password` | Envía el email de recuperación |
| `index.php?action=reset_password` | Guarda la nueva contraseña |

#### `modules/clients/`
| Archivo | Función |
|---|---|
| `Controllers/ClientsController.php` | CRUD de clientes |
| `Models/ClientsModel.php` | Consultas de clientes |
| `Views/list.php` | Listado y alta de clientes |
| `clients_list.js` | Búsqueda live y modal de eliminación |
| `clients.php` | Entrypoint del módulo |
| `edit_client.php` | Formulario de edición |

#### `modules/employee/`
| Archivo | Función |
|---|---|
| `dashboard.php` | Dashboard del empleado |

#### `modules/inventory/`
| Archivo | Función |
|---|---|
| `Controllers/InventoryController.php` | CRUD de productos |
| `Models/InventoryModel.php` | Consultas de inventario |
| `Views/inventory.php` | Listado de productos (tabla + grilla) |
| `inventory.js` | Toggle vista tabla/grilla |
| `inventory.php` | Entrypoint del módulo |
| `add_product.php` / `edit_stock.php` | Alta y edición de productos |
| `change_status.php` | Alias interno (la lógica está en `api/cambiar_estado.php`) |

#### `modules/sales/`
| Archivo | Función |
|---|---|
| `Controllers/SalesController.php` | Registro y consulta de ventas |
| `Models/SalesModel.php` | Consultas de ventas |
| `Views/new_sale.php` | Terminal POS (punto de venta) |
| `Views/list.php` | Historial de ventas |
| `Views/invoice.php` | Factura imprimible de una venta |
| `Views/sales_charts.php` | Estadísticas con gráficos |
| `new_sale.js` | Lógica del carrito y envío de la venta |
| `sales_list.js` | Búsqueda y botón de exportación animado |
| `sales_charts.js` | Inicialización de Chart.js |
| `sales_list.css` | Estilos del botón de exportación |
| `sales_invoice.css` | Estilos de la factura para impresión |
| `sales.php` | Entrypoint del módulo |

#### `modules/suppliers/`
| Archivo | Función |
|---|---|
| `Controllers/SuppliersController.php` | CRUD de proveedores y compras |
| `Models/SuppliersModel.php` | Consultas de proveedores |
| `Views/list.php` | Directorio de proveedores |
| `Views/new_purchase.php` | Registrar una orden de compra |
| `Views/purchase_history.php` | Historial de compras |
| `suppliers_list.js` | Búsqueda y toggle de historial inline |
| `new_purchase.js` | Carrito de compra y envío |
| `purchase_history.js` | Toggle de detalles de compra |
| `suppliers_list.css` | Estilos de la tabla de proveedores |
| `suppliers.php` | Entrypoint del módulo |

#### `modules/workshop/`
| Archivo | Función |
|---|---|
| `Controllers/WorkshopController.php` | CRUD de reparaciones |
| `Models/WorkshopModel.php` | Consultas del taller |
| `Views/view.php` | Listado de órdenes de reparación |
| `Views/track.php` | Portal público de seguimiento (sin login) |
| `Views/print_receipt.php` | Comprobante imprimible de reparación |
| `track.css` | Estilos del portal de seguimiento |
| `print_receipt.css` | Estilos del comprobante |
| `index.php` | Entrypoint del módulo |
| `view.php` | Entrypoint de la vista de órdenes |

---

## Páginas standalone (sin módulo)

| Archivo | Función |
|---|---|
| `index.php` | Login + Registro (doble panel animado) |
| `forgot_password.php` | Recuperación de contraseña |
| `track.php` | Redirección al portal de seguimiento del taller |

---

## Flujo de una petición PHP típica

```
Usuario → index.php / entrypoint.php
  → includes/auth.php (verifica sesión)
  → modules/<modulo>/Controllers/<Controlador>.php
      → modules/<modulo>/Models/<Modelo>.php (consulta BD)
      → modules/<modulo>/Views/<vista>.php (renderiza HTML)
          → classes/Layout.php (head, sidebar, footer)
          → modules/<modulo>/<vista>.js (lógica frontend)
          → modules/<modulo>/<vista>.css (estilos)
```

## Flujo de una llamada AJAX

```
modules/<modulo>/Views/<vista>.php + <vista>.js
  → fetch('/nokia1100/api/<endpoint>.php')
      → api/<endpoint>.php
          → modules/<modulo>/Controllers/<Controlador>.php
              → modules/<modulo>/Models/<Modelo>.php
                  → Base de datos MySQL
          ← JSON response
      ← Actualiza el DOM
```
