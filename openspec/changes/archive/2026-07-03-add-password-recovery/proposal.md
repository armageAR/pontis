## Why

No existe flujo de recuperacion de contrasena. Un usuario que olvida su clave queda bloqueado de forma permanente y solo un administrador tocando la base de datos puede recuperarlo. Es una funcion critica de operacion que no figura en los docs pero sin la cual el sistema no funciona bien en produccion.

## What Changes

- Agregar endpoint publico `forgot-password` que recibe un email y, si corresponde, envia un enlace de restablecimiento con token temporal.
- Agregar endpoint publico `reset-password` que valida token + email y define la nueva contrasena.
- La respuesta de `forgot-password` debe ser neutra: no revelar si el email existe o no en el sistema (privacidad).
- Tokens de un solo uso y con expiracion corta; invalidar tokens anteriores al generar uno nuevo.
- Aplicar rate limiting a ambos endpoints para evitar abuso y enumeracion.
- Solo usuarios con acceso al sistema pueden completar el flujo; un usuario suspendido o dado de baja puede restablecer la clave pero mantiene las restricciones de acceso de su estado.
- Frontend: pantalla "Olvide mi contrasena" desde el login y pantalla de restablecimiento desde el enlace del email.
- Al restablecer, cerrar sesiones/tokens activos del usuario.

## Capabilities

### New Capabilities
- `password-recovery`: Flujo de recuperacion de contrasena por email con token temporal.

### Modified Capabilities

## Impact

- Backend Laravel: rutas publicas nuevas, notificacion/mailable de restablecimiento, uso de la tabla de password reset tokens.
- Frontend React: pantallas de solicitud y restablecimiento, enlace desde login.
- Tests de flujo completo, expiracion, un solo uso, respuesta neutra y rate limiting.
