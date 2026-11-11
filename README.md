# TecnoStock · Sistema de Gestión de Inventario y Control de Existencias

[![PHP 8](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange.svg)](https://www.mysql.com/)
[![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Aplicación web desarrollada para el emprendimiento **TecnoStock**, orientada a resolver las deficiencias de gestión en planillas de cálculo mediante un sistema centralizado de control de existencias, catálogo de productos tecnológicos y trazabilidad de movimientos de entrada y salida.

---

## 📋 Tabla de Contenidos
1. [Requisitos del Sistema](#-requisitos-del-sistema)
2. [Estructura del Proyecto](#-estructura-del-proyecto)
3. [Instalación y Puesta en Marcha en XAMPP](#-instalación-y-puesta-en-marcha-en-xampp)
4. [Credenciales de Acceso](#-credenciales-de-acceso)
5. [Cumplimiento de Reglas de Negocio](#-cumplimiento-de-reglas-de-negocio)
6. [Trazabilidad de Requerimientos Funcionales](#-trazabilidad-de-requerimientos-funcionales)
7. [Documentación Complementaria](#-documentación-complementaria)
8. [Pasos para Subir el Código a GitHub](#-pasos-para-subir-el-código-a-github)

---

## 💻 Requisitos del Sistema
- **Servidor Web Local:** XAMPP, WampServer o Laragon (Apache + MySQL/MariaDB).
- **Versión PHP:** PHP 8.0 o superior (con extensión `pdo_mysql` habilitada).
- **Base de Datos:** MySQL 5.7+ o MariaDB 10.4+.
- **Navegador Web Moderno:** Google Chrome, Edge, Firefox, Brave o Safari.

---

## 📁 Estructura del Proyecto

```text
tecnostock/
├── assets/
│   ├── css/
│   │   └── styles.css              # Estilos personalizados, alertas y estados de stock
│   └── js/
│       └── validaciones.js         # Validaciones cliente inmediatas (reglas de negocio)
├── config/
│   ├── app.php                     # Helpers, manejo de sesiones, formato y mensajes flash
│   └── db.php                      # Conexión PDO con manejo seguro de excepciones
├── database/
│   └── tecnostock.sql              # Script SQL ejecutable: tablas, relaciones, checks y seeds
├── docs/
│   ├── ANALISIS_REQUERIMIENTOS.md  # Alcance, actores, RF, RNF y matriz de trazabilidad
│   ├── CASOS_DE_PRUEBA.md          # 10 casos de prueba con flujos normales, errores y límites
│   └── GUION_VIDEO_5_MINUTOS.md    # Guion detallado minuto a minuto para el video evaluativo
├── includes/
│   ├── auth_check.php              # Middleware de autenticación para protección de rutas
│   ├── footer.php                  # Pie de página y librerías JavaScript (Bootstrap 5 CDN)
│   └── header.php                  # Encabezado HTML, barra de navegación responsiva
├── dashboard.php                   # Panel de control: KPIs, alertas críticas y accesos rápidos
├── index.php                       # Enrutador principal según estado de autenticación
├── login.php                       # Pantalla de inicio de sesión segura
├── logout.php                      # Cierre de sesión y destrucción de credenciales
├── movimiento_registrar.php        # Registro transaccional de Entradas / Salidas (PDO atómico)
├── movimientos.php                 # Listado histórico y auditoría con filtros avanzados
├── producto_crear.php              # Formulario y validación para registrar nuevo accesorio
├── producto_desactivar.php         # Desactivación lógica (Soft Delete) según reglas
├── producto_editar.php             # Modificación de precios, existencias y categoría
└── README.md                       # Manual de uso y documentación principal
```

---

## 🚀 Instalación y Puesta en Marcha en XAMPP

### Paso 1: Ubicación de los archivos
1. Copia o mueve la carpeta `tecnostock` dentro del directorio público de tu servidor local.
   - En **XAMPP Windows**: `C:\xampp\htdocs\tecnostock`
   - En **Linux / macOS**: `/opt/lampp/htdocs/tecnostock`

### Paso 2: Iniciar Servicios
1. Abre el **Panel de Control de XAMPP**.
2. Haz clic en **Start** en el módulo **Apache** y en el módulo **MySQL**.

### Paso 3: Importar la Base de Datos
1. Abre tu navegador e ingresa a [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Haz clic en la pestaña superior **Importar**.
3. Selecciona el archivo `tecnostock/database/tecnostock.sql`.
4. Presiona el botón **Importar** al final de la página.
   *(El script creará automáticamente la base de datos `tecnostock_db`, todas sus tablas, restricciones y datos de prueba).*

### Paso 4: Abrir la Aplicación
- Ingresa en el navegador a:  
  **`http://localhost/tecnostock`**

> [!NOTE]
> La conexión por defecto en `config/db.php` está configurada para el usuario `root` sin contraseña en `localhost`, estándar en instalaciones locales de XAMPP.

---

## 🔑 Credenciales de Acceso

El script SQL incluye dos usuarios pre-registrados con contraseñas encriptadas:

| Rol | Correo Electrónico | Contraseña | Estado |
| :--- | :--- | :--- | :---: |
| **Administrador** | `admin@tecnostock.cl` | `admin123` | Activo |
| **Encargado de Bodega** | `carlos@tecnostock.cl` | `admin123` | Activo |

---

## ⚖️ Cumplimiento de Reglas de Negocio

| Regla de Negocio | Implementación en TecnoStock |
| :--- | :--- |
| **1. Código no repetible** | Restricción `UNIQUE` en tabla `productos` y verificación previa en `producto_crear.php` y `producto_editar.php`. |
| **2. Valores no negativos** | Restricciones `CHECK (precio >= 0 AND stock_actual >= 0 AND stock_minimo >= 0)` en MySQL, complementadas con control en `validaciones.js` y servidor PHP. |
| **3. Trazabilidad completa** | Todo registro en `movimientos` almacena fecha, tipo, cantidad, producto y el ID del usuario en sesión (`$_SESSION['usuario_id']`). |
| **4. Stock nunca bajo cero** | En `movimiento_registrar.php`, se valida preventivamente en JS y se bloquea con `throw new Exception` y `rollBack()` en PHP si la cantidad solicitada supera el stock disponible. |
| **5. Desactivación lógica (No eliminación física)** | En `producto_desactivar.php` se actualiza `estado = 0` (soft delete) para asegurar que ningún registro con movimientos previos pierda su integridad referencial. |
| **6. Actualización automática de existencias** | En `movimiento_registrar.php` se ejecuta una **transacción atómica con bloqueo de fila (`FOR UPDATE`)** que inserta el movimiento y actualiza `stock_actual` en el mismo paso. |

---

## 🎯 Trazabilidad de Requerimientos Funcionales

| Requerimiento Funcional | Archivo de Interfaz | Archivo de Lógica / Backend | Estado |
| :--- | :--- | :--- | :---: |
| **RF-01: Iniciar Sesión** | `login.php` | `login.php`, `config/app.php` | ✅ Completo |
| **RF-02: Registrar Producto** | `producto_crear.php` | `producto_crear.php` | ✅ Completo |
| **RF-03: Consultar Catálogo** | `productos.php` | `productos.php` | ✅ Completo |
| **RF-04: Buscar Productos** | `productos.php` | `productos.php` | ✅ Completo |
| **RF-05: Modificar Producto** | `producto_editar.php` | `producto_editar.php` | ✅ Completo |
| **RF-06: Desactivar Producto** | `productos.php` | `producto_desactivar.php` | ✅ Completo |
| **RF-07: Movimientos (In/Out)** | `movimiento_registrar.php` | `movimiento_registrar.php` | ✅ Completo |
| **RF-08: Alerta de Stock Bajo** | `dashboard.php`, `productos.php` | `dashboard.php` | ✅ Completo |

---

## 📚 Documentación Complementaria
Para cumplir con los criterios de evaluación de la rúbrica al 100%:
- 📄 [Análisis y Requerimientos de Software (docs/ANALISIS_REQUERIMIENTOS.md)](docs/ANALISIS_REQUERIMIENTOS.md)
- 🧪 [Matriz de Casos de Prueba y Calidad (docs/CASOS_DE_PRUEBA.md)](docs/CASOS_DE_PRUEBA.md)
- 🎬 [Guion Minuto a Minuto para el Video de 5 Minutos (docs/GUION_VIDEO_5_MINUTOS.md)](docs/GUION_VIDEO_5_MINUTOS.md)

---

## 📤 Pasos para Subir el Código a GitHub

Para obtener el enlace requerido en la entrega:

1. **Crear el repositorio en GitHub:**
   - Inicia sesión en [GitHub.com](https://github.com/).
   - Haz clic en el botón verde **New** (Nuevo Repositorio).
   - Nómbralo: `tecnostock-inventario` (o el nombre de tu preferencia).
   - Déjalo como **Público** y no agregues README ni .gitignore (ya están creados).
   - Copia la URL del repositorio (ejemplo: `https://github.com/tu-usuario/tecnostock-inventario.git`).

2. **Ejecutar en la terminal de tu proyecto:**
   ```bash
   cd C:\xampp\htdocs\tecnostock
   git init
   git add .
   git commit -m "feat: Sistema de inventario TecnoStock MVP completo con documentación"
   git branch -M main
   git remote add origin https://github.com/tu-usuario/tecnostock-inventario.git
   git push -u origin main
   ```

3. **Verificar:**
   - Abre la URL en tu navegador y comprueba que se visualicen todos los archivos, el `README.md` y la carpeta `database/`.
