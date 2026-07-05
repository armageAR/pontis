## Why

La pantalla de Hermanos expone datos administrativos sensibles y hoy comparte parte del flujo con capacidades de administración de taller. Es necesario reforzar que solo el Superadmin pueda verla y mejorar sus filtros, ordenamiento y legibilidad para operar listados grandes sin ambigüedad.

## What Changes

- Reforzar el acceso a la pantalla de Hermanos de administración para que solo el Superadmin pueda cargarla, navegarla y consultar su payload administrativo.
- Reemplazar el filtro actual de Taller por el selector existente que permite escribir y seleccionar un Taller por nombre o número.
- Agregar filtro por Provincia para traer Hermanos de la provincia seleccionada, combinable con búsqueda, Taller, rol de Taller y estado.
- Reordenar la tabla con columnas: Apellido/s, Nombre/s, Email, Talleres, Estado y Acciones.
- Permitir ordenar haciendo click en el encabezado de cada columna visible, manteniendo filtros y paginación coherente.
- Corregir la línea divisoria entre renglones para que sea continua y quede alineada en todas las celdas.

## Capabilities

### New Capabilities

- `superadmin-hermanos-screen`: Cubre el acceso exclusivo del Superadmin a la pantalla administrativa de Hermanos, sus filtros, columnas, ordenamiento y presentación tabular.

### Modified Capabilities

- None.

## Impact

- Frontend: pantalla de administración/Hermanos, filtros de usuarios, tabla de usuarios, estilos de tabla y guardas de ruta/estado.
- Backend/API: endpoint administrativo de usuarios, filtros `workshop_id` y `province`, sort whitelist, payload de usuario administrativo si requiere apellido/s y provincia.
- Seguridad: controles server-side obligatorios para impedir acceso de Admin de Taller o usuarios comunes a esta pantalla y su payload.
- Tests: cobertura de autorización, filtros combinados, ordenamiento por columnas visibles y rendering de tabla sin divisores desalineados.
