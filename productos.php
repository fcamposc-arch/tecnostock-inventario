<?php
/**
 * Catálogo de Productos y Búsqueda
 * Proyecto: TecnoStock
 * 
 * Funcionalidades:
 * - Consultar listado completo de productos.
 * - Búsqueda multicriterio: por nombre, código y categoría.
 * - Filtro por estado (Activos / Desactivados).
 * - Indicadores visuales de bajo stock.
 * - Acceso a acciones de edición, desactivación y movimientos.
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Catálogo de Productos';

// Capturar parámetros de búsqueda y filtrado
$buscar     = trim($_GET['buscar'] ?? '');
$categoriaF = trim($_GET['categoria'] ?? '');
$estadoF    = trim($_GET['estado'] ?? 'activos'); // por defecto muestra los activos

try {
    // 1. Obtener todas las categorías para el select de filtro
    $stmtCats = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $stmtCats->fetchAll();

    // 2. Construir consulta dinámica segura con PDO y parámetros vinculados
    $sql = "
        SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio, 
               p.stock_actual, p.stock_minimo, p.estado,
               c.nombre AS categoria_nombre,
               (SELECT COUNT(*) FROM movimientos m WHERE m.id_producto = p.id_producto) AS total_movimientos
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1
    ";
    $params = [];

    // Filtro por término de búsqueda (nombre o código)
    if (!empty($buscar)) {
        $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)";
        $params[] = "%{$buscar}%";
        $params[] = "%{$buscar}%";
    }

    // Filtro por categoría
    if (!empty($categoriaF)) {
        $sql .= " AND p.id_categoria = ?";
        $params[] = $categoriaF;
    }

    // Filtro por estado
    if ($estadoF === 'activos') {
        $sql .= " AND p.estado = 1";
    } elseif ($estadoF === 'desactivados') {
        $sql .= " AND p.estado = 0";
    }

    $sql .= " ORDER BY p.estado DESC, p.nombre ASC";

    $stmtProds = $pdo->prepare($sql);
    $stmtProds->execute($params);
    $productos = $stmtProds->fetchAll();

} catch (PDOException $e) {
    die("Error al consultar el catálogo de productos: " . htmlspecialchars($e->getMessage()));
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Encabezado con Botón de Creación -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Catálogo de Productos</h2>
        <p class="text-muted mb-0">Gestión integral de accesorios, control de precios y existencias.</p>
    </div>
    <div>
        <a href="producto_crear.php" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-circle-fill"></i> Registrar Nuevo Producto
        </a>
    </div>
</div>

<!-- Barra de Búsqueda y Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="productos.php" class="row g-2 align-items-center">
            
            <!-- Búsqueda por texto (nombre o código) -->
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="buscar" class="form-control border-start-0" placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($buscar); ?>">
                </div>
            </div>

            <!-- Filtro por Categoría -->
            <div class="col-12 col-sm-6 col-md-3">
                <select name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria']; ?>" <?= ($categoriaF == $cat['id_categoria']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro por Estado -->
            <div class="col-12 col-sm-6 col-md-2">
                <select name="estado" class="form-select">
                    <option value="activos" <?= ($estadoF === 'activos') ? 'selected' : ''; ?>>Solo Activos</option>
                    <option value="desactivados" <?= ($estadoF === 'desactivados') ? 'selected' : ''; ?>>Solo Desactivados</option>
                    <option value="todos" <?= ($estadoF === 'todos') ? 'selected' : ''; ?>>Todos los estados</option>
                </select>
            </div>

            <!-- Botones -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($buscar) || !empty($categoriaF) || $estadoF !== 'activos'): ?>
                    <a href="productos.php" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<!-- Tabla de Productos -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($productos)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Producto y Descripción</th>
                            <th>Categoría</th>
                            <th class="text-end">Precio</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Stock Mínimo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): ?>
                            <?php 
                                $esStockBajo = ($p['stock_actual'] <= $p['stock_minimo']) && ((int)$p['estado'] === 1);
                            ?>
                            <tr class="<?= $esStockBajo ? 'row-stock-critico' : ''; ?> <?= ((int)$p['estado'] === 0) ? 'table-secondary opacity-75' : ''; ?>">
                                
                                <!-- Código Único -->
                                <td class="ps-3">
                                    <span class="badge bg-dark-subtle text-dark border font-monospace">
                                        <?= htmlspecialchars($p['codigo']); ?>
                                    </span>
                                </td>

                                <!-- Nombre y Descripción -->
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($p['nombre']); ?></strong>
                                    <?php if (!empty($p['descripcion'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 320px;">
                                            <?= htmlspecialchars($p['descripcion']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Categoría -->
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-tag me-1"></i><?= htmlspecialchars($p['categoria_nombre']); ?>
                                    </span>
                                </td>

                                <!-- Precio -->
                                <td class="text-end fw-semibold">
                                    <?= formatPrice($p['precio']); ?>
                                </td>

                                <!-- Stock Actual con Alerta Visual -->
                                <td class="text-center">
                                    <?php if ($esStockBajo): ?>
                                        <span class="badge badge-stock-critico px-2 py-1" title="¡Stock actual menor o igual al mínimo!">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $p['stock_actual']; ?> (Bajo)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-stock-normal px-2 py-1">
                                            <?= $p['stock_actual']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Stock Mínimo -->
                                <td class="text-center text-muted fw-semibold">
                                    <?= $p['stock_minimo']; ?>
                                </td>

                                <!-- Estado -->
                                <td class="text-center">
                                    <?php if ((int)$p['estado'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Desactivado</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones -->
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        
                                        <!-- Modificar Producto -->
                                        <a href="producto_editar.php?id=<?= $p['id_producto']; ?>" class="btn btn-outline-primary" title="Editar producto">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- Registrar Movimiento de Existencias -->
                                        <?php if ((int)$p['estado'] === 1): ?>
                                            <a href="movimiento_registrar.php?producto_id=<?= $p['id_producto']; ?>" class="btn btn-outline-success" title="Registrar movimiento (Entrada / Salida)">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Desactivar / Reactivar (Regla de negocio: No se elimina físicamente si tiene movimientos) -->
                                        <?php if ((int)$p['estado'] === 1): ?>
                                            <button type="button" class="btn btn-outline-danger" title="Desactivar producto" 
                                                    onclick="confirmarDesactivacion('<?= addslashes(htmlspecialchars($p['nombre'])); ?>', 'producto_desactivar.php?id=<?= $p['id_producto']; ?>&accion=desactivar');">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="producto_desactivar.php?id=<?= $p['id_producto']; ?>&accion=activar" class="btn btn-outline-secondary" title="Reactivar producto">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                <h5>No se encontraron productos</h5>
                <p class="mb-3">No hay productos que coincidan con los criterios de búsqueda seleccionados.</p>
                <a href="productos.php" class="btn btn-sm btn-outline-primary">Ver todos los productos</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
