document.addEventListener("DOMContentLoaded", function() {
    const botonesVer = document.querySelectorAll('.btn-ver-calibracion');
    
    if (botonesVer.length > 0) {
        botonesVer.forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Capturamos los datos de la fila
                const id = this.getAttribute('data-id');
                const serie = this.getAttribute('data-serie');
                const nombre = this.getAttribute('data-nombre');
                const fecha = this.getAttribute('data-fecha');
                
                // Llenamos el modal
                document.getElementById('calib_id').value = id;
                document.getElementById('calib_serie').textContent = serie;
                document.getElementById('calib_nombre').textContent = nombre;
                document.getElementById('calib_fecha').value = fecha;
                
                // Mostramos el modal
                const modal = new bootstrap.Modal(document.getElementById('modalCalibracion'));
                modal.show();
            });
        });
    }
});
