## Why

El panel inicial debe facilitar que cualquier usuario vuelva rapidamente a completar o revisar su ficha. Hoy el acceso a "Mi Perfil" existe en otras zonas de la aplicacion, pero no aparece como acceso rapido destacado en el dashboard.

## What Changes

- Agregar en el dashboard/panel inicial un recuadro "Mi Perfil" para todos los usuarios autenticados.
- El recuadro debe funcionar como acceso rapido adicional y no reemplazar accesos existentes.
- El recuadro debe navegar al perfil propio editable (`/profile`).
- El recuadro debe mostrar una barra de porcentaje de completitud del perfil.
- El porcentaje de completitud debe venir del payload de dashboard o de una fuente central equivalente, para evitar calculos divergentes en UI.

## Capabilities

### New Capabilities

- `dashboard-profile-card`: Define el acceso rapido "Mi Perfil" en el dashboard, incluyendo link al perfil editable y barra de completitud.

### Modified Capabilities

- Ninguna.

## Impact

- Backend:
  - `DashboardController` y respuesta de `GET /api/dashboard` para incluir completitud de perfil.
  - Posible helper/servicio de calculo de completitud si se quiere reutilizar fuera del dashboard.
- Frontend:
  - `DashboardPage`, `dashboard.ts` y estilos de dashboard.
  - Tests de dashboard para asegurar visibilidad del recuadro y link a `/profile`.
- UX:
  - El dashboard gana una tarjeta adicional para todos los roles: usuario comun, Admin de Taller y Superadmin.
