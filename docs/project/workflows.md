# Pontis - Workflows

## Propósito del documento

Este documento define los flujos funcionales principales de Pontis V1.

Su objetivo es describir cómo se mueven los usuarios, solicitudes, publicaciones, servicios, necesidades, contactos, talleres, grados, cargos y acciones administrativas dentro del sistema.

Este documento no define endpoints, controladores, jobs, eventos técnicos, tablas, migrations ni componentes frontend. Es una guía funcional para implementar los procesos de manera coherente.

---

# 1. Principios generales de workflows

Todos los flujos de Pontis V1 deben respetar estos principios:

* El usuario debe estar activo y validado para acceder al sistema interno.
* Toda información es privada por defecto.
* La visibilidad debe ser explícita.
* Las acciones sensibles requieren revisión humana.
* El contacto entre Hermanos debe respetar consentimiento.
* Las acciones administrativas relevantes deben auditarse.
* Los roles administrativos no son lo mismo que los cargos masónicos.
* Los cargos masónicos pueden generar permisos contextuales, pero solo dentro de su Taller.
* El grado Maestro puede habilitar reglas específicas, pero no otorga permisos administrativos generales.
* El sistema debe priorizar privacidad cuando haya dudas.

---

# 2. Actores principales

## Visitante público

Usuario no autenticado.

Puede:

* Ver homepage pública.
* Iniciar registro.

No puede:

* Ver Hermanos.
* Ver Talleres internos.
* Ver servicios.
* Ver necesidades.
* Ver publicaciones.
* Realizar búsquedas internas.
* Contactar Hermanos.

## Hermano pendiente

Usuario registrado pero todavía no validado.

Puede:

* Completar datos requeridos.
* Seleccionar Taller principal desde catálogo mínimo.
* Consultar estado de validación, si se implementa.
* Corregir datos si se le solicita.

No puede:

* Acceder al sistema interno.
* Buscar Hermanos.
* Ver información interna.
* Publicar.
* Contactar.

## Hermano activo

Usuario validado.

Puede:

* Acceder al sistema interno.
* Administrar su ficha.
* Configurar visibilidad.
* Crear servicios.
* Crear necesidades.
* Crear publicaciones.
* Buscar Hermanos, servicios, necesidades o Talleres según permisos.
* Iniciar solicitudes de contacto.
* Responder solicitudes de contacto recibidas.

## Admin de Taller

Usuario con permisos administrativos sobre uno o más Talleres.

Puede actuar solo dentro de su alcance.

## Superadmin

Usuario con permisos administrativos globales.

Puede administrar usuarios, Talleres, catálogos, validaciones, crawler, cambios sensibles y auditoría funcional.

---

# 3. Flujo de registro y validación

## Objetivo

Permitir que una persona se registre como Hermano y sea validada antes de acceder al sistema interno.

## Actores

* Visitante público.
* Hermano pendiente.
* Admin de Taller.
* Superadmin.

## Flujo principal

1. El visitante accede a la homepage pública.
2. El visitante inicia el registro.
3. El sistema solicita datos obligatorios iniciales:

   * Nombre.
   * Apellido.
   * Email.
   * Documento / DNI.
   * Matrícula masónica nacional.
   * Taller principal o Taller de pertenencia inicial.
   * Grado actual declarado.
4. El usuario selecciona su Taller principal desde un catálogo mínimo.
5. El usuario declara su grado actual.
6. El sistema crea el usuario en estado `Pendiente de verificación de email`.
7. El sistema envía un email de verificación al usuario.
8. El usuario hace clic en el enlace del email para verificar su dirección.
9. El sistema cambia el estado del usuario a `Pendiente de validación` y notifica al Admin del Taller.
10. El sistema crea la ficha personal asociada, privada por defecto.
11. El Admin del Taller principal o un Superadmin revisa la solicitud.
12. El revisor puede:

    * Aprobar.
    * Rechazar.
    * Solicitar corrección.
13. Si aprueba:

    * El usuario pasa a estado `Activo`.
    * Se habilita acceso al sistema interno.
    * Se registra la relación Hermano-Taller inicial.
    * Se registra o confirma el grado actual.
    * Se notifica al usuario.
