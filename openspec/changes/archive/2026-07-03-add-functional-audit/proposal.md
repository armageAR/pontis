## Why

La trazabilidad de acciones relevantes es un objetivo explicito de V1 (overview, workflows #15, domain-model #18, decisions #22), pero el backend no tiene ningun registro de auditoria. Hoy no se puede saber quien valido un usuario, quien aprobo un cambio sensible, quien intervino una publicacion ni quien ejecuto el crawler.

## What Changes

- Crear una tabla `audit_logs` con: accion, actor, rol/contexto usado, entidad afectada (tipo + id), decision tomada cuando aplique, metadatos minimos y timestamp.
- Crear un servicio de auditoria central (`AuditLogger` o equivalente) para registrar eventos desde los controladores sin duplicar logica.
- Registrar los eventos relevantes definidos en los docs:
  - Registro y validacion de usuarios (aprobar, rechazar, pedir correccion).
  - Cambios de estado de usuario (suspension, baja, reactivacion).
  - Solicitudes y resoluciones de cambios sensibles.
  - Alta, aprobacion, rechazo o salida de membresias Hermano-Taller.
  - Cambios de grado y cargo (incluida futura validacion).
  - Creacion, baja o intervencion administrativa de publicaciones.
  - Resolucion de solicitudes de contacto (aceptada/rechazada).
  - Ejecucion y confirmacion del crawler de Talleres.
- Exponer consulta de auditoria solo para Superadmin (V1), con filtros basicos por accion, actor, entidad y rango de fechas.
- La auditoria no debe exponer datos privados: guardar referencias e identificadores, no contenido sensible.
- No auditar interacciones menores (busquedas, visualizaciones normales).

## Capabilities

### New Capabilities
- `functional-audit`: Registro y consulta de auditoria funcional de acciones relevantes del sistema.

### Modified Capabilities

## Impact

- Nueva migracion y modelo `AuditLog`.
- Controladores existentes que ejecutan acciones auditables (usuarios, membresias, cambios sensibles, publicaciones, contacto, crawler) llaman al servicio de auditoria.
- Nueva pantalla o seccion de administracion para consulta (solo Superadmin).
- Tests de que cada accion relevante genera su registro y de que la consulta respeta permisos.
