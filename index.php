<?php
/**
 * Punto de Entrada Principal
 * Proyecto: TecnoStock
 */

require_once __DIR__ . '/config/app.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
