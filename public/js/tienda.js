document.addEventListener("DOMContentLoaded", function() {
    const botonesAgregar = document.querySelectorAll('.btnAgregar');
    const badgeCarrito = document.getElementById('contadorCarrito');

    if (botonesAgregar.length > 0) {
        botonesAgregar.forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault(); // Evita que el botón recargue la página
                
                const idServicio = this.getAttribute('data-id');
                const textoOriginal = this.innerHTML;
                
                // Animación de carga en el botón
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;

                // Preparamos los datos
                const formData = new FormData();
                formData.append('accion', 'agregar');
                formData.append('id_servicio', idServicio);

                fetch('ajax_carrito.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        // EL FIX ESTÁ AQUÍ: Actualizamos el texto del badge
                        if (badgeCarrito) {
                            badgeCarrito.textContent = data.totalItems;
                            
                            // Efecto de "rebote" para que el usuario note el cambio
                            badgeCarrito.style.transform = 'translate(-50%, -50%) scale(1.3)';
                            setTimeout(() => {
                                badgeCarrito.style.transform = 'translate(-50%, -50%) scale(1)';
                            }, 200);
                        }
                        
                        // Efecto visual de éxito en el botón
                        this.innerHTML = '<i class="fas fa-check"></i> Añadido';
                        this.classList.replace('btn-primary', 'btn-success');
                        
                        setTimeout(() => {
                            this.innerHTML = textoOriginal;
                            this.classList.replace('btn-success', 'btn-primary');
                            this.disabled = false;
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = textoOriginal;
                    this.disabled = false;
                });
            });
        });
    }
});