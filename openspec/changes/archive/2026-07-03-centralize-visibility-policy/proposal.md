## Why

Las reglas de visibilidad hoy aparecen en varios controladores y flujos. Cuando una regla de privacidad se duplica, aumenta el riesgo de que una pantalla o endpoint revele mas que otro. Pontis necesita una politica central de visibilidad para que busqueda, perfil, publicaciones y contacto decidan de la misma manera.

## What Changes

- Centralizar la resolucion de visibilidad en un servicio de dominio unico.
- Cubrir bloques de perfil, identidad anonima, datos de contacto, ubicacion, profesion, grados, cargos, publicaciones y solicitudes de contacto.
- Exponer metodos claros para:
  - Determinar si un viewer puede ver un bloque.
  - Determinar si un resultado puede aparecer anonimo.
  - Construir payloads filtrados por viewer.
  - Resolver previews de datos visibles antes de publicar o contactar.
- Reemplazar reglas duplicadas en People, PublicProfile, Explore y Contact Requests por el servicio central.
- Agregar tests unitarios del servicio y tests de integracion por endpoint critico.

## Capabilities

### New Capabilities
- `visibility-policy`: Servicio central de politicas de visibilidad y construccion segura de datos visibles por viewer.

### Modified Capabilities

## Impact

- Backend: refactor de `ProfileVisibility` hacia una politica mas completa y reutilizable.
- Controladores: People, PublicProfile, Explore, ContactRequest, publicaciones y notificaciones.
- Frontend: posible uso de previews generadas por backend para indicadores de privacidad.
- Tests: matriz de visibilidad por relacion entre Hermanos, Talleres, estados y consentimiento.
