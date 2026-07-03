## Why

La privacidad no se garantiza solo ocultando campos en la UI. Si la API envia datos innecesarios, esos datos quedan expuestos a clientes, logs, extensiones o errores futuros. Pontis debe aplicar minimo dato desde el backend.

## What Changes

- Revisar respuestas de API para enviar solo los campos necesarios para cada pantalla y estado.
- No enviar email, telefono, WhatsApp, matricula, DNI u otros datos sensibles salvo que la regla de visibilidad o consentimiento los habilite para ese request.
- Crear resources/serializers o helpers de respuesta para perfiles, busquedas, publicaciones y solicitudes de contacto.
- Separar payloads de administracion de payloads comunitarios.
- Evitar que notificaciones incluyan nombres o datos si el flujo exige identidad reservada.
- Agregar tests que inspeccionen JSON de respuestas para confirmar ausencia de campos no autorizados.

## Capabilities

### New Capabilities
- `minimum-data-payloads`: Politica de minimo dato en respuestas API, notificaciones y payloads comunitarios.

### Modified Capabilities

## Impact

- Backend Laravel: resources/serializers para User, perfil publico, busqueda, explore, contacto y notificaciones.
- Frontend: ajustes donde hoy dependa de campos que no deberian llegar siempre.
- Tests de privacidad a nivel API para busquedas, contacto, publicaciones y fichas.
- Menor riesgo de filtracion por cambios UI o errores de cliente.
