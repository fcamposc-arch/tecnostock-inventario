<?php
/**
 * Middleware de Control de Acceso
 * Proyecto: TecnoStock
 * 
 * Protege las páginas que requieren que el usuario esté autenticado.
 */

require_once __DIR__ . '/../config/app.php';

if (!isLoggedIn()) {
    setFlash('warning', 'Debes iniciar sesión con tus credenciales para acceder al inventario.');
    redirect('login.php');
}
