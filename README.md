## 1. Qué trae la aplicación

| Módulo | ¿Qué hace? |
| :--- | :--- |
| **Login con roles y permisos** | Administrador, Técnico y Almacén; contraseñas cifradas con `password_hash`. |
| **Panel Principal (Dashboard)** | Visualización de métricas clave: instrumentos disponibles, en uso, calibrados y próximos a vencer. |
| **Inventario de Instrumentos** | Registro y control de equipos. Incluye validación estricta de series (prefijos por categoría), validación de duplicados en tiempo real (AJAX) y gestión de fechas de calibración. |
| **Asignaciones** | Control de salida de equipos a técnicos, con validación de concurrencia para evitar doble asignación. |
| **Calibración y Reportes** | Alertas de vencimientos, cálculo de días restantes y actualización de certificados. |
| **Tienda Pública B2B** | Catálogo de servicios técnicos (inspecciones, calibraciones) con carrito de compras dinámico. |
| **Checkout de Invitado** | Los clientes empresariales compran sin necesidad de registrarse. Incluye selección de método de pago (Tarjeta, Transferencia, Efectivo). |
| **Gestión de Pedidos B2B** | Panel administrativo para revisar compras de clientes, aprobar pedidos y generar automáticamente las asignaciones de equipos. |
| **API de datos (`datos.php`)** | Endpoint seguro (vía Token) que entrega datos en JSON o CSV de ventas, inventario y asignaciones para herramientas de BI (Power BI, Looker Studio, Tableau). |


## 2. Usuarios de prueba

Para probar el sistema, puedes utilizar las siguientes credenciales de acceso (según el rol):

| Correo Electrónico | Contraseña | Rol / Permisos |
| :--- | :--- | :--- |
| `admin@controlinternacional.com` | *(Consultar en BD)* | **Administrador** (Acceso total, aprueba pedidos B2B) |
| `maria@controlinternacional.com` | *(Consultar en BD)* | **Técnico** (Gestión de inventario y asignaciones) |
| `carlos@controlinternacional.com` | *(Consultar en BD)* | **Almacén** (Visualización de inventario) |

##  Endpoints de la API (Descarga en formato CSV por defecto)

| Recurso | URL del endpoint |
| :--- | :--- |
| **Instrumentos** | `http://atwebpages.com` |
| **Asignaciones** | `http://atwebpages.com&recurso=asignaciones` |
| **Vencimientos de calibración** *(con días restantes)* | `http://atwebpages.com&recurso=vencimientos` |
| **Ventas** | `http://atwebpages.com&recurso=ventas` |


