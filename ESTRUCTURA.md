# Nokia 1100 — Mapa del Proyecto

Guía de referencia técnica: qué hace cada carpeta, qué archivos contiene y cómo se relacionan entre sí.
Última actualización: 2026-11-20

---

## Estructura general

```
nokia1100/
├── api/                  → Endpoints AJAX directos (compatibilidad con JS del frontend)
├── assets/               → Recursos estáticos globales y de módulos principales
│   ├── css/              → Hojas de estilo globales, login, OTP, loader, tracker e inventario
│   ├── img/              → Imágenes subidas de órdenes de reparación (reparaciones/)
│   └── js/               → Scripts globales (main, theme, export, toaster, loader, tracker, inventory)
├── auth/                 → Páginas standalone de autenticación (forgot_password.php, verificar.php)
├── classes/              → Clases base MVC, conexión a BD, Layout, QR SVG y ServiceProduct
├── config/               → Bootstrap (.env + cookies), conexión MySQLi, SMTP y auditoría de inventario
├── database/             → Dumps SQL y motor de migración automática en runtime (run_migrations.php)
├── modules/              → Módulos funcionales (arquitectura MVC por Vertical Slicing)
│   ├── admin/            → Dashboard gerencial, ABM de usuarios/permisos, perfil y auditoría
│   ├── auth/             → Controlador y modelo de autenticación, OTP y restablecimiento de clave
│   ├── caja/             → Apertura de caja, libro inmutable de movimientos, contra-asientos y cierre ciego
│   ├── clients/          → Gestión de clientes
│   ├── employee/         → Centro de operaciones para empleados e invitados
│   ├── inventory/        → Catálogo e inventario reactivo de productos y repuestos
│   ├── reports/          → Centro de reportes y estadísticas (10 gráficos Chart.js en 4 pestañas)
│   ├── sales/            → Terminal POS (pago único/combinado), historial, facturas, notas de crédito y cobro de taller
│   ├── suppliers/        → Hub unificado de proveedores, asistente de reposición, compra manual y recepción verificada
│   └── workshop/         → Taller de reparaciones, ingreso de equipos, repuestos, fotos, recibos QR y seguimiento en vivo
├── help.php              → Manual de uso interactivo en 6 pestañas con datos reales del local
├── index.php             → Pantalla de acceso (Login / Registro / Invitado / Modal OTP)
└── track.php             → Punto de entrada público al portal de seguimiento de taller
```

---

## Arquitectura: MVC por Vertical Slicing

Cada módulo dentro de `modules/<modulo>/` encapsula su propio router (`index.php`), controlador (`Controllers/`), modelo (`Models/`), vistas (`Views/`) y recursos específicos (`*.js`, `*.css`):

```
modules/<modulo>/
├── index.php             → Router del módulo (despacha ?action=... al controlador)
├── Controllers/          → Validación de entrada, permisos, CSRF y orquestación
├── Models/               → Consultas SQL preparadas y transacciones atómicas MySQLi
├── Views/                → Plantillas HTML/PHP renderizadas dentro de Layout
├── *.js                  → Lógica cliente del módulo
└── *.css                 → Estilos específicos del módulo
```

---

## Detalle por componente

### 1. Raíz del proyecto y `auth/` — Páginas de entrada

| Archivo | Función |
|---|---|
| `index.php` | Pantalla principal de Login, Registro, Acceso de Invitado y Modal OTP de 6 dígitos |
| `help.php` | Centro de ayuda interactivo en 6 pestañas con previsualizaciones conectadas a la BD |
| `track.php` | Punto de entrada público que delega en `WorkshopController::track()` |
| `auth/forgot_password.php` | Formulario público para solicitar enlace de recuperación de contraseña por correo |
| `auth/verificar.php` | Vista de verificación de código OTP o redirección al modal OTP de `index.php` |

---

### 2. `api/` — Endpoints AJAX

| Archivo | Delega en | Función |
|---|---|---|
| `api/change_status.php` | `InventoryController::change_status()` | Alterna el estado lógico (`is_active`) de un producto |
| `api/check_stock.php` | `InventoryController::check_stock()` | Consulta el stock disponible de un producto en tiempo real (usado en POS) |
| `api/rollback_sale.php` | `SalesController::rollback()` | Anula una venta, devuelve stock al inventario y registra el contra-asiento en caja |

