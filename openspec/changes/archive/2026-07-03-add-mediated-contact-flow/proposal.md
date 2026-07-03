## Why

Cuando una publicacion o resultado aparece con identidad reservada, Pontis debe permitir iniciar un puente de comunicacion sin revelar automaticamente la identidad de ninguna parte. El valor de la herramienta esta en facilitar contacto util, pero el primer paso debe preservar consentimiento y privacidad.

## What Changes

- Agregar un flujo de contacto intermediado para resultados anonimos o publicaciones con identidad no publicada.
- Permitir que el solicitante envie una solicitud asociada al resultado anonimo sin conocer la identidad real del destinatario.
- Mostrar al destinatario el mensaje, el contexto y solo los datos que el solicitante eligio compartir.
- El destinatario puede aceptar, rechazar o pedir que el solicitante comparta mas informacion antes de aceptar.
- Al aceptar, se revelan solo los datos consentidos por ambas partes segun las reglas de solicitud de contacto.
- Al rechazar, no se revela informacion adicional.
- Evitar que respuestas, notificaciones o URLs expongan el id real del usuario anonimo al solicitante antes de la aceptacion.

## Capabilities

### New Capabilities
- `mediated-contact-flow`: Contacto intermediado para resultados anonimos o de identidad reservada, con revelado progresivo basado en consentimiento.

### Modified Capabilities

## Impact

- Backend: solicitudes de contacto asociadas a publicaciones/resultados anonimos sin exponer destinatario real antes de corresponder.
- Frontend: acciones de "Solicitar contacto" en resultados anonimos y bandeja de solicitudes con contexto intermediado.
- Notificaciones: contenido neutro que no revele identidades reservadas.
- Tests de no filtracion de ids, nombres, emails o telefonos antes de aceptacion.
