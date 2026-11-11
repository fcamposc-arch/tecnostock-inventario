<?php
/**
 * Configuración General y Helpers de la Aplicación
 * Proyecto: TecnoStock
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nombre y versión de la aplicación
define('APP_NAME', 'TecnoStock');
define('APP_VERSION', '1.0.0');
define('APP_SLOGAN', 'Sistema de Inventario y Control de Existencias');

/**
 * Verifica si el usuario actual ha iniciado sesión
 */
function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Retorna los datos del usuario en sesión
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'     => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'] ?? 'Usuario',
        'correo' => $_SESSION['usuario_correo'] ?? '',
        'rol'    => $_SESSION['usuario_rol'] ?? 'Encargado de inventario'
    ];
}

/**
 * Establece un mensaje flash en la sesión (success, danger, warning, info)
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Obtiene y remueve el mensaje flash actual si existe
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Renderiza el mensaje flash en formato alerta de Bootstrap 5
 */
function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) {
        return '';
    }

    $type = htmlspecialchars($flash['type']);
    $msg = htmlspecialchars($flash['message']);
    $icon = match ($type) {
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        default   => 'bi-info-circle-fill'
    };

    return "
        <div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm mb-4' role='alert'>
            <i class='bi {$icon} me-2 fs-5'></i>
            <div>{$msg}</div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Cerrar'></button>
        </div>
    ";
}

/**
 * Redirecciona a una URL y finaliza la ejecución
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit();
}

/**
 * Formatea un monto monetario en formato pesos chilenos ($)
 */
function formatPrice(float|int|string $amount): string {
    return '$' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Formatea una fecha y hora legible
 */
function formatDateTime(?string $datetime): string {
    if (!$datetime) return 'N/A';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Limpia y sanitiza una cadena para prevenir ataques XSS
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
