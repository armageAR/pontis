## Context

Pontis ya permite configurar visibilidad por bloque del perfil y usa el bloque `identity` para nombre, apellido, matricula y email. En la implementacion actual, `anonymous` funciona como un nivel de visibilidad dentro de `identity`, por lo que al activarlo reemplaza la audiencia configurada y deshabilita el selector en la interfaz.

Esa representacion mezcla dos conceptos:

- Audiencia de Identidad: quien esta autorizado a ver nombre, apellido, matricula y email.
- Aparicion sin revelar identidad: si el Hermano acepta aparecer en busquedas para quienes no califican para ver su identidad.

La regla esperada es contextual al Hermano que busca. Un Hermano puede permitir que su identidad la vean los Hermanos de sus Talleres y, al mismo tiempo, aparecer enmascarado para otros Hermanos registrados.

## Goals / Non-Goals

**Goals:**

- Hacer que la pagina de perfil comunique con claridad las dos decisiones.
- Mejorar la claridad general del formulario de perfil para que las acciones principales sean predecibles.
- Persistir audiencia de Identidad y aparicion sin revelar identidad de manera independiente.
- Evaluar busquedas segun la audiencia real del viewer.
- Conservar privacidad por defecto y no ampliar exposicion de identidad.
- Migrar o interpretar configuraciones existentes con `identity = anonymous` sin revelar mas datos que antes.

**Non-Goals:**

- No agregar un motor generico de reglas de privacidad.
- No cambiar los niveles generales de visibilidad de otras secciones.
- No implementar perfiles publicos ni busqueda sin login.
- No cambiar reglas de consentimiento para contacto.

## Decisions

1. Separar el flag de aparicion anonima de la audiencia de Identidad.

   La audiencia de Identidad debe seguir usando niveles existentes (`private`, `workshop`, `my_workshops`, `registered`). La aparicion sin revelar identidad debe ser un booleano independiente del bloque `identity`.

   Alternativa considerada: mantener `anonymous` como nivel especial. Se descarta porque no puede representar "mis Talleres ven identidad, otros me ven enmascarado" sin perder la audiencia elegida.

2. Usar un default conservador para datos existentes con `identity = anonymous`.

   Para usuarios existentes que tengan Identidad en `anonymous`, la migracion debe activar la preferencia de aparicion sin revelar identidad y asignar una audiencia de Identidad conservadora, preferentemente `workshop` si no existe una audiencia previa. Esto preserva la posibilidad de aparecer enmascarado sin abrir identidad a todos.

   Alternativa considerada: mapear `anonymous` a `registered` mas flag anonimo. Se descarta porque podria revelar identidad a mas usuarios que antes.

3. Evaluar busquedas en dos pasos.

   Primero se calcula si el viewer puede ver la Identidad segun la audiencia. Si puede, el resultado muestra identidad. Si no puede, el sistema solo incluye el resultado enmascarado cuando el flag de aparicion sin revelar identidad esta activo y el match no depende de campos de identidad no visibles.

   Alternativa considerada: permitir match por nombre aunque la identidad este enmascarada. Se descarta porque buscar por nombre/matricula/email revelaria indirectamente identidad.

4. Separar los controles visualmente en la pagina de perfil.

   El selector "Quienes pueden ver mi identidad" debe estar en su propio bloque. El checkbox "Aparecer en busquedas sin revelar mi identidad" debe estar en otro bloque o fila separada con texto que explique que no anula la seleccion anterior.

   Alternativa considerada: mantener ambos controles en el mismo box con mejor copy. Se descarta porque el problema principal es la asociacion visual y funcional entre ambos controles.

5. Ubicar la accion principal de guardado al pie derecho de la pagina.

   El boton que guarda los cambios de datos del perfil debe aparecer al final del formulario, alineado a la derecha. Esto hace que el flujo de edicion sea mas claro: el Hermano revisa los datos y confirma los cambios al terminar.

   Alternativa considerada: mantener el boton cerca de secciones superiores. Se descarta porque en una pagina de perfil con varias secciones puede hacer menos evidente que la accion aplica a los datos editables del perfil.

## Risks / Trade-offs

- Riesgo: migracion incompleta de `identity = anonymous` deja usuarios con estados ambiguos. Mitigacion: agregar compatibilidad temporal que interprete `anonymous` como flag activo y audiencia conservadora hasta completar migracion.
- Riesgo: resultados enmascarados aparezcan por coincidencias de identidad no visibles. Mitigacion: mantener la regla de que nombre, apellido, matricula y email solo matchean cuando el viewer puede ver Identidad.
- Riesgo: el usuario no entienda por que algunos ven identidad y otros no. Mitigacion: copy breve en la interfaz con ejemplo concreto por audiencia.
- Riesgo: mover el boton de guardado cambie expectativas si algunos controles guardan automaticamente. Mitigacion: distinguir visualmente entre controles de privacidad que se guardan al cambiar y datos del perfil que requieren el boton final, o unificar el comportamiento si el producto decide hacerlo.

## Migration Plan

1. Agregar almacenamiento para la preferencia independiente de aparicion sin revelar identidad.
2. Migrar configuraciones existentes `identity = anonymous` a audiencia `workshop` y flag anonimo activo, salvo que exista una fuente confiable de audiencia previa.
3. Actualizar backend para aceptar y devolver ambos valores en `/profile/visibility`.
4. Actualizar busqueda para evaluar audiencia y flag por separado.
5. Actualizar frontend para mostrar controles separados y no deshabilitar el selector de audiencia.
6. Mover el boton de guardado de datos del perfil al pie derecho de la pagina.
7. Mantener compatibilidad de lectura para `anonymous` durante la transicion si quedan datos previos.

## Open Questions

- Confirmar el nombre final del campo persistido para el flag, por ejemplo `identity_search_anonymous` o `anonymous_identity_search`.
- Confirmar si el flag pertenece al bloque `identity` o a una tabla/preferencia general de busqueda de perfil.
