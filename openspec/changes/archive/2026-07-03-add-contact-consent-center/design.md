## Context

Las preferencias de contacto y los datos que un Hermano acepta compartir estan dispersos entre perfil, visibilidad y solicitudes. El centro de consentimiento debe actuar como configuracion de defaults, sin convertir esos datos en publicos.

## Goals / Non-Goals

**Goals:**
- Centralizar defaults de datos compartidos y canales preferidos.
- Aplicar esos defaults al crear solicitudes de contacto.
- Permitir editarlos por solicitud.
- Mantener privacidad por defecto.

**Non-Goals:**
- No crear chat completo.
- No publicar datos de contacto en perfiles o busquedas.

## Decisions

- Guardar preferencias por usuario como estructura tipada o JSON validado.
- Usar defaults solo como precarga del formulario de solicitud.
- Separar "acepto solicitudes desde" de "datos que comparto".

## Risks / Trade-offs

- Defaults confundidos con publicacion permanente -> Mitigar con copy claro.
- Solapamiento con `contact-request-sharing` -> Mitigar usando los defaults como entrada de `shared_fields`, no como reemplazo.
