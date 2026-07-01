## Context

Pontis ya modela la relacion Hermano-Taller en `user_workshop` con estados como `active`, `pending`, `rejected` y `correction_requested`, indicador `is_principal` y flujo de aprobacion de solicitudes de ingreso. Tambien existe `/my-workshops`, aunque hoy devuelve principalmente Talleres activos para otros usos del perfil.

La nueva seccion "Mis Talleres" en el perfil debe reunir ese comportamiento en un lugar visible para el usuario: listar pertenencias, mostrar solicitudes pendientes, iniciar una nueva solicitud, salir de un Taller y elegir el Taller principal.

## Goals / Non-Goals

**Goals:**

- Mostrar en el perfil una tabla de Talleres propios y solicitudes pendientes.
- Reutilizar el flujo existente de solicitud/aprobacion de ingreso a Taller.
- Permitir iniciar una solicitud desde un modal con busqueda por nombre o numero.
- Permitir salir de un Taller no principal con confirmacion.
- Al salir, eliminar tambien los cargos del usuario asociados a ese Taller.
- Permitir marcar un Taller activo como principal con confirmacion cuando reemplaza a otro.
- Garantizar que un usuario tenga como maximo un Taller principal activo y que no pueda eliminar su Taller principal.

**Non-Goals:**

- No crear un flujo nuevo de aprobacion paralelo.
- No permitir que el usuario apruebe su propia solicitud.
- No permitir salir del unico Taller activo o del Taller principal desde esta propuesta.
- No cambiar el catalogo o administracion general de Talleres.
- No rediseñar la pantalla general de Talleres.

## Decisions

1. Crear una API de perfil para membresias completas o ampliar `/my-workshops`.

   La seccion necesita Talleres activos y solicitudes pendientes con `status`, `requested_by_user`, `is_principal` y rol. Si `/my-workshops` se mantiene para desplegables de Talleres activos, conviene agregar un endpoint especifico como `/profile/workshops` para no romper consumidores existentes.

   Alternativa considerada: reutilizar `/admin/workshops` filtrado. Se descarta porque la seccion del perfil necesita una vista centrada en la relacion del usuario, no una lista administrativa paginada.

2. Reutilizar el endpoint de join existente para crear solicitudes.

   El modal de perfil debe buscar Talleres activos por nombre o numero y llamar a la misma accion que la pantalla de Talleres usa para solicitar ingreso. La respuesta debe refrescar la tabla de "Mis Talleres" y mostrar estado pendiente.

   Alternativa considerada: crear una solicitud separada de perfil. Se descarta para evitar dos fuentes de verdad de aprobacion.

3. Bloquear salida del Taller principal.

   El backend debe rechazar la eliminacion de una relacion marcada como `is_principal`. La UI debe ocultar o deshabilitar la accion de salir para ese Taller y explicar el motivo.

   Alternativa considerada: permitir salir y elegir otro principal en el mismo flujo. Se descarta porque aumenta el riesgo de dejar al usuario sin Taller principal ante errores parciales.

4. Hacer atomico el cambio de Taller principal.

   El backend debe actualizar la relacion en una transaccion: quitar `is_principal` del Taller actual del usuario y marcar el nuevo Taller activo como principal. No se debe permitir marcar como principal una relacion pendiente, rechazada, inactiva o inexistente.

   Alternativa considerada: dos llamadas separadas desde frontend. Se descarta porque podria dejar dos principales o ninguno si falla una llamada.

5. Al salir de un Taller, eliminar cargos asociados a ese Taller.

   La accion de salir debe eliminar la relacion Hermano-Taller y los registros de cargos del usuario para ese Taller. Dado que el usuario pidio borrar cargos, esta propuesta lo define como limpieza directa de `user_positions` asociados.

   Alternativa considerada: cerrar cargos historicamente con fecha de fin. Se descarta para esta propuesta porque el requerimiento explicito es borrar cargos asociados al salir del Taller.

6. Usar iconos con confirmaciones claras.

   La tabla debe exponer acciones con iconos: salir del Taller y marcar como principal. Las acciones destructivas o que cambian principal requieren confirmacion con nombres concretos de Talleres.

## Risks / Trade-offs

- Riesgo: borrar cargos puede perder historial institucional. Mitigacion: limitar la accion a cargos asociados al Taller abandonado y considerar auditoria si ya existe para acciones sensibles.
- Riesgo: estados pendientes no aparezcan si se usa una relacion que filtra solo `active`. Mitigacion: usar `workshopMemberships()` o consulta equivalente sin filtro de estado para la seccion del perfil.
- Riesgo: usuario queda sin Taller principal. Mitigacion: bloquear salida del principal y hacer cambio de principal en transaccion.
- Riesgo: solicitudes duplicadas. Mitigacion: reutilizar la restriccion unica `user_id + workshop_id` y reactivar solicitudes rechazadas como hace el flujo actual.

## Migration Plan

1. Revisar usuarios con cero o mas de un `is_principal` activo y corregir inconsistencias antes de exponer la accion.
2. Agregar endpoint de perfil para listar membresias activas y pendientes con estado y principal.
3. Agregar endpoint para cambiar Taller principal de forma atomica.
4. Ajustar salida de Taller para rechazar Taller principal y eliminar cargos del usuario en ese Taller.
5. Crear la seccion "Mis Talleres" en el perfil con tabla, modal de solicitud, iconos y confirmaciones.
6. Reutilizar el flujo de solicitud de ingreso y refrescar estado pendiente en la tabla.
7. Agregar pruebas backend y verificacion frontend.

## Open Questions

- Confirmar si la accion de salir debe borrar cargos fisicamente o cerrar cargos historicos. Esta propuesta sigue el requerimiento de borrar cargos asociados.
- Confirmar si se deben listar tambien relaciones rechazadas o con correccion solicitada. La propuesta requiere activas y pendientes; puede extenderse si producto quiere mostrar estados historicos.
