## Why

Pontis V1 debe reducir alcance y complejidad: el modulo de publicaciones ya tiene codigo avanzado, pero no debe formar parte de la primera version funcional. Mantenerlo visible o accesible en V1 introduce superficie de producto, privacidad, soporte y validacion que se decidio postergar a V2.

## What Changes

- **BREAKING**: Las publicaciones dejan de estar disponibles para cualquier usuario en V1, incluyendo Hermanos activos, admins de Taller y superadmins.
- Ocultar navegacion, pantallas y acciones de publicaciones, incluyendo "Mis publicaciones", "Lo que ofrezco", "Lo que necesito", exploracion de ofertas/necesidades y flujos de publicar/guardar/suspender/republicar.
- Bloquear acceso directo a rutas frontend y endpoints API relacionados con publicaciones, aun cuando el codigo exista.
- Mantener el codigo, modelos, migraciones y datos actuales sin borrarlos, para reactivar o migrar el modulo en V2.
- Mantener fuera del alcance visible los previews de publicacion, indicadores de privacidad de publicacion y contacto iniciado desde publicaciones.
- Actualizar documentacion/specs para dejar claro que `offers / needs` es nomenclatura objetivo de V2, pero V1 no expone publicaciones.

## Capabilities

### New Capabilities

- `publication-v2-deferral`: Define el comportamiento transversal de V1 para dejar publicaciones dormidas, no visibles y no accesibles sin borrar codigo ni datos existentes.

### Modified Capabilities

- `project`: Ajusta el alcance funcional de V1 para excluir publicaciones, ofertas y necesidades publicadas.
- `publication-lifecycle`: Suspende los requerimientos de ciclo de vida de publicaciones durante V1.
- `my-publications-page`: Retira la pagina y los listados de publicaciones del flujo visible de V1.
- `visibility-policy`: Evita que previews o reglas de visibilidad de publicaciones sean accesibles como funcionalidad de usuario en V1.
- `action-privacy-indicators`: Retira indicadores especificos de publicar/republicar del alcance de V1.
- `contact-reason`: Retira el origen "desde publicacion" del flujo accesible de contacto en V1.

## Impact

- Frontend: rutas, navegacion/sidebar, links, tabs, paginas `MisPublicacionesPage`, `ServicesPage`, `NeedsPage`, exploracion de servicios/necesidades, y cualquier accion de publicar o contactar desde publicaciones.
- Backend: rutas API de `services`, `needs`, `explore/services`, `explore/needs`, `profile/publication-preview` y categorias usadas solo por publicaciones cuando correspondan.
- Tests: agregar o ajustar pruebas para verificar que ningun rol pueda acceder a publicaciones en V1 y que las rutas directas queden bloqueadas.
- Documentacion: actualizar docs de publicaciones para indicar que el modulo queda diferido a V2 y que el codigo actual permanece dormido.
- Datos: no se eliminan tablas, migraciones, seeders ni registros existentes.
