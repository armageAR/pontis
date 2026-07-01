## Why

La gestion de grados masonicos y cargos en Talleres dentro del perfil permite hoy seleccionar Talleres de forma demasiado abierta y modela los grados con fecha de fin manual. Esto puede generar datos institucionales inconsistentes: cargos vinculados a Talleres donde el Hermano no pertenece, grados fuera de secuencia, saltos de grado o periodos donde un Hermano queda sin grado.

## What Changes

- Reemplazar el selector/buscador de Taller por un desplegable limitado a los Talleres a los que el usuario ya pertenece cuando se agrega o edita un grado o cargo.
- Hacer obligatorio que cargos y grados asociados a un Taller usen uno de los Talleres propios del usuario.
- Eliminar la fecha de fin manual de los grados en el formulario de perfil.
- Definir que un grado termina automaticamente cuando inicia el siguiente grado.
- Validar la progresion de grados en orden estricto: `aprendiz` -> `companero` -> `maestro`.
- Impedir saltar grados, retroceder de grado, duplicar grados vigentes o dejar a un Hermano activo sin grado actual.
- Mantener historial de grados calculando el estado vigente por la fecha de inicio y la secuencia.
- Conservar la fecha de fin opcional para cargos, porque los cargos si pueden terminar sin que empiece otro cargo.
- Mostrar mensajes claros en el perfil cuando una accion no sea valida por Taller o por secuencia de grado.

## Capabilities

### New Capabilities
- `profile-degrees-positions`: Reglas y experiencia de usuario para gestionar grados masonicos y cargos de Taller desde el perfil, incluyendo seleccion de Taller propio y progresion valida de grados.

### Modified Capabilities

## Impact

- Frontend: modales de grados y cargos en la pagina de perfil, tipos de API de perfil, desplegables de Talleres propios, validaciones y mensajes.
- Backend: endpoints `/profile/degrees` y `/profile/positions`, validacion de pertenencia a Taller, validacion de secuencia de grados y calculo de grado vigente.
- Datos existentes: revision o compatibilidad para historiales con `end_date` manual, grados fuera de secuencia o Talleres que no pertenecen al usuario.
- Tests: cobertura de seleccion de Taller propio, rechazo de Taller ajeno, avance valido de grado, rechazo de salto/retroceso y preservacion de historial.
