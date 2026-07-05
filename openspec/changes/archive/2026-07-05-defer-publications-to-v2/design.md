## Context

El codigo actual ya contiene un modulo avanzado de publicaciones sobre `services` y `needs`: pantallas de "Mis publicaciones", endpoints CRUD, exploracion, categorias, ciclo de vida, preview de visibilidad y tests. La decision de producto es excluir todo ese modulo de V1 sin borrar codigo, migraciones ni datos, para poder retomarlo en V2 con mejor nomenclatura y alcance (`offers / needs`).

La restriccion central es que "no visible" no alcanza: ningun usuario debe poder acceder al modulo por navegacion, rutas directas frontend ni llamadas directas a API.

## Goals / Non-Goals

**Goals:**

- Retirar publicaciones del alcance funcional de V1.
- Ocultar toda entrada de UI hacia publicaciones, ofertas, necesidades publicadas, exploracion de publicaciones y previews de publicacion.
- Bloquear endpoints API de publicaciones para todos los roles mientras el modulo este diferido.
- Preservar codigo, tablas, migrations, seeders y datos existentes.
- Dejar una ruta clara para reactivar o migrar el modulo en V2 usando la nomenclatura objetivo `offers / needs`.

**Non-Goals:**

- No borrar tablas `services`, `needs` ni `service_categories`.
- No renombrar tablas, modelos, controladores ni rutas en este cambio.
- No migrar `services` a `offers` todavia.
- No implementar una experiencia V2 de publicaciones.
- No eliminar historico de datos ya creado en ambientes existentes.

## Decisions

1. Aplicar apagado funcional con doble barrera: frontend y backend.

   Rationale: ocultar links evita descubrimiento accidental, pero no impide acceso directo a rutas o API. El backend debe ser la autoridad final.

   Alternative considered: solo quitar menu y rutas frontend. Se descarta porque no cumple "no accesible por ningun usuario".

2. Mantener el codigo existente dormido.

   Rationale: el usuario pidio explicitamente no borrar codigo. Ademas el modulo puede servir como base de V2, especialmente para ciclo de vida, visibilidad, auditoria y tests.

   Alternative considered: eliminar controladores, modelos, componentes y migrations. Se descarta porque aumenta riesgo, destruye trabajo realizado y complica reactivacion.

3. Bloquear endpoints con respuesta uniforme.

   Rationale: las rutas de `services`, `needs`, `explore/services`, `explore/needs` y preview de publicacion deben responder como funcionalidad no disponible en V1. Para usuarios autenticados, `404` evita exponer superficie funcional; `403` tambien seria aceptable pero comunica que existe un recurso restringido.

   Decision recomendada: usar `404 Not Found` para rutas de publicaciones diferidas, salvo que el proyecto ya tenga un patron central de feature flags que prefiera `403`.

   Alternative considered: quitar rutas API. Se descarta si rompe imports/tests o dificulta reactivacion; puede hacerse si se confirma que no hay clientes externos.

4. No introducir un sistema general de feature flags salvo que ya exista un patron local.

   Rationale: el cambio es un diferimiento de alcance, no una configuracion dinamica por tenant/ambiente. Un guard simple o agrupacion de rutas deshabilitadas es suficiente para V1.

   Alternative considered: variable `PUBLICATIONS_ENABLED=false`. Puede ser util si se espera activar en staging, pero agrega una dimension operativa innecesaria si V1 siempre debe bloquear publicaciones.

5. Documentar `offers / needs` como nomenclatura objetivo de V2, sin tocar nombres tecnicos actuales.

   Rationale: ahora se decidio que el par conceptual correcto es `offers / needs`, pero renombrar tablas/API es un cambio profundo separado. Este change solo apaga el modulo en V1.

   Alternative considered: renombrar `services` a `offers` antes de apagar. Se descarta porque mezcla diferimiento de alcance con migracion de dominio.

## Risks / Trade-offs

- Codigo dormido puede quedar desactualizado frente a cambios futuros -> Mitigar documentando que V2 debe reevaluar y migrar el modulo antes de reactivarlo.
- Tests existentes de publicaciones van a fallar si siguen esperando CRUD activo -> Mitigar ajustandolos o moviendolos a cobertura pendiente/V2, y agregando tests de bloqueo V1.
- Datos seed de categorias/publicaciones pueden seguir cargandose aunque no sean visibles -> Mitigar aceptando que son datos dormidos o deshabilitando solo seeders demo si contaminan UI/admin.
- Rutas 404 pueden ocultar errores reales durante desarrollo -> Mitigar con nombres claros en tests y documentacion del guard.
- Contacto desde publicaciones y previews de publicacion quedan inaccesibles aunque parte del codigo siga existiendo -> Mitigar cubriendo rutas directas en tests.

## Migration Plan

1. Implementar bloqueo backend para endpoints de publicaciones y exploracion de publicaciones.
2. Retirar u ocultar rutas y navegacion frontend hacia publicaciones.
3. Ajustar tests: las pruebas de ciclo de vida publicable pasan a no ejecutarse en V1 o se reemplazan por pruebas de bloqueo.
4. Actualizar documentacion para indicar modulo diferido a V2.
5. Deploy sin migraciones destructivas.

Rollback:

1. Rehabilitar rutas backend.
2. Restaurar navegacion/rutas frontend.
3. Reactivar tests de lifecycle/publicacion.

## Open Questions

- En V1, el catalogo `service_categories` debe seguir administrable por superadmin aunque solo lo usen publicaciones dormidas, o tambien debe ocultarse?
- La respuesta backend final para endpoints diferidos debe ser `404` uniforme o `403` con mensaje de funcionalidad no disponible?
