# Publicaciones

## Estado en V1: diferido a V2

**Las publicaciones NO forman parte de V1.** El módulo (`services` / `needs`,
categorías, exploración, ciclo de vida y preview) queda **dormido**: el código,
los modelos, las migraciones y los datos existentes se conservan, pero:

* La navegación de V1 no expone "Mis publicaciones", "Lo que ofrezco", "Lo que
  necesito" ni la exploración de servicios/necesidades.
* Las rutas frontend de publicaciones redirigen al panel y no renderizan datos.
* Los endpoints de API (`services`, `needs`, `service-categories`,
  `explore/services`, `explore/needs`, `profile/publication-preview`) responden
  **403** para todos los roles, vía el middleware `publications.deferred`.
* No se eliminan tablas, migraciones, seeders ni registros.

Nomenclatura objetivo de V2: `offers` mapea a "Lo que ofrezco" y `needs` a "Lo
que necesito". El código actual sigue usando `services / needs` y permanece
dormido hasta una futura migración de dominio en V2. Ver el cambio OpenSpec
`defer-publications-to-v2`. El resto de este documento describe el
comportamiento del módulo para cuando se reactive en V2.

## Proposito

Este documento resume el comportamiento esperado y el estado actual de implementacion de las publicaciones en Pontis, tomando como base las especificaciones OpenSpec y el codigo existente.

Decision de nomenclatura funcional objetivo:

* `offers`: "Lo que ofrezco".
* `needs`: "Lo que necesito".

En la implementacion actual, "publicaciones" no es una tabla unica. El concepto funcional se materializa en dos recursos separados:

* `services`: implementacion actual de "Lo que ofrezco"; deberia evolucionar a `offers`.
* `needs`: implementacion actual de "Lo que necesito"; el nombre ya coincide con la nomenclatura objetivo.

Ambos recursos comparten ciclo de vida, reglas de vigencia, visibilidad, vista previa y acciones principales. En este documento, cuando se habla funcionalmente de "ofertas" se refiere al dominio objetivo `offers`; cuando se citan endpoints, controladores o archivos actuales, se conserva `services` porque es el nombre que existe hoy en el codigo.

## Que deben hacer

Las publicaciones permiten que un Hermano activo registre algo que ofrece o algo que necesita, con una audiencia controlada por visibilidad y una vigencia limitada.

Una publicacion debe:

* Tener titulo y descripcion obligatorios.
* Opcionalmente pertenecer a una categoria del catalogo de categorias de servicios.
* Definir visibilidad: `private`, `workshop`, `my_workshops`, `registered` o `anonymous`.
* Poder guardarse como borrador sin aparecer en busquedas.
* Poder publicarse como activa solo con vigencia de 10, 30, 60 o 90 dias.
* Mostrar vista previa antes de publicar o republicar.
* Vencer automaticamente por fecha, sin depender de que un job cambie el estado persistido.
* Poder suspenderse manualmente.
* Poder republicarse sin crear un duplicado.
* Quedar visible para su propietario en "Mis publicaciones", aun si esta borrador, suspendida o vencida.

## Pantallas y uso

La pantalla principal es `Mis publicaciones`, implementada en `pontis-app/src/pages/publications/MisPublicacionesPage.tsx`.

La pantalla separa las publicaciones en dos tabs:

* `Ofrezco`: renderiza `ServicesPage`.
* `Necesito`: renderiza `NeedsPage`.

### Crear

El usuario abre el formulario con:

* `+ Agregar servicio` para ofertas en la implementacion actual; conceptualmente deberia ser `+ Agregar oferta`.
* `+ Agregar necesidad` para necesidades.

El formulario no tiene selector manual de estado. El estado se define por la accion elegida:

* `Guardar`: crea o actualiza como `draft`.
* `Publicar`: abre la vista previa; al confirmar, crea o actualiza como `active`.
* `Cancelar`: cierra el formulario sin persistir cambios nuevos.

### Editar

La accion `Editar` precarga los datos existentes. Si el usuario guarda, el backend deja la publicacion como borrador. Si publica, el backend activa la publicacion y recalcula `published_at` y `expires_at`.

### Suspender

La accion `Suspender` aparece solo cuando el estado efectivo es `active`. Llama a:

* `POST /api/services/{service}/suspend`
* `POST /api/needs/{need}/suspend`

El estado persistido pasa a `suspended` y la publicacion deja de aparecer en exploracion.

### Republicar

La accion `Republicar` aparece cuando el estado efectivo es `expired`. Abre el mismo formulario con datos precargados. Al confirmar publicacion, se actualiza el mismo registro mediante `PATCH`, no se crea otro.

### Filtrar y paginar

Los listados propios aceptan filtro por estado y paginacion:

* `GET /api/services?status=active&page=1&per_page=10`
* `GET /api/needs?status=expired&page=1&per_page=10`

Estados filtrables desde UI:

* `draft`
* `active`
* `suspended`
* `expired`

El estado `expired` es efectivo: se calcula para registros `active` cuyo `expires_at` ya paso.

## Endpoints principales

Ofertas, implementadas hoy como `services`:

