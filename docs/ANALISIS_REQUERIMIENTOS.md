# Documento de Análisis y Requerimientos de Software
## Proyecto: Sistema de Inventario para TecnoStock
**Caso 1 · Sistemas de Información · Nivel Básico**

---

### 1. Contexto del Problema y Justificación
El emprendimiento **TecnoStock** se dedica a la comercialización de accesorios tecnológicos (cables, cargadores, auriculares, adaptadores, soportes, etc.). Históricamente, la empresa gestionaba sus existencias mediante una planilla de cálculo manual, lo que ocasionó deficiencias operativas críticas:
- Generación inadvertida de códigos de producto duplicados.
- Desfase entre las cantidades anotadas y el stock real en bodega.
- Quiebres de stock sin aviso oportuno y pérdida de ventas.
- Ausencia de un registro fidedigno de quién y cuándo autorizó salidas o entradas de mercadería.

Para resolver esto, se desarrolló un sistema web centralizado, accesible para un usuario no técnico, que opera en servidor local y asegura la integridad de los datos mediante una arquitectura cliente-servidor robusta y libre de frameworks pesados.

---

### 2. Alcance y Actores del Sistema

#### 2.1 Actor Principal
| Actor | Rol y Responsabilidad |
| :--- | :--- |
| **Encargado de Inventario** | Responsable de iniciar sesión, administrar el catálogo de productos (creación, edición y desactivación lógica), gestionar categorías y registrar entradas y salidas de existencias bajo control estricto de reglas de negocio. |

#### 2.2 Alcance del MVP (Producto Mínimo Viable)
- Control de acceso mediante autenticación segura con usuarios preexistentes.
- Administración del catálogo de productos (CRUD con baja lógica).
- Motor de búsqueda multicriterio (por nombre, código y categoría).
- Registro transaccional de movimientos de inventario (Entradas y Salidas).
- Monitoreo en tiempo real de niveles críticos de stock con alertas visuales.

---

### 3. Requerimientos Funcionales (RF)

| ID | Requerimiento Funcional | Descripción |
| :--- | :--- | :--- |
| **RF-01** | **Autenticación de Usuarios** | El sistema debe permitir iniciar sesión mediante correo electrónico y contraseña encriptada, validando usuarios con estado activo. |
| **RF-02** | **Registro de Productos** | El usuario debe poder registrar productos ingresando código único, nombre, categoría, descripción, precio, stock actual y stock mínimo. |
| **RF-03** | **Consulta de Catálogo** | El sistema debe mostrar el listado tabular de productos con su información esencial, estado y disponibilidad de stock. |
| **RF-04** | **Búsqueda y Filtrado** | Permitir filtrar productos por coincidencia en nombre o código, por categoría y por estado (activos/desactivados). |
| **RF-05** | **Modificación de Productos** | Permitir actualizar precio, descripción, categoría y cantidades (stock actual y mínimo) de cualquier producto existente. |
| **RF-06** | **Desactivación de Productos (Soft Delete)** | Permitir dar de baja productos del catálogo comercial sin eliminar físicamente el registro si tiene movimientos asociados. |
| **RF-07** | **Registro de Entradas y Salidas** | Registrar movimientos de existencias indicando producto, tipo (ENTRADA/SALIDA), cantidad, observación y usuario responsable. |
| **RF-08** | **Alerta Visual de Bajo Stock** | Desplegar indicadores y alertas destacadas cuando el stock actual de un producto sea menor o igual al stock mínimo configurado. |

---

### 4. Reglas de Negocio (RN)

| ID | Regla de Negocio | Implementación Técnica |
| :--- | :--- | :--- |
| **RN-01** | **Código único de producto** | Restricción `UNIQUE` a nivel de base de datos (`ALTER TABLE productos ADD UNIQUE (codigo)`) y validación previa con consulta preparada en PHP. |
| **RN-02** | **Valores no negativos** | Restricciones `CHECK (precio >= 0)`, `CHECK (stock_actual >= 0)` y `CHECK (stock_minimo >= 0)` en MySQL, complementadas con validaciones inmediatas en JS y servidor PHP. |
| **RN-03** | **Trazabilidad total de movimientos** | La tabla `movimientos` almacena obligatoriamente `fecha_hora`, `tipo`, `cantidad`, `stock_anterior`, `stock_posterior`, `id_producto` e `id_usuario`. |
| **RN-04** | **No permitir stock bajo cero** | En movimientos de tipo `SALIDA`, si la cantidad supera el stock actual disponible, la operación se cancela y se revierte la transacción. |
| **RN-05** | **Conservación histórica (No eliminación física)** | Los productos con movimientos registrados nunca se eliminan físicamente (`DELETE`), sino que se cambia su estado a `0` (desactivado). |
| **RN-06** | **Actualización atómica de existencias** | Toda entrada o salida ejecuta en una sola transacción PDO (`beginTransaction`, `commit`) la inserción del movimiento y la actualización del campo `stock_actual` del producto. |

---

### 5. Requerimientos No Funcionales (RNF)

- **RNF-01 (Seguridad y PDO):** No se permite la concatenación directa de variables en consultas SQL. Todo acceso a datos usa PDO con sentencias preparadas (`prepare` / `execute`).
- **RNF-02 (Validación Dual):** Toda entrada de datos se valida en el navegador con JavaScript para feedback rápido, y se revalida obligatoriamente en PHP para seguridad.
- **RNF-03 (Protección XSS):** Toda salida de datos hacia la vista se escapa mediante `htmlspecialchars()`.
- **RNF-04 (Diseño Responsivo):** Interfaz maquetada con Bootstrap 5.3, adaptable a pantallas de escritorio, tabletas y teléfonos móviles.
- **RNF-05 (Sin Frameworks Complejos):** Código PHP nativo modular y comprensible, ideal para aprendizaje y evaluación académica.

---

### 6. Matriz de Trazabilidad de Requerimientos

| Requerimiento | Reglas de Negocio | Vista / Controlador Frontend | Lógica Backend / Modelo | Tabla DB Afectada |
| :--- | :--- | :--- | :--- | :--- |
| **RF-01** Login | - | `login.php` | `login.php`, `config/app.php` | `usuarios` |
| **RF-02** Crear Producto | RN-01, RN-02, RN-03 | `producto_crear.php` | `producto_crear.php` | `productos`, `movimientos` |
| **RF-03** Listar Catálogo | RN-02 | `productos.php` | `productos.php` | `productos`, `categorias` |
| **RF-04** Buscar/Filtrar | - | `productos.php` | `productos.php` | `productos`, `categorias` |
| **RF-05** Editar Producto | RN-01, RN-02, RN-03 | `producto_editar.php` | `producto_editar.php` | `productos`, `movimientos` |
| **RF-06** Desactivar Producto | RN-05 | `productos.php` | `producto_desactivar.php` | `productos` |
| **RF-07** Movimientos | RN-02, RN-03, RN-04, RN-06 | `movimiento_registrar.php`, `movimientos.php` | `movimiento_registrar.php` | `movimientos`, `productos` |
| **RF-08** Alerta Stock Bajo | RN-02 | `dashboard.php`, `productos.php` | `dashboard.php` | `productos` |