14. Si rechaza:

    * El usuario pasa a estado `Rechazado`.
    * No accede al sistema interno.
    * Se notifica al usuario, si corresponde.
15. Si solicita corrección:

    * El usuario pasa a estado `Pendiente de corrección`.
    * Se informa qué datos debe corregir.
    * El usuario puede corregir y reenviar.

## Reglas

* Un usuario pendiente no puede acceder al sistema interno.
* Un usuario pendiente solo puede ver el catálogo mínimo necesario para seleccionar Taller principal.
* La validación puede hacerla un Admin del Taller declarado o un Superadmin.
* El Admin de Taller no puede validar usuarios de otros Talleres.
* La aprobación debe quedar auditada.
* El rechazo debe quedar auditado.
* Las correcciones solicitadas deben quedar registradas.

## Estados involucrados

Estados de usuario:

* Pendiente de verificación de email.
* Pendiente de validación.
* Pendiente de corrección.
* Activo.
* Rechazado.

---

# 4. Flujo de completar y administrar ficha personal

## Objetivo

Permitir que un Hermano activo complete y mantenga su ficha personal respetando privacidad y restricciones de datos sensibles.

## Actores

* Hermano activo.
* Superadmin, para cambios sensibles.
* Admin de Taller, solo si existe una necesidad funcional dentro de su alcance.

## Flujo principal

1. El Hermano activo ingresa a su ficha.
2. El sistema muestra la ficha completa al propietario.
3. El Hermano completa o actualiza bloques de información:

   * Datos de contacto.
   * Ubicación.
   * Profesión.
   * Oficio.
   * Actividad.
   * Servicios.
   * Necesidades.
   * Intereses.
   * Configuración de visibilidad.
4. El Hermano configura visibilidad por bloque o campo, según disponibilidad.
5. El sistema muestra qué datos quedarán visibles y para qué audiencia.
6. El Hermano guarda cambios.
7. El sistema mantiene privados todos los datos no habilitados explícitamente.
8. El sistema registra cambios relevantes de visibilidad.

## Flujo alternativo: intento de cambio sensible

1. El Hermano intenta modificar un dato sensible.
2. El sistema no permite edición directa.
3. El sistema ofrece iniciar una solicitud de cambio sensible.
4. El flujo continúa en `Flujo de cambios sensibles`.

## Reglas

* El Hermano puede ver su ficha completa.
* Otros usuarios solo ven lo habilitado por visibilidad.
* Los datos sensibles no se editan directamente.
* La ficha no es pública sin login.
* La publicación de un servicio, necesidad o publicación interna no cambia automáticamente la visibilidad general de la ficha.

---

# 5. Flujo de cambio sensible

## Objetivo

Permitir cambios en datos restringidos mediante solicitud, revisión y aprobación.

## Datos sensibles

* Nombre.
* Apellido, si se considera parte de identidad legal.
* Documento / DNI.
* Matrícula masónica nacional.
* Taller principal inicial, cuando afecte validación institucional.
* Otros datos que el sistema marque como sensibles.

## Actores

* Hermano solicitante.
* Superadmin.

## Flujo principal

1. El Hermano ingresa a su ficha.
2. Elige solicitar cambio de un dato sensible.
3. Indica:

   * Dato a cambiar.
   * Valor actual.
   * Nuevo valor propuesto.
   * Motivo u observación, si corresponde.
4. El sistema crea una solicitud en estado `Pendiente`.
5. El sistema notifica a Superadmin.
6. El Superadmin revisa la solicitud.
7. El Superadmin puede:

   * Aprobar.
   * Rechazar.
   * Solicitar información adicional.
8. Si aprueba:

   * El sistema actualiza el dato.
   * Registra quién aprobó y cuándo.
   * Notifica al Hermano.
9. Si rechaza:

   * El dato original se conserva.
   * Se registra el rechazo.
   * Se notifica al Hermano.
10. Si solicita información adicional:

    * La solicitud pasa a `Requiere información adicional`.
    * El Hermano puede responder o completar datos.

