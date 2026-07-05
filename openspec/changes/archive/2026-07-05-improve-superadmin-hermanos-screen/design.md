## Context

La administración de usuarios se renderiza dentro de la sección de Administración y consume el endpoint administrativo `/users`. Ese payload incluye datos sensibles como email, estado y membresías de Taller, por lo que no debe quedar disponible para Admin de Taller ni usuarios comunes en la pantalla de Hermanos del Superadmin.

Actualmente el filtro de Taller usa un `select` poblado desde `/my-workshops`, la tabla muestra `name` como primera columna, no expone apellido/s por separado y solo algunas columnas son ordenables. El componente `WorkshopPicker` ya resuelve la selección por escritura de nombre o número de Taller y debe reutilizarse para mantener una experiencia consistente.

## Goals / Non-Goals

**Goals:**

- Hacer que la pantalla administrativa de Hermanos sea exclusiva del Superadmin en frontend y backend.
- Filtrar Hermanos por Taller usando el selector typeahead existente.
- Agregar filtro por Provincia y combinarlo con filtros existentes.
- Separar apellido/s y nombre/s en columnas distintas, en el orden solicitado.
- Ordenar desde los encabezados de todas las columnas visibles sin perder filtros activos.
- Corregir la presentación de filas para que los separadores sean continuos y alineados.

**Non-Goals:**

- Cambiar reglas de privacidad de la búsqueda comunitaria de Hermanos.
- Cambiar la pantalla `Mis Hermanos` ni los flujos de administración de Taller.
- Crear un nuevo sistema de permisos global; se usará la lógica de rol existente.
- Rediseñar acciones administrativas fuera de las disponibles en la tabla actual.

## Decisions

1. **Control de acceso server-side como fuente de verdad**

   El endpoint que alimenta esta pantalla debe abortar con 403 si el actor no es Superadmin. La UI también debe ocultar navegación y evitar renderizar la tabla para otros roles, pero esa protección es secundaria porque el payload es sensible.

   Alternativa considerada: mantener acceso a Admin de Taller y limitar filas por Taller. Se descarta para esta pantalla porque el requerimiento indica que solo el Superadmin puede verla; las tareas de Admin de Taller deben permanecer en Administración/Validaciones.

2. **Filtros y sort en API**

   `workshop_id`, `province`, `sort_by`, `sort_direction` y `page` deben viajar al endpoint de listado para que el resultado paginado sea estable. El frontend solo conserva el estado de filtros, renderiza la selección y solicita datos.

   Alternativa considerada: ordenar y filtrar en frontend sobre la página actual. Se descarta porque produciría resultados incompletos en listados paginados.

3. **Reuso de `WorkshopPicker`**

   El filtro de Taller debe reemplazar el `select` por `WorkshopPicker` o una variante mínima compatible con filtros. Al seleccionar un Taller se guarda su id en `workshop_id`; al limpiar el selector se remueve el filtro y se vuelve a la página 1.

   Alternativa considerada: mejorar el `select` actual. Se descarta porque no permite escribir por nombre y duplica una interacción ya existente.

4. **Ordenamiento explícito por columnas visibles**

   El backend debe aceptar una whitelist alineada con los encabezados visibles: `last_name`, `name`, `email`, `workshops`, `status` y `actions`. Para `workshops` se debe definir un orden determinístico por Taller principal o primer Taller ordenado por número/nombre. Para `actions`, que no es un dato propio del usuario, el sort debe ser determinístico y documentado; una opción aceptable es ordenar por disponibilidad/cantidad de acciones administrativas y luego por id para mantener estabilidad.

   Alternativa considerada: dejar Acciones sin sort porque no representa un dato. Se descarta para respetar el requerimiento de que todas las columnas puedan ordenarse al hacer click en el encabezado.

5. **Apellido/s como campo de primer nivel**

   El resource administrativo debe exponer `last_name` y el frontend debe tratarlo como columna independiente. La búsqueda existente puede seguir incluyendo nombre, email y matrícula, pero debe considerar apellido/s si aún no lo hace.

## Risks / Trade-offs

- **Acceso usado por flujos existentes de Admin de Taller** -> Separar esta pantalla sensible de las validaciones/tareas de taller y revisar que ningún flujo de Admin dependa del listado global `/users`.
- **Sort por Taller puede requerir joins/subqueries** -> Implementar una estrategia estable y cubierta por tests para evitar duplicados cuando un Hermano tiene varios Talleres.
- **Sort por Acciones no es semánticamente fuerte** -> Documentar el criterio y mantenerlo determinístico; si todas las filas tienen las mismas acciones, usar id como desempate.
- **Filtro por Provincia puede referirse a provincia del perfil o del Taller** -> Para esta pantalla debe filtrar por provincia del Hermano, porque el requerimiento dice “trae a los hermanos de la pcia seleccionada”.
