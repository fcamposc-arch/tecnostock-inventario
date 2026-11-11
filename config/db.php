<?php
/**
 * Configuración de Conexión a la Base de Datos con PDO
 * Proyecto: TecnoStock
 * 
 * Cumple con el criterio de calidad y seguridad:
 * - Uso exclusivo de PDO con emulación de preparados desactivada (previene SQL Injection).
 * - Manejo de excepciones en bloque try-catch.
 * - Modo de obtención asociativo por defecto.
 */

// Parámetros de conexión para entorno local (XAMPP / MySQL)
define('DB_HOST', 'localhost');
define('DB_NAME', 'tecnostock_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // En caso de error de conexión, se muestra un mensaje informativo y seguro
    die("
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #dc3545; border-radius: 8px; background: #fff5f5; color: #721c24;'>
            <h2 style='margin-top: 0;'>⚠️ Error de Conexión a la Base de Datos</h2>
            <p>No fue posible conectar con MySQL en <strong>" . DB_HOST . "</strong> con la base de datos <strong>" . DB_NAME . "</strong>.</p>
            <hr style='border: 0; border-top: 1px solid #f5c6cb;'>
            <p><strong>Pasos para solucionar:</strong></p>
            <ol>
                <li>Abre el panel de control de <strong>XAMPP</strong> y verifica que el servicio <strong>MySQL</strong> esté iniciado (en verde).</li>
                <li>Importa el archivo <code>database/tecnostock.sql</code> en <strong>phpMyAdmin</strong>.</li>
                <li>Revisa las credenciales en el archivo <code>config/db.php</code> (por defecto: usuario <code>root</code> sin contraseña).</li>
            </ol>
            <p><small style='color: #6c757d;'>Detalle técnico: " . htmlspecialchars($e->getMessage()) . "</small></p>
        </div>
    ");
}
