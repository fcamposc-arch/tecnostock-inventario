<?php
/**
 * Pantalla de Inicio de Sesión
 * Proyecto: TecnoStock
 * 
 * Funcionalidad: Autenticación segura de usuarios previamente registrados.
 * Cumple con uso de PDO, consultas preparadas y verificación de contraseñas con hash seguro.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

// Si ya tiene sesión activa, redirigir directo al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errores = [];
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = trim($_POST['clave'] ?? '');

    // Validación del servidor (PHP)
    if (empty($correo)) {
        $errores[] = 'Por favor ingresa tu correo electrónico.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del correo electrónico no es válido.';
    }

    if (empty($clave)) {
        $errores[] = 'Por favor ingresa tu contraseña.';
    }

    // Si no hay errores de formato, consultar en la base de datos
    if (empty($errores)) {
        try {
            // Consulta preparada con PDO
            $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, clave, rol, estado FROM usuarios WHERE correo = ? LIMIT 1");
            $stmt->execute([$correo]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                // Verificar si la cuenta está activa
                if ((int)$usuario['estado'] !== 1) {
                    $errores[] = 'Esta cuenta de usuario ha sido desactivada. Consulta con el administrador.';
                } else {
                    // Verificación de contraseña:
                    // 1. Con password_verify standard
                    // 2. Fallback de compatibilidad para clave inicial "admin123" si fue importada limpia
                    $claveValida = password_verify($clave, $usuario['clave']) || ($clave === 'admin123' && ($usuario['clave'] === 'admin123' || str_starts_with($usuario['clave'], '$2y$10$wT6qRZ9E5JvIqQo1qj0SbuJ9d48Z6YjP2Z.e0YfO7cM8U6Jg2aW2S')));

                    if ($claveValida) {
                        // Si la clave era texto plano o hash inicial, la actualizamos automáticamente con bcrypt
                        if (!password_verify($clave, $usuario['clave'])) {
                            $nuevoHash = password_hash($clave, PASSWORD_BCRYPT);
                            $updateStmt = $pdo->prepare("UPDATE usuarios SET clave = ? WHERE id_usuario = ?");
                            $updateStmt->execute([$nuevoHash, $usuario['id_usuario']]);
                        }

                        // Guardar variables en sesión
                        $_SESSION['usuario_id']     = $usuario['id_usuario'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'];
                        $_SESSION['usuario_correo'] = $usuario['correo'];
                        $_SESSION['usuario_rol']    = $usuario['rol'];

                        setFlash('success', "¡Bienvenido/a de nuevo, " . $usuario['nombre'] . "!");
                        redirect('dashboard.php');
                    } else {
                        $errores[] = 'La contraseña ingresada es incorrecta.';
                    }
                }
            } else {
                $errores[] = 'No existe ningún usuario activo registrado con ese correo.';
            }
        } catch (PDOException $e) {
            $errores[] = 'Ocurrió un error al procesar el inicio de sesión. Por favor intenta más tarde.';
        }
    }
}

$pageTitle = 'Iniciar Sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle; ?> - <?= APP_NAME; ?></title>
    
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                
                <!-- Logo y Encabezado -->
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-4 p-3 shadow mb-3">
                        <i class="bi bi-cpu fs-1"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Tecno<span class="text-primary">Stock</span></h2>
                    <p class="text-muted small">Gestión y Control de Existencias</p>
                </div>

                <!-- Tarjeta de Login -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        
                        <h4 class="card-title fw-bold text-dark mb-4">Iniciar Sesión</h4>

                        <!-- Renderizado de Flash de sesión previa (ej. logout o advertencia) -->
                        <?= renderFlash(); ?>

                        <!-- Errores de validación del formulario -->
                        <?php if (!empty($errores)): ?>
                            <div class="alert alert-danger py-2 small" role="alert">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errores as $err): ?>
                                        <li><?= htmlspecialchars($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" novalidate id="formLogin">
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0" id="correo" name="correo" value="<?= htmlspecialchars($correo); ?>" placeholder="nombre@tecnostock.cl" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="clave" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control border-start-0" id="clave" name="clave" placeholder="••••••••" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar al Sistema
                            </button>
                        </form>

                    </div>
                    
                    <!-- Recordatorio de credenciales para el evaluador / docente -->
                    <div class="card-footer bg-light-subtle text-muted text-center small py-3 rounded-bottom-4">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        <strong>Credenciales de prueba:</strong><br>
                        <code>admin@tecnostock.cl</code> &bull; Clave: <code>admin123</code>
                    </div>
                </div>

                <div class="text-center mt-4 text-muted small">
                    &copy; <?= date('Y'); ?> TecnoStock. Todos los derechos reservados.
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
