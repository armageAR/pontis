## Context

El dashboard actual (`DashboardPage`) muestra tarjetas de administracion, talleres y directorio segun rol. El endpoint `GET /api/dashboard` ya centraliza datos de panel como notificaciones, permiso administrativo y validaciones pendientes, pero no informa completitud de perfil.

La ficha propia editable vive en `/profile`. El nuevo recuadro debe ser un acceso rapido adicional para todos los usuarios autenticados y debe mostrar una barra de completitud.

## Goals / Non-Goals

**Goals:**

- Mostrar una tarjeta "Mi Perfil" en el dashboard para todos los usuarios autenticados.
- Navegar desde la tarjeta a `/profile`.
- Mostrar un porcentaje y barra de completitud del perfil.
- Mantener el calculo de completitud centralizado para no duplicarlo en UI.
- No reemplazar accesos existentes al perfil.

**Non-Goals:**

- No redisenar el dashboard completo.
- No bloquear acceso por perfil incompleto.
- No agregar wizard de onboarding.
- No crear nuevas tablas para completitud.
- No hacer completitud por rol o por taller en esta iteracion.

## Decisions

1. Agregar `profile_completion` al payload de `GET /api/dashboard`.

   Rationale: el dashboard ya carga datos de panel desde un endpoint central. Incluir completitud ahi evita una segunda llamada a `/profile` y mantiene la UI simple.

   Alternative considered: calcular completitud en frontend desde `AuthContext` o llamando `/profile`. Se descarta porque puede duplicar reglas y aumentar latencia.

2. Calcular completitud en backend con una lista explicita de campos.

   Rationale: la ficha tiene muchos campos opcionales. Una lista explicita permite ajustar el concepto de completitud sin depender de todos los campos de la tabla.

   Campos candidatos iniciales:
   - Identidad/base: `name`, `last_name`, `email`, `dni`, `masonic_id`.
   - Masonicos: `birth_date`, `initiation_date`, `masonic_status`.
   - Contacto: `phone` o `whatsapp` o `alternative_email`.
   - Ubicacion: `province`, `locality`.
   - Profesional/bio: `profession`, `occupation`, `bio`.
   - Taller/grados: al menos un taller activo y al menos un grado.

3. Representar completitud como objeto, no solo numero.

   Rationale: permite extender luego con campos faltantes sin romper contrato.

   Forma propuesta:

   ```json
   {
     "percent": 72,
     "completed": 13,
     "total": 18
   }
   ```

4. Renderizar "Mi Perfil" como tarjeta dentro del grid existente de dashboard.

   Rationale: es un acceso rapido comparable a las otras tarjetas. Debe aparecer siempre, independiente de rol.

   Alternative considered: ponerlo en header o sidebar. Se descarta porque el pedido especifica un recuadro en el panel inicial.

## Risks / Trade-offs

- La definicion de completitud puede ser debatible -> Mitigar manteniendo una lista explicita y testeada de campos.
- Un superadmin puede no tener datos masonicos completos -> Mitigar mostrando la tarjeta igual; el porcentaje refleja los datos existentes.
- Si la barra se basa en muchos campos opcionales, puede frustrar al usuario -> Mitigar usando pocos campos importantes.
- Cambiar el contrato de `/api/dashboard` puede requerir actualizar tests y tipos frontend -> Mitigar agregando campo backward-compatible sin remover campos existentes.

## Migration Plan

1. Agregar calculo backend de completitud al dashboard.
2. Actualizar tipo `DashboardData`.
3. Agregar tarjeta "Mi Perfil" al grid existente.
4. Agregar estilos responsive para barra de progreso.
5. Agregar tests backend y frontend.
