## Why

Los docs definen el flujo O Eterno (workflows #17, permissions #4) para Hermanos fallecidos: el estado debe mostrarse como "O Eterno" en la UI, el acceso debe cerrarse y el historial preservarse. Hoy existe el campo `masonic_status` y referencias sueltas en el frontend, pero no hay un flujo administrativo en el backend para marcarlo ni efectos consistentes sobre acceso, busqueda y contacto.

## What Changes

- Agregar accion administrativa para marcar a un Hermano como O Eterno:
  - Superadmin en cualquier caso; Admin de Taller solo para miembros de sus Talleres.
- Efectos del estado O Eterno:
  - El usuario no puede iniciar sesion ni operar en el sistema.
  - No aparece en busquedas comunitarias ni en explore.
  - No puede recibir solicitudes de contacto; las pendientes se cierran.
  - Sus publicaciones activas dejan de mostrarse en listados publicados.
  - Ficha, historial de grados, cargos y pertenencias se preservan (sin borrado).
  - Donde el estado se muestre en la UI debe decir "O Eterno", nunca "Fallecido" (regla de wording de CLAUDE.md).
- La accion debe quedar auditada (se integra con el proposal de auditoria funcional) y debe poder revertirse solo por Superadmin ante un error de carga.

## Capabilities

### New Capabilities
- `eterno-state`: Estado O Eterno del Hermano: marcado administrativo, efectos sobre acceso, busqueda, contacto y publicaciones, y preservacion de historial.

### Modified Capabilities

## Impact

- Backend: accion/endpoint administrativo, chequeos en login, busqueda de Hermanos, explore, solicitudes de contacto y listados de publicaciones.
- Frontend: accion en administracion de usuarios/miembros, etiqueta "O Eterno" en fichas y listados donde el estado sea visible.
- Tests de acceso denegado, exclusion de busquedas/contacto y preservacion de historial.
