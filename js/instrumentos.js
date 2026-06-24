// Datos de prueba (Mock Data)
var instrumentos = [
    { serie: "UT-001", nombre: "Medidor Espesor Olympus", categoria: "Medidores", estado: "Disponible", vencimiento: "2026-12-15" },
    { serie: "DG-002", nombre: "Detector Gases RAE", categoria: "Detectores", estado: "En Uso", vencimiento: "2025-11-20" },
    { serie: "CP-003", nombre: "Calibrador Fluke 718", categoria: "Calibradores", estado: "Disponible", vencimiento: "2025-10-01" },
    { serie: "END-004", nombre: "Kit Líquidos Penetrantes", categoria: "END", estado: "En Calibracion", vencimiento: "2025-09-15" }
];

function dibujarTabla(lista) {
    var cuerpoTabla = document.getElementById("cuerpoTablaInstrumentos");
    cuerpoTabla.innerHTML = "";
    for (var i = 0; i < lista.length; i++) {
        var inst = lista[i];
        var colorBadge = "bg-secondary";
        if (inst.estado === "Disponible") colorBadge = "bg-success";
        else if (inst.estado === "En Uso") colorBadge = "bg-warning text-dark";
        else if (inst.estado === "En Calibracion") colorBadge = "bg-info text-dark";

        cuerpoTabla.innerHTML += `
            <tr>
                <td><code>${inst.serie}</code></td>
                <td>${inst.nombre}</td>
                <td>${inst.categoria}</td>
                <td><span class="badge ${colorBadge}">${inst.estado}</span></td>
                <td>${inst.vencimiento}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    }
}

function aplicarFiltros() {
    var textoBusqueda = document.getElementById("filtroBusqueda").value.toLowerCase();
    var estadoSeleccionado = document.getElementById("filtroEstado").value;
    var filtrados = instrumentos.filter(function(inst) {
        var coincideTexto = inst.serie.toLowerCase().includes(textoBusqueda) || 
                            inst.nombre.toLowerCase().includes(textoBusqueda);
        var coincideEstado = estadoSeleccionado === "" || inst.estado === estadoSeleccionado;
        return coincideTexto && coincideEstado;
    });
    dibujarTabla(filtrados);
}

document.addEventListener("DOMContentLoaded", function() {
    dibujarTabla(instrumentos);
    document.getElementById("filtroBusqueda").addEventListener("input", aplicarFiltros);
    document.getElementById("filtroEstado").addEventListener("change", aplicarFiltros);

    document.getElementById("formNuevoInstrumento").addEventListener("submit", function(e) {
        e.preventDefault();
        alert("✅ Instrumento registrado correctamente (Simulación Frontend)");
        this.reset();
    });
});