<?php
session_start();
require_once __DIR__ . '/config/Database.php';

$instrumentos = [];
$totalInstrumentos = 0;
$countOK = 0;
$countAlerta = 0;
$countCritico = 0;

try {
  $pdo = Database::getInstance()->getConnection();

  // Obtener todos los instrumentos con cálculo de días restantes
  $stmt = $pdo->query("
        SELECT 
            id,
            serie,
            nombre,
            categoria,
            fecha_vencimiento_calibracion,
            DATEDIFF(fecha_vencimiento_calibracion, CURDATE()) as dias_restantes,
            estado
        FROM instrumentos
        ORDER BY dias_restantes ASC
    ");
  $instrumentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Calcular métricas
  $totalInstrumentos = count($instrumentos);

  foreach ($instrumentos as $inst) {
    $dias = (int)$inst['dias_restantes'];

    if ($dias <= 0 || $dias <= 7) {
      $countCritico++;
    } elseif ($dias <= 30) {
      $countAlerta++;
    } else {
      $countOK++;
    }
  }
} catch (PDOException $e) {
  $error = "Error al cargar datos: " . htmlspecialchars($e->getMessage());
}

// Exportar a CSV
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=reporte_calibracion_' . date('Y-m-d') . '.csv');

  $output = fopen('php://output', 'w');
  fputcsv($output, ['N° Serie', 'Instrumento', 'Última Calibración', 'Vencimiento', 'Días Restantes', 'Estado']);

  foreach ($instrumentos as $inst) {
    $dias = (int)$inst['dias_restantes'];
    if ($dias <= 0 || $dias <= 7) {
      $estado = 'Crítico';
    } elseif ($dias <= 30) {
      $estado = 'Alerta';
    } else {
      $estado = 'OK';
    }

    fputcsv($output, [
      $inst['serie'],
      $inst['nombre'],
      $inst['fecha_vencimiento_calibracion'],
      $inst['fecha_vencimiento_calibracion'],
      $dias . ' días',
      $estado
    ]);
  }
  fclose($output);
  exit;
}
?>


