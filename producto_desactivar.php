<?php
/**
 * Desactivación Lógica de Producto (Soft Delete)
 * Proyecto: TecnoStock
 * 
 * Cumplimiento estricto de la Regla de Negocio 5:
 * "Un producto con movimientos registrados debe desactivarse; no se elimina físicamente."
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$accion      = trim($_GET['accion'] ?? 'desactivar');

if (!$id_producto) {
    setFlash('danger', 'ID de producto no válido.');
    redirect('productos.php');
}

try {
    // 1. Obtener datos del producto
    $stmt = $pdo->prepare("SELECT id_producto, nombre, codigo, estado FROM productos WHERE id_producto = ?");
    $stmt->execute([$id_producto]);
    $producto = $stmt->fetch();

    if (!$producto) {
        setFlash('danger', 'El producto no fue encontrado.');
        redirect('productos.php');
    }

    // 2. Verificar cuántos movimientos históricos tiene registrados
    $stmtMov = $pdo->prepare("SELECT COUNT(*) AS total FROM movimientos WHERE id_producto = ?");
    $stmtMov->execute([$id_producto]);
    $totalMovimientos = (int)$stmtMov->fetch()['total'];

    if ($accion === 'activar') {
        // Reactivación
        $stmtUpdate = $pdo->prepare("UPDATE productos SET estado = 1 WHERE id_producto = ?");
        $stmtUpdate->execute([$id_producto]);

        setFlash('success', "El producto '{$producto['nombre']}' ({$producto['codigo']}) ha sido reactivado en el catálogo.");
    } else {
        // Desactivación lógica (Soft delete)
        $stmtUpdate = $pdo->prepare("UPDATE productos SET estado = 0 WHERE id_producto = ?");
        $stmtUpdate->execute([$id_producto]);

        $mensaje = "El producto '{$producto['nombre']}' ({$producto['codigo']}) fue desactivado del catálogo comercial.";
        if ($totalMovimientos > 0) {
            $mensaje .= " Posee {$totalMovimientos} movimiento(s) de inventario asociados que se preservan íntegros en auditoría (Regla de negocio: Sin eliminación física).";
        }

        setFlash('warning', $mensaje);
    }

} catch (PDOException $e) {
    setFlash('danger', 'Error al cambiar el estado del producto: ' . $e->getMessage());
}

redirect('productos.php');
