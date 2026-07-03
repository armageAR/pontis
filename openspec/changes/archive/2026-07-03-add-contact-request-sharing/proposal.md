## Why

Los docs (workflows #12, decisions #14) establecen que el solicitante de contacto elige que informacion compartir con el destinatario. Hoy la solicitud solo lleva un mensaje de texto y siempre revela el nombre del solicitante en la notificacion, sin que el solicitante controle que datos expone ni que el destinatario reciba datos de contacto utiles al aceptar.

## What Changes

- Al crear una solicitud de contacto, el solicitante elige que bloques de su informacion compartir:
  - Identidad (nombre completo o solo nombre).
  - Datos de contacto (email y/o telefono).
  - Taller principal.
  - Profesion.
- Guardar la seleccion en la solicitud (`shared_fields` o equivalente).
- El destinatario ve, junto al mensaje, solo los datos que el solicitante decidio compartir.
- Si el solicitante no comparte identidad, la solicitud se muestra con identidad reservada y la notificacion no debe revelar el nombre.
- Al aceptar, ambas partes ven los datos compartidos correspondientes; al rechazar, no se revela informacion adicional (regla existente de los docs).
- La decision de aceptar o rechazar sigue siendo auditable (se integra con el proposal de auditoria funcional).

## Capabilities

### New Capabilities
- `contact-request-sharing`: Seleccion de informacion a compartir por el solicitante en solicitudes de contacto, y revelado consistente segun consentimiento.

### Modified Capabilities

## Impact

- Migracion para `shared_fields` en `contact_requests`.
- `ContactRequestController` y notificaciones: respetar la seleccion al crear, listar, aceptar y rechazar.
- Frontend: selector de datos a compartir en el formulario de solicitud y visualizacion en la bandeja de solicitudes.
- Tests de que no se filtran datos no compartidos en respuestas de API ni notificaciones.