* `GET /api/services`
* `POST /api/services`
* `GET /api/services/{service}`
* `PATCH /api/services/{service}`
* `DELETE /api/services/{service}`
* `POST /api/services/{service}/suspend`

Necesidades:

* `GET /api/needs`
* `POST /api/needs`
* `GET /api/needs/{need}`
* `PATCH /api/needs/{need}`
* `DELETE /api/needs/{need}`
* `POST /api/needs/{need}/suspend`

Exploracion publicada:

* `GET /api/explore/services`
* `GET /api/explore/needs`

Vista previa:

* `GET /api/profile/publication-preview?visibility=registered`

## Payloads

### Publicar una oferta

```json
{
  "title": "Asesoria contable",
  "description": "Puedo orientar en monotributo y facturacion.",
  "service_category_id": 1,
  "modality": "both",
  "location": "CABA",
  "availability": "Tardes",
  "conditions": "Primer contacto por la plataforma",
  "visibility": "registered",
  "publish": true,
  "validity_days": 30,
  "preview_confirmed": true
}
```

### Guardar una necesidad como borrador

```json
{
  "title": "Busco proveedor",
  "description": "Necesito referencias de imprentas.",
  "service_category_id": 2,
  "location": "La Plata",
  "urgency": "medium",
  "visibility": "my_workshops",
  "publish": false
}
```

## Validaciones

Las validaciones de backend estan en `ServiceController::validatePublication` y `NeedController::validatePublication`.

Validaciones comunes:

* `title`: requerido, string, maximo 255 caracteres.
* `description`: requerido, string.
* `service_category_id`: opcional, debe existir en `service_categories`.
* `visibility`: opcional, debe ser `private`, `workshop`, `my_workshops`, `registered` o `anonymous`.
* `publish`: booleano.
* `validity_days`: requerido al publicar; solo admite `10`, `30`, `60` o `90`.
* `preview_confirmed`: requerido y aceptado al publicar.

Validaciones especificas de ofertas, implementadas hoy en `ServiceController`:

* `modality`: opcional; `presencial`, `remoto` o `both`.
* `location`: opcional, maximo 255 caracteres.
* `availability`: opcional.
* `conditions`: opcional.

Validaciones especificas de necesidades:

* `location`: opcional, maximo 255 caracteres.
* `urgency`: opcional; `low`, `medium` o `high`.

El frontend tambien marca titulo y descripcion como requeridos y ofrece solo las opciones validas de vigencia.

## Estados y vigencia

Estados persistidos principales:

* `draft`: borrador, no publicado.
* `active`: publicado, visible si no vencio y la visibilidad lo permite.
* `suspended`: suspendido por el propietario.

Estado efectivo:

* `expired`: no necesariamente se persiste. Se deriva cuando `status = active` y `expires_at` esta en el pasado.

Al publicar:

* `status` pasa a `active`.
* `published_at` se setea con la fecha/hora actual.
* `expires_at` se calcula sumando `validity_days`.
* La fecha de vencimiento no puede superar 90 dias porque el backend solo acepta 10, 30, 60 o 90.

Al guardar:

* `status` pasa a `draft`.
* No se setean fechas de publicacion/vencimiento nuevas para altas de borrador.

Al suspender:

* `status` pasa a `suspended`.
* La publicacion queda fuera de exploracion.

Al republicar:

* Se usa `PATCH` sobre el registro existente.
* `status` vuelve a `active`.
* Se recalculan `published_at` y `expires_at`.

## Visibilidad y busqueda

Las publicaciones publicadas se exponen en `ExploreController`.

Para aparecer en exploracion, una publicacion debe cumplir:

* No pertenecer al viewer.
* Tener `status = active`.
* Tener `expires_at > now()`.
* Pertenecer a un usuario autor con `status = active`.
* Pasar la politica central de visibilidad.

La politica central esta en `VisibilityPolicy::scopePublicationVisibility`.

Reglas implementadas:

* `registered` y `anonymous` son visibles para usuarios registrados segun los niveles abiertos definidos por la politica.
* `workshop` se limita a Hermanos del taller principal compartido.
* `my_workshops` se limita a Hermanos que comparten alguno de los talleres del autor.
* `anonymous` enmascara identidad del autor en resultados publicados.

La vista previa usa `VisibilityPolicy::publicationPreview` y muestra audiencia y datos visibles del autor antes de confirmar la publicacion.

## Auditoria

Las acciones relevantes registran auditoria mediante `AuditLogger`:

* `service.saved`
* `service.updated`
* `service.suspended`
* `service.deleted`
* `need.saved`
* `need.updated`
* `need.suspended`
* `need.deleted`

La auditoria incluye estado, visibilidad y categoria cuando corresponde.

## Migraciones y datos legacy

La migration `2026_07_03_030000_add_lifecycle_fields_to_services_and_needs.php` agrega:

* `published_at`
* `expires_at`
* indice sobre `expires_at`

Tambien normaliza estados legacy:

