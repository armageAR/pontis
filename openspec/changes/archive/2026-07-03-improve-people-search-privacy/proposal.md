## Why

La busqueda de Hermanos tiene dos problemas respecto a los docs:

1. Permite buscar por email y matricula masonica. La matricula es dato sensible y ambos criterios permiten confirmar por inferencia datos privados de un Hermano, lo que contradice "la busqueda no debe permitir inferencia de datos sensibles a traves de filtros" (permissions #14, decisions #12/#13).
2. No permite buscar por profesion u oficio, que son criterios exigidos por los docs (workflows #11, decisions #13) y centrales al proposito de Pontis: encontrar un Hermano que pueda ayudar antes de recurrir a un externo.

## What Changes

- Quitar email y matricula masonica como criterios de busqueda para usuarios comunes en la busqueda de Hermanos.
- Superadmin conserva la busqueda por email y matricula unicamente en la administracion de usuarios (pantalla de admin), no en la busqueda comunitaria.
- Agregar busqueda por profesion/oficio en la busqueda de Hermanos, usando el campo de profesion de la ficha.
- La coincidencia por profesion debe respetar la visibilidad del bloque `profession`:
  - Si el bloque no es visible para el buscador, el Hermano no debe aparecer identificado por ese criterio; puede aparecer como coincidencia anonima solo si su configuracion lo permite.
- Mantener el resto de criterios existentes (nombre, taller, provincia, localidad, pais) sin cambios.

## Capabilities

### New Capabilities
- `people-search`: Criterios y reglas de privacidad de la busqueda de Hermanos: campos buscables, campos excluidos por sensibilidad y respeto de visibilidad por bloque.

### Modified Capabilities

## Impact

- `PeopleController`: cambio de los campos incluidos en el criterio `q` y nueva condicion por profesion con chequeo de visibilidad.
- Pantalla de busqueda: campo o indicacion para busqueda por profesion/oficio.
- Admin de usuarios: verificar que la busqueda administrativa por email/matricula siga disponible solo para Superadmin.
- Tests de visibilidad: un Hermano con bloque profesion privado no aparece identificado al buscar su profesion; email y matricula ya no devuelven resultados en la busqueda comunitaria.
