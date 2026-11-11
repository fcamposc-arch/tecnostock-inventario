<?php
/**
 * Historial de Movimientos de Inventario (Auditoría)
 * Proyecto: TecnoStock
 * 
 * Cumplimiento de Reglas de Negocio:
 * - Registro histórico con fecha, tipo, cantidad, producto y usuario responsable.
 * - Filtros avanzados por producto, tipo y fechas.
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Movimientos de Inventario';

// Captura de filtros
$productoF = filter_input(INPUT_GET, 'producto', FILTER_VALIDATE_INT);
$tipoF     = trim($_GET['tipo'] ?? '');
$fechaF    = trim($_GET['fecha'] ?? '');

try {
    // 1. Obtener lista de productos para el filtro
    $stmtProds = $pdo->query("SELECT id_producto, codigo, nombre FROM productos ORDER BY nombre ASC");
    $listaProductos = $stmtProds->fetchAll();

    // 2. Consulta con filtros dinámicos preparados
    $sql = "
        SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.stock_anterior, m.stock_posterior, m.observacion,
               p.id_producto, p.codigo, p.nombre AS producto_nombre,
               u.nombre AS usuario_nombre, u.correo AS usuario_correo
        FROM movimientos m
        INNER JOIN productos p ON m.id_producto = p.id_producto
        INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
        WHERE 1=1
    ";
    $params = [];

    if (!empty($productoF)) {
        $sql .= " AND m.id_producto = ?";
        $params[] = $productoF;
    }

    if (in_array($tipoF, ['ENTRADA', 'SALIDA'])) {
        $sql .= " AND m.tipo = ?";
        $params[] = $tipoF;
    }

    if (!empty($fechaF)) {
        $sql .= " AND DATE(m.fecha_hora) = ?";
        $params[] = $fechaF;
    }

    $sql .= " ORDER BY m.fecha_hora DESC, m.id_movimiento DESC";

    $stmtMovs = $pdo->prepare($sql);
    $stmtMovs->execute($params);
    $movimientos = $stmtMovs->fetchAll();

} catch (PDOException $e) {
    die("Error al consultar movimientos: " . htmlspecialchars($e->getMessage()));
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Encabezado con Botón de Registro -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Movimientos de Inventario</h2>
        <p class="text-muted mb-0">Pista de auditoría de todas las entradas y salidas de existencias.</p>
    </div>
    <div>
        <a href="movimiento_registrar.php" class="btn btn-success d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-circle-fill"></i> Registrar Entrada o Salida
        </a>
    </div>
</div>

<!-- Filtros de Auditoría -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="movimientos.php" class="row g-2 align-items-center">
            
            <!-- Filtro por Producto -->
            <div class="col-12 col-md-4">
                <select name="producto" class="form-select">
                    <option value="">Todos los productos</option>
                    <?php foreach ($listaProductos as $lp): ?>
                        <option value="<?= $lp['id_producto']; ?>" <?= ($productoF == $lp['id_producto']) ? 'selected' : ''; ?>>
                            [<?= htmlspecialchars($lp['codigo']); ?>] <?= htmlspecialchars($lp['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro por Tipo -->
            <div class="col-12 col-sm-6 col-md-3">
                <select name="tipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="ENTRADA" <?= ($tipoF === 'ENTRADA') ? 'selected' : ''; ?>>Solo Entradas (+)</option>
                    <option value="SALIDA" <?= ($tipoF === 'SALIDA') ? 'selected' : ''; ?>>Solo Salidas (-)</option>
                </select>
            </div>

            <!-- Filtro por Fecha -->
            <div class="col-12 col-sm-6 col-md-3">
                <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($fechaF); ?>">
            </div>

            <!-- Botones de Acción -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($productoF) || !empty($tipoF) || !empty($fechaF)): ?>
                    <a href="movimientos.php" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<!-- Tabla de Movimientos -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($movimientos)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Fecha y Hora</th>
                            <th class="text-center">Tipo</th>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Variación de Stock</th>
                            <th>Observación / Motivo</th>
                            <th class="pe-3">Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td class="ps-3 text-muted">#<?= $m['id_movimiento']; ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= formatDateTime($m['fecha_hora']); ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if ($m['tipo'] === 'ENTRADA'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-box-arrow-in-down-left me-1"></i> ENTRADA
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> SALIDA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($m['codigo']); ?></code> - 
                                    <strong><?= htmlspecialchars($m['producto_nombre']); ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold fs-6 <?= $m['tipo'] === 'ENTRADA' ? 'text-success' : 'text-danger'; ?>">
                                        <?= $m['tipo'] === 'ENTRADA' ? '+' : '-'; ?><?= $m['cantidad']; ?>
                                    </span>
                                </td>
                                <td class="text-center small text-muted">
                                    <span class="badge bg-light text-dark border"><?= $m['stock_anterior']; ?></span>
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    <span class="badge bg-light text-dark border fw-bold"><?= $m['stock_posterior']; ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($m['observacion'])): ?>
                                        <span class="text-secondary small"><?= htmlspecialchars($m['observacion']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Sin observaciones</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3">
                                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($m['usuario_nombre']); ?></div>
                                    <div class="small text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($m['usuario_correo']); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary"></i>
                <h5>No hay movimientos registrados</h5>
                <p class="mb-3">No existen registros que coincidan con la búsqueda actual.</p>
                <a href="movimientos.php" class="btn btn-sm btn-outline-primary">Ver todos los movimientos</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
