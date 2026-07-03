## Why

Los usuarios necesitan entender que se revelara antes de publicar, buscar o contactar. La privacidad no debe depender de recordar configuraciones ocultas: cada accion sensible debe mostrar una indicacion breve, contextual y accionable.

## What Changes

- Agregar indicadores de privacidad en acciones sensibles:
  - Publicar o republicar una publicacion.
  - Guardar configuracion de visibilidad.
  - Enviar solicitud de contacto.
  - Aceptar solicitud de contacto.
  - Activar aparicion anonima en busquedas.
- Los indicadores deben resumir:
  - Audiencia visible.
  - Si la identidad queda visible, parcial o reservada.
  - Que datos de contacto se comparten.
  - Si la accion queda auditada.
- Mostrar estos indicadores cerca del boton de accion, no solo en pantallas de ayuda.
- Usar lenguaje breve y no alarmista.
- Permitir abrir un detalle expandido cuando haya varias reglas combinadas.

## Capabilities

### New Capabilities
- `action-privacy-indicators`: Indicadores contextuales de privacidad y auditoria para acciones sensibles.

### Modified Capabilities

## Impact

- Frontend: componentes reutilizables de resumen de privacidad en formularios y modales.
- Backend/API: puede requerir endpoints o helpers para resolver previews de audiencia/datos visibles.
- Publicaciones, contacto y perfil: integracion visual antes de acciones sensibles.
- Tests o QA manual de que cada accion muestra el resumen correcto.
