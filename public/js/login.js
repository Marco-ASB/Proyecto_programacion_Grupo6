// public/js/login.js

document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/Ocultar contraseña
    const checkboxMostrar = document.getElementById('inputRememberPassword');
    const inputPassword = document.getElementById('inputPassword');
    
    if (checkboxMostrar && inputPassword) {
        checkboxMostrar.addEventListener('change', function() {
            inputPassword.type = this.checked ? 'text' : 'password';
        });
    }
    
    // Validación de formulario (opcional)
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('inputEmail').value.trim();
            const password = inputPassword.value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Por favor, complete todos los campos');
            }
        });
    }
});