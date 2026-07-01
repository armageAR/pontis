## Why

El perfil del Hermano debe mostrar y permitir gestionar sus pertenencias a Talleres sin obligarlo a ir a la pantalla general de Talleres. Hoy el flujo de solicitud de ingreso existe, pero falta una seccion clara en el perfil para listar Talleres propios, ver estados pendientes, solicitar nuevas pertenencias, elegir Taller principal y salir de Talleres con reglas seguras.

## What Changes

- Agregar al perfil del usuario una seccion "Mis Talleres" con una tabla de Talleres a los que el usuario pertenece o cuya solicitud esta pendiente.
- Mostrar en la tabla informacion del Taller, estado de la relacion, indicador de Taller principal y acciones.
- Agregar accion "Agregar Taller" que abre un modal.
- En el modal, el usuario debe comenzar a escribir nombre o numero de Taller, seleccionar un resultado y luego ver/habilitar el boton "Solicitar unirse".
- Al solicitar unirse desde el perfil, reutilizar el proceso de aprobacion existente del Taller.
- Mostrar la nueva relacion en la tabla del perfil como "Pendiente de aprobacion" hasta que sea aprobada o rechazada.
- Agregar accion con icono para salir de un Taller, con confirmacion antes de eliminar la relacion.
- Al salir de un Taller, eliminar tambien los cargos del usuario asociados a ese Taller.
- No permitir salir del Taller principal.
- Agregar accion con icono para marcar un Taller como principal.
- Si ya existe otro Taller principal, mostrar confirmacion indicando que el Taller actual dejara de ser principal y el seleccionado pasara a ser principal.
- Garantizar que solo exista un Taller principal activo por usuario.

## Capabilities

### New Capabilities
- `profile-my-workshops`: Gestion de Talleres propios desde el perfil, incluyendo listado, solicitud de ingreso, salida de Taller, cargos asociados y seleccion de Taller principal.

### Modified Capabilities

## Impact

- Frontend: pagina de perfil, tabla "Mis Talleres", modal de busqueda/solicitud, iconos de accion, confirmaciones y estados.
- Backend: endpoint de mis Talleres con estados completos, reutilizacion del endpoint de solicitud de ingreso, nuevo endpoint o accion para marcar Taller principal, validacion para impedir salir del principal y limpieza de cargos al salir.
- Datos: relaciones `user_workshop`, indicador `is_principal`, estados de membresia y registros de cargos `user_positions`.
- Tests: cobertura de solicitud pendiente, salida con eliminacion de cargos, bloqueo de salida del principal y cambio atomico de Taller principal.
