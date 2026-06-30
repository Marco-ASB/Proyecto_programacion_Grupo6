var instrumentosCal = [
    { serie: "UT-001", nombre: "Medidor Espesor Olympus", ultima: "2024-12-15", vencimiento: "2025-12-15" },
    { serie: "DG-002", nombre: "Detector Gases RAE", ultima: "2024-11-20", vencimiento: "2025-11-20" },
    { serie: "CP-003", nombre: "Calibrador Fluke 718", ultima: "2024-10-01", vencimiento: "2025-10-01" },
    { serie: "END-004", nombre: "Kit Líquidos Penetrantes", ultima: "2024-09-15", vencimiento: "2025-09-15" },
    { serie: "ME-005", nombre: "Multímetro Fluke 87-V", ultima: "2023-08-10", vencimiento: "2024-08-10" }
];

function procesarDatos() {
    var hoy = new Date();
    var resultados = [];
    for (var i = 0; i < instrumentosCal.length; i++) {
        var inst = instrumentosCal[i];
        var fechaVenc = new Date(inst.vencimiento);
        var diferenciaMs = fechaVenc - hoy;
        var diasRestantes = Math.ceil(diferenciaMs / (1000 * 60 * 60 * 24));
        var estado = "OK";
        if (diasRestantes <= 0) estado = "Critico";
        else if (diasRestantes <= 7) estado = "Critico";
        else if (diasRestantes <= 30) estado = "Alerta";
        resultados.push({ serie: inst.serie, nombre: inst.nombre, ultima: inst.ultima, vencimiento: inst.vencimiento, dias: diasRestantes, estado: estado });
    }
    return resultados;
}

function renderizar(data) {
    var tbody = document.getElementById("cuerpoTablaCalibracion");
    tbody.innerHTML = "";
    var countOK = 0, countAlerta = 0, countCritico = 0;
    for (var i = 0; i < data.length; i++) {
        var d = data[i];
        if (d.estado === "OK") countOK++;
        else if (d.estado === "Alerta") countAlerta++;
        else countCritico++;

        var badgeColor = d.estado === "OK" ? "bg-success" : (d.estado === "Alerta" ? "bg-warning text-dark" : "bg-danger");
        var textoEstado = d.estado === "OK" ? "✅ OK" : (d.estado === "Alerta" ? "⚠️ Alerta" : "🔴 Crítico");
        var claseTextoDias = d.dias <= 7 ? "text-danger fw-bold" : (d.dias <= 30 ? "text-warning fw-bold" : "");

        tbody.innerHTML += `
            <tr>
                <td><code>${d.serie}</code></td>
                <td>${d.nombre}</td>
                <td>${d.ultima}</td>
                <td>${d.vencimiento}</td>
                <td class="${claseTextoDias}">${d.dias} días</td>
                <td><span class="badge ${badgeColor}">${textoEstado}</span></td>
                <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> Ver</button></td>
            </tr>
        `;
    }
    document.getElementById("count-total").textContent = data.length;
    document.getElementById("count-ok").textContent = countOK;
    document.getElementById("count-alerta").textContent = countAlerta;
    document.getElementById("count-critico").textContent = countCritico;
}

function aplicarFiltrosCalibracion() {
    var texto = document.getElementById("filtroBusqueda").value.toLowerCase();
    var estadoFiltro = document.getElementById("filtroEstadoCalibracion").value;
    var datosProcesados = procesarDatos();
    var filtrados = datosProcesados.filter(function(d) {
        var coincideTexto = d.serie.toLowerCase().includes(texto) || d.nombre.toLowerCase().includes(texto);
        var coincideEstado = estadoFiltro === "" || d.estado === estadoFiltro;
        return coincideTexto && coincideEstado;
    });
    renderizar(filtrados);
}

document.getElementById("btnExportarCSV").addEventListener("click", function() {
    var datos = procesarDatos();
    var csv = "Serie,Instrumento,Ultima Calibracion,Vencimiento,Dias Restantes,Estado\n";
    for (var i = 0; i < datos.length; i++) {
        var d = datos[i];
        csv += d.serie + ',"' + d.nombre + '",' + d.ultima + ',' + d.vencimiento + ',' + d.dias + ',' + d.estado + '\n';
    }
    var blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    var url = URL.createObjectURL(blob);
    var enlace = document.createElement("a");
    enlace.href = url;
    enlace.download = "reporte_calibracion.csv";
    enlace.click();
    URL.revokeObjectURL(url);
});

document.addEventListener("DOMContentLoaded", function() {
    renderizar(procesarDatos());
    document.getElementById("filtroBusqueda").addEventListener("input", aplicarFiltrosCalibracion);
    document.getElementById("filtroEstadoCalibracion").addEventListener("change", aplicarFiltrosCalibracion);
});