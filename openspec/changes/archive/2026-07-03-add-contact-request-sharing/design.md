## Context

Las solicitudes de contacto hoy revelan nombre y datos en momentos que no necesariamente tienen consentimiento explicito. Este cambio agrega seleccion de datos compartidos por solicitud.

## Goals / Non-Goals

**Goals:**
- Guardar `shared_fields` por solicitud.
- Respetar identidad reservada en respuestas y notificaciones.
- Revelar datos solo segun aceptacion y consentimiento.

**Non-Goals:**
- No crear chat completo.
- No reemplazar preferencias generales de contacto.

## Decisions

- Usar lista validada de campos compartibles.
- Renderizar requester/requestee con serializers segun estado y direccion.
- Las notificaciones usan texto neutro si identidad no se comparte.

## Risks / Trade-offs

- Datos historicos sin seleccion -> Mitigar con default conservador o migracion explicita.
