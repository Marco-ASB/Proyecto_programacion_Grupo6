document.addEventListener("DOMContentLoaded", function() {
    const botonesEliminar = document.querySelectorAll('.btnEliminar');

    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Pedimos confirmación al usuario
            if (!confirm('¿Estás seguro de que deseas eliminar este servicio del carrito?')) {
                return;
            }

            const idServicio = this.getAttribute('data-id');
            const filaTabla = this.closest('tr'); // Encontramos la fila de la tabla

            // Efecto visual (atenuar la fila)
            filaTabla.style.opacity = '0.5';
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id_servicio', idServicio);

            fetch('ajax_carrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // Si se eliminó correctamente, recargamos la página para recalcular totales
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un error al eliminar el servicio.');
                filaTabla.style.opacity = '2';
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-trash"></i>';
            });
        });
    });
});