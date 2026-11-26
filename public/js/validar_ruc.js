/**
 * validar_ruc.js
 * Validación en tiempo real de RUC/Cédula (Solo algorítmica, sin SRI).
 */
(function () {
    'use strict';

    const CONFIG = {
        inputId: 'rucInput',
        feedbackId: 'rucFeedback',
        formId: 'checkoutForm',
        endpoint: 'controllers/validar_ruc.php', // <-- Ruta corregida
        debounceMs: 400
    };

    let rucInput, feedback, form;
    let debounceTimer;

    function setEstado(input, estado) {
        input.classList.remove('is-valid', 'is-invalid', 'is-loading');
        input.style.borderColor = '';
        input.style.backgroundColor = '';

        if (estado === 'valid') {
            input.style.borderColor = '#198754';
            input.style.backgroundColor = '#f8fff9';
        } else if (estado === 'invalid') {
            input.style.borderColor = '#dc3545';
            input.style.backgroundColor = '#fff8f8';
        } else if (estado === 'loading') {
            input.style.borderColor = '#ffc107';
            input.style.backgroundColor = '#fffdf5';
        }
    }

    function setFeedback(mensaje, tipo) {
        feedback.textContent = mensaje;
        feedback.className = 'small mt-1 d-block';
        const colores = { success: '#198754', error: '#dc3545', warning: '#856404' };
        feedback.style.color = colores[tipo] || '#6c757d';
    }

    async function validarRucEnBackend(ruc) {
        setEstado(rucInput, 'loading');
        setFeedback('Validando...', 'warning');

        const formData = new FormData();
        formData.append('ruc', ruc);

        try {
            const response = await fetch(CONFIG.endpoint, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}`);
            }

            const data = await response.json();
            setEstado(rucInput, 'none');

            if (data.status === 'success') {
                setEstado(rucInput, 'valid');
                setFeedback(' ' + data.message, 'success');
                // Ya NO se busca data.razon_social porque el SRI está eliminado
            } else {
                setEstado(rucInput, 'invalid');
                setFeedback(' ' + data.message, 'error');
            }
        } catch (error) {
            console.error('[validar_ruc] Error en la petición:', error);
            setEstado(rucInput, 'invalid');
            setFeedback(' Error de conexión con el validador.', 'error');
        }
    }

    function onRucInput() {
        let valor = rucInput.value.replace(/\D/g, '');
        if (valor.length > 13) valor = valor.slice(0, 13);
        rucInput.value = valor;

        setEstado(rucInput, 'none');
        setFeedback('', 'none');

        if (valor.length === 0) return;

        if (valor.length !== 13) {
            setEstado(rucInput, 'invalid');
            setFeedback('El RUC debe tener 13 dígitos.', 'error');
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            validarRucEnBackend(valor);
        }, CONFIG.debounceMs);
    }

    function onFormSubmit(e) {
        const esInvalido = rucInput.style.borderColor === 'rgb(220, 53, 69)';
        const longitudIncorrecta = rucInput.value.length !== 13;

        if (esInvalido || longitudIncorrecta) {
            e.preventDefault();
            alert('Por favor, corrija el RUC antes de confirmar el pedido.');
            rucInput.focus();
        }
    }

    function init() {
        rucInput = document.getElementById(CONFIG.inputId);
        feedback = document.getElementById(CONFIG.feedbackId);
        form = document.getElementById(CONFIG.formId);

        if (!rucInput || !feedback || !form) {
            console.warn('[validar_ruc] Elementos del DOM no encontrados.');
            return;
        }

        rucInput.addEventListener('input', onRucInput);
        form.addEventListener('submit', onFormSubmit);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();