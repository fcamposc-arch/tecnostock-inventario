<?php
/**
 * Modificación de Producto
 * Proyecto: TecnoStock
 * 
 * Requerimiento:
 * "Modificar el precio, la descripción, la categoría y las cantidades de un producto."
 * 
 * Reglas de negocio:
 * 1. Precio, stock y stock mínimo no pueden ser negativos.
 * 2. Si se altera el stock actual directamente, se audita el movimiento correspondiente.
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Modificar Producto';
$usuarioActual = getCurrentUser();

$id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_producto) {
    setFlash('danger', 'ID de producto no válido.');
    redirect('productos.php');
}

// Cargar información del producto
try {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
    $stmt->execute([$id_producto]);
    $producto = $stmt->fetch();

    if (!$producto) {
        setFlash('danger', 'El producto solicitado no existe.');
        redirect('productos.php');
    }

    // Cargar categorías
    $stmtCat = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $stmtCat->fetchAll();

} catch (PDOException $e) {
    die("Error al consultar datos del producto: " . htmlspecialchars($e->getMessage()));
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = trim($_POST['precio'] ?? '0');
    $stock_actual = trim($_POST['stock_actual'] ?? '0');
    $stock_minimo = trim($_POST['stock_minimo'] ?? '0');
    $id_categoria = trim($_POST['id_categoria'] ?? '');

    // Validaciones del servidor (PHP)
    if (empty($codigo)) {
        $errores[] = 'El código del producto es obligatorio.';
    }

    if (empty($nombre)) {
        $errores[] = 'El nombre del producto es obligatorio.';
    }

    if (empty($id_categoria) || !is_numeric($id_categoria)) {
        $errores[] = 'Debes seleccionar una categoría válida.';
    }

    if (!is_numeric($precio) || (float)$precio < 0) {
        $errores[] = 'El precio no puede ser negativo.';
    }

    if (!is_numeric($stock_actual) || (int)$stock_actual < 0) {
        $errores[] = 'El stock actual no puede ser negativo.';
    }

    if (!is_numeric($stock_minimo) || (int)$stock_minimo < 0) {
        $errores[] = 'El stock mínimo no puede ser negativo.';
    }

    // Verificar unicidad del código (excluyendo el producto actual)
    if (empty($errores)) {
        try {
            $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = ? AND id_producto != ? LIMIT 1");
            $stmtCheck->execute([$codigo, $id_producto]);
            if ($stmtCheck->fetch()) {
                $errores[] = "El código '{$codigo}' ya está asignado a otro producto.";
            }
        } catch (PDOException $e) {
            $errores[] = "Error al validar código: " . $e->getMessage();
        }
    }

    // Actualización con transacción PDO
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $stockAnterior = (int)$producto['stock_actual'];
            $stockNuevo    = (int)$stock_actual;
            $diferencia    = $stockNuevo - $stockAnterior;

            // Actualizar producto
            $sqlUpdate = "
                UPDATE productos 
                SET codigo = ?, nombre = ?, descripcion = ?, precio = ?, 
                    stock_actual = ?, stock_minimo = ?, id_categoria = ?
                WHERE id_producto = ?
            ";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                $codigo,
                $nombre,
                $descripcion,
                (float)$precio,
                $stockNuevo,
                (int)$stock_minimo,
                (int)$id_categoria,
                $id_producto
            ]);

            // Si hubo cambio manual en el stock actual, registramos el movimiento de auditoría
            if ($diferencia !== 0) {
                $tipoMov = ($diferencia > 0) ? 'ENTRADA' : 'SALIDA';
                $cantMov = abs($diferencia);
                $obsMov  = "Ajuste manual de inventario en edición de producto ({$stockAnterior} -> {$stockNuevo})";

                $sqlMov = "
                    INSERT INTO movimientos (fecha_hora, tipo, cantidad, stock_anterior, stock_posterior, observacion, id_producto, id_usuario)
                    VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmtMov = $pdo->prepare($sqlMov);
                $stmtMov->execute([
                    $tipoMov,
                    $cantMov,
                    $stockAnterior,
                    $stockNuevo,
                    $obsMov,
                    $id_producto,
                    $usuarioActual['id']
                ]);
            }

            $pdo->commit();

            setFlash('success', "Producto '{$nombre}' actualizado correctamente.");
            redirect('productos.php');

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = "Error al actualizar el producto: " . $e->getMessage();
        }
    }
} else {
    // Cargar datos actuales para el formulario
    $codigo       = $producto['codigo'];
    $nombre       = $producto['nombre'];
    $descripcion  = $producto['descripcion'];
    $precio       = $producto['precio'];
    $stock_actual = $producto['stock_actual'];
    $stock_minimo = $producto['stock_minimo'];
    $id_categoria = $producto['id_categoria'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <!-- Encabezado -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1">Modificar Producto</h2>
                <p class="text-muted mb-0">Actualiza precio, descripción, categoría y existencias del producto.</p>
            </div>
            <a href="productos.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver al Catálogo
            </a>
        </div>

        <!-- Errores -->
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger shadow-sm py-3 mb-4" role="alert">
                <h6 class="alert-heading fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-1"></i> Hubo errores en los datos enviados:</h6>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errores as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulario de Modificación -->
        <div class="card form-card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="producto_editar.php?id=<?= $id_producto; ?>" id="formProducto" novalidate>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label for="codigo" class="form-label">Código del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="codigo" name="codigo" 
                                   value="<?= htmlspecialchars($codigo); ?>" required maxlength="50">
                            <div class="invalid-feedback">El código es obligatorio.</div>
                        </div>

                        <div class="col-12 col-md-8">
                            <label for="id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_categoria" name="id_categoria" required>
                                <option value="">-- Seleccionar Categoría --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categoria']; ?>" <?= ($id_categoria == $cat['id_categoria']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Selecciona una categoría.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($nombre); ?>" required maxlength="150">
                        <div class="invalid-feedback">El nombre es obligatorio.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($descripcion); ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="precio" class="form-label">Precio Unitario ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="precio" name="precio" 
                                       value="<?= htmlspecialchars($precio); ?>" min="0" step="10" required>
                            </div>
                            <div class="invalid-feedback">El precio no puede ser negativo.</div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="stock_actual" class="form-label">Stock Actual <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stock_actual" name="stock_actual" 
                                   value="<?= htmlspecialchars($stock_actual); ?>" min="0" step="1" required>
                            <div class="form-text text-muted">Ajusta el stock y audita la variación.</div>
                            <div class="invalid-feedback">El stock actual no puede ser negativo.</div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="stock_minimo" class="form-label">Stock Mínimo (Alerta) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" 
                                   value="<?= htmlspecialchars($stock_minimo); ?>" min="0" step="1" required>
                            <div class="form-text text-muted">Umbral de alerta.</div>
                            <div class="invalid-feedback">El stock mínimo no puede ser negativo.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="productos.php" class="btn btn-light border px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
