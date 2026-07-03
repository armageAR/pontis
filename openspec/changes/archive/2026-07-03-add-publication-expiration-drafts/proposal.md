## Why

Las publicaciones propias necesitan un ciclo de vida claro para evitar que ofrecimientos o necesidades viejas sigan visibles indefinidamente. Tambien se necesita que el usuario pueda preparar borradores, suspender publicaciones y republicar contenido vencido sin cargar todo de nuevo.

## What Changes

- Agregar fecha de publicacion y fecha de vencimiento a publicaciones de "Lo que ofrezco" y "Lo que necesito".
- Limitar la vigencia maxima de una publicacion a 90 dias.
- En el formulario, mostrar opciones de validez por 10, 30, 60 o 90 dias.
- Crear publicaciones en estado activa cuando el usuario elige "Publicar".
- Permitir guardar publicaciones como borrador cuando el usuario elige "Guardar".
- Permitir cancelar la creacion o edicion sin grabar cambios.
- Hacer que las publicaciones vencidas dejen de mostrarse en listados publicados o busquedas, pero permanezcan en la pantalla del usuario con estado "Vencida".
- Agregar accion para republicar una publicacion vencida, abriendo el formulario de alta con datos precargados y permitiendo modificarlos.
- Al republicar, actualizar la fecha de publicacion y vencimiento sin crear una publicacion duplicada.
- Permitir que el usuario suspenda una publicacion activa para sacarla del estado publicado.
- Permitir modificar publicaciones propias.
- Poblar el dropdown de categorias con las categorias existentes del sistema.
- Hacer obligatorios titulo y descripcion.
- Eliminar el dropdown de estado del formulario; el estado se define por las acciones Publicar, Guardar, Suspender, Vencimiento y Republicar.

## Capabilities

### New Capabilities
- `publication-lifecycle`: Cubre estados, vencimiento, republicacion y formulario de creacion/edicion para publicaciones propias de ofrecimientos y necesidades.

### Modified Capabilities

## Impact

- Modelo de datos y migraciones para servicios/necesidades o publicaciones equivalentes.
- API Laravel para crear, editar, suspender, guardar borrador, publicar y republicar publicaciones propias.
- Logica de filtrado para excluir vencidas y suspendidas de listados publicados o busquedas.
- Frontend React en Mis Publicaciones, formularios de "Lo que ofrezco" y "Lo que necesito", categorias y acciones por estado.
- Seeders/factories/tests para cubrir estados activa, borrador, suspendida y vencida.
