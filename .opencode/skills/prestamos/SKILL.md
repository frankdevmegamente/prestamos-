---
name: prestamos
description: Usar para TODO el código PHP de este proyecto. Aplica el patrón de página, helpers globales, convenciones de naming, seguridad (CSRF, XSS, prepared statements) y UI/UX definidos en el proyecto.
---

# Skill: Sistema de Gestión de Préstamos

Eres un experto PHP desarrollando en el **Sistema de Gestión de Préstamos**. Sigue estrictamente todas las reglas abajo.

## Patrón de página (OBLIGATORIO)

```php
<?php
$pageTitle = 'Título de la Página';
require_once __DIR__ . '/../includes/header.php';
$pdo = getConnection();
// — lógica de negocio —
//   1. Obtener parámetros GET/POST
//   2. Validar CSRF si es POST
//   3. Construir queries con prepared statements
//   4. Manejar errores con try/catch
?>
<div class="page-header animate-in">...</div>
<div class="card animate-in">...</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```

## Helpers globales (config/database.php)

| Helper | Uso |
|--------|-----|
| `getConnection()` | Devuelve PDO |
| `getConfiguracion($pdo)` | Array con config de empresa |
| `redirect($url)` | Redirige y exit |
| `sanitize($data)` | htmlspecialchars(trim()) — SIEMPRE en outputs |
| `formatMoney($amount, 'MXN')` | "MXN 1,234.56" |
| `flash('success'|'danger', $msg)` | Mensaje flash en sesión |
| `getFlash()` | Obtiene y borra flash |
| `calcularMora($saldo, $diasAtraso, $tasaMoraDiaria, $diasGracia)` | Fórmula de mora |
| `generateCSRFToken()` | Token CSRF de 64 hex |
| `validateCSRFToken($token)` | Comparación constant-time |
| `csrfField()` | `<input type="hidden" name="csrf_token" value="...">` |
| `requireLogin()` | Redirige si no hay sesión |
| `isLoggedIn()` | bool |
| `tasaPorPeriodo($tasaMensual, $tipoPlazo)` | Tasa ajustada por periodo |
| `numCuotasPorPlazo($plazo, $tipoPlazo)` | Número de cuotas |
| `calcularCuota($monto, $tasaMensual, $plazo, $tipoPlazo)` | Fórmula de anualidad |
| `textoPeriodo($tipoPlazo)` | "diario" / "semanal" / "mensual" / "anual" |
| `textoPlazo($tipoPlazo)` | "días" / "semanas" / "meses" / "años" |
| `textoCuota($tipoPlazo)` | "Cuota diaria" / "semanal" / "mensual" / "anual" |

## Convenciones de código

### Naming
- **PHP vars**: `$snake_case`
- **PHP/JS funciones**: `camelCase()`
- **CSS classes**: `kebab-case`
- **Archivos**: lowercase (`crear.php`, `editar.php`, `ver.php`, `eliminar.php`)
- **Tablas DB**: plural snake_case (`clientes`, `prestamos`, `pagos`)
- **Columnas DB**: `snake_case`

### Seguridad (OBLIGATORIO)
- Toda salida HTML: `<?= sanitize($variable) ?>`
- Todo formulario: `<?= csrfField() ?>` y en POST `validateCSRFToken($_POST['csrf_token'] ?? '')`
- Prepared statements SIEMPRE con placeholders `?`, nunca interpolación directa
- `intval()` en LIMIT/OFFSET
- `ON DELETE RESTRICT` en FK

### UI/UX
- **Tablas**: `.table .table-hover .mb-0`, celdas numéricas con `.num`
- **Badges**: `<span class="badge badge-{estado}">` (activo, moroso, pagado, inactivo)
- **Acciones**: dropdown con tres-puntos `.dropdown-actions`
- **Formularios**: `.floating-group` + `data-loading` en el form
- **Empty state**: `<div class="empty-state"><i class="bi ..."></i><p>Mensaje</p></div>`
- **Breadcrumb**: `<?php renderBreadcrumb(); ?>`
- **Búsqueda**: `.search-bar` con input + botón Buscar + Limpiar
- **Paginación**: LIMIT/OFFSET manual, variables `$pagina`, `$offset`, `$totalPaginas`
- **Alertas de error**: `<div class="alert alert-danger animate-in"><ul>...</ul></div>`
- **Header de página**: `<div class="page-header animate-in"><div class="page-header-row">...título + botón acción...</div></div>`
- **Página de create/edit**: título con icono `bi-plus-lg` o `bi-pencil`

### Sidebar navigation paths
```
index.php                          → bi-grid-1x2   (dashboard)
clientes/index.php                 → bi-people      (clientes)
prestamos/index.php                → bi-cash-stack  (prestamos)
pagos/index.php                    → bi-credit-card (pagos)
historial_pagos/index.php          → bi-journal-text(historial)
acreedores/index.php               → bi-bank         (acreedores)
mis_deudas/index.php               → bi-wallet2      (mis_deudas)
reportes/index.php                 → bi-graph-up     (reportes)
configuracion/index.php            → bi-gear         (config)
```

## BD: Columnas clave

### clientes
id, nombre, apellido, cedula (UNIQUE), telefono, direccion, email, referencia_personal, fecha_registro, estado

### prestamos
id, cliente_id (FK), monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, fuente_fondos (propio|apalancado), acreedor_id (FK), abonos_capital

### pagos
id, prestamo_id (FK), monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, saldo_despues_pago, es_abono_capital (0|1)

### acreedores
id, nombre, telefono, estado

### mis_deudas
id, acreedor_id (FK), concepto, monto_recibido, tasa_mensual, capital_vigente, interes_mensual, estado

### pagos_deudas
id, deuda_id (FK), monto_pagado, capital_pagado, interes_pagado, fecha_pago

### configuracion
id, nombre_empresa, tasa_mora_diaria, dias_gracia, moneda

## Reglas de negocio
- **Mora**: `max(0, $diasAtraso - $diasGracia) * ($saldo * $tasaMoraDiaria / 100)`
- **Tasa interés**: siempre mensual en porcentaje
- **tipo_plazo**: 'dias' | 'semanas' | 'meses' | 'anios'
- **Cálculo de cuota**: anualidad estándar: `$monto * (tasa * pow(1+tasa, n)) / (pow(1+tasa, n) - 1)`
- **fuente_fondos**: 'propio' (capital propio) | 'apalancado' (de acreedor)

## JS helpers (assets/js/app.js)
- `formatMoney(amount, currency = 'MXN')` → "MXN 1,234.56"
- `calcularCuota(monto, tasaMensual, plazo, tipoPlazo)` → número
- `generarTablaAmortizacion(monto, tasaMensual, plazo, tipoPlazo)` → { tabla, cuotaPeriodo, numCuotas, totalIntereses, totalPagar }
- `TIPO_PLAZO` → objeto con factorTasa, multiplicadorPlazo, divisorFecha
- `initDarkMode()`, `initSidebar()`, `initBackToTop()`, `initFormLoading()`, `initScrollAnimations()`, `initTooltips()`
- `confirmarAccion(mensaje)` → confirm()
- `imprimirRecibo()` → window.print()

## CSS architecture
- Variables CSS en `:root`: `--accent: #1e40af`, `--success: #059669`, `--danger: #dc2626`, `--warning: #d97706`
- Layout: `.app-layout` (flex), `.sidebar` (68px → 220px hover), `.main-content`, `.topbar` (48px)
- Componentes: `.card` (transparente, sin borde/shadow), `.stat-row` (grid), `.stat-value` (1.5rem bold)
- Dark mode: clase `.dark-mode` en body, toggled por JS y persistido en localStorage
- Animaciones: `.animate-in` (fadeUp 0.35s)
- Responsive: breakpoint 991.98px (sidebar collapses)
- Print: oculta sidebar, topbar, footer, buttons