## Estados posibles

* Pendiente.
* Aprobada.
* Rechazada.
* Cancelada por el usuario.
* Requiere información adicional.

## Reglas

* Solo Superadmin puede aprobar cambios sensibles globales.
* El Admin de Taller no puede aprobar documento, matrícula o identidad legal.
* Toda resolución debe quedar auditada.
* Si el usuario cancela la solicitud, el dato original se conserva.

---

# 6. Flujo de relación Hermano-Taller

## Objetivo

Administrar la pertenencia de un Hermano a uno o más Talleres.

## Actores

* Hermano.
* Admin de Taller.
* Superadmin.

## Flujo principal: creación de relación inicial

1. Durante el registro, el usuario selecciona un Taller principal.
2. Al aprobarse el registro, el sistema crea la relación Hermano-Taller inicial.
3. La relación queda como `Activa`.
4. Se marca como Taller principal.
5. Se registra fecha de inicio.
6. Se audita la creación.

## Flujo: solicitud de pertenencia a otro Taller

1. El Hermano activo solicita vincularse a otro Taller.
2. El sistema crea una relación en estado `Pendiente`.
3. El Admin del Taller destino o Superadmin revisa la solicitud.
4. El revisor puede:

   * Aprobar.
   * Rechazar.
   * Solicitar corrección o información adicional, si se implementa.
5. Si aprueba:

   * La relación pasa a `Activa`.
   * Se registra fecha de inicio.
   * Se notifica al Hermano.
6. Si rechaza:

   * La relación queda rechazada o finalizada según definición.
   * Se notifica al Hermano.

## Flujo: cambio de estado de relación

1. Admin de Taller o Superadmin accede a la relación.
2. Cambia estado:

   * Activa.
   * Inactiva.
   * Suspendida.
   * Finalizada.
   * Histórica.
3. El sistema registra fecha, usuario y motivo si corresponde.
4. El sistema audita el cambio.
5. Si la relación deja de estar activa, se deben revisar permisos contextuales derivados.

## Reglas

* Un Hermano puede pertenecer a varios Talleres.
* Todo Hermano debe tener un Taller principal.
* Solo puede haber un Taller principal activo a la vez, salvo decisión futura contraria.
* El Admin de Taller solo puede gestionar relaciones dentro de sus Talleres.
* La pertenencia a un Taller no expone automáticamente todos los datos privados del Hermano.
* Si una relación finaliza, los permisos contextuales vinculados deben dejar de aplicar.

---

# 7. Flujo de grados

## Objetivo

Registrar el grado actual y el historial de grados del Hermano.

## Actores

* Admin de Taller, si tiene permiso.
* Superadmin.
* Hermano, solo como visualizador o solicitante.

## Flujo principal: grado inicial

1. El usuario declara su grado durante el registro.
2. Admin de Taller o Superadmin revisa la información.
3. El revisor confirma o corrige el grado.
4. Al aprobar el registro, el sistema registra el grado actual.
5. El sistema crea el primer registro de historial de grado.
6. Se audita la acción.

## Flujo: cambio de grado

1. Admin autorizado o Superadmin inicia cambio de grado.
2. Selecciona:

   * Hermano.
   * Nuevo grado.
   * Taller asociado, si corresponde.
   * Fecha de inicio.
   * Observación, si corresponde.
3. El sistema cierra el grado anterior si corresponde, asignando fecha de fin.
4. El sistema crea el nuevo registro de historial.
5. El grado actual del Hermano queda actualizado.
6. El sistema audita la acción.
7. Se notifica al Hermano si corresponde.

## Reglas

* Grados V1:

  * Aprendiz.
  * Compañero.
  * Maestro.
* Todo Hermano activo debe tener grado actual.
* Maestro es el grado mayor en V1.
* El grado no equivale a rol administrativo.
* Ser Maestro puede permitir publicar sin autorización previa.
* Ser Maestro no permite ver datos privados ni administrar usuarios automáticamente.
* El historial de grados no debe eliminarse.

---

# 8. Flujo de cargos

## Objetivo

Registrar cargos masónicos ocupados por un Hermano dentro de un Taller y conservar historial.

