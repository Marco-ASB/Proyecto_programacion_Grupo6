document.addEventListener("DOMContentLoaded", function() {
    // 1. Toggle del Sidebar
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(event) {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    // 2. Inicialización de DataTables
    const dataTable = document.querySelector("#datatablesSimple");
    if (dataTable) {
        new simpleDatatables.DataTable(dataTable, { perPage: 10 });
    }

    // 3. Ejecutar cambio de fecha al cargar (para el modal de Nuevo)
    cambiarFecha();

    // 4. Lógica de Validación en Tiempo Real (Serie Repetida) - Optimizada
    const inputSerie = document.getElementById('inputSerie');
    const errorSerie = document.getElementById('errorSerieRepetida');
    const formNuevo = document.getElementById('formNuevoInstrumento');
    let serieEsValida = true;
    let timerValidacion; 

    if (inputSerie) {
        inputSerie.addEventListener('input', function() {
            clearTimeout(timerValidacion);
            const serie = this.value.trim();
            
            if (serie.length < 6) {
                errorSerie.classList.add('d-none');
                inputSerie.classList.remove('is-invalid');
                serieEsValida = true;
                return;
            }

            errorSerie.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Verificando...';
            errorSerie.classList.remove('d-none', 'text-danger', 'text-success');
            errorSerie.classList.add('text-primary');

            timerValidacion = setTimeout(() => {
                const formData = new FormData();
                formData.append('serie', serie);

                fetch('verificar_serie.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    errorSerie.classList.remove('text-primary');
                    if (data.existe) {
                        errorSerie.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Esta serie ya está registrada.';
                        errorSerie.classList.add('text-danger');
                        inputSerie.classList.add('is-invalid');
                        serieEsValida = false;
                    } else {
                        errorSerie.innerHTML = '<i class="fas fa-check-circle me-1"></i> Serie disponible.';
                        errorSerie.classList.add('text-success');
                        inputSerie.classList.remove('is-invalid');
                        serieEsValida = true;
                        
                        setTimeout(() => {
                            errorSerie.classList.add('d-none');
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorSerie.classList.add('d-none');
                });
            }, 400); 
        });
    }

    // 5. Validaciones antes de enviar el formulario NUEVO
    if (formNuevo) {
        formNuevo.addEventListener('submit', function(e) {
            if (!serieEsValida) {
                e.preventDefault();
                alert('⚠️ No puedes guardar el instrumento porque la serie ya existe.');
                inputSerie.focus();
                return;
            }

            const categoria = document.getElementById('selectCategoria').value;
            const serie = document.getElementById('inputSerie').value;
            const estado = document.getElementById('selectEstado').value;
            
            let prefijoCorrecto = '';
            if (categoria === 'Medidores') prefijoCorrecto = 'MD-';
            else if (categoria === 'Detectores') prefijoCorrecto = 'DT-';
            else if (categoria === 'Calibradores') prefijoCorrecto = 'CP-';
            else if (categoria === 'END') prefijoCorrecto = 'EN-';

            if (!serie.startsWith(prefijoCorrecto) || serie.length < 6) {
                e.preventDefault();
                alert(`⚠️ Error: La serie debe empezar con ${prefijoCorrecto} y tener al menos 3 números.`);
                return;
            }

            if (estado === 'En Calibracion' && !document.getElementById('inputFechaCalibracion').value) {
                e.preventDefault();
                alert('⚠️ Ingrese la fecha de vencimiento de calibración.');
                return;
            }
            if (estado === 'En Uso' && !document.querySelector('input[name="fecha_devolucion"]').value) {
                e.preventDefault();
                alert('⚠️ Ingrese la fecha estimada de devolución.');
                return;
            }
        });
    }

    // 6. Lógica para los botones de la tabla (Eliminar)
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    if (botonesEliminar.length > 0) {
        botonesEliminar.forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const serie = this.getAttribute('data-serie');

                if (confirm(`¿Estás seguro de que deseas eliminar el instrumento con serie ${serie}? Esta acción no se puede deshacer.`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    // RUTA CORREGIDA A LA CARPETA MODELS
                    form.action = 'models/eliminar_instrumento.php';
                    
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    
                    form.appendChild(inputId);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }

    // 7. Lógica para los botones de la tabla (Editar - Llenar modal)
    const botonesEditar = document.querySelectorAll('.btn-editar');
    if (botonesEditar.length > 0) {
        botonesEditar.forEach(boton => {
            boton.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_serie').value = this.getAttribute('data-serie');
                document.getElementById('edit_nombre').value = this.getAttribute('data-nombre');
                document.getElementById('edit_categoria').value = this.getAttribute('data-categoria');
                document.getElementById('edit_estado').value = this.getAttribute('data-estado');
                
                const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
                modal.show();
            });
        });
    }
});

// ==========================================
// FUNCIONES GLOBALES (Llamadas desde el HTML)
// ==========================================

function actualizarPrefijo() {
    const categoria = document.getElementById('selectCategoria').value;
    const inputSerie = document.getElementById('inputSerie');
    const ayudaSerie = document.getElementById('ayudaSerie');
    
    let prefijo = '';
    if (categoria === 'Medidores') prefijo = 'MD-';
    else if (categoria === 'Detectores') prefijo = 'DT-';
    else if (categoria === 'Calibradores') prefijo = 'CP-';
    else if (categoria === 'END') prefijo = 'EN-';

    if (prefijo) {
        ayudaSerie.textContent = `Formato requerido: ${prefijo}001013`;
        ayudaSerie.className = 'text-primary fw-bold';
        inputSerie.placeholder = `Ej: ${prefijo}001013`;
        if (inputSerie.value === '' || inputSerie.value.length <= 3) {
            inputSerie.value = prefijo; 
        }
    } else {
        ayudaSerie.textContent = 'Seleccione una categoría para ver el formato.';
        ayudaSerie.className = 'text-muted';
    }
}

function cambiarFecha() {
    const estado = document.getElementById('selectEstado').value;
    
    document.getElementById('divFechaCalibracion').style.display = 'none';
    document.getElementById('divFechaUso').style.display = 'none';
    document.getElementById('divFechaDisponible').style.display = 'none';

    if (estado === 'En Calibracion') {
        document.getElementById('divFechaCalibracion').style.display = 'block';
    } else if (estado === 'En Uso') {
        document.getElementById('divFechaUso').style.display = 'block';
    } else if (estado === 'Disponible') {
        document.getElementById('divFechaDisponible').style.display = 'block';
    }
}
