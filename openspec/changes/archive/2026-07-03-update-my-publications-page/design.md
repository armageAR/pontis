## Context

La ruta `/mis-publicaciones` existe en el frontend y agrupa publicaciones propias, incluyendo el flujo embebido de servicios. El backend ya expone endpoints para servicios y necesidades con estados de autorizacion, rechazo y pedido de correccion, pero el cambio solicitado define que una publicacion creada por un usuario registrado no requiere autorizacion previa ni el estado "pedir correccion".

El ajuste combina cambios de interfaz en la pagina de Mis Publicaciones con un cambio funcional en el ciclo de vida de publicaciones propias. La implementacion debe preservar la privacidad y el acceso autenticado de Pontis, pero no debe bloquear la publicacion por revision administrativa.

## Goals / Non-Goals

**Goals:**
- Cambiar el titulo del area de servicios/ofrecimientos a "Lo que ofrezco".
- Compactar los filtros de estado y cantidad por pagina.
- Reubicar la paginacion al pie del listado con selector de cantidad a la izquierda y paginas a la derecha.
- Hacer que las publicaciones creadas por usuarios registrados queden publicadas sin aprobacion administrativa previa.
- Retirar de la experiencia de publicaciones el estado y accion "pedir correccion".

**Non-Goals:**
- No crear un marketplace, pagos, carrito ni contratacion directa.
- No cambiar las reglas generales de acceso privado de Pontis para usuarios no autenticados.
- No redisenar por completo la pagina de Mis Publicaciones ni la navegacion principal.
- No introducir un motor nuevo de moderacion o revision.

## Decisions

1. Las publicaciones propias se consideraran publicables por defecto al ser creadas por un usuario registrado.

   Rationale: el requerimiento explicita que no requieren autorizacion. Mantener un estado pendiente despues de crear generaria una experiencia contradictoria.

   Alternative considered: conservar el estado `pending_authorization` solo como estado interno legacy. Se descarta para nuevas publicaciones porque seguiria condicionando UI, filtros y busqueda.

2. La accion "pedir correccion" se retirara de la UI y del flujo operativo de publicaciones.

   Rationale: si no hay autorizacion previa, una accion administrativa de correccion no forma parte del ciclo basico de publicar. Los estados existentes en datos historicos pueden tratarse como legacy hasta una migracion especifica, pero no deben ser seleccionables ni generarse desde la UI nueva.

   Alternative considered: mantener "pedir correccion" solo para administradores. Se descarta porque el requerimiento indica que no hace falta ese estado.

3. La paginacion quedara como pie del listado, separando tamano de pagina y navegacion.

   Rationale: el usuario necesita controles mas chicos y una ubicacion predecible. El selector de items por pagina a la izquierda y paginas clickeables a la derecha evita que los filtros principales se sobrecarguen.

   Alternative considered: mantener todos los controles arriba. Se descarta porque el pedido ubica explicitamente la paginacion abajo.

4. Los filtros compactos deben afectar solo densidad visual, no semantica de filtrado.

   Rationale: el cambio busca reducir tamano, no remover la capacidad de filtrar por estado o cambiar cantidad de pagina.

   Alternative considered: eliminar filtros de estado junto con estados de autorizacion. Se descarta porque pueden persistir otros estados validos como activo, pausado o rechazado segun el modelo actual.

## Risks / Trade-offs

- Datos existentes con `pending_authorization` o `requires_correction` pueden seguir apareciendo si no se migran -> Mitigar ocultando esos estados de controles nuevos y definiendo fallback visual para datos legacy.
- Endpoints legacy de autorizacion/correccion pueden quedar disponibles aunque la UI no los use -> Mitigar ajustando rutas/controladores o pruebas para asegurar que nuevas publicaciones no dependan de esos endpoints.
- La exploracion interna podria estar filtrando solo publicaciones `active` -> Mitigar alineando el estado creado por defecto con el estado visible en busquedas internas.
- Cambiar estados puede afectar seeders y tests existentes -> Mitigar actualizando factories/seeders para no crear ejemplos nuevos de "requiere correccion" en publicaciones propias.
