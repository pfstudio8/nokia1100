# Módulo de Caja · Nokia 1100

Módulo de gestión y arqueo de caja diaria para el sistema de gestión Nokia 1100.

## Archivos del Módulo

- `Controllers/CajaController.php`: Controlador principal, orquesta la apertura, movimientos, arqueos, cierres, detalle AJAX y exportación a CSV.
- `Models/CajaModel.php`: Modelo de datos para las tablas `caja` y `caja_movimiento`. Realiza transacciones y cálculos automáticos de saldos.
- `Views/index.php`: Vista interactiva con panel de apertura, display LCD de saldo esperado, formulario de movimientos, modal de cierre/arqueo, modal de detalle y tabla histórica con segmentadores.
- `caja.css`: Estilos visuales acordes al UI Kit Nokia 1100 (Graphite, LCD `#c6e24a`, papel `#eeebe3`, bordes `#d3cebf`).
- `caja.js`: Lógica cliente (Fetch API, apertura/cierre de modales, cálculo dinámico de diferencias, integración con `showConfirmModal` y `showToast`).
- `index.php`: Router y punto de entrada con verificación de sesión (`auth_middleware.php`) y control CSRF.

## Base de Datos

Las tablas se definen en `database/migration_caja.sql`:
- `caja`: Registro de cada jornada/turno de caja (apertura, cierre, saldo esperado, saldo real, diferencia y observaciones).
- `caja_movimiento`: Movimientos individuales asociados (`in` = ingreso, `out` = egreso).

Para poblar con datos de prueba:
- `database/seed_caja.sql`

## Ciclo de Uso

1. **Apertura:** Ingrese el saldo inicial en efectivo o use el botón "Usar último cierre".
2. **Movimientos:** Registre ingresos (`+`) o egresos (`−`) con concepto y monto. El saldo esperado se recalcula al instante.
3. **Cierre:** Al finalizar el día, cuente el dinero físico real. El sistema calculará la diferencia (sobrante o faltante). Si existe diferencia, la observación es obligatoria.
4. **Auditoría:** Utilice los filtros por estado (Todas / Abiertas / Cerradas / Con diferencia) o por mes para auditorías, ver detalles de cajas anteriores o exportar a CSV.

