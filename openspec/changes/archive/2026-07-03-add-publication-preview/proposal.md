## Why

Los docs exigen que antes de publicar el sistema muestre una vista previa de que datos quedaran visibles (overview, workflows #13, CLAUDE.md). El formulario actual de servicios y necesidades publica directamente al presionar "Publicar", sin mostrar al autor que informacion personal quedara expuesta segun la visibilidad elegida.

## What Changes

- Agregar un paso de vista previa al presionar "Publicar" en los formularios de "Lo que ofrezco" y "Lo que necesito".
- La vista previa debe mostrar la publicacion tal como la vera la audiencia elegida, incluyendo:
  - Titulo, descripcion, categoria y demas datos de la publicacion.
  - Que datos del autor quedaran visibles segun la visibilidad seleccionada (identidad completa, parcial o anonima; ubicacion; profesion).
- Si la visibilidad es "Busqueda anonima", la vista previa debe mostrar la identidad enmascarada tal como la veran los demas.
- El autor confirma desde la vista previa ("Confirmar y publicar") o vuelve a editar.
- "Guardar" como borrador no requiere vista previa.
- La vista previa no cambia la visibilidad general de la ficha ni persiste nada hasta confirmar.

## Capabilities

### New Capabilities

### Modified Capabilities
- `publication-lifecycle`: Se agrega el requisito de vista previa de datos visibles antes de publicar o republicar.

## Impact

- Frontend React: paso/modal de vista previa en los formularios de servicios y necesidades (crear, editar y republicar).
- Posible endpoint o logica frontend para resolver como se vera la identidad del autor segun su configuracion de visibilidad actual.
- Tests o QA manual del flujo publicar con confirmacion y del enmascarado anonimo en la vista previa.
