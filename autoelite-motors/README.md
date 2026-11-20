# AutoElite Motors — sistema de gestión

Pantallas en **HTML puro** (`*.html`); PHP se usa solo en `api/` y `config/` para conectarse a MySQL y ejecutar las operaciones (PDO, consultas preparadas, token CSRF).

## Estructura
- `*.html`: páginas (panel, clientes, ventas, recibo, inventario, reparaciones, empleados, soporte) y `admin/index.html`.
- `public/css/` y `public/js/`: estilos y un script por página.
- `api/*.php`: endpoints JSON. `config/`: lectura de `.env`, sesión y conexión.
- `database/concesionaria_completa.sql`: la base de datos completa.

## Requisitos
XAMPP con Apache, MariaDB y PHP 8.1 o superior (extensiones `pdo_mysql` y `mbstring`).

## Instalación en XAMPP
1. Copiá la carpeta `autoelite-motors` en `C:\xampp\htdocs\`.
2. Iniciá Apache y MySQL desde XAMPP.
3. En `http://localhost/phpmyadmin/` → pestaña *Importar*, importá **un solo archivo**: `database/concesionaria_completa.sql`. Crea la base `concesionaria` con todas las tablas y los datos de prueba (8 empleados, 10 vehículos y 3 clientes ficticios).
4. Revisá `.env` (host, puerto, base, usuario y contraseña). En XAMPP, `root` suele no tener contraseña.
5. Abrí `http://localhost/autoelite-motors/`.

*Base ya existente con datos:* no importes el archivo anterior (borraría/duplicaría tablas). La aplicación agrega sola las columnas nuevas la primera vez que se usa y no modifica ni borra datos.

## Acceso a Administración (`/admin`)
`http://localhost/autoelite-motors/admin/` — usuario `admin1234`, contraseña `admin1234`. Cambiala en `config/admin.php` antes de publicar el sistema.

## Módulos
- **Panel principal** con métricas de la base.
- **Clientes:** alta, búsqueda (nombre, DNI o teléfono), edición y baja. DNI de 7 u 8 dígitos, sin repetir.
- **Inventario:** alta, búsqueda, edición y baja. Se identifica por **patente**. La garantía solo aplica a vehículos nuevos.
- **Ventas y recibos:** contado o crédito (cuotas y recargo en pesos), impuestos en pesos, cambio de estado del vehículo y recibo imprimible / PDF desde el navegador.
- **Reparaciones:** cliente, vehículo y mecánico; fecha de ingreso y de salida; cambio de estado.
- **Empleados:** consulta por área (asesores de ventas, gerentes financieros, asesores de servicio y mecánicos) con DNI, edad, dirección y horario.
- **Administración:** alta, edición, baja y listado de empleados (requiere inicio de sesión).
- **Soporte** con diagnóstico básico.

## Cambios en la base de datos respecto del esquema original
| Tabla | Cambio |
|---|---|
| `Cliente` | `CURP` pasa a llamarse `DNI`. `RFC` y `Preferencias_CRM` ya no se usan. |
| `Asesor_Ventas`, `Gerente_FI`, `Asesor_Servicio`, `Mecanico` | Se agregan `DNI` (único), `Edad`, `Direccion`, `Horario_Trabajo`. Las columnas anteriores ya no se usan. |
| `Orden_Reparacion` | `ID_Asesor_Srv` admite `NULL` (ya no se pide asesor de servicio). |
| `Venta` | Se agregan `Cuotas` y `Recargo`. |

Las columnas que dejaron de usarse no se borran (no se pierde información). La columna `VIN` guarda la patente.

## Notas
- El recibo es un comprobante **interno**, no una factura fiscal.
- Los borrados de registros relacionados (clientes, vehículos, empleados con ventas u órdenes) son rechazados a propósito para proteger el historial.
- Datos de prueba: todos ficticios. Si no los querés, borrá desde la aplicación los registros de ejemplo.

## Seguridad
- `.env` y las carpetas internas están protegidos por `.htaccess` (Apache).
- En un entorno público: `APP_DEBUG=false`, usuario MySQL con privilegios mínimos, HTTPS y credenciales de admin propias. Solo `/admin` tiene login; las demás pantallas no.

## Problemas frecuentes
- **No se pudo conectar:** MySQL apagado o `.env` incorrecto (la base debe llamarse `concesionaria`).
- **Tabla inexistente:** falta importar `database/concesionaria_completa.sql`.
- **PDO driver not found:** habilitá `pdo_mysql` en el `php.ini` de XAMPP y reiniciá Apache.
