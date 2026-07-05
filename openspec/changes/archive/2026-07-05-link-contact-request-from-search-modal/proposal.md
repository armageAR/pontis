## Why

La busqueda de Hermanos muestra perfiles en `PersonProfileModal`, pero ese modal no permite iniciar la solicitud de contacto. La capacidad existe en backend y en la pagina completa `/people/:id`, pero la UX principal de busqueda quedo desconectada.

## What Changes

- Agregar accion clara "Solicitar contacto" dentro de `PersonProfileModal`.
- Mostrar la accion cuando el perfil permita contacto, incluyendo perfiles anonimos/incognito.
- Mostrar la accion deshabilitada cuando `can_request_contact === false`, con aclaracion visible al pasar por arriba.
- Reutilizar el mismo flujo/modal de solicitud de contacto que usa `PersonPage`, evitando duplicar logica.
- En el flujo desde busqueda, el usuario debe elegir motivo antes de enviar.
- Mantener el modal de perfil abierto luego de enviar y mostrar mensaje de "Solicitud enviada".
- Mantener validaciones existentes: mensaje obligatorio, motivo obligatorio y seleccion de datos propios a compartir.

## Capabilities

### New Capabilities

- Ninguna.

### Modified Capabilities

- `mediated-contact-flow`: Conecta el inicio de solicitud de contacto desde resultados de busqueda y perfiles modales.
- `contact-reason`: Asegura que el usuario elija motivo al iniciar contacto desde el modal de busqueda.
- `contact-request-sharing`: Reutiliza la seleccion de datos a compartir en el flujo modal de busqueda.

## Impact

- Frontend:
  - `PersonProfileModal` debe cargar/usar `can_request_contact`.
  - Extraer el formulario/modal de contacto de `PersonPage` a un componente reutilizable.
  - `PersonPage` debe usar el nuevo componente compartido sin cambiar comportamiento.
  - Tests de busqueda/modal y de pagina completa.
- Backend:
  - No se esperan cambios estructurales si `/people/{id}` ya devuelve `can_request_contact` y `POST /contact-requests` soporta `source=search`.
  - Puede requerir ajustar tipos si el modal actual no modela `can_request_contact`.
- UX:
  - Desde Busqueda -> Hermanos -> perfil modal, el usuario puede iniciar contacto sin navegar a pagina completa.