---

### 3. `classes/` — Clases Globales del Núcleo

| Archivo | Función |
|---|---|
| `BaseController.php` | Controlador padre: inicia sesión, valida sesión única (`check_auth`), permisos por módulo (`check_access`, `check_any_access`, `require_primary_admin`), CSRF (`verify_csrf`), detección AJAX (`is_ajax`), política de contraseñas (`validate_password`) y renderizado (`render_view`) |
| `BaseModel.php` | Modelo padre que inyecta la conexión global `$conn` (`mysqli`) |
| `Database.php` | Conecta a MySQL con `utf8mb4` y zona horaria `-03:00`, y ejecuta `apply_system_migrations()` |
| `Layout.php` | Renderiza `<head>`, barra superior, sidebars de Admin/Empleado/Invitado, botones de exportación Excel/PDF, insignias de medios de pago, paginación y control de permisos por rol y `modulos_permitidos` |
| `QRCodeSVG.php` | Generador nativo autónomo de códigos QR en formato SVG (ISO/IEC 18004, versiones 1 a 10) |
| `ServiceProduct.php` | Gestiona e identifica por nombre el producto contable `"Servicio Técnico Taller"` y provee `excludeSql()` para ocultarlo de métricas y listados de stock físico |

---

### 4. `config/` — Configuración y Servicios Transversales

| Archivo | Función |
|---|---|
| `bootstrap.php` | Fija zona horaria `America/Argentina/Buenos_Aires`, endurece cookies de sesión (`httponly`, `strict_mode`, `samesite=Lax`), carga Composer y valida variables `.env` con `phpdotenv` |
| `db.php` | Carga `bootstrap.php`, define `BASE_URL` e instancia `Database` (`$conn`) |
| `config_mail.php` | Normaliza variables `SMTP_*` desde `.env` y verifica disponibilidad de `PHPMailer` |
| `audit.php` | Define `audit_log()`, dedicada exclusivamente a registrar movimientos y eventos de inventario (`INVENTORY_*` / tablas `inventario` y `producto`) en la tabla `audit_log` |

---

### 5. `database/` — Esquema y Migraciones

| Archivo | Función |
|---|---|
| `nokia1100_actual.sql` | Esquema completo actualizado de la base de datos (20 tablas y claves foráneas) |
| `run_migrations.php` | Migración automática idempotente ejecutada al iniciar conexión (protegida por `.schema_v5_clean_der.lock`) |
| `migration_caja.sql` | Script SQL de referencia para las tablas `caja` y `caja_movimiento` |
| `migration_pago_combinado.sql` | Script SQL de referencia para la tabla `venta_pago` |

---

### 6. `assets/` — Recursos Estáticos Globales

#### `assets/css/`
| Archivo | Usado en |
|---|---|
| `style.css` | Estilos globales del sistema y tema claro/oscuro Nokia 1100 (cargado por `Layout.php` e `index.php`) |
| `inventory.css` | Estilos del módulo de inventario (`modules/inventory/Views/inventory.php`) |
| `login.css` | Estilos del doble panel de acceso (`index.php`) |
| `forgot_password.css` | Estilos de `auth/forgot_password.php` y `auth/verificar.php` |
| `one-time-code.css` | Estilos del componente de ingreso de código OTP de 6 dígitos (`index.php`) |
| `nokia-loader.css` | Estilos de la pantalla de transición/carga Nokia 1100 |
| `nokia-tracker.css` | Estilos de la línea de seguimiento animada v2.0 (`.nkt`) en `track.php` |

