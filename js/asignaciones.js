var instrumentosDisponibles = [
    { id: 1, serie: "UT-001", nombre: "Medidor Espesor Olympus" },
    { id: 3, serie: "CP-003", nombre: "Calibrador Fluke 718" }
];

var historial = [
    { id: 101, instrumento: "DG-002", tecnico: "María López", salida: "2025-11-01 08:00", devolucion: "2025-11-05 17:00", estado: "Devuelta" },
    { id: 102, instrumento: "END-004", tecnico: "Carlos Ruiz", salida: "2025-11-03 09:30", devolucion: "2025-11-10 16:00", estado: "Activa" }
];

document.addEventListener("DOMContentLoaded", function() {
    var select = document.getElementById("selectInstrumento");
    for (var i = 0; i < instrumentosDisponibles.length; i++) {
        var inst = instrumentosDisponibles[i];
        var opcion = document.createElement("option");
        opcion.value = inst.id;
        opcion.textContent = inst.serie + " - " + inst.nombre;
        select.appendChild(opcion);
    }
    dibujarHistorial();
});

function dibujarHistorial() {
    var tbody = document.getElementById("tablaHistorial");
    tbody.innerHTML = "";
    for (var i = 0; i < historial.length; i++) {
        var h = historial[i];
        var badge = h.estado === "Activa" ? "bg-warning text-dark" : "bg-success";
        tbody.innerHTML += `
            <tr>
                <td><strong>#${h.id}</strong></td>
                <td>${h.instrumento}</td>
                <td>${h.tecnico}</td>
                <td>${h.salida}</td>
                <td>${h.devolucion}</td>
                <td><span class="badge ${badge}">${h.estado}</span></td>
            </tr>
        `;
    }
}

document.getElementById("fechaSalida").addEventListener("change", function() {
    document.getElementById("fechaDevolucion").min = this.value;
});

document.getElementById("formAsignacion").addEventListener("submit", function(e) {
    e.preventDefault();
    var btn = this.querySelector("button[type='submit']");
    btn.disabled = true;
    btn.innerHTML = "⏳ Procesando...";

    setTimeout(function() {
        if (Math.random() > 0.7) {
            document.getElementById("mensajeConcurrencia").classList.remove("d-none");
            btn.disabled = false;
            btn.innerHTML = "Confirmar Asignación";
        } else {
            alert("✅ Asignación registrada. El instrumento ha sido bloqueado temporalmente.");
            document.getElementById("mensajeConcurrencia").classList.add("d-none");
            document.getElementById("formAsignacion").reset();
            btn.disabled = false;
            btn.innerHTML = "Confirmar Asignación";
            
            historial.unshift({
                id: 103, instrumento: "Nuevo Instrumento", tecnico: "Técnico Actual",
                salida: new Date().toLocaleString(), devolucion: "Pendiente", estado: "Activa"
            });
            dibujarHistorial();
        }
    }, 1200);
});