## Actores

* Admin de Taller, si tiene permiso.
* Superadmin.
* Hermano, como visualizador.

## Flujo principal: asignar cargo

1. Admin de Taller o Superadmin selecciona un Hermano.
2. Selecciona el Taller correspondiente.
3. Selecciona el cargo desde catálogo.
4. Indica fecha de inicio.
5. Opcionalmente agrega observación.
6. El sistema crea un registro de cargo vigente.
7. Si el cargo genera permisos contextuales, quedan activos solo dentro de ese Taller.
8. El sistema audita la acción.
9. Se notifica al Hermano si corresponde.

## Flujo: cerrar cargo

1. Admin de Taller o Superadmin selecciona un cargo vigente.
2. Indica fecha de fin.
3. El sistema cierra el período del cargo.
4. Los permisos contextuales derivados dejan de aplicar.
5. Se audita la acción.

## Reglas

* El cargo es opcional.
* Un Hermano puede no tener cargo.
* Un Hermano puede tener cargos diferentes en Talleres diferentes.
* El cargo no reemplaza roles administrativos.
* Un cargo no otorga permisos fuera del Taller correspondiente.
* Los cargos deben conservar historial.
* El historial de cargos no debe eliminarse.

---

# 9. Flujo de servicios ofrecidos

## Objetivo

Permitir que un Hermano publique qué puede ofrecer a la comunidad.

## Actores

* Hermano propietario.
* Otros Hermanos activos.
* Superadmin, solo por razones administrativas.
* Admin de Taller, solo si existe regla funcional específica.

## Flujo principal: crear servicio

1. El Hermano activo ingresa a servicios ofrecidos.
2. Crea un nuevo servicio.
3. Carga:

   * Título.
   * Descripción.
   * Categoría o rubro.
   * Zona o alcance geográfico.
   * Modalidad.
   * Disponibilidad general.
   * Visibilidad.
4. El servicio se crea como `Borrador` o `Activo`, según decisión de UX.
5. El Hermano revisa vista previa de visibilidad.
6. El Hermano activa el servicio.
7. El servicio puede aparecer en búsquedas según visibilidad.

## Flujo: búsqueda encuentra servicio

1. Otro Hermano activo realiza una búsqueda.
2. El sistema evalúa coincidencias.
3. Si el servicio es visible para ese usuario, se muestra según configuración.
4. Si el servicio permite coincidencia anónima, se muestra sin identidad.
5. Si corresponde, el usuario puede iniciar solicitud de contacto.

## Estados posibles

* Borrador.
* Activo.
* Pausado.
* Oculto.
* Dado de baja lógica.

## Reglas

* Los servicios son privados por defecto.
* El propietario decide visibilidad.
* Un servicio visible no hace visible toda la ficha.
* No hay pagos, carrito, contratación directa ni reviews en V1.
* El contacto derivado del servicio debe respetar consentimiento.

---

# 10. Flujo de necesidades

## Objetivo

Permitir que un Hermano registre algo que necesita o busca resolver.

## Actores

* Hermano propietario.
* Otros Hermanos activos.
* Sistema de búsqueda / coincidencias.
* Superadmin, solo por razones administrativas.

## Flujo principal: crear necesidad

1. El Hermano activo ingresa a necesidades.
2. Crea una nueva necesidad.
3. Carga:

   * Título.
   * Descripción.
   * Categoría o rubro.
   * Zona o localidad relevante.
   * Urgencia o prioridad, si corresponde.
   * Alcance de visibilidad.
4. El sistema crea la necesidad como `Borrador` o `Abierta`, según decisión de UX.
5. El Hermano revisa visibilidad.
6. El Hermano publica o abre la necesidad.
7. El sistema puede buscar coincidencias con:

   * Servicios.
   * Profesiones.
   * Oficios.
   * Intereses.
   * Ubicaciones.
   * Datos visibles o habilitados para búsqueda.

## Flujo: necesidad con coincidencias

