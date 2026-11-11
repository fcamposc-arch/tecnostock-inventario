# Guion Paso a Paso para Video Explicativo (Máximo 5 Minutos)
## Proyecto: Sistema de Inventario para TecnoStock
**Criterio 6 de Evaluación: Video - Producto, Interfaz y Código (10 Puntos)**

> [!IMPORTANT]
> **Regla de Oro de la Rúbrica:** Solo se evalúa el contenido entre los minutos **0:00 y 5:00**. Practica con cronómetro para finalizar entre el minuto 4:40 y 4:55. Habla con tono claro, seguro y fluido.

---

### Cronograma de Tiempos
```mermaid
gantt
    title Distribución de los 5 Minutos de Video
    dateFormat  m:s
    axisFormat  %M:%S
    Introducción y Caso TecnoStock    :00:00, 00:40
    Demostración MVP (Login y Catálogo) :00:40, 01:50
    Movimientos y Reglas de Negocio   :01:50, 03:00
    Recorrido Técnico de Código       :03:00, 04:20
    Conclusión y Rúbrica              :04:20, 04:55
```

---

### Segmento 1: Introducción, Contexto y Solución (0:00 - 0:40)
* **Qué mostrar en pantalla:** Diapositiva inicial o pantalla principal de login de TecnoStock con el título del proyecto y tu nombre.
* **Qué decir:**
  > *"Hola, estimado docente y compañeros. Mi nombre es [Tu Nombre y Apellido], y a continuación presento el desarrollo del Sistema Web de Gestión de Inventario para el emprendimiento **TecnoStock**, enfocado en la comercialización de accesorios tecnológicos.*
  >
  > *El problema principal de TecnoStock radicaba en el uso de planillas manuales, lo que generaba duplicidad de códigos, inexactitud en el stock real y falta de alertas ante quiebres de inventario.*
  >
  > *Para solucionarlo, construí una aplicación web en arquitectura cliente-servidor utilizando **PHP 8 nativo con PDO**, base de datos relacional **MySQL**, interfaz responsiva con **Bootstrap 5** y validaciones inmediatas en **JavaScript**, sin frameworks complejos, garantizando la integridad de los datos y el cumplimiento de todas las reglas del negocio."*

---

### Segmento 2: Demostración del MVP - Acceso, Dashboard y Catálogo (0:40 - 1:50)
* **Qué mostrar en pantalla:**
  1. Login en el navegador (`login.php`).
  2. Ingreso con `admin@tecnostock.cl` y clave `admin123`.
  3. Dashboard con las tarjetas de KPI y la **Alerta de Stock Crítico** destacada en rojo.
  4. Clic en **Catálogo de Productos** (`productos.php`).
  5. Mostrar el buscador filtrando por palabra (ej. "Bluetooth") y por categoría.
  6. Clic en **Registrar Nuevo Producto** (`producto_crear.php`) e ingresar datos válidos.
* **Qué decir:**
  > *"Iniciamos sesión con un usuario previamente registrado. El sistema verifica la clave encriptada mediante `password_verify` y abre la sesión del encargado de inventario.*
  >
  > *En el Dashboard observamos de inmediato el cumplimiento del requerimiento 8: una **alerta visual destacada en rojo** que nos advierte sobre productos cuyo stock actual es menor o igual al mínimo permitido, como el Cargador GaN o el Pendrive.*
  >
  > *Al pasar al Catálogo, vemos la lista completa. Disponemos de un motor de búsqueda que filtra en tiempo real por coincidencia de nombre o código y por categoría.*
  >
  > *En el formulario de registro, aplicamos validaciones en cliente con JavaScript y en servidor con PHP: el sistema impide registrar precios o cantidades negativas y asegura que el código de producto sea estrictamente único."*

---

### Segmento 3: Movimientos de Existencias y Reglas de Negocio (1:50 - 3:00)
* **Qué mostrar en pantalla:**
  1. Clic en el botón **Registrar Movimiento** (`movimiento_registrar.php`).
  2. Seleccionar un producto (ej. "Cable USB-C a Lightning") y tipo **ENTRADA** por 10 unidades. Mostrar cómo el stock sube automáticamente.
  3. Intentar registrar una **SALIDA** por una cantidad mayor a la disponible (ej. si quedan 5, escribir 20). Mostrar cómo el sistema bloquea inmediatamente en el cliente y en el servidor.
  4. Realizar una salida válida.
  5. Volver al Catálogo y mostrar la acción de **Desactivación Lógica** (hacer clic en desactivar un producto con movimientos; mostrar que no se elimina de la base de datos).
