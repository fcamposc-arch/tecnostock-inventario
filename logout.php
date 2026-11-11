<?php
/**
 * Cierre de Sesión Seguro
 * Proyecto: TecnoStock
 */

require_once __DIR__ . '/config/app.php';

// Limpiar todas las variables de sesión
$_SESSION = [];

// Destruir cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Iniciar una nueva sesión temporal para enviar el mensaje flash de salida
session_start();
setFlash('info', 'Has cerrado sesión exitosamente. ¡Hasta pronto!');
redirect('login.php');