1. El sistema encuentra posibles coincidencias.
2. Muestra resultados según visibilidad.
3. Si hay coincidencia anónima, no revela identidad.
4. El propietario puede iniciar solicitud de contacto.
5. Si la solicitud es aceptada, se habilita contacto según datos acordados.

## Estados posibles

* Borrador.
* Abierta.
* En búsqueda.
* Con coincidencias.
* Contacto solicitado.
* Vinculada.
* Cerrada.
* Cancelada.

## Reglas

* Las necesidades son privadas por defecto.
* El propietario decide audiencia.
* Una necesidad no implica obligación comercial.
* Una necesidad no implica contratación dentro del sistema.
* Una necesidad puede derivar en publicación interna si la búsqueda no alcanza.

---

# 11. Flujo de búsqueda

## Objetivo

Permitir que un Hermano activo consulte si existe otro Hermano, Taller, servicio, necesidad, profesión, oficio, producto, ubicación o contacto relevante.

## Actores

* Hermano activo buscador.
* Hermanos encontrados.
* Sistema.
* Talleres encontrados.

## Flujo principal

1. El Hermano activo ingresa a búsqueda.
2. Escribe una consulta o selecciona filtros.
3. El sistema busca coincidencias en:

   * Hermanos.
   * Servicios.
   * Necesidades.
   * Profesiones.
   * Oficios.
   * Ubicaciones.
   * Talleres.
   * Categorías.
   * Rubros.
4. El sistema evalúa permisos y visibilidad para el usuario buscador.
5. El sistema clasifica resultados:

   * Identificados.
   * Parcialmente identificados.
   * Anónimos.
   * Institucionales.
6. El sistema muestra solo información permitida.
7. Si el resultado permite contacto directo, se muestra acción correspondiente.
8. Si el resultado requiere consentimiento, se ofrece iniciar solicitud de contacto.
9. Si no hay resultados suficientes, el usuario puede crear necesidad o publicación.

## Criterios de búsqueda permitidos

La búsqueda puede incluir:

* Nombre visible.
* Profesión.
* Oficio.
* Servicio.
* Necesidad.
* Producto.
* Categoría.
* Rubro.
* Ciudad.
* Provincia.
* País.
* Estado.
* Zona.
* Taller.
* Datos de ubicación habilitados.

## Tipos de resultado

### Resultado identificado

Muestra datos habilitados por el Hermano encontrado.

### Resultado parcialmente identificado

Muestra información limitada, por ejemplo:

* Nombre de pila.
* Zona.
* Rubro.
* Taller, si fue habilitado.

### Resultado anónimo

Indica que existe una coincidencia, pero no revela identidad ni contacto.

### Resultado institucional

Muestra información de un Taller cuando la búsqueda apunta a Talleres.

## Reglas

* Solo usuarios activos y validados pueden buscar.
* La búsqueda no debe revelar datos privados.
* La búsqueda puede indicar cantidad de coincidencias sin revelar identidad.
* La búsqueda no debe permitir inferir datos sensibles por combinaciones de filtros.
* La búsqueda no debe generar notificación al Hermano encontrado por cada aparición común.
* Si la coincidencia es anónima, el siguiente paso debe ser solicitud de contacto.

---

# 12. Flujo de solicitud de contacto

## Objetivo

Permitir contacto entre Hermanos respetando privacidad y consentimiento.

## Actores

* Hermano solicitante.
* Hermano destinatario.
* Sistema.

## Flujo principal

1. El Hermano solicitante encuentra un resultado en búsqueda, servicio, necesidad o publicación.
2. El sistema determina si el contacto directo está habilitado.
3. Si el contacto directo está habilitado:

   * Se muestran los datos permitidos.
   * El solicitante puede contactar según medios habilitados.
4. Si el contacto requiere consentimiento:

   * El solicitante inicia solicitud de contacto.
   * El solicitante define qué datos propios quiere compartir.
   * Puede agregar un mensaje inicial.
5. El sistema crea la solicitud en estado `Pendiente de respuesta`.
6. El sistema notifica al destinatario sin revelar datos innecesarios.
7. El destinatario revisa la solicitud.
8. El destinatario puede:

   * Aceptar.
   * Rechazar.