* **Qué decir:**
  > *"Veamos ahora el núcleo del control de existencias. En el módulo de movimientos registramos una **Entrada** de 10 unidades. Como exige la regla de negocio 6, el stock del producto se actualiza de manera automática y atómica, registrando fecha, tipo, cantidad y el usuario responsable.*
  >
  > *Ahora probemos la regla de negocio 4: **una salida no puede dejar el stock bajo cero**. Si intento retirar 20 unidades de un producto que solo tiene 5 disponibles, JavaScript me muestra una advertencia inmediata y el backend con PDO rechaza la transacción revirtiendo cualquier cambio.*
  >
  > *Por último, para cumplir la regla de negocio 5, los productos con movimientos históricos **no se eliminan físicamente**. Al pulsar desactivar, el sistema aplica una baja lógica cambiando su estado, conservando la integridad referencial y toda la pista de auditoría."*

---

### Segmento 4: Recorrido por el Código Fuente (3:00 - 4:20)
* **Qué mostrar en pantalla:** Abrir Visual Studio Code y mostrar 4 archivos clave:
  1. `config/db.php`: Mostrar la instancia PDO con `PDO::ERRMODE_EXCEPTION` y `PDO::ATTR_EMULATE_PREPARES => false`.
  2. `database/tecnostock.sql`: Mostrar las relaciones `FOREIGN KEY`, la restricción `UNIQUE` en el código y los `CHECK` de valores positivos.
  3. `movimiento_registrar.php`: Mostrar las líneas de la transacción PDO:
     - `$pdo->beginTransaction();`
     - Consulta preparada `SELECT ... FOR UPDATE`
     - Verificación `if ($cantidad > $stockAnterior)`
     - `INSERT INTO movimientos` y `UPDATE productos`
     - `$pdo->commit();` / `$pdo->rollBack();`
  4. `assets/js/validaciones.js`: Mostrar la función `validarSalidaEnVivo` y validación de números no negativos.
* **Qué decir:**
  > *"Pasemos a revisar el código fuente en Visual Studio Code.*
  >
  > *En `config/db.php` configuramos la conexión utilizando **PDO**, asegurando el modo de excepciones y desactivando la emulación de preparados para prevenir cualquier riesgo de inyección SQL.*
  >
  > *En el script de base de datos `tecnostock.sql`, normalizamos las entidades `usuarios`, `categorias`, `productos` y `movimientos`, con claves primarias, claves foráneas en cascada y restricciones `CHECK` para garantizar que precios y existencias sean siempre mayores o iguales a cero.*
  >
  > *En `movimiento_registrar.php` implementamos la lógica transaccional más importante: abrimos una transacción con `beginTransaction`, bloqueamos la fila del producto para evitar condiciones de carrera, validamos que la salida no supere el stock disponible, insertamos el movimiento y actualizamos el stock antes de ejecutar el `commit`. Si ocurre cualquier error, capturamos la excepción y ejecutamos `rollBack`.*
  >
  > *Finalmente, en `validaciones.js` aplicamos control de eventos en tiempo real para alertar al usuario antes de enviar el formulario, cumpliendo con la validación dual en cliente y servidor."*

---

### Segmento 5: Conclusión y Cierre (4:20 - 4:55)
* **Qué mostrar en pantalla:** Navegador mostrando la vista de movimientos (`movimientos.php`) y luego la portada del repositorio en GitHub o pantalla final con tus datos.
* **Qué decir:**
  > *"Como hemos comprobado, el sistema cumple al 100% con los requerimientos funcionales, las 6 reglas de negocio y los criterios de la rúbrica de evaluación.*
  >
  > *El código fuente completo y el script SQL reproducible se encuentran disponibles en el repositorio de GitHub indicado en la entrega.*
  >
  > *Muchas gracias por su atención."*

---

### Consejos Técnicos de Grabación
1. **Herramienta recomendada:** Utiliza **OBS Studio** (gratuito) o la barra de juegos de Windows (`Windows + G`) para grabar pantalla completa a resolución 1080p.
2. **Audio nítido:** Usa audífonos con micrófono o acércate al micrófono de tu laptop en un ambiente silencioso.
3. **Pestañas listas:** Ten abiertas con antelación las pestañas del navegador (Login, Dashboard, phpMyAdmin) y VS Code con los archivos ya abiertos en pestañas adyacentes para no perder segundos valiosos navegando.
4. **Verificación de tiempo:** Mantén un cronómetro en tu celular visible al lado de tu pantalla. Si te pasas de los 5:00 minutos, la rúbrica invalida el contenido excedente.
