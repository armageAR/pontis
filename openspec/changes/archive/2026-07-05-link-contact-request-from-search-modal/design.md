## Context

La pagina completa `PersonPage` ya implementa el flujo de solicitud de contacto: boton "Solicitar contacto", modal con mensaje, motivo, datos propios a compartir, indicador de privacidad y envio a `POST /contact-requests` con `source=search`.

La busqueda de Hermanos abre `PersonProfileModal` desde el listado, pero ese modal solo muestra datos del perfil. Como resultado, el flujo principal de descubrimiento queda sin salida clara hacia contacto, aunque backend y `PersonPage` ya soporten la capacidad.

## Goals / Non-Goals

**Goals:**

- Agregar "Solicitar contacto" dentro de `PersonProfileModal`.
- Reutilizar el mismo flujo/modal que usa `PersonPage`.
- Permitir contacto desde perfiles anonimos/incognito cuando `can_request_contact` lo permita.
- Mostrar boton deshabilitado y aclaracion en hover cuando `can_request_contact === false`.
- Exigir que el usuario elija motivo antes de enviar.
- Mantener el modal de perfil abierto y mostrar confirmacion "Solicitud enviada" luego del envio.

**Non-Goals:**

- No cambiar reglas backend de consentimiento/contacto salvo que falte exponer `can_request_contact`.
- No redisenar la pagina completa de perfil.
- No eliminar la ruta `/people/:id`.
- No agregar contacto desde publicaciones, porque publicaciones estan diferidas a V2.

## Decisions

1. Extraer un componente reutilizable de solicitud de contacto.

   Rationale: `PersonPage` ya contiene el formulario correcto. Extraerlo evita duplicar validaciones, labels, seleccion de campos y `PrivacyIndicator`.

   Nombre sugerido: `ContactRequestModal`.

   Props sugeridas:
   - `open`
   - `requesteeId`
   - `requesteeName`
   - `onClose`
   - `onSent`
   - `source="search"`

   Alternative considered: copiar el formulario dentro de `PersonProfileModal`. Se descarta porque generaria experiencias divergentes.

2. `PersonProfileModal` debe usar el mismo endpoint `/people/{id}` que ya usa, pero modelando `can_request_contact`.

   Rationale: el modal ya carga el perfil completo. Si el backend ya devuelve `can_request_contact`, solo falta tiparlo y usarlo.

3. El motivo no debe tener default efectivo para enviar.

   Rationale: el usuario pidio que elija. La UI puede mostrar un placeholder como "Selecciona un motivo" y requerir una opcion antes de enviar.

   Alternative considered: default `profession_search`. Se descarta por decision explicita.

4. En `can_request_contact === false`, mostrar boton deshabilitado con aclaracion en hover.

   Rationale: ocultar la accion puede parecer una omision. El estado deshabilitado comunica que el flujo existe pero no esta permitido por la configuracion del Hermano.

   Implementacion sugerida: `button disabled title="Este Hermano no acepta solicitudes de contacto desde busquedas."`

5. Confirmacion dentro del modal de perfil.

   Rationale: el usuario viene de busqueda y abrio un modal contextual. Cerrar todo al enviar puede cortar el contexto. Se muestra un `Alert` o mensaje de exito dentro del modal y se desactiva/reemplaza la accion.

## Risks / Trade-offs

- Anidar un modal de contacto dentro del modal de perfil puede complicar foco/accesibilidad -> Mitigar usando el mismo `Modal` con manejo claro de apertura y cierre, o renderizar el contacto como submodal controlado desde el perfil.
- `can_request_contact` puede no estar tipado en `PersonProfileModal` -> Mitigar extendiendo la interface y agregando tests.
- Reutilizar el componente puede requerir adaptar `PersonPage` -> Mitigar con refactor pequeño y tests de regresion.
- Perfiles anonimos requieren no revelar identidad -> Mitigar usando el nombre/label ya enmascarado del perfil modal y enviando internamente `requestee_id`.

## Migration Plan

1. Extraer formulario/modal de contacto desde `PersonPage` a componente compartido.
2. Reemplazar uso local en `PersonPage` por el componente compartido sin cambiar comportamiento.
3. Agregar accion de contacto a `PersonProfileModal`.
4. Agregar estado de exito dentro del modal de perfil.
5. Agregar tests frontend para contacto desde modal de busqueda, estado deshabilitado y perfiles anonimos.