9. Si acepta:

   * El destinatario define qué datos acepta compartir, si corresponde.
   * El sistema habilita el contacto según datos acordados.
   * La solicitud pasa a `Aceptada`.
   * Se notifica al solicitante.
10. Si rechaza:

    * No se revelan datos adicionales.
    * La solicitud pasa a `Rechazada`.
    * Se notifica al solicitante sin exponer información privada.
11. La acción queda auditada.

## Estados posibles

* Iniciada.
* Pendiente de respuesta.
* Aceptada.
* Rechazada.
* Cancelada.
* Cerrada.
* Expirada, si se define vencimiento.

## Reglas

* El solicitante no puede forzar contacto.
* El destinatario no está obligado a compartir información.
* Si el resultado era anónimo, no se debe revelar identidad salvo aceptación.
* El rechazo no debe exponer datos adicionales.
* La solicitud debe registrar origen:

  * Búsqueda.
  * Servicio.
  * Necesidad.
  * Publicación.

---

# 13. Flujo de publicación interna

## Objetivo

Permitir que un Hermano comunique una necesidad, búsqueda, aviso u ofrecimiento a una audiencia definida.

## Actores

* Hermano autor.
* Admin de Taller.
* Superadmin.
* Audiencia de la publicación.

## Flujo principal

1. El Hermano activo crea una publicación.
2. Define tipo:

   * Necesidad.
   * Búsqueda.
   * Aviso.
   * Ofrecimiento.
   * Recomendación o pedido de ayuda.
3. Carga:

   * Título.
   * Descripción.
   * Categoría.
   * Rubro.
   * Zona o alcance.
   * Audiencia.
4. Define identidad visible:

   * Completa.
   * Parcial.
   * Texto libre.
   * Institucional mínima.
   * Anónima, si se permite.
5. El sistema muestra vista previa de qué datos quedarán visibles.
6. El Hermano confirma.
7. El sistema evalúa grado y reglas de aprobación.
8. Si el autor es Maestro:

   * La publicación puede quedar activa sin autorización previa.
9. Si el autor es Aprendiz o Compañero:

   * La publicación queda `Pendiente de autorización`.
10. Admin de Taller o Superadmin revisa si corresponde.
11. El revisor puede:

    * Aprobar.
    * Rechazar.
    * Solicitar corrección.
12. Si aprueba:

    * La publicación pasa a `Activa`.
    * Se notifica al autor.
13. Si rechaza:

    * La publicación pasa a `Rechazada`.
    * Se notifica al autor.
14. Si solicita corrección:

    * La publicación pasa a `Requiere corrección`.
    * El autor puede editar y reenviar.

## Estados posibles

* Borrador.
* Pendiente de autorización.
* Activa.
* Requiere corrección.
* Rechazada.
* Pausada.
* Cerrada.
* Cancelada.
* Dada de baja lógica.

## Reglas

* No hay publicaciones públicas sin login en V1.
* Solo usuarios activos y validados pueden crear publicaciones.
* La publicación no modifica la visibilidad general de la ficha.
* El contacto derivado de una publicación debe respetar consentimiento.
* Ser Maestro no otorga permisos administrativos generales.
* Admin de Taller solo puede aprobar publicaciones dentro de su alcance.
* Superadmin puede intervenir cualquier publicación.

---

# 14. Flujo de notificaciones

## Objetivo

Avisar eventos relevantes sin revelar información sensible innecesaria.

## Eventos notificables

* Registro pendiente de validación.
* Registro aprobado.
* Registro rechazado.
* Registro pendiente de corrección.
* Solicitud de cambio sensible.
* Resolución de cambio sensible.
* Solicitud de contacto recibida.
* Solicitud de contacto aceptada.
* Solicitud de contacto rechazada.
* Necesidad con coincidencias relevantes.
* Servicio con solicitud de contacto.
* Publicación con solicitud de contacto.
* Publicación pendiente de autorización.
* Publicación aprobada.
* Publicación rechazada.
* Publicación con correcciones requeridas.
* Resultado del crawler con diferencias para revisar.

## Flujo principal

