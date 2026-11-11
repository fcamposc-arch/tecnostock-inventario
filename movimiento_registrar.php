<?php
/**
 * Registro de Movimientos de Inventario (Entradas y Salidas)
 * Proyecto: TecnoStock
 * 
 * Reglas de Negocio Implementadas:
 * 1. Todo movimiento debe registrar fecha, tipo, cantidad, producto y usuario responsable.
 * 2. La cantidad del movimiento debe ser estrictamente positiva (> 0).
 * 3. Una salida no puede dejar el stock bajo cero (Transacción con bloqueo preventivo).
 * 4. El stock debe actualizarse automáticamente después de cada movimiento válido.
 * 5. Muestra una advertencia inmediata si el nuevo stock queda en nivel crítico (<= stock_minimo).
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Registrar Movimiento de Inventario';
$usuarioActual = getCurrentUser();

// Parámetros iniciales por URL si viene de accesos directos
$preProductoId = filter_input(INPUT_GET, 'producto_id', FILTER_VALIDATE_INT) ?? '';
$preTipo       = trim($_GET['tipo'] ?? 'ENTRADA');

$errores = [];
$id_producto = $preProductoId;
$tipo        = in_array($preTipo, ['ENTRADA', 'SALIDA']) ? $preTipo : 'ENTRADA';
$cantidad    = '';
$observacion = '';

// Obtener catálogo de productos activos
try {
    $stmtProds = $pdo->query("
        SELECT id_producto, codigo, nombre, stock_actual, stock_minimo 
        FROM productos 
        WHERE estado = 1 
        ORDER BY nombre ASC
    ");
    $productos = $stmtProds->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar productos: " . htmlspecialchars($e->getMessage()));
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
    $tipo        = trim($_POST['tipo'] ?? '');
    $cantidad    = trim($_POST['cantidad'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    // ==========================================
    // VALIDACIONES DEL SERVIDOR (PHP)
    // ==========================================
    if (!$id_producto) {
        $errores[] = 'Debes seleccionar un producto válido.';
    }

    if (!in_array($tipo, ['ENTRADA', 'SALIDA'])) {
        $errores[] = 'El tipo de movimiento debe ser ENTRADA o SALIDA.';
    }

    if (!is_numeric($cantidad) || (int)$cantidad <= 0) {
        $errores[] = 'La cantidad del movimiento debe ser un número entero mayor a 0.';
    } else {
        $cantidad = (int)$cantidad;
    }

    // Procesamiento con Transacción PDO Atómica
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // 1. Obtener producto y bloquear la fila para asegurar consistencia concurrente
            $stmtProd = $pdo->prepare("SELECT id_producto, codigo, nombre, stock_actual, stock_minimo, estado FROM productos WHERE id_producto = ? FOR UPDATE");
            $stmtProd->execute([$id_producto]);
            $prod = $stmtProd->fetch();

            if (!$prod || (int)$prod['estado'] !== 1) {
                throw new Exception("El producto seleccionado no está disponible o ha sido desactivado.");
            }

            $stockAnterior = (int)$prod['stock_actual'];
            $stockMinimo   = (int)$prod['stock_minimo'];

            // Regla de Negocio 4: Una salida no puede dejar el stock bajo cero
            if ($tipo === 'SALIDA') {
                if ($cantidad > $stockAnterior) {
                    throw new Exception("Regla de negocio: Una salida no puede dejar el stock bajo cero. Stock disponible: {$stockAnterior} unidades, cantidad solicitada: {$cantidad}.");
                }
                $stockPosterior = $stockAnterior - $cantidad;
            } else {
                // ENTRADA
                $stockPosterior = $stockAnterior + $cantidad;
            }

            // 2. Insertar en tabla movimientos (Regla de negocio: fecha, tipo, cantidad, producto y usuario responsable)
            $sqlMov = "
                INSERT INTO movimientos (fecha_hora, tipo, cantidad, stock_anterior, stock_posterior, observacion, id_producto, id_usuario)
                VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmtMov = $pdo->prepare($sqlMov);
            $stmtMov->execute([
                $tipo,
                $cantidad,
                $stockAnterior,
                $stockPosterior,
                $observacion,
                $id_producto,
                $usuarioActual['id']
            ]);

            // 3. Regla de Negocio 6: El stock debe actualizarse automáticamente
            $sqlUpdateStock = "UPDATE productos SET stock_actual = ? WHERE id_producto = ?";
            $stmtUpdateStock = $pdo->prepare($sqlUpdateStock);
            $stmtUpdateStock->execute([$stockPosterior, $id_producto]);

            // Confirmar transacción en la base de datos
            $pdo->commit();

            // Mensajes y Alertas
            if ($stockPosterior <= $stockMinimo) {
                setFlash('warning', "Movimiento registrado con éxito. ⚠️ ¡Atención! El producto '{$prod['nombre']}' ha quedado en nivel crítico: {$stockPosterior} unidades restantes (Mínimo: {$stockMinimo}).");
            } else {
                setFlash('success', "Movimiento de {$tipo} por {$cantidad} unidad(es) de '{$prod['nombre']}' registrado exitosamente. Stock actualizado de {$stockAnterior} a {$stockPosterior}.");
            }

            redirect('movimientos.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <!-- Encabezado -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1">Registrar Movimiento de Existencias</h2>
                <p class="text-muted mb-0">Control de entradas (abastecimiento) y salidas (ventas o despachos).</p>
            </div>
            <a href="movimientos.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a Movimientos
            </a>
        </div>

        <!-- Errores del Servidor -->
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger shadow-sm py-3 mb-4" role="alert">
                <h6 class="alert-heading fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-1"></i> No se pudo procesar el movimiento:</h6>
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
                <form method="POST" action="movimiento_registrar.php" id="formMovimiento" novalidate>
                    
                    <!-- Selección de Producto -->
                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" id="id_producto" name="id_producto" required>
                            <option value="">-- Selecciona el producto a mover --</option>
                            <?php foreach ($productos as $prod): ?>
                                <option value="<?= $prod['id_producto']; ?>" 
                                        data-stock="<?= $prod['stock_actual']; ?>"
                                        data-minimo="<?= $prod['stock_minimo']; ?>"
                                        <?= ($id_producto == $prod['id_producto']) ? 'selected' : ''; ?>>
                                    [<?= htmlspecialchars($prod['codigo']); ?>] <?= htmlspecialchars($prod['nombre']); ?> (Stock actual: <?= $prod['stock_actual']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2">
                            <span id="stockActualInfo" class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 d-none">
                                Stock actual disponible: 0 unidades
                            </span>
                        </div>
                        <div class="invalid-feedback">Debes seleccionar un producto.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Tipo de Movimiento -->
                        <div class="col-12 col-md-6">
                            <label for="tipo" class="form-label">Tipo de Movimiento <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="tipo" name="tipo" required>
                                <option value="ENTRADA" <?= ($tipo === 'ENTRADA') ? 'selected' : ''; ?>>
                                    📥 ENTRADA (Recepción / Proveedor)
                                </option>
                                <option value="SALIDA" <?= ($tipo === 'SALIDA') ? 'selected' : ''; ?>>
                                    📤 SALIDA (Venta / Merma / Traslado)
                                </option>
                            </select>
                            <div class="form-text">Entrada incrementa el stock; Salida lo decrementa.</div>
                        </div>

                        <!-- Cantidad -->
                        <div class="col-12 col-md-6">
                            <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-lg" id="cantidad" name="cantidad" 
                                   value="<?= htmlspecialchars($cantidad); ?>" min="1" step="1" placeholder="Ej: 5" required>
                            <div class="form-text">Debe ser un número entero mayor a cero.</div>
                            <div class="invalid-feedback">La cantidad no es válida o excede el stock disponible.</div>
                        </div>
                    </div>

                    <!-- Observación o Motivo -->
                    <div class="mb-4">
                        <label for="observacion" class="form-label">Motivo u Observación</label>
                        <input type="text" class="form-control" id="observacion" name="observacion" 
                               value="<?= htmlspecialchars($observacion); ?>" 
                               placeholder="Ej: Factura de compra #8901, Venta local, Ajuste por inventario físico..." maxlength="255">
                        <div class="form-text">Breve justificación para la trazabilidad y auditoría.</div>
                    </div>

                    <!-- Cuadro informativo del usuario responsable -->
                    <div class="p-3 bg-light rounded-3 border mb-4 small text-muted d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-primary fs-5"></i>
                        <div>
                            <strong>Usuario Responsable:</strong> <?= htmlspecialchars($usuarioActual['nombre']); ?> 
                            (<code><?= htmlspecialchars($usuarioActual['correo']); ?></code>). Quedará registrado en la base de datos según la Regla de Negocio 3.
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="movimientos.php" class="btn btn-light border px-4">Cancelar</a>
                        <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Confirmar y Registrar Movimiento
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script>
    // Inicializar la visualización de stock al cargar la página si ya hay un producto seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        const selectProd = document.getElementById('id_producto');
        if (selectProd && selectProd.value) {
            selectProd.dispatchEvent(new Event('change'));
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