#### `assets/js/`
| Archivo | Función |
|---|---|
| `tailwind_config.js` | Configuración de paleta y fuentes de Tailwind CSS |
| `theme.js` | Conmutador de modo claro/oscuro persistido en `localStorage('nokia1100_theme')` |
| `session-cache.js` | Sincroniza el usuario activo en `localStorage('nokia1100_user')` y lo limpia al cerrar sesión |
| `main.js` | Paginación de tablas (`[data-paginate]`), búsqueda difusa (Levenshtein), validación de formularios con Toast, modales de confirmación, atajos de teclado (`F2`, `F3`, `F4`, `Ctrl+B`) y polling en vivo de taller |
| `framer-toaster.js` | Sistema de notificaciones `window.showToast()` y `window.showConfirmModal()` |
| `export-helper.js` | Exportación de tablas HTML a Excel (SheetJS) y PDF (jsPDF + AutoTable) con ventana de cancelación |
| `nokia-loader.js` | Animación de carga al iniciar/cerrar sesión |
| `one-time-code.js` | Componente interactivo de 6 casillas para el código OTP |
| `login.js` | Lógica cliente de login, registro AJAX y modal de verificación OTP en `index.php` |
| `inventory.js` | Motor reactivo del módulo de Inventario (filtros, ordenamiento, KPIs, paginación, modales CRUD AJAX y exportación) |

---

### 7. `modules/` — Módulos Funcionales

#### `modules/admin/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`dashboard`, `users`, `add_user`, `edit_user`, `delete_user`, `save_user_modules`, `update_user_role`, `profile`, `audit`, `get_dashboard_data`) |
| `Controllers/AdminController.php` | Controlador del panel gerencial, gestión de usuarios (exclusiva del admin principal), perfil y auditoría |
| `Models/AdminModel.php` | Consultas de KPIs, usuarios/personas, asignación de módulos/roles, auditoría de inventario y estadísticas financieras |
| `Views/dashboard.php` | Panel principal del administrador con carga asíncrona de métricas y ventas recientes |
| `Views/users.php` | ABM de usuarios con baja lógica, filtros de estado y drawer de permisos por módulo |
| `Views/profile.php` | Edición de datos personales y nombre de usuario del administrador |
| `Views/audit.php` | Historial paginado de auditoría de inventario |
| `admin_users.js` / `admin_users.css` | Lógica de modales de usuario, validador de reglas de contraseña y estilos de administración |

#### `modules/auth/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`login`, `logout`, `register`, `verify_otp`, `resend_otp`, `forgot_password`, `reset_password_view`, `reset_password`, `guestLogin`) |
| `Controllers/AuthController.php` | Autenticación, creación de cuenta, envío de emails SMTP (OTP y recuperación) y acceso de invitado |
| `Models/AuthModel.php` | Consultas de credenciales, registro transaccional (`persona` + `usuario`), tokens OTP y tokens de recuperación |
| `Views/reset.php` / `reset_password.css` | Vista y estilos para definir una nueva contraseña mediante token válido |

#### `modules/caja/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`index`, `open`, `add_movement`, `remove_movement`, `reverse_movement`, `close`, `detail`, `export_csv`) |
| `Controllers/CajaController.php` | Apertura de caja, ingresos/egresos manuales, contra-asientos con motivo obligatorio, cierre ciego y exportación CSV |
| `Models/CajaModel.php` | Libro inmutable `caja_movimiento`, cálculo de efectivo esperado en vivo, bloqueo `FOR UPDATE` e integración transaccional con ventas y cobros de taller |
| `Views/index.php` | Interfaz del módulo de Caja Diaria y Arqueos |
| `caja.js` / `caja.css` | Lógica interactiva de apertura, movimientos, contra-asientos, cierre ciego y estilos del módulo |

#### `modules/clients/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`index`, `add`, `edit`, `delete`) |
| `Controllers/ClientsController.php` | Listado, búsqueda, alta, edición y eliminación protegida (verifica órdenes de reparación asociadas) |
| `Models/ClientModel.php` | CRUD sobre la tabla `cliente` |
| `Views/list.php` / `Views/edit.php` | Listado con modal de alta y formulario de edición de cliente |
| `clients_list.js` | Apertura de modal y baja por `POST` con confirmación |

#### `modules/employee/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`dashboard`) |
| `Controllers/EmployeeController.php` | Carga estadísticas de ventas del día, reparaciones listas para cobrar y catálogo rápido |
| `Models/EmployeeModel.php` | Consultas de ventas diarias y productos destacados en stock |
| `Views/dashboard.php` | Centro de operaciones para empleados e invitados |