1. Ocurre un evento relevante.
2. El sistema determina destinatarios permitidos.
3. El sistema verifica:

   * Estado del destinatario.
   * Rol.
   * Taller.
   * Cargo contextual.
   * Visibilidad.
   * Privacidad.
4. El sistema genera notificación.
5. La notificación muestra solo datos necesarios.
6. El usuario puede acceder a la acción relacionada, si tiene permiso.

## Reglas

* No se notifican resultados de búsqueda comunes al Hermano encontrado.
* No se revelan datos sensibles en notificaciones.
* Usuarios pendientes no reciben notificaciones sobre contenido interno.
* Las notificaciones no deben convertirse en vía indirecta para acceder a datos privados.

---

# 15. Flujo de auditoría funcional

## Objetivo

Registrar acciones relevantes para conservar trazabilidad.

## Eventos a auditar

* Registro de nuevos usuarios.
* Aprobación, rechazo o corrección de registros.
* Cambio de estado de usuario.
* Alta, modificación o baja lógica de Talleres.
* Ejecución y confirmación del crawler.
* Alta, modificación o cierre de relación Hermano-Taller.
* Cambios de grado.
* Cambios de cargo.
* Solicitudes y resoluciones de cambios sensibles.
* Cambios relevantes de visibilidad.
* Creación, modificación, pausa, cierre o baja de servicios.
* Creación, modificación, cierre o cancelación de necesidades.
* Creación, aprobación, rechazo, corrección o baja de publicaciones.
* Solicitudes de contacto y resoluciones.
* Acciones realizadas por permisos derivados de cargos.

## Flujo principal

1. Ocurre una acción auditable.
2. El sistema registra:

   * Qué ocurrió.
   * Quién lo hizo.
   * Cuándo lo hizo.
   * Sobre qué entidad actuó.
   * Con qué rol, cargo o permiso actuó.
   * Resultado de la acción.
3. El sistema conserva el evento.
4. Roles autorizados pueden consultar auditoría según permisos.

## Reglas

* La auditoría no debe registrar cada interacción menor.
* La auditoría no debe permitir ver datos privados por vía indirecta.
* El acceso a auditoría debe estar limitado.
* Acciones sensibles deben quedar auditadas siempre.

---

# 16. Flujo de crawler de Talleres

## Objetivo

Cargar o actualizar Talleres / Logias desde una fuente pública definida, con revisión humana antes de aplicar cambios.

## Actores

* Superadmin.
* Sistema crawler.

## Flujo principal

1. Superadmin inicia ejecución del crawler.
2. El sistema lee la fuente pública definida.
3. El sistema extrae posibles Talleres / Logias.
4. El sistema compara con datos existentes.
5. El sistema genera un reporte de diferencias:

   * Nuevos Talleres.
   * Talleres modificados.
   * Talleres posiblemente removidos.
   * Cambios de zona, provincia, localidad o datos descriptivos.
   * Errores o inconsistencias de fuente.
6. El Superadmin revisa diferencias.
7. El Superadmin decide:

   * Confirmar alta.
   * Confirmar modificación.
   * Marcar posible baja.
   * Ignorar cambio.
   * Corregir manualmente.
8. El sistema aplica solo los cambios confirmados.
9. El sistema registra fuente y fecha de última actualización.
10. El sistema audita ejecución y confirmación.

## Reglas

* El crawler solo lee fuentes públicas.
* No hace scraping de sitios con login.
* No crea usuarios.
* No crea fichas personales.
* No modifica datos sin confirmación humana.
* Solo Superadmin puede confirmar cambios globales.
* Debe conservar trazabilidad de cambios aplicados.

---

# 17. Flujo de baja, suspensión o rechazo de usuario

## Objetivo

Administrar estados restrictivos de usuarios sin perder trazabilidad.

## Actores

* Superadmin.
* Admin de Taller, solo si se define alcance limitado.
* Hermano afectado.

## Flujo: suspensión

1. Superadmin selecciona un Hermano.
2. Define motivo de suspensión.
3. El sistema cambia estado a `Suspendido`.
4. El sistema restringe acceso interno.
5. El sistema evita publicaciones, búsquedas y contactos.
6. Se audita la acción.
7. Se notifica al usuario si corresponde.

