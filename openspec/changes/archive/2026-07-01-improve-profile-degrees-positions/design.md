## Context

Pontis V1 conserva historial de grados masonicos y cargos. Los cargos son contextuales a un Taller y pueden finalizar sin que empiece otro cargo. Los grados, en cambio, representan una progresion institucional lineal: Aprendiz, luego Compañero, luego Maestro.

La pagina de perfil actualmente permite agregar y editar grados y cargos con un selector de Taller basado en busqueda. Tambien permite cargar fecha de fin en grados. Esa experiencia no refleja correctamente dos reglas de dominio:

- Un cargo o grado asociado a Taller debe usar un Taller al que el Hermano pertenece.
- Un grado no finaliza por una fecha manual independiente; deja de ser vigente cuando inicia el siguiente grado.

## Goals / Non-Goals

**Goals:**

- Limitar el selector de Taller en grados y cargos a los Talleres propios del usuario.
- Mantener validacion backend para impedir Talleres ajenos aunque el frontend falle o sea manipulado.
- Modelar grados como una progresion ordenada sin saltos ni retrocesos.
- Eliminar la fecha de fin manual de grados desde el perfil.
- Mantener historial completo y derivar el grado vigente desde la ultima progresion valida.
- Conservar fecha de fin opcional en cargos.

**Non-Goals:**

- No cambiar el catalogo de cargos.
- No convertir grados en permisos administrativos globales.
- No cambiar la visibilidad de grados o cargos.
- No agregar un flujo completo de aprobacion institucional si no existe en el alcance actual.
- No cambiar la gestion de pertenencia a Talleres.

## Decisions

1. Usar un desplegable de Talleres propios para grados y cargos.

   El frontend debe poblar el selector con `/my-workshops` o la fuente equivalente ya usada por el perfil. No debe permitir busqueda global de Talleres en estos modales.

   Alternativa considerada: mantener `WorkshopPicker` con filtros. Se descarta porque sigue sugiriendo que se puede seleccionar cualquier Taller y deja mas espacio para error de usuario.

2. Validar pertenencia a Taller en backend.

   Los endpoints de grados y cargos deben aceptar `workshop_id` solo si el usuario objetivo pertenece activamente a ese Taller. Para acciones de Superadmin o Admin de Taller, la validacion debe aplicarse contra el Hermano cuyo historial se modifica, no contra el actor administrativo.

   Alternativa considerada: confiar solo en el desplegable frontend. Se descarta porque las reglas institucionales deben protegerse en API.

3. Tratar la fecha de fin de grados como derivada.

   La UI de perfil no debe pedir `end_date` para grados. El periodo de un grado termina cuando inicia el siguiente grado valido. Para respuestas de API o vistas historicas, la fecha de fin puede calcularse como la fecha de inicio del siguiente grado, o conservarse solo como dato migrado/derivado sin permitir edicion manual desde el perfil.

   Alternativa considerada: mantener `end_date` editable para grados. Se descarta porque permite estados imposibles, como un Hermano activo sin grado vigente.

4. Validar progresion estricta de grados.

   La secuencia valida es `aprendiz` -> `companero` -> `maestro`. El primer grado debe ser `aprendiz`; el siguiente solo puede avanzar un paso; no se puede saltar a Maestro, retroceder, duplicar un grado vigente ni cargar un grado con fecha anterior que rompa el orden historico.

   Alternativa considerada: permitir correcciones libres desde el perfil. Se descarta porque mezcla autogestion con correcciones institucionales sensibles. Las correcciones excepcionales deben quedar para roles administrativos autorizados o un flujo separado.

5. Mantener cargos con fecha de fin opcional.

   Los cargos si pueden terminar sin que empiece otro cargo. Por eso conservan `end_date` opcional y estado vigente derivado de ausencia de fecha de fin o reglas equivalentes.

   Alternativa considerada: aplicar el mismo modelo de progresion de grados a cargos. Se descarta porque un Hermano puede no tener cargo y puede tener cargos distintos o periodos discontinuos.

## Risks / Trade-offs

- Riesgo: datos existentes con grados fuera de secuencia o fechas de fin manuales. Mitigacion: agregar revision/migracion que detecte inconsistencias y no borre historial sin decision explicita.
- Riesgo: la UI oculte Talleres si `/my-workshops` no devuelve todos los Talleres activos del usuario. Mitigacion: usar la fuente canonica de pertenencias activas y cubrirla con pruebas.
- Riesgo: bloquear correcciones legitimas de historial. Mitigacion: diferenciar acciones normales del perfil de correcciones administrativas autorizadas.
- Riesgo: cambiar `end_date` de grados afecte vistas existentes. Mitigacion: mantener compatibilidad de respuesta calculando el fin del periodo cuando la interfaz publica/historica lo necesite.

## Migration Plan

1. Auditar registros actuales de grados con `end_date`, saltos, duplicados, retrocesos o usuarios activos sin grado vigente.
2. Agregar o ajustar validaciones backend para pertenencia a Taller y progresion de grados.
3. Actualizar los endpoints para ignorar/rechazar `end_date` manual en grados desde el perfil.
4. Actualizar frontend para reemplazar `WorkshopPicker` por un desplegable de Talleres propios en grados y cargos.
5. Quitar el campo "Fecha de fin" del modal de grados y ajustar tipos/payloads.
6. Mantener "Fecha de fin" en cargos.
7. Agregar pruebas backend y verificacion frontend de los casos principales.

## Open Questions

- Confirmar si el usuario comun puede seguir agregando grados/cargos desde su perfil o si estas acciones deben quedar restringidas a roles administrativos. La propuesta define las reglas cuando la accion esta disponible, pero la autorizacion final debe seguir el modelo institucional de Pontis.
- Confirmar si las correcciones historicas excepcionales de grados se implementan ahora o quedan para un cambio separado.
