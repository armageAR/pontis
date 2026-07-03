## Why

La pagina de Mis Publicaciones debe reflejar mejor el lenguaje del usuario y simplificar la gestion cotidiana de sus publicaciones. El flujo actual esperado incluye estados y controles mas pesados que no corresponden si cualquier usuario registrado puede publicar sin autorizacion previa.

## What Changes

- Cambiar el titulo visible de la pagina de "Mis Servicios" a "Lo que ofrezco".
- Reducir el tamano visual de los filtros de estado y pagina para que ocupen menos espacio.
- Mover la paginacion al pie del listado.
- Mostrar a la izquierda del pie la cantidad de items por pagina.
- Mostrar a la derecha los numeros de pagina clickeables y los controles para avanzar o retroceder entre paginas.
- Permitir que un usuario registrado cree publicaciones sin autorizacion administrativa previa.
- Eliminar el estado o accion "pedir correccion" del flujo de publicaciones.

## Capabilities

### New Capabilities
- `my-publications-page`: Cubre la experiencia de usuario y reglas funcionales para administrar publicaciones propias desde la pagina de Mis Publicaciones.

### Modified Capabilities

## Impact

- Frontend React en la pagina de Mis Publicaciones y sus componentes de filtros/listado/paginacion.
- API Laravel relacionada con creacion y estados de publicaciones propias.
- Validaciones, enums o constantes de estados de publicaciones si actualmente contemplan autorizacion previa o pedido de correccion.
- Tests frontend y backend asociados a creacion, listado, filtros y paginacion de publicaciones propias.
