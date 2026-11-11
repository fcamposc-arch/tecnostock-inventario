<?php
/**
 * Registro de Nuevo Producto
 * Proyecto: TecnoStock
 * 
 * Reglas de negocio aplicadas:
 * 1. Código de producto no puede repetirse.
 * 2. Precio, stock actual y stock mínimo no pueden ser negativos.
 * 3. Si se inicia con stock > 0, se registra el movimiento de entrada inicial correspondiente.
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Registrar Nuevo Producto';
$usuarioActual = getCurrentUser();

$errores = [];
$codigo       = '';
$nombre       = '';
$descripcion  = '';
$precio       = '0';
$stock_actual = '0';
$stock_minimo = '5';
$id_categoria = '';

// Obtener categorías activas para el select
try {
    $stmtCat = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $stmtCat->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar categorías: " . htmlspecialchars($e->getMessage()));
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = trim($_POST['precio'] ?? '0');
    $stock_actual = trim($_POST['stock_actual'] ?? '0');
    $stock_minimo = trim($_POST['stock_minimo'] ?? '0');
    $id_categoria = trim($_POST['id_categoria'] ?? '');

    // ==========================================
    // VALIDACIONES DEL SERVIDOR (PHP)
    // ==========================================
    if (empty($codigo)) {
        $errores[] = 'El código del producto es obligatorio.';
    } elseif (strlen($codigo) > 50) {
        $errores[] = 'El código del producto no puede exceder los 50 caracteres.';
    }

    if (empty($nombre)) {
        $errores[] = 'El nombre del producto es obligatorio.';
    }

    if (empty($id_categoria) || !is_numeric($id_categoria)) {
        $errores[] = 'Debes seleccionar una categoría válida.';
    }

    if (!is_numeric($precio) || (float)$precio < 0) {
        $errores[] = 'El precio debe ser un número válido mayor o igual a cero (no negativo).';
    }

    if (!is_numeric($stock_actual) || (int)$stock_actual < 0) {
        $errores[] = 'El stock actual no puede ser negativo.';
    }

    if (!is_numeric($stock_minimo) || (int)$stock_minimo < 0) {
        $errores[] = 'El stock mínimo no puede ser negativo.';
    }

    // Regla de Negocio 1: El código de producto no puede repetirse
    if (empty($errores)) {
        try {
            $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = ? LIMIT 1");
            $stmtCheck->execute([$codigo]);
            if ($stmtCheck->fetch()) {
                $errores[] = "El código '{$codigo}' ya se encuentra registrado en el sistema. Debe ser único.";
            }
        } catch (PDOException $e) {
            $errores[] = "Error al verificar código único: " . $e->getMessage();
        }
    }

    // Inserción en Base de Datos utilizando Transacción PDO
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $sqlInsert = "
                INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                $codigo,
                $nombre,
                $descripcion,
                (float)$precio,
                (int)$stock_actual,
                (int)$stock_minimo,
                (int)$id_categoria
            ]);

            $idNuevoProducto = (int)$pdo->lastInsertId();

            // Si el producto se registra con stock inicial > 0, auditamos el movimiento inicial
            if ((int)$stock_actual > 0) {
                $sqlMov = "
                    INSERT INTO movimientos (fecha_hora, tipo, cantidad, stock_anterior, stock_posterior, observacion, id_producto, id_usuario)
                    VALUES (NOW(), 'ENTRADA', ?, 0, ?, 'Inventario inicial registrado al crear el producto', ?, ?)
                ";
                $stmtMov = $pdo->prepare($sqlMov);
                $stmtMov->execute([
                    (int)$stock_actual,
                    (int)$stock_actual,
                    $idNuevoProducto,
                    $usuarioActual['id']
                ]);
            }

            $pdo->commit();

            setFlash('success', "¡Producto '{$nombre}' (Código: {$codigo}) registrado exitosamente!");
            redirect('productos.php');

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = "Error al guardar el producto en la base de datos: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <!-- Encabezado con navegación de retorno -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1">Registrar Nuevo Producto</h2>
                <p class="text-muted mb-0">Ingresa la información técnica, categoría y existencias iniciales.</p>
            </div>
            <a href="productos.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver al Catálogo
            </a>
        </div>

        <!-- Alerta de Errores del Servidor -->
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger shadow-sm py-3 mb-4" role="alert">
                <h6 class="alert-heading fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-1"></i> Por favor corrige los siguientes inconvenientes:</h6>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errores as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card form-card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="producto_crear.php" id="formProducto" novalidate>
                    
                    <div class="row g-3 mb-3">
                        <!-- Código Único -->
                        <div class="col-12 col-md-4">
                            <label for="codigo" class="form-label">Código del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="codigo" name="codigo" 
                                   value="<?= htmlspecialchars($codigo); ?>" placeholder="Ej: CAB-007" required maxlength="50">
                            <div class="form-text">Debe ser único en el sistema.</div>
                            <div class="invalid-feedback">El código es obligatorio.</div>
                        </div>

                        <!-- Categoría -->
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

                    <!-- Nombre del Producto -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($nombre); ?>" placeholder="Ej: Cable HDMI 2.1 Ultra High Speed 2m" required maxlength="150">
                        <div class="invalid-feedback">El nombre es obligatorio.</div>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Detalles sobre especificaciones, compatibilidad o color..."><?= htmlspecialchars($descripcion); ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Precio Unitario -->
                        <div class="col-12 col-md-4">
                            <label for="precio" class="form-label">Precio Unitario ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="precio" name="precio" 
                                       value="<?= htmlspecialchars($precio); ?>" min="0" step="10" required>
                            </div>
                            <div class="invalid-feedback">El precio no puede ser negativo.</div>
                        </div>

                        <!-- Stock Inicial -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="stock_actual" class="form-label">Stock Inicial <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stock_actual" name="stock_actual" 
                                   value="<?= htmlspecialchars($stock_actual); ?>" min="0" step="1" required>
                            <div class="form-text">Cantidad inicial disponible.</div>
                            <div class="invalid-feedback">El stock actual no puede ser negativo.</div>
                        </div>

                        <!-- Stock Mínimo -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="stock_minimo" class="form-label">Stock Mínimo (Alerta) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" 
                                   value="<?= htmlspecialchars($stock_minimo); ?>" min="0" step="1" required>
                            <div class="form-text">Umbral para disparar alerta.</div>
                            <div class="invalid-feedback">El stock mínimo no puede ser negativo.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="productos.php" class="btn btn-light border px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Guardar Producto
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
