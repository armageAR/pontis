## Context

Los seeders actuales mezclan datos de prueba amplios con escenarios de publicaciones y estados variados. Para V1, publicaciones quedan diferidas, y los tests/manual QA necesitan un dataset estable: pocos talleres, usuarios activos, grados/cargos consistentes y privacidad variada pero predecible.

La base local consultada contiene talleres reales cargados por crawler. Los seleccionados para hardcodear en `WorkshopSeeder` son:

| Jurisdiccion | Numero | Nombre | Zona | Dia | Direccion | Ciudad/Provincia |
| --- | ---: | --- | --- | --- | --- | --- |
| CABA | 1 | UNION DEL PLATA | Logias de Ciudad Autonoma de Buenos Aires | Lunes | TTE. GRAL. J. D. PERON 1242 | CABA, Ciudad Autonoma de Buenos Aires |
| CABA | 2 | CONFRATERNIDAD ARGENTINA | Logias de Ciudad Autonoma de Buenos Aires | Jueves | TTE. GRAL. J. D. PERON 1242 | CABA, Ciudad Autonoma de Buenos Aires |
| Provincia de Buenos Aires | 80 | LA PLATA | Logias de Buenos Aires / La Pampa | Miercoles | CALLE 49 No 731 E/9 Y 10 | La Plata, Buenos Aires |
| Salta | 702 | DR. MARCELO O' CONNOR | Logias de Salta / Tucuman / Jujuy | Lunes | Calle Mitre No 1555 | Salta, Salta |
| Chubut | 1222 | TRIANGULO LUZ DEL ARA 679 | Logias de las Provincias Patagonicas | Miercoles | null | Rawson, Chubut |

## Goals / Non-Goals

**Goals:**

- Dejar un dataset demo/test chico y deterministicamente reproducible.
- Garantizar login conocido: `admin@pontis.com` / `password`.
- Crear cinco talleres reales hardcodeados con distribucion geografica definida.
- Crear 40 usuarios activos, 8 por taller, todos con password `password`.
- Crear una estructura masonica consistente: 6 maestros, 1 companero y 1 aprendiz por taller.
- Asignar cargos solo a maestros y usar exactamente los seis cargos requeridos.
- Cubrir visibilidad variada por taller, incluyendo incognito, nada visible y todo visible.
- Evitar crear publicaciones/ofertas/necesidades en V1.

**Non-Goals:**

- No importar dinamicamente desde crawler durante el seeding de tests.
- No depender de la base local para ejecutar tests.
- No crear publicaciones ni datos demo de `services`/`needs`.
- No cambiar el modelo de permisos ni las migrations.
- No crear usuarios pendientes, rechazados, suspendidos o verificando en este seeder demo.

## Decisions

1. Hardcodear talleres reales en `WorkshopSeeder`.

   Rationale: el dataset debe ser estable en cualquier ambiente de test. El crawler sirvio como fuente, pero los tests no deben depender de que el crawler haya corrido.

   Alternative considered: consultar la base cargada por crawler durante el seeder. Se descarta porque fallaria en bases limpias.

2. Reemplazar el comportamiento amplio de `DemoUsersSeeder` por una matriz cerrada.

   Rationale: los tests necesitan saber exactamente cuantos usuarios hay por taller, que estado tienen, que grados/cargos poseen y que visibilidad exponen.

   Alternative considered: conservar el seeder actual y agregar otro. Se descarta si ambos corren desde `DatabaseSeeder`, porque duplicaria datos y volveria inestable el dataset.

3. Usar emails deterministicos por taller y posicion.

   Rationale: facilita login manual, asserts y re-ejecucion idempotente.

   Ejemplo recomendado: `uniondelplata.maestro1@pontis.test`, `laplata.aprendiz@pontis.test`.

4. Modelar grados historicos segun progresion.

   Rationale: un Maestro debe tener Aprendiz y Companero previos. Un Companero debe tener Aprendiz previo. Un Aprendiz solo tiene Aprendiz vigente.

   Alternative considered: crear solo grado vigente. Se descarta porque el requerimiento explicita historial para maestros y porque mejora cobertura de UI.

5. Usar `Maestro de Ceremonias` como nombre de cargo.

   Rationale: coincide con el catalogo actual de `PositionSeeder`.

6. Crear presets de visibilidad por taller.

   Rationale: cada taller debe permitir probar casos extremos y mixtos.

   Presets minimos por taller:
   - `incognito`: identidad privada con aparicion anonima cuando corresponda, contacto privado, datos personales mayormente privados.
   - `private`: no muestra nada.
   - `open`: muestra todo a registrados.
   - `mixed_*`: combinaciones intermedias para los cinco usuarios restantes.

## Risks / Trade-offs

- El nombre de provincia en datos crawler venia vacio para algunos talleres de Buenos Aires/Chubut -> Mitigar hardcodeando `province` corregida segun ciudad/zona seleccionada.
- Los tests existentes pueden asumir mas talleres/usuarios o estados variados -> Mitigar revisando tests que dependan del seeder demo y ajustar assertions al nuevo dataset.
- Usuarios demo con password comun no deben existir en produccion -> Mitigar manteniendo este seeder para local/test y documentando que no debe ejecutarse como seed productivo salvo entornos controlados.
- El seeder actual borra publicaciones demo; al removerlas pueden fallar tests antiguos de publicaciones -> Mitigar alineando con `publication-v2-deferral`.
- Diferencias de acentos en nombres de cargos/talleres pueden romper busquedas exactas -> Mitigar usando nombres exactamente iguales al catalogo y normalizando solo emails/slugs.

## Migration Plan

1. Actualizar `SuperAdminSeeder` para asegurar `admin@pontis.com` con password `password`, incluso si ya existe.
2. Actualizar `WorkshopSeeder` con los cinco talleres hardcodeados.
3. Actualizar `DemoUsersSeeder` para generar 8 usuarios activos por taller, grados, cargos y visibilidad.
4. Eliminar creacion de publicaciones demo desde `DemoUsersSeeder`.
5. Agregar tests de seeders con `RefreshDatabase` o comando de seed controlado.
6. Ejecutar seeders en base limpia y verificar conteos.
