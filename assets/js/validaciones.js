/**
 * Validaciones en Cliente para Formularios de TecnoStock
 * 
 * Reglas de negocio validadas inmediatamente:
 * 1. Precio, stock actual y stock mínimo no pueden ser negativos (>= 0).
 * 2. La cantidad de movimiento debe ser un entero positivo (> 0).
 * 3. En salidas de inventario, la cantidad no puede exceder el stock actual disponible.
 * 4. El código de producto no puede contener solo espacios en blanco.
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1. Manejo del formulario de creación y edición de productos
    const formProducto = document.getElementById('formProducto');
    if (formProducto) {
        formProducto.addEventListener('submit', function (event) {
            let isValid = true;
            
            const codigo = document.getElementById('codigo');
            const nombre = document.getElementById('nombre');
            const precio = document.getElementById('precio');
            const stockActual = document.getElementById('stock_actual');
            const stockMinimo = document.getElementById('stock_minimo');
            const categoria = document.getElementById('id_categoria');

            // Limpiar errores previos
            formProducto.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            // Validar código
            if (codigo && codigo.value.trim() === '') {
                marcarInvalido(codigo, 'El código del producto es obligatorio.');
                isValid = false;
            }

            // Validar nombre
            if (nombre && nombre.value.trim() === '') {
                marcarInvalido(nombre, 'El nombre del producto es obligatorio.');
                isValid = false;
            }

            // Validar categoría
            if (categoria && categoria.value === '') {
                marcarInvalido(categoria, 'Debes seleccionar una categoría.');
                isValid = false;
            }

            // Validar precio >= 0
            if (precio) {
                const valPrecio = parseFloat(precio.value);
                if (isNaN(valPrecio) || valPrecio < 0) {
                    marcarInvalido(precio, 'El precio no puede ser negativo.');
                    isValid = false;
                }
            }

            // Validar stock actual >= 0
            if (stockActual) {
                const valStock = parseInt(stockActual.value, 10);
                if (isNaN(valStock) || valStock < 0) {
                    marcarInvalido(stockActual, 'El stock actual no puede ser negativo.');
                    isValid = false;
                }
            }

            // Validar stock mínimo >= 0
            if (stockMinimo) {
                const valMinimo = parseInt(stockMinimo.value, 10);
                if (isNaN(valMinimo) || valMinimo < 0) {
                    marcarInvalido(stockMinimo, 'El stock mínimo no puede ser negativo.');
                    isValid = false;
                }
            }

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }

    // 2. Manejo interactivo del formulario de Movimientos (Entrada / Salida)
    const formMovimiento = document.getElementById('formMovimiento');
    const selectProducto = document.getElementById('id_producto');
    const selectTipo = document.getElementById('tipo');
    const inputCantidad = document.getElementById('cantidad');
    const stockInfoBadge = document.getElementById('stockActualInfo');
    const observacion = document.getElementById('observacion');

    // Función para actualizar la información de stock según el producto elegido
    function actualizarInfoStock() {
        if (!selectProducto || !stockInfoBadge) return;
        
        const selectedOption = selectProducto.options[selectProducto.selectedIndex];
        if (selectedOption && selectedOption.dataset.stock !== undefined) {
            const stockActual = parseInt(selectedOption.dataset.stock, 10);
            stockInfoBadge.textContent = `Stock actual disponible: ${stockActual} unidades`;
            stockInfoBadge.classList.remove('d-none');
            
            // Si el tipo es salida, validar cantidad al cambiar producto
            validarSalidaEnVivo();
        } else {
            stockInfoBadge.classList.add('d-none');
        }
    }

    function validarSalidaEnVivo() {
        if (!selectProducto || !selectTipo || !inputCantidad) return true;

        const selectedOption = selectProducto.options[selectProducto.selectedIndex];
        const stockActual = selectedOption ? parseInt(selectedOption.dataset.stock, 10) : 0;
        const tipo = selectTipo.value;
        const cantidad = parseInt(inputCantidad.value, 10);

        inputCantidad.classList.remove('is-invalid');
        const feedback = inputCantidad.parentElement.querySelector('.invalid-feedback');

        if (cantidad <= 0 || isNaN(cantidad)) {
            marcarInvalido(inputCantidad, 'La cantidad debe ser mayor a 0.');
            return false;
        }

        if (tipo === 'SALIDA' && cantidad > stockActual) {
            marcarInvalido(inputCantidad, `Regla de negocio: Una salida no puede dejar el stock bajo cero. Stock disponible: ${stockActual}.`);
            return false;
        }

        return true;
    }

    if (selectProducto) {
        selectProducto.addEventListener('change', actualizarInfoStock);
    }

    if (selectTipo) {
        selectTipo.addEventListener('change', validarSalidaEnVivo);
    }

    if (inputCantidad) {
        inputCantidad.addEventListener('input', validarSalidaEnVivo);
    }

    if (formMovimiento) {
        formMovimiento.addEventListener('submit', function (event) {
            let isValid = true;

            if (!selectProducto.value) {
                marcarInvalido(selectProducto, 'Debes seleccionar un producto.');
                isValid = false;
            }

            if (!selectTipo.value) {
                marcarInvalido(selectTipo, 'Debes seleccionar el tipo de movimiento.');
                isValid = false;
            }

            if (!validarSalidaEnVivo()) {
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }

    // Helper para marcar input inválido con mensaje dinámico
    function marcarInvalido(inputElement, mensaje) {
        inputElement.classList.add('is-invalid');
        let feedback = inputElement.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            inputElement.parentElement.appendChild(feedback);
        }
        feedback.textContent = mensaje;
    }
});

/**
 * Función global para confirmar desactivación lógica de un producto
 */
function confirmarDesactivacion(nombreProducto, urlDestino) {
    if (confirm(`¿Estás seguro de que deseas desactivar el producto "${nombreProducto}"?\n\nDe acuerdo a las reglas de negocio, el producto no se eliminará físicamente para preservar la integridad de los movimientos históricos.`)) {
        window.location.href = urlDestino;
    }
}
