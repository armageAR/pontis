## Why

La configuracion actual de Identidad mezcla dos decisiones distintas: quien puede ver la identidad y si el Hermano acepta aparecer en busquedas sin revelar identidad. Esto confunde al usuario y puede hacerle creer que activar el modo anonimo anula la audiencia seleccionada, cuando deberian combinarse.
Ademas la pagina es confusa y debe tener mas claridad para su uso.

## What Changes

- Separar visualmente en la pagina de perfil la audiencia de la seccion Identidad del control "Aparecer en las busquedas sin revelar mi identidad".
- Mantener la audiencia de Identidad editable aunque el modo sin revelar identidad este activado.
- Cambiar la regla funcional para que el modo sin revelar identidad no reemplace la audiencia de Identidad:
  - Si quien busca califica para la audiencia configurada de Identidad, ve la identidad.
  - Si quien busca no califica, el Hermano puede aparecer enmascarado cuando tenga activado el modo sin revelar identidad.
  - Si quien busca no califica y el modo sin revelar identidad no esta activado, el Hermano no aparece por identidad.
- Ajustar textos de ayuda para explicar la combinacion con ejemplos simples.
- Persistir la audiencia de Identidad y la preferencia de aparicion sin revelar identidad como decisiones independientes.
- El boton de guardar cambios que dispara la actualizacion de los datos del usuario debe estar ubicado al pie de la pagina a la derecha.

## Capabilities

### New Capabilities
- `profile-identity-visibility`: Reglas y experiencia de usuario para configurar la audiencia de Identidad y la aparicion en busquedas sin revelar identidad.

### Modified Capabilities

## Impact

- Frontend: pagina de perfil, controles de privacidad de Identidad, copy de ayuda y tipos/API de visibilidad si corresponde.
- Backend: modelo o almacenamiento de configuracion de visibilidad de Identidad, endpoints de `/profile/visibility`, busqueda de Hermanos y perfil publico/enmascarado.
- Datos existentes: migracion o compatibilidad para usuarios con Identidad en `anonymous`, conservando la intencion de aparecer sin revelar identidad y asignando una audiencia de Identidad segura por defecto.
- Tests: cobertura de reglas de busqueda para audiencia calificada, audiencia no calificada con modo anonimo, y audiencia no calificada sin modo anonimo.
