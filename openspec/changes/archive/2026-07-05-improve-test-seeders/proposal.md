## Why

Los seeders actuales generan demasiada variacion aleatoria para pruebas manuales y automatizadas: estados mixtos, publicaciones dormidas, muchos talleres y usuarios con patrones poco previsibles. V1 necesita un dataset chico, deterministico y representativo para validar login, talleres, grados, cargos y privacidad sin depender de datos externos en tiempo de test.

## What Changes

- Mantener el superadmin por defecto `admin@pontis.com` con password `password`.
- Crear exactamente 5 talleres demo/test usando datos reales del crawler/base local y hardcodearlos en el seeder:
  - 2 talleres de Ciudad Autonoma de Buenos Aires.
  - 1 taller de Provincia de Buenos Aires.
  - 1 taller de Salta.
  - 1 taller de Chubut.
- Crear 8 usuarios activos por taller, 40 usuarios en total, todos con password `password`.
- Para cada taller, crear:
  - 6 Maestros.
  - 1 Companero.
  - 1 Aprendiz.
- Para cada Maestro, crear historial completo de grados: Aprendiz, Companero y Maestro.
- Asignar cargo solo a los 6 Maestros de cada taller:
  - Venerable Maestro.
  - Primer Vigilante.
  - Segundo Vigilante.
  - Maestro de Ceremonias.
  - Experto.
  - Tesorero.
- Asegurar que las personas de cada taller tengan provincia/localidad coherente con la provincia del taller.
- Asegurar que cada taller tenga al menos:
  - 1 persona incognito.
  - 1 persona que no muestra nada.
  - 1 persona que muestra todo.
- Variar el resto de perfiles con niveles intermedios de visibilidad.
- Eliminar del seeder demo la creacion de publicaciones/ofertas/necesidades porque publicaciones quedan diferidas a V2.

## Capabilities

### New Capabilities

- `test-seeders`: Define el dataset deterministico de seeders para V1: superadmin, cinco talleres reales, usuarios activos por taller, grados, cargos y presets de visibilidad.

### Modified Capabilities

- `publication-v2-deferral`: Asegura que los seeders de V1 no creen contenido visible ni datos demo activos de publicaciones.

## Impact

- Backend seeders:
  - `SuperAdminSeeder`.
  - `WorkshopSeeder`.
  - `DemoUsersSeeder`.
  - Posiblemente `PositionSeeder` solo para verificar que exista `Maestro de Ceremonias`.
- Tests y entorno local:
  - Login deterministico para superadmin.
  - Dataset estable para probar busqueda, permisos por taller, grados/cargos y visibilidad.
- Datos:
  - No requiere migraciones.
  - Los talleres seleccionados deben hardcodearse con datos reales ya observados desde crawler/base local.
  - El seeder debe ser idempotente.
