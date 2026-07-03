## Why

Existe un conflicto entre docs y codigo: `permissions.md` dice que el Hermano no puede cambiar su grado ni cargar ascensos sin validacion, pero el codigo actual (cambio archivado `improve-profile-degrees-positions`) permite autogestionar grados y cargos desde el perfil sin ninguna revision.

Decision tomada: modelo hibrido. El Hermano declara sus grados y cargos, y un Admin de Taller (del Taller correspondiente) o el Superadmin los valida para que queden firmes. Esto conserva la comodidad de la autogestion sin perder el control institucional que piden los docs.

## What Changes

- Agregar estado de validacion a grados y cargos declarados por el propio Hermano: `declarado` (pendiente de validacion) y `validado`.
- El Hermano puede seguir cargando, editando y eliminando sus grados y cargos mientras esten en estado declarado.
- Un grado o cargo declarado con Taller asociado puede ser validado por un Admin de ese Taller o por el Superadmin; sin Taller asociado, solo por Superadmin.
- Un grado o cargo validado queda firme: el Hermano ya no puede editarlo ni eliminarlo directamente (requiere intervencion administrativa).
- Las cargas hechas directamente por un Admin de Taller o Superadmin nacen validadas.
- Visibilidad del estado: el propio Hermano y los administradores ven si un registro esta declarado o validado; el resto de la comunidad ve el dato sin distincion de estado (no exponer flujos internos).
- Notificar al Hermano cuando su declaracion es validada o rechazada, y al Admin de Taller cuando hay declaraciones pendientes de sus Talleres.
- Registrar la validacion en auditoria (se integra con el proposal de auditoria funcional).
- Actualizar `docs/project/permissions.md`, `workflows.md` y `decisions.md` para reflejar el modelo hibrido (los docs actuales dicen que solo el admin gestiona; el codigo actual permite autogestion total; ambos cambian).
- Datos existentes: los grados y cargos ya cargados se migran como validados para no invalidar el historial actual.

## Capabilities

### New Capabilities

### Modified Capabilities
- `profile-degrees-positions`: Se agrega el ciclo declarado/validado, permisos de validacion por Taller y restricciones de edicion post-validacion.

## Impact

- Migracion para el estado de validacion en `user_degrees` y `user_positions` (+ quien y cuando valido).
- `DegreeController` y `UserPositionController`: reglas de edicion segun estado; endpoints de validacion para admins.
- Frontend: indicador de estado en el perfil, acciones de validacion para admins, notificaciones.
- Actualizacion de los tres documentos de proyecto.
- Tests de permisos por rol y por Taller, restricciones post-validacion y migracion de datos existentes.
