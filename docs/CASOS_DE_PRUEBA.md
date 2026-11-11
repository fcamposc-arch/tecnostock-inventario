# Plan y Evidencia de Casos de Prueba de Software
## Proyecto: Sistema de Inventario para TecnoStock
**Criterio 5 de Evaluación: Pruebas y Calidad del Software (10 Puntos)**

---

### Resumen de Ejecución de Pruebas
- **Total de Casos Diseñados:** 10
- **Casos Aprobados:** 10 (100%)
- **Casos Fallidos:** 0 (0%)
- **Tipología de Pruebas:** Flujo normal, flujos de error, casos límite, validaciones cliente/servidor y reglas de negocio.

---

### Matriz Detallada de Casos de Prueba

| ID | Módulo / Función | Tipo | Descripción | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| **CP-01** | Autenticación | Flujo Normal | Inicio de sesión con credenciales correctas registradas en DB. | **Correo:** `admin@tecnostock.cl`<br>**Clave:** `admin123` | Acceso concedido, creación de sesión PHP, mensaje de bienvenida y redirección a `dashboard.php`. | Acceso exitoso, sesión iniciada con rol "Encargado de inventario". | **APROBADO** |
| **CP-02** | Autenticación | Flujo de Error | Intento de inicio de sesión con clave incorrecta. | **Correo:** `admin@tecnostock.cl`<br>**Clave:** `claveErronea999` | Acceso denegado, mensaje de alerta visible: "La contraseña ingresada es incorrecta." | Muestra alerta de error y mantiene al usuario en `login.php`. | **APROBADO** |
| **CP-03** | Catálogo Productos | Flujo Normal | Registro de un nuevo accesorio con código único y stock inicial. | **Código:** `CBL-999`<br>**Nombre:** Cable USB-C a HDMI 4K<br>**Categoría:** Cables y Adaptadores<br>**Precio:** $14.990<br>**Stock Inicial:** 10<br>**Stock Mínimo:** 3 | Inserción en tabla `productos`, generación de movimiento inicial de ENTRADA por 10 unidades y redirección al catálogo. | Producto creado con éxito y visible en catálogo con stock de 10 unidades. | **APROBADO** |
| **CP-04** | Catálogo Productos | Límite / Error (RN-01) | Intento de registro con código de producto duplicado. | **Código:** `CAB-001` (ya existente en la base de datos)<br>**Nombre:** Cable duplicado de prueba | El sistema bloquea la inserción y despliega el mensaje: "El código 'CAB-001' ya se encuentra registrado en el sistema. Debe ser único." | Inserción rechazada por validación previa y restricción `UNIQUE` en DB. | **APROBADO** |
| **CP-05** | Catálogo Productos | Límite / Error (RN-02) | Ingreso de precio negativo o stock negativo. | **Precio:** `-5000`<br>**Stock:** `-2` | Validación en JavaScript bloquea el submit; si se fuerza por POST, PHP rechaza con mensaje de error. | Bloqueado en cliente (formulario no se envía) y bloqueado en PHP si se omite JS. | **APROBADO** |
| **CP-06** | Catálogo Productos | Flujo Normal | Búsqueda por término parcial y filtro de categoría. | **Término:** `Bluetooth`<br>**Categoría:** Audio y Auriculares | La grilla muestra exclusivamente productos que contengan 'Bluetooth' en código o nombre y pertenezcan a la categoría. | Lista filtrada mostrando únicamente los Auriculares Bluetooth TWS. | **APROBADO** |
| **CP-07** | Movimientos | Flujo Normal (RN-06) | Registro de ENTRADA de existencias con actualización automática. | **Producto:** `CAB-001` (Stock actual: 35)<br>**Tipo:** ENTRADA<br>**Cantidad:** 15<br>**Motivo:** Recepción de proveedor | El stock del producto pasa atómicamente de 35 a 50 unidades. Se inserta registro en tabla `movimientos`. | Transacción completada con éxito. Stock actualizado a 50 y movimiento auditado. | **APROBADO** |
| **CP-08** | Movimientos | Caso Límite / Error (RN-04) | Intento de SALIDA superior al stock actual disponible. | **Producto:** `ALM-003` (Stock actual: 3)<br>**Tipo:** SALIDA<br>**Cantidad:** 10 | Bloqueo preventivo en JS en tiempo real y excepción en transacción PDO con rollback. Mensaje: "Una salida no puede dejar el stock bajo cero." | No se descuenta stock, transacción revertida y mensaje de advertencia presentado. | **APROBADO** |
| **CP-09** | Catálogo Productos | Flujo de Negocio (RN-05) | Desactivación lógica de un producto que posee movimientos registrados. | **Producto:** `CAB-001` (Posee movimientos históricos)<br>**Acción:** Desactivar | El campo `estado` cambia de 1 a 0. No se ejecuta `DELETE FROM productos`. Los movimientos permanecen intactos. | Producto cambia a estado 'Desactivado' con fila atenuada; movimientos conservados al 100%. | **APROBADO** |
| **CP-10** | Monitoreo y Alertas | Flujo Normal (RF-08) | Generación y visualización de alerta de bajo stock. | **Producto:** `CAR-004`<br>Stock actual: 1, Stock mínimo: 6 (1 &le; 6) | Banner destacado rojo en `dashboard.php`, badge "Stock Bajo" en tabla y aparición en listado de reposición prioritaria. | Alerta visual visible en Dashboard y Catálogo con botón rápido de reposición. | **APROBADO** |

---

### Conclusión Técnica de Calidad
1. **Seguridad PDO:** No se identificaron vulnerabilidades de Inyección SQL gracias al uso de sentencias preparadas con parámetros vinculados (`?`).
2. **Atomicidad:** Las operaciones de inventario emplean `beginTransaction()`, `commit()` y `rollBack()` para garantizar que jamás existan discrepancias entre el historial de movimientos y el saldo en catálogo.
3. **Validación Dual:** Se corroboró que el sistema es tolerante a manipulaciones en el cliente, pues toda regla de negocio se valida invariablemente en el servidor (PHP 8).
