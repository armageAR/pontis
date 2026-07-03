## Why

Pontis necesita que cada Hermano controle de forma comprensible que datos personales esta dispuesto a compartir cuando inicia o recibe contacto. Hoy las decisiones de contacto estan dispersas entre visibilidad de perfil, publicaciones y solicitudes, lo que puede generar exposicion accidental o friccion para comunicarse.

## What Changes

- Agregar un centro de consentimiento de contacto dentro del perfil o configuracion.
- Permitir definir datos predeterminados a compartir al iniciar una solicitud de contacto:
  - Identidad completa o identidad reservada.
  - Email.
  - Telefono.
  - WhatsApp.
  - Taller principal.
  - Profesion/oficio.
- Permitir definir preferencia de contacto: email, telefono, WhatsApp o solo respuesta dentro del flujo de solicitud.
- Permitir configurar si el Hermano acepta solicitudes de contacto desde busquedas, publicaciones, ambas o ninguna.
- Usar estos valores como defaults editables al crear una solicitud de contacto.
- Mostrar una explicacion clara de que estos defaults no hacen publica la informacion: solo se aplican cuando el usuario decide contactar o aceptar contacto.

## Capabilities

### New Capabilities
- `contact-consent-center`: Configuracion central de consentimiento y preferencias para compartir datos en solicitudes de contacto.

### Modified Capabilities

## Impact

- Backend: nuevos campos o estructura para preferencias de contacto y defaults de datos compartidos.
- Frontend: nueva seccion en perfil/configuracion y uso de defaults en el formulario de solicitud de contacto.
- Solicitudes de contacto: integracion con `shared_fields` si se implementa `add-contact-request-sharing`.
- Tests de persistencia, defaults, permisos y no exposicion publica de datos.
