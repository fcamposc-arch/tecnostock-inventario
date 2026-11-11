<?php
/**
 * Header General del Sistema TecnoStock
 */
require_once __DIR__ . '/../config/app.php';
$usuarioActual = getCurrentUser();
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . APP_NAME : APP_NAME . ' | Control de Inventario'; ?></title>
    
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Barra de Navegación Principal -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="dashboard.php">
                <span class="brand-icon bg-primary text-white rounded-3 px-2 py-1">
                    <i class="bi bi-cpu"></i>
                </span>
                <span>Tecno<span class="text-primary">Stock</span></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContenido">
                <?php if ($usuarioActual): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= ($paginaActual == 'dashboard.php') ? 'active fw-semibold' : '' ?>" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (in_array($paginaActual, ['productos.php', 'producto_crear.php', 'producto_editar.php'])) ? 'active fw-semibold' : '' ?>" href="productos.php">
                                <i class="bi bi-box-seam me-1"></i> Catálogo de Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (in_array($paginaActual, ['movimientos.php', 'movimiento_registrar.php'])) ? 'active fw-semibold' : '' ?>" href="movimientos.php">
                                <i class="bi bi-arrow-left-right me-1"></i> Movimientos
                            </a>
                        </li>
                    </ul>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-6"></i>
                                <span><?= htmlspecialchars($usuarioActual['nombre']); ?></span>
                                <span class="badge bg-primary text-white ms-1"><?= htmlspecialchars($usuarioActual['rol']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li class="dropdown-header">Conectado como</li>
                                <li class="dropdown-item-text fw-bold text-dark"><?= htmlspecialchars($usuarioActual['correo']); ?></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="ms-auto">
                        <a href="login.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Contenedor Principal -->
    <main class="flex-grow-1 py-4">
        <div class="container-fluid px-lg-4">
            <!-- Mensajes Flash de Retroalimentación -->
            <?= renderFlash(); ?>