* Servicios `paused`, `hidden`, `disabled`, `closed`, `cancelled` pasan a `suspended`.
* Servicios y necesidades `pending_authorization`, `requires_correction`, `rejected` pasan a `draft`.
* Necesidades `open`, `searching`, `with_matches`, `contact_requested`, `linked` pasan a `active`.
* Necesidades `closed`, `cancelled` pasan a `suspended`.

Para registros activos sin fechas, la migration setea:

* `published_at = created_at`
* `expires_at = now() + 90 dias`

## Que esta hecho

Implementado en backend:

* CRUD de ofertas y necesidades; las ofertas estan implementadas tecnicamente como `services`.
* Validaciones de titulo, descripcion, visibilidad, categoria y vigencia.
* Publicar sin autorizacion administrativa previa.
* Estados `draft`, `active`, `suspended` y estado efectivo `expired`.
* Fechas `published_at` y `expires_at`.
* Filtro de vencidas fuera de exploracion.
* Listado propio con todos los estados.
* Filtro por estado en listado propio.
* Suspension de publicaciones propias.
* Republicacion actualizando el mismo registro.
* Vista previa obligatoria para publicar mediante `preview_confirmed`.
* Enmascarado de autor para visibilidad anonima.
* Eliminacion de endpoints de pedir correccion para publicaciones.
* Tests feature para ciclo de vida y publicacion sin autorizacion.

Implementado en frontend:

* Pantalla `Mis publicaciones` con tabs `Ofrezco` y `Necesito`.
* Formularios sin dropdown de estado.
* Acciones `Publicar`, `Guardar` y `Cancelar`.
* Vista previa antes de confirmar publicacion.
* Opciones de vigencia 10, 30, 60 y 90 dias.
* Filtro compacto por estado.
* Paginacion al pie.
* Acciones `Editar`, `Suspender`, `Republicar` y `Eliminar`.
* Etiquetas visibles para `Borrador`, `Activa`, `Suspendida` y `Vencida`.
* Uso del catalogo existente de categorias.

Cubierto por tests:

* `PublicationLifecycleTest`.
* `PublicationPublishingTest`.
* Tests unitarios de `VisibilityPolicy` para vista previa y anonimato.

## Que falta o conviene revisar

No se observo una tabla unificada `publications`. Esto es consistente con la decision de implementacion actual, pero cualquier funcionalidad transversal debe tocar `services` y `needs`.

La nomenclatura objetivo elegida es `offers / needs`. Por lo tanto, si se encara una migracion de nombres, el candidato natural es renombrar el recurso tecnico `services` a `offers`, manteniendo `needs`.

Pendientes o riesgos detectados:

* Guardar una publicacion existente siempre fuerza `draft`; si el usuario edita una activa y elige `Guardar`, la saca de publicacion. Esto coincide con la spec, pero puede sorprender en UX.
* No hay endpoint explicito `republish`; republicar se implementa como `PATCH` con `publish = true`. Funciona, pero debe documentarse en clientes externos.
* No hay job que persista `expired`; el sistema lo deriva por fecha. Esto es correcto para visibilidad, pero reportes SQL directos deben usar `expires_at`.
* La vista previa confirma datos de autor y audiencia, pero la persistencia depende de `preview_confirmed`; no hay token de preview ni comparacion contra el payload exacto previsualizado.
* Los errores de guardado en frontend se muestran como alert generico; no se exponen detalles de validacion campo por campo.
* Los listados propios ordenan por `created_at`, no por `published_at`, `expires_at` ni ultima actualizacion.
* No se detectaron tests frontend especificos para `MisPublicacionesPage`, `ServicesPage` o `NeedsPage`.
* La documentacion funcional general en `docs/project` menciona publicaciones conceptualmente, pero este documento es el detalle operativo actualizado.

## Referencias revisadas

Specs OpenSpec:

* `openspec/specs/publication-lifecycle/spec.md`
* `openspec/specs/my-publications-page/spec.md`
* `openspec/changes/archive/2026-07-03-add-publication-expiration-drafts/design.md`
* `openspec/changes/archive/2026-07-03-add-publication-preview/design.md`

Backend:

* `pontis-api/app/Http/Controllers/ServiceController.php`
* `pontis-api/app/Http/Controllers/NeedController.php`
* `pontis-api/app/Http/Controllers/ExploreController.php`
* `pontis-api/app/Http/Controllers/VisibilitySettingsController.php`
* `pontis-api/app/Models/Service.php`
* `pontis-api/app/Models/Need.php`
* `pontis-api/app/Support/VisibilityPolicy.php`
* `pontis-api/database/migrations/2026_07_03_030000_add_lifecycle_fields_to_services_and_needs.php`

Frontend:

* `pontis-app/src/pages/publications/MisPublicacionesPage.tsx`
* `pontis-app/src/pages/services/ServicesPage.tsx`
* `pontis-app/src/pages/needs/NeedsPage.tsx`
* `pontis-app/src/api/services.ts`
* `pontis-app/src/api/needs.ts`
* `pontis-app/src/api/profile.ts`

Tests:

* `pontis-api/tests/Feature/PublicationLifecycleTest.php`
* `pontis-api/tests/Feature/PublicationPublishingTest.php`
* `pontis-api/tests/Unit/VisibilityPolicyTest.php`
