## Why

Una solicitud de contacto sin motivo claro puede sentirse invasiva y obliga al destinatario a decidir sin contexto. Pontis debe promover contacto fraterno, concreto y respetuoso, evitando solicitudes curiosas o ambiguas.

## What Changes

- Hacer obligatoria una razon o contexto al crear una solicitud de contacto.
- Permitir seleccionar un origen de contacto:
  - Publicacion de "Lo que ofrezco".
  - Publicacion de "Lo que necesito".
  - Resultado de busqueda por profesion/oficio.
  - Resultado de busqueda por Taller o ubicacion.
  - Otro motivo.
- Mantener un mensaje libre obligatorio o semiestructurado con longitud minima razonable.
- Mostrar al destinatario el motivo y el contexto junto con la solicitud.
- Guardar la razon para auditoria funcional sin registrar mas datos privados de los necesarios.
- Usar textos de ayuda que recuerden que la solicitud debe tener un proposito concreto de colaboracion o contacto.

## Capabilities

### New Capabilities
- `contact-reason`: Motivo obligatorio y contexto trazable para solicitudes de contacto.

### Modified Capabilities

## Impact

- Backend: validacion y almacenamiento del motivo/contexto en `contact_requests`.
- Frontend: formulario de solicitud con selector de motivo y mensaje requerido.
- Bandeja de solicitudes: visualizacion del motivo y origen.
- Auditoria: integracion futura con eventos de solicitud de contacto.
- Tests de validacion y renderizado del contexto.
