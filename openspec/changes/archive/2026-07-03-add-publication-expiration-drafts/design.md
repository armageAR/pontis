## Context

Mis Publicaciones agrupa publicaciones propias de servicios/ofrecimientos y necesidades. Actualmente los formularios permiten elegir estado directamente y los modelos existentes ya manejan estados, categoria, titulo y descripcion, pero no hay un contrato de vigencia con fecha de publicacion y vencimiento.

Este cambio agrega ciclo de vida temporal y acciones explicitas. El usuario decide si publica o guarda borrador desde el formulario, puede suspender una publicacion activa, y puede republicar una publicacion vencida reutilizando los datos anteriores.

## Goals / Non-Goals

**Goals:**
- Agregar vigencia obligatoria para publicaciones activas con opciones de 10, 30, 60 y 90 dias.
- Garantizar que ninguna publicacion pueda vencer a mas de 90 dias de su fecha de publicacion.
- Mantener vencidas en Mis Publicaciones con estado "Vencida" y ocultarlas de listados publicados o busquedas.
- Agregar republicacion de vencidas precargando el formulario y permitiendo cambios antes de publicar.
- Reemplazar el dropdown de estado del formulario por acciones: Publicar, Guardar, Cancelar, Suspender y Republicar.
- Aplicar las mismas reglas a "Lo que ofrezco" y "Lo que necesito".
- Poblar categorias desde el catalogo existente de categorias.
- Requerir titulo y descripcion.

**Non-Goals:**
- No agregar pagos, contratacion, chat ni flujo comercial.
- No crear categorias nuevas desde el formulario de publicacion.
- No agregar aprobacion administrativa previa.
- No eliminar historicamente publicaciones vencidas o suspendidas.

## Decisions

1. Usar campos explicitos de fechas en cada tipo de publicacion.

   Rationale: servicios y necesidades existen como modelos separados. Agregar `published_at` y `expires_at` en ambos permite filtrar y mostrar estados sin introducir una abstraccion nueva de publicaciones.

   Alternative considered: crear una tabla comun `publications`. Se descarta para este cambio porque aumenta el alcance y obligaria a migrar servicios y necesidades a una jerarquia nueva.

2. Derivar la visibilidad publica desde estado y vencimiento.

   Rationale: una publicacion solo debe mostrarse fuera de la pantalla propia si esta activa y `expires_at` es posterior al momento actual. Las vencidas y suspendidas siguen existiendo para el usuario, pero no se publican.

   Alternative considered: ejecutar un proceso que cambie fisicamente el estado a `expired`. Puede usarse como optimizacion, pero el filtrado por fecha debe existir igual para evitar depender de un job.

3. Mantener un estado persistido para `draft`, `active`, `suspended` y `expired`.

   Rationale: el usuario necesita ver y filtrar su propio contenido por estado. `expired` puede derivarse de `expires_at`, pero persistirlo o normalizarlo en respuestas simplifica la UI y los tests.

   Alternative considered: calcular siempre "Vencida" solo en frontend. Se descarta porque los listados y busquedas del backend tambien deben excluir vencidas.

4. La republicacion actualiza la misma publicacion.

   Rationale: el requerimiento indica que el boton Publicar cambia fecha de publicacion y vencimiento. Mantener el mismo registro evita duplicados y preserva la relacion con ediciones previas.

   Alternative considered: clonar una publicacion vencida como nueva. Se descarta porque generaria duplicacion visible y complicaria historial.

5. El formulario no tendra dropdown de estado.

   Rationale: el estado se controla por acciones. Publicar activa, Guardar deja borrador, Cancelar no persiste, Suspender saca del estado publicado, y Republicar reactiva una vencida.

   Alternative considered: conservar dropdown para administradores. Se descarta para Mis Publicaciones porque el usuario pidio que no este.

## Risks / Trade-offs

- Registros existentes sin `published_at` o `expires_at` pueden no encajar en el nuevo filtro -> Mitigar con migracion que asigne fechas razonables o trate activos sin vencimiento como vencidos/legacy segun decision de implementacion.
- Si solo se marca vencimiento por job, puede haber publicaciones vencidas visibles hasta que corra el job -> Mitigar aplicando filtro `expires_at > now()` en consultas publicadas.
- Estados actuales como `paused`, `open` o `searching` pueden solaparse con los nuevos estados -> Mitigar normalizando el ciclo de vida de publicaciones propias a `draft`, `active`, `suspended`, `expired` y mapeando estados legacy.
- Republicar desde una ventana de alta puede confundirse con crear duplicado -> Mitigar manteniendo copy de accion "Republicar" y usando update del registro existente.
- La obligatoriedad de descripcion puede romper formularios existentes que la trataban como opcional -> Mitigar actualizando validaciones frontend y backend al mismo tiempo.