## Flujo: baja lógica

1. Superadmin selecciona un Hermano.
2. Define motivo.
3. El sistema cambia estado a `Dado de baja`.
4. El usuario pierde acceso interno.
5. Los datos históricos se conservan según reglas.
6. Se audita la acción.

## Flujo: O∴ Eterno

1. Superadmin marca el estado especial si corresponde.
2. El sistema conserva ficha histórica con tratamiento respetuoso.
3. El usuario no puede iniciar sesión.
4. No aparece como contacto disponible.
5. La visibilidad queda restringida según reglas específicas.
6. Se audita la acción.

## Reglas

* La suspensión restringe acceso aunque el usuario tenga roles, grados o cargos.
* La baja no debe borrar trazabilidad.
* El estado O∴ Eterno no debe comportarse como usuario activo.
* Estas acciones deben requerir motivo y auditoría.

---

# 17b. Flujo de cambio de email propio con verificación

## Objetivo

Permitir que un Hermano cambie su propio email sin que el cambio sea inmediato, garantizando que la nueva dirección le pertenece.

## Actores

* Usuario / Hermano (dueño de la cuenta).

## Flujo

1. El usuario solicita cambiar su email desde su perfil e ingresa la nueva dirección.
2. La nueva dirección se valida (formato y unicidad) y se guarda como `pending_email`. El email actual no cambia.
3. Se envía un correo de confirmación a la nueva dirección con un enlace firmado y temporal (expira en 60 minutos).
4. El email actual sigue vigente mientras el cambio esté pendiente.
5. Al abrir el enlace y confirmar, se valida la firma y el hash de `pending_email`; recién entonces el email se reemplaza, se limpia `pending_email` y la dirección queda verificada.
6. Si la dirección fue tomada por otra cuenta antes de confirmar, el cambio se cancela y se informa.

## Reglas

* El cambio nunca se aplica sin confirmación desde la nueva casilla.
* El email es dato de contacto, no dato sensible: no requiere aprobación de Superadmin.
* El enlace de confirmación es de un solo uso efectivo y expira.

---

# 18. Flujo de resolución de conflictos de permisos

## Objetivo

Definir cómo actuar cuando varias reglas aplican al mismo caso.

## Casos

### Rol permite, visibilidad niega

Debe prevalecer la visibilidad, salvo acción administrativa explícita y auditada.

### Cargo permite, Taller no coincide

No se permite la acción.

El cargo solo aplica dentro del Taller correspondiente.

### Usuario suspendido con rol activo

No se permite la acción.

El estado suspendido prevalece sobre roles y cargos.

### Maestro intentando administrar

No se permite por grado solamente.

Maestro no equivale a rol administrativo.

### Resultado de búsqueda con datos privados

No se muestran datos privados.

Puede mostrarse coincidencia anónima si la configuración lo permite.

### Usuario pendiente intentando acceder

No se permite acceso interno.

Debe completar validación.

## Regla final

Cuando dos reglas entren en conflicto, debe elegirse la alternativa que:

1. Exponga menos información.
2. Respete consentimiento.
3. Requiera revisión humana si la acción es sensible.
4. Deje trazabilidad si corresponde.

---

# 19. Workflows fuera de alcance de V1

Pontis V1 no incluye:

* Pagos.
* Carrito.
* Contratación directa.
* Reviews o calificaciones.
* Chat interno completo.
* Red social pública.
* Perfiles públicos sin login.
* Reportes avanzados.
* Automatizaciones sofisticadas.
* Gestión económica de Talleres.
* Actas.
* Calendario completo de reuniones.
* Gestión de asistencia.
* Votaciones.
* Promoción automática de grados.
* Renovación automática de cargos.
* Motor de permisos ilimitado.
* Auditoría de cada visualización común.

---

# 20. Regla final del documento

Los workflows de Pontis V1 deben permitir colaboración entre Hermanos sin convertir el sistema en exposición automática de datos privados.

Cada flujo debe ser simple, auditable y respetuoso de la privacidad.