<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <title>Calibración y Reportes - Control Internacional Del Ecuador</title>
  <link
    href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css"
    rel="stylesheet" />
  <link href="public/css/styles.css" rel="stylesheet" />
  <script
    src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"
    crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="index.php">Control Internacional <br />
      Del Ecuador
    </a>
    <!-- Sidebar Toggle-->
    <button
      class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0"
      id="sidebarToggle"
      href="#!">
      <i class="fas fa-bars"></i>
    </button>
    <!-- Navbar Search-->
    <form
      class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
      <div class="input-group">
        <input
          class="form-control"
          type="text"
          placeholder="Buscar"
          aria-label="Search for..."
          aria-describedby="btnNavbarSearch" />
        <button class="btn btn-primary" id="btnNavbarSearch" type="button">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </form>

    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle"
          id="navbarDropdown"
          href="#"
          role="button"
          data-bs-toggle="dropdown"
          aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
        <ul
          class="dropdown-menu dropdown-menu-end"
          aria-labelledby="navbarDropdown">
          <li><a class="dropdown-item" href="#!">Configuración</a></li>
          <li><a class="dropdown-item" href="#!">Activity Log</a></li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li><a class="dropdown-item" href="#!">Salir</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <div id="layoutSidenav">
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Principal</div>
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon">
                <i class="fas fa-tachometer-alt"></i>
              </div>
              Panel Principal
            </a>

            <div class="sb-sidenav-menu-heading">Gestión Operativa</div>
            <a class="nav-link" href="instrumentos.php">
              <div class="sb-nav-link-icon">
                <i class="fas fa-toolbox"></i>
              </div>
              Inventario de Instrumentos
            </a>

            <a class="nav-link" href="asignaciones.php">
              <div class="sb-nav-link-icon">
                <i class="fas fa-clipboard-check"></i>
              </div>
              Asignaciones y Concurrencia
            </a>

            <a class="nav-link active" href="calibracion_reportes.php">
              <div class="sb-nav-link-icon">
                <i class="fas fa-chart-line"></i>
              </div>
              Calibración y Reportes
            </a>

            <div class="sb-sidenav-menu-heading">Administración</div>
            <a class="nav-link" href="login.html">
              <div class="sb-nav-link-icon">
                <i class="fas fa-sign-in-alt"></i>
              </div>
              Iniciar Sesión (Demo)
            </a>
          </div>
        </div>
        <div class="sb-sidenav-footer">
          <div class="small">Proyecto Academico:</div>
          Grupo 6
        </div>
      </nav>
    </div>
    // contenido principal
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid px-4">
          <h1 class="mt-4">Control de Calibración y Reportes</h1>
          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item active">Calibración y Reportes</li>
          </ol>

          <!-- Métricas de Calibración -->
          <div class="row">
            <div class="col-xl-3 col-md-6">
              <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                  <h5>Total Instrumentos</h5>
                  <h2 class="mb-0"><?= $totalInstrumentos ?></h2>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card bg-success text-white mb-4">
                <div class="card-body">
                  <h5>✅ Calibración OK</h5>
                  <h2 class="mb-0"><?= $countOK ?></h2>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                  <h5>⚠️ Próximos a Vencer</h5>
                  <h2 class="mb-0"><?= $countAlerta ?></h2>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                  <h5>🔴 Vencidos / Críticos</h5>
                  <h2 class="mb-0"><?= $countCritico ?></h2>
                </div>
              </div>
            </div>
          </div>

          <!-- Panel de Filtros y Exportación -->
          <div class="card shadow mb-4">
            <div
              class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="d-flex gap-2 flex-wrap">
                <input
                  type="text"
                  id="filtroBusqueda"
                  class="form-control form-control-sm"
                  placeholder=" Buscar por serie o nombre..."
                  style="max-width: 250px" />
                <select
                  id="filtroEstadoCalibracion"
                  class="form-select form-select-sm"
                  style="max-width: 220px">
                  <option value="">Todos los estados</option>
                  <option value="OK">✅ OK (Mayor a 30 días)</option>
                  <option value="Alerta">⚠️ Alerta (≤30 días)</option>
                  <option value="Critico">
                    🔴 Crítico (≤7 días o vencido)
                  </option>
                </select>
              </div>
              <a href="?exportar=csv" class="btn btn-sm btn-success">
                <i class="fas fa-file-csv me-1"></i> Exportar Reporte CSV
              </a>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table
                  class="table table-bordered table-hover"
                  id="tablaCalibracion">
                  <thead class="table-dark">
                    <tr>
                      <th>N° Serie</th>
                      <th>Instrumento</th>
                      <th>Última Calibración</th>
                      <th>Vencimiento</th>
                      <th>Días Restantes</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="cuerpoTablaCalibracion">
                    <?php if (!empty($instrumentos)): ?> <?php foreach ($instrumentos as $inst): ?> <?php $dias =
                                                                                                      (int)$inst['dias_restantes'];
                                                                                                    if (
                                                                                                      $dias <= 0 || $dias <=
                                                                                                      7
                                                                                                    ) {
                                                                                                      $estado = 'Critico';
                                                                                                      $textoEstado = '🔴 Crítico';
                                                                                                      $badgeClass = 'bg-danger';
                                                                                                      $textoDiasClass = 'text-danger
                      fw-bold';
                                                                                                    } elseif ($dias <= 30) {
                                                                                                      $estado = 'Alerta';
                                                                                                      $textoEstado = '⚠️ Alerta';
                                                                                                      $badgeClass = 'bg-warning
                      text-dark';
                                                                                                      $textoDiasClass = 'text-warning fw-bold';
                                                                                                    } else {
                                                                                                      $estado = 'OK';
                                                                                                      $textoEstado = '✅ OK';
                                                                                                      $badgeClass
                                                                                                        = 'bg-success';
                                                                                                      $textoDiasClass = '';
                                                                                                    } ?>
                        <tr data-estado="<?= $estado ?>">
                          <td>
                            <code><?= htmlspecialchars($inst['serie']) ?></code>
                          </td>
                          <td><?= htmlspecialchars($inst['nombre']) ?></td>
                          <td><?= $inst['fecha_vencimiento_calibracion'] ?></td>
                          <td><?= $inst['fecha_vencimiento_calibracion'] ?></td>
                          <td class="<?= $textoDiasClass ?>">
                            <?= $dias ?>
                            días
                          </td>
                          <td>
                            <span class="badge <?= $badgeClass ?>"><?= $textoEstado ?></span>
                          </td>
                          <td>
                            <button class="btn btn-sm btn-outline-primary">
                              <i class="fas fa-eye"></i> Ver
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?> <?php else: ?>
                      <tr>
                        <td colspan="7" class="text-center text-muted">
                          No hay instrumentos registrados
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </main>
      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div
            class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; UAE 2026</div>
            <div>
              <a href="#">Politica de Privacidad</a>
              &middot;
              <a href="#">Terminos &amp; Condiciones</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>
  <script src="js/scripts.js"></script>

  <script
    src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
    crossorigin="anonymous"></script>
  <script src="js/datatables-simple-demo.js"></script>
  <script src="public/js/calibracion_reportes.js"></script>
  <script>
    window.addEventListener("DOMContentLoaded", (event) => {
      const sidebarToggle = document.body.querySelector("#sidebarToggle");
      if (sidebarToggle) {
        sidebarToggle.addEventListener("click", (event) => {
          event.preventDefault();
          document.body.classList.toggle("sb-sidenav-toggled");
          localStorage.setItem(
            "sb|sidebar-toggle",
            document.body.classList.contains("sb-sidenav-toggled"),
          );
        });
      }
    });
  </script>
  <script>
    // Filtrado en tiempo real
    document.addEventListener("DOMContentLoaded", function() {
      const filtroBusqueda = document.getElementById("filtroBusqueda");
      const filtroEstado = document.getElementById("filtroEstadoCalibracion");
      const tabla = document.getElementById("tablaCalibracion");
      const filas = tabla.querySelectorAll("tbody tr[data-estado]");

      function aplicarFiltros() {
        const texto = filtroBusqueda.value.toLowerCase();
        const estado = filtroEstado.value;

        filas.forEach((fila) => {
          const serie = fila.cells[0].textContent.toLowerCase();
          const nombre = fila.cells[1].textContent.toLowerCase();
          const estadoFila = fila.dataset.estado;

          const coincideTexto =
            serie.includes(texto) || nombre.includes(texto);
          const coincideEstado = estado === "" || estadoFila === estado;

          fila.style.display = coincideTexto && coincideEstado ? "" : "none";
        });
      }

      filtroBusqueda.addEventListener("input", aplicarFiltros);
      filtroEstado.addEventListener("change", aplicarFiltros);
    });
  </script>
</body>

</html>