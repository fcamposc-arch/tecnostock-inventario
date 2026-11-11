<?php
/**
 * Dashboard Principal
 * Proyecto: TecnoStock
 * 
 * Funcionalidades:
 * - Indicadores clave de rendimiento (KPIs): catálogo, stock activo, stock crítico, movimientos.
 * - Alerta visual destacada cuando el stock actual sea menor que el stock mínimo.
 * - Resumen de últimos movimientos y accesos directos de operación.
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Dashboard';

try {
    // 1. KPI: Total de productos
    $stmtTotal = $pdo->query("SELECT COUNT(*) AS total FROM productos");
    $totalProductos = (int)$stmtTotal->fetch()['total'];

    // 2. KPI: Productos activos
    $stmtActivos = $pdo->query("SELECT COUNT(*) AS activos FROM productos WHERE estado = 1");
    $totalActivos = (int)$stmtActivos->fetch()['activos'];

    // 3. KPI: Productos con stock bajo o crítico (stock_actual <= stock_minimo y activos)
    $stmtStockBajo = $pdo->query("SELECT COUNT(*) AS bajo FROM productos WHERE stock_actual <= stock_minimo AND estado = 1");
    $totalStockBajo = (int)$stmtStockBajo->fetch()['bajo'];

    // 4. KPI: Total de movimientos registrados
    $stmtMovimientos = $pdo->query("SELECT COUNT(*) AS total_mov FROM movimientos");
    $totalMovimientos = (int)$stmtMovimientos->fetch()['total_mov'];

    // 5. Lista de productos en estado crítico de stock
    $stmtCriticos = $pdo->query("
        SELECT p.id_producto, p.codigo, p.nombre, p.stock_actual, p.stock_minimo, c.nombre AS categoria
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.stock_actual <= p.stock_minimo AND p.estado = 1
        ORDER BY (p.stock_actual - p.stock_minimo) ASC
        LIMIT 10
    ");
    $productosCriticos = $stmtCriticos->fetchAll();

    // 6. Últimos 5 movimientos registrados
    $stmtUltimosMov = $pdo->query("
        SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.observacion,
               p.codigo, p.nombre AS producto_nombre,
               u.nombre AS usuario_nombre
        FROM movimientos m
        INNER JOIN productos p ON m.id_producto = p.id_producto
        INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
        ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
        LIMIT 5
    ");
    $ultimosMovimientos = $stmtUltimosMov->fetchAll();

} catch (PDOException $e) {
    die("Error al consultar métricas del dashboard: " . htmlspecialchars($e->getMessage()));
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Encabezado de la página -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Panel de Control de Inventario</h2>
        <p class="text-muted mb-0">Resumen operativo de existencias y catálogo de <strong>TecnoStock</strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="producto_crear.php" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle"></i> Nuevo Producto
        </a>
        <a href="movimiento_registrar.php" class="btn btn-outline-dark d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-arrow-left-right"></i> Registrar Movimiento
        </a>
    </div>
</div>

<!-- ============================================================== -->
<!-- 1. ALERTA VISUAL DE STOCK BAJO (Requerimiento Funcional 8)     -->
<!-- ============================================================== -->
<?php if ($totalStockBajo > 0): ?>
    <div class="alert alert-danger alert-banner-stock shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 p-3 rounded-3" role="alert">
        <div class="d-flex align-items-center gap-3">
            <div class="fs-1 text-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <h5 class="alert-heading fw-bold mb-1">¡Alerta de Stock Crítico!</h5>
                <p class="mb-0">
                    Se han detectado <strong><?= $totalStockBajo; ?></strong> producto(s) cuyo stock actual es menor o igual al stock mínimo configurado.
                    Requieren reposición inmediata para evitar quiebres de inventario.
                </p>
            </div>
        </div>
        <div>
            <a href="#seccionCriticos" class="btn btn-danger btn-sm px-3 fw-semibold">
                Ver Productos Afectados <i class="bi bi-arrow-down-short"></i>
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success d-flex align-items-center gap-2 shadow-sm mb-4 py-2" role="alert">
        <i class="bi bi-check-circle-fill fs-5 text-success"></i>
        <div>Todos los productos activos cuentan con stock superior al mínimo requerido.</div>
    </div>
<?php endif; ?>

<!-- ============================================================== -->
<!-- 2. TARJETAS DE INDICADORES (KPIs)                              -->
<!-- ============================================================== -->
<div class="row g-3 mb-4">
    <!-- Total Productos -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-kpi border-0 shadow-sm bg-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Catálogo Total</span>
                    <h3 class="fw-bold mb-0 mt-1"><?= $totalProductos; ?></h3>
                    <small class="text-muted">Productos registrados</small>
                </div>
                <div class="kpi-icon bg-primary-subtle text-primary">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos Activos -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-kpi border-0 shadow-sm bg-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Disponibles</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= $totalActivos; ?></h3>
                    <small class="text-muted">Activos en venta</small>
                </div>
                <div class="kpi-icon bg-success-subtle text-success">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Bajo / Crítico -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-kpi border-0 shadow-sm bg-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Alerta Stock</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= $totalStockBajo; ?></h3>
                    <small class="text-muted">&le; Stock mínimo definido</small>
                </div>
                <div class="kpi-icon bg-danger-subtle text-danger">
                    <i class="bi bi-bell-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total de Movimientos -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-kpi border-0 shadow-sm bg-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Movimientos</span>
                    <h3 class="fw-bold text-info mb-0 mt-1"><?= $totalMovimientos; ?></h3>
                    <small class="text-muted">Entradas y salidas</small>
                </div>
                <div class="kpi-icon bg-info-subtle text-info">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ============================================================== -->
    <!-- 3. DETALLE DE PRODUCTOS CON STOCK CRÍTICO                      -->
    <!-- ============================================================== -->
    <div class="col-12 col-lg-7" id="seccionCriticos">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0 text-danger d-flex align-items-center gap-2">
                    <i class="bi bi-shield-exclamation"></i> Productos con Bajo Stock
                </h5>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= count($productosCriticos); ?> producto(s)</span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($productosCriticos)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="text-center">Stock Actual</th>
                                    <th class="text-center">Stock Mínimo</th>
                                    <th class="text-end">Acción Rápida</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productosCriticos as $prod): ?>
                                    <tr class="row-stock-critico">
                                        <td class="fw-semibold"><code><?= htmlspecialchars($prod['codigo']); ?></code></td>
                                        <td>
                                            <strong><?= htmlspecialchars($prod['nombre']); ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($prod['categoria']); ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger fs-6"><?= $prod['stock_actual']; ?></span>
                                        </td>
                                        <td class="text-center text-muted fw-semibold">
                                            <?= $prod['stock_minimo']; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="movimiento_registrar.php?producto_id=<?= $prod['id_producto']; ?>&tipo=ENTRADA" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-plus-lg"></i> Reponer
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                        <p class="mb-0">No hay productos en estado de alerta actualmente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 4. ÚLTIMOS MOVIMIENTOS REGISTRADOS                             -->
    <!-- ============================================================== -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i> Movimientos Recientes
                </h5>
                <a href="movimientos.php" class="btn btn-sm btn-link text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($ultimosMovimientos)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($ultimosMovimientos as $mov): ?>
                            <li class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <?php if ($mov['tipo'] === 'ENTRADA'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1">
                                                <i class="bi bi-arrow-down-left"></i> ENTRADA
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-1">
                                                <i class="bi bi-arrow-up-right"></i> SALIDA
                                            </span>
                                        <?php endif; ?>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($mov['producto_nombre']); ?></span>
                                    </div>
                                    <span class="fw-bold fs-6 <?= $mov['tipo'] === 'ENTRADA' ? 'text-success' : 'text-danger'; ?>">
                                        <?= $mov['tipo'] === 'ENTRADA' ? '+' : '-'; ?><?= $mov['cantidad']; ?>
                                    </span>
                                </div>
                                <div class="small text-muted d-flex justify-content-between">
                                    <span><i class="bi bi-person"></i> <?= htmlspecialchars($mov['usuario_nombre']); ?></span>
                                    <span><i class="bi bi-calendar3"></i> <?= formatDateTime($mov['fecha_hora']); ?></span>
                                </div>
                                <?php if (!empty($mov['observacion'])): ?>
                                    <div class="small text-secondary mt-1 fst-italic">
                                        "<?= htmlspecialchars($mov['observacion']); ?>"
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        <p class="mb-0">No se han registrado movimientos todavía.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