#### `modules/inventory/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`inventory`, `change_status`, `check_stock`, `ajax_save_product`) |
| `Controllers/InventoryController.php` | Carga del catálogo completo y endpoints AJAX de alta/edición, cambio de estado y consulta de stock |
| `Models/InventoryModel.php` | Transacciones sobre `producto`, `producto_detalle` e `inventario`, KPIs y métricas para reportes |
| `Views/inventory.php` | Interfaz reactiva Nokia 1100 de inventario (alimentada por `assets/js/inventory.js` y `assets/css/inventory.css`) |

#### `modules/reports/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`dashboard`) |
| `Controllers/ReportsController.php` | Consolida indicadores financieros, de inventario, de ventas y de taller |
| `Views/reports.php` / `reports.js` | Dashboard analítico con 10 gráficos interactivos Chart.js en 4 pestañas y tablas exportables |

#### `modules/sales/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`sales`, `new_sale`, `repairs_to_charge`, `charge_repair`, `invoice`, `credit_note`, `rollback`, `check_ready_repairs`) |
| `Controllers/SalesController.php` | Historial de ventas, terminal POS, cobro de reparaciones, facturas, notas de crédito, anulación y polling de taller |
| `Models/SalesModel.php` | Validación de pagos (único o combinado de hasta 2 medios), registro transaccional de ventas, anulación con devolución de stock y métricas de ventas |
| `Views/list.php` / `sales_list.js` / `sales_list.css` | Historial paginado de ventas con anulación y acceso a factura o nota de crédito |
| `Views/new_sale.php` / `new_sale.js` | Terminal POS con carrito en `localStorage`, cálculo de vuelto y pago único o combinado |
| `Views/repairs_to_charge.php` | Bandeja de cobro y entrega de equipos finalizados en taller |
| `Views/invoice.php` / `Views/credit_note.php` / `sales_invoice.css` | Comprobantes imprimibles de factura y nota de crédito |

#### `modules/suppliers/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`suppliers`, `save_supplier`, `delete_supplier`, `new_purchase`, `purchase_history`, `receive_purchase`, `cancel_purchase`, `quick_product`) |
| `Controllers/SuppliersController.php` | Hub unificado de abastecimiento, CRUD AJAX de proveedores, alta rápida de productos, órdenes de compra y control de recepción |
| `Models/SupplierModel.php` | Consultas y transacciones de `proveedor`, `compra` y `detalle_compra` con impacto en `inventario` al recibir mercadería |
| `Views/list.php` / `suppliers_list.js` / `suppliers_list.css` | Interfaz integral en 5 secciones: Directorio, Asistente de Reposición, Compra Manual, Pedidos/Recepción y Stock en Vivo |

#### `modules/workshop/`
| Archivo | Función |
|---|---|
| `index.php` | Router (`index`, `add`, `view`, `add_repuesto`, `remove_repuesto`, `update_status`, `send_to_cashier`, `add_image`, `delete_image`, `print_receipt`, `delivery_receipt`, `track`, `history`, `check_status`) |
| `Controllers/WorkshopController.php` | Gestión de órdenes de reparación, ingreso con fotos, asignación de repuestos, derivación a caja, recibos y portal público de seguimiento |
| `Models/WorkshopModel.php` | Transacciones de órdenes, repuestos (con descuento/devolución de stock), historial de estados, galería de imágenes y cobro/entrega atómica |
| `Views/index.php` | Listado de órdenes del taller con alertas de nuevos ingresos y filtros por estado |
| `Views/add.php` | Formulario de recepción de equipos en mostrador e historial rápido |
| `Views/view.php` | Ficha técnica de la orden: cliente, equipo, galería con lightbox, repuestos, cambio de estado y envío a ventas |
| `Views/history.php` | Historial paginado de reparaciones cobradas |
| `Views/print_receipt.php` / `Views/delivery_receipt.php` / `print_receipt.css` | Recibo de ingreso con código QR SVG y acta de conformidad de entrega |
| `Views/track.php` / `track.css` | Portal público de seguimiento en tiempo real con línea de progreso `.nkt` |
