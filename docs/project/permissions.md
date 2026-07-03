# Pontis - Permissions and Visibility

## Propósito del documento

Este documento define las reglas funcionales de permisos, roles, visibilidad y privacidad de Pontis V1.

Su objetivo es evitar ambigüedades sobre:

* Quién puede ver información.
* Quién puede modificar información.
* Quién puede aprobar acciones.
* Qué datos son privados por defecto.
* Cómo funciona la búsqueda respetando privacidad.
* Cómo se habilita el contacto entre Hermanos.
* Qué diferencia existe entre roles administrativos, grados masónicos y cargos masónicos.

Este documento no define implementación técnica final, policies, middleware, tablas, endpoints ni componentes frontend. Es una guía funcional para implementar permisos de forma coherente.

---

# 1. Principios generales

Pontis V1 debe respetar estos principios:

* Todo dato es privado por defecto.
* La visibilidad debe ser explícita.
* El usuario decide qué datos mostrar, ante quién y bajo qué condiciones.
* La búsqueda nunca debe revelar más información que la permitida por la visibilidad.
* El contacto debe requerir consentimiento cuando no exista contacto directo habilitado.
* Los roles administrativos no son lo mismo que los cargos masónicos.
* Los grados masónicos no otorgan permisos administrativos generales.
* Los cargos pueden otorgar permisos contextuales solo dentro de un Taller.
* Las acciones sensibles deben quedar auditadas.
* Ante la duda, se debe elegir la opción que exponga menos información.

---

# 2. Conceptos principales

## Rol administrativo

Define qué puede hacer un usuario dentro del sistema.

Roles base de V1:

* Superadmin.
* Admin de Taller.
* Usuario / Hermano.

## Grado masónico

Representa el grado del Hermano.

Grados de V1:

* Aprendiz.
* Compañero.
* Maestro.

El grado puede influir en reglas funcionales específicas, pero no convierte automáticamente al usuario en administrador.

Ejemplo:

* El grado no habilita publicar: publicar es libre para todo usuario registrado, sin importar el grado (ver Decisión 16).
* Un Maestro no es automáticamente Admin de Taller.
* Un Maestro no puede ver datos privados por el solo hecho de ser Maestro.

## Cargo masónico

Representa una función que un Hermano ocupa dentro de un Taller.

El cargo es contextual.

Ejemplo:

Un Hermano puede tener un cargo en el Taller A, pero ese cargo no le da permisos sobre el Taller B.

## Visibilidad

Define qué audiencia puede ver determinado dato, bloque, servicio, necesidad o publicación.

La visibilidad es opt-in: nada se muestra automáticamente.

## Consentimiento

Define si un Hermano acepta o no ser contactado, y qué datos acepta compartir.

---

# 3. Roles administrativos base

## 3.1 Superadmin

El Superadmin puede administrar todo el sistema.

### Puede

* Administrar usuarios / Hermanos.
* Validar, rechazar o solicitar correcciones en registros.
* Validar usuarios de cualquier Taller.
* Administrar Talleres / Logias.
* Administrar zonas, provincias, localidades y catálogos.
* Ejecutar o confirmar actualizaciones del crawler de Talleres.
* Administrar roles administrativos.
* Resolver cambios sensibles.
* Suspender usuarios.
* Dar de baja usuarios.
* Consultar auditoría funcional cuando corresponda.
* Administrar estados generales del sistema.
* Ver información necesaria para resolver tareas administrativas.

### No debería

* Usar su rol para acceder a información privada sin motivo funcional.
* Modificar datos sensibles sin dejar trazabilidad.
* Aplicar cambios del crawler sin revisión humana.
* Convertir auditoría en una vía indirecta para consultar datos privados.

---

## 3.2 Admin de Taller

El Admin de Taller administra información y solicitudes relacionadas con uno o más Talleres donde tiene permisos.

### Puede

* Ver solicitudes vinculadas a sus Talleres.
* Validar usuarios que declaran como Taller principal uno de sus Talleres.
* Aprobar o rechazar pertenencias a sus Talleres.
* Gestionar estados de relación Hermano-Taller dentro de sus Talleres.
* Consultar miembros vinculados a sus Talleres según permisos.
* Administrar información básica de sus Talleres si tiene permiso.
* Cargar o actualizar cargos masónicos dentro de sus Talleres si tiene permiso.
* Solicitar correcciones en registros vinculados a sus Talleres.
* Resolver cambios sensibles de identidad (nombre, apellido, DNI y matrícula masónica) solo de Hermanos que pertenezcan a un Taller que administra: aprobar, rechazar o pedir más información.
* Ver información administrativa mínima necesaria para cumplir sus funciones.

### No puede

* Administrar usuarios ajenos a sus Talleres.
* Validar usuarios de otros Talleres.
* Ejecutar o confirmar el crawler global.
* Administrar roles globales.
* Ver ni resolver cambios sensibles de identidad de Hermanos fuera de sus Talleres administrados.
* Ver datos privados sin relación funcional con sus Talleres.
* Administrar Talleres donde no tiene permiso.
* Usar un cargo o rol de un Taller para operar sobre otro Taller.

---

## 3.3 Usuario / Hermano

Es el usuario común del sistema.

### Puede

* Ver y editar su propia ficha.
* Solicitar cambios sobre datos sensibles.
* Configurar la visibilidad de sus datos.
* Cargar servicios ofrecidos.
* Crear necesidades.
* Crear publicaciones.
* Buscar Hermanos, Talleres, servicios o necesidades según permisos y visibilidad.
* Iniciar solicitudes de contacto.
* Aceptar o rechazar solicitudes de contacto recibidas.
* Pausar, ocultar o dar de baja sus propios servicios.
* Cerrar o cancelar sus propias necesidades.
* Pausar, cerrar o cancelar sus propias publicaciones, según estado y reglas.
* Consultar información que otros Hermanos hayan decidido hacer visible para su audiencia.

### No puede

* Ver datos privados de otros Hermanos sin autorización.
* Contactar automáticamente a otro Hermano si el contacto no fue habilitado.
* Validar nuevos usuarios.
* Administrar Talleres.
* Administrar catálogos.
* Administrar roles.
* Modificar cargos o grados institucionales salvo que tenga rol o permiso adicional.
* Aprobar cambios sensibles.
* Acceder a información interna si todavía está pendiente de validación.

---

# 4. Estados de usuario y acceso

## Pendiente de validación

Puede:

* Acceder al proceso de registro.
* Consultar el catálogo mínimo necesario para seleccionar Taller principal.
* Ver el estado de su solicitud, si se implementa.

No puede:

* Acceder al sistema interno.
* Ver Hermanos.
* Ver Talleres internos.
* Ver servicios.
* Ver necesidades.
* Ver publicaciones.
* Realizar búsquedas internas.
* Contactar Hermanos.

## Pendiente de corrección

Puede:

* Ver qué corrección se le solicita.
* Editar o completar la información requerida.
* Reenviar la solicitud.

No puede:

* Acceder al sistema interno hasta ser validado.
* Ver información interna.

## Activo

Puede:

* Acceder al sistema interno.
* Usar las funciones disponibles según rol, visibilidad y permisos.

## Suspendido

No puede:

* Acceder normalmente al sistema interno.
* Publicar.
* Buscar.
* Contactar.
* Modificar visibilidad para evadir restricciones.

Puede:

* Ver información mínima de estado, si se define.
* Contactar soporte o administración, si se define.

## Dado de baja

No puede acceder al sistema interno.

La información histórica puede conservarse para auditoría, integridad institucional o trazabilidad.

## Rechazado

No puede acceder al sistema interno.

## O∴ Eterno

Estado especial para conservar una ficha histórica con tratamiento respetuoso.

Reglas sugeridas:

* No debe usarse como usuario activo.
* No debe permitir login.
* No debe aparecer como contacto disponible.
* Su visibilidad debe estar restringida según reglas específicas.
* Debe conservar trazabilidad histórica cuando corresponda.

---

# 5. Visibilidad general

## Regla base

Todo dato es privado por defecto.

Nada de lo siguiente debe mostrarse automáticamente:

* Datos personales.
* Datos de contacto.
* Ubicación.
* Datos masónicos.
* Talleres.
* Grados.
* Cargos.
* Servicios.
* Necesidades.
* Publicaciones.
* Actividad.
* Información profesional.
* Información de búsqueda.

## Audiencias disponibles en V1

### Privado

Visible solo para:

* El propio Hermano.
* Roles administrativos autorizados, solo cuando exista motivo funcional.

### Taller principal

Visible para miembros activos y validados del Taller principal del Hermano, según configuración.

### Mis Talleres

Visible para miembros activos y validados de todos los Talleres a los que pertenece el Hermano, según configuración.

### Usuarios activos / validados

Visible para usuarios registrados, aprobados y activos en Pontis.

Excluye:

* Pendientes.
* Rechazados.
* Suspendidos.
* Dados de baja.
* No validados.

### Disponible en búsqueda sin revelar identidad

Permite que un dato, servicio, necesidad o coincidencia aparezca en resultados de búsqueda sin exponer identidad ni datos de contacto.

Ejemplo:

El sistema puede mostrar que existe un Hermano contador en determinada zona, pero sin mostrar nombre ni contacto hasta que haya consentimiento.

---

# 6. Visibilidad por bloque y por campo

## Regla general

La V1 debe resolver la visibilidad principalmente por bloques.

Cuando sea necesario, puede permitir visibilidad por campo.

## Bloques sugeridos

* Identidad básica.
* Datos de contacto.
* Ubicación.
* Datos masónicos.
* Talleres.
* Grado actual.
* Historial de grados.
* Cargo actual.
* Historial de cargos.
* Profesión / oficio / actividad.
* Servicios ofrecidos.
* Necesidades.
* Intereses.
* Publicaciones.

## Campos sensibles

Algunos campos no deben ser libremente editables ni visibles por defecto:

* Documento / DNI.
* Matrícula masónica nacional.
* Nombre legal.
* Apellido legal.
* Taller principal inicial, cuando afecte validación institucional.

## Reglas

* Un bloque visible no implica que todos sus campos sensibles sean visibles.
* Una publicación visible no modifica la visibilidad general de la ficha.
* Un servicio visible no modifica la visibilidad general del Hermano.
* Una necesidad visible no modifica la visibilidad general del Hermano.
* La visibilidad debe evaluarse siempre desde la perspectiva del usuario que está mirando.

---

# 7. Permisos sobre ficha personal

## El propio Hermano

Puede:

* Ver su ficha completa.
* Editar datos no sensibles.
* Configurar visibilidad.
* Solicitar cambios sensibles.
* Cargar servicios.
* Cargar necesidades.
* Crear publicaciones.
* Pausar u ocultar información propia, según reglas.

No puede:

* Modificar directamente datos sensibles.
* Modificar su grado institucional sin validación.
* Modificar cargos institucionales salvo que tenga permiso.
* Cambiar su Taller principal inicial si afecta validación, salvo solicitud aprobada.

## Otros Hermanos

Pueden ver:

* Solo los datos habilitados por visibilidad.
* Solo si pertenecen a una audiencia autorizada.
* Solo si están activos y validados.

No pueden ver:

* Datos privados.
* Datos sensibles no habilitados.
* Información administrativa.
* Auditoría.
* Datos ocultos por configuración.

## Admin de Taller

Puede ver:

* Información mínima necesaria para validar o administrar miembros de sus Talleres.
* Datos de relación Hermano-Taller dentro de sus Talleres.
* Solicitudes vinculadas a sus Talleres.
* Datos necesarios para aprobar pertenencias a sus Talleres.

No puede ver:

* Información privada sin relación funcional.
* Datos de otros Talleres donde no tiene permiso.
* Datos sensibles globales salvo que la acción administrativa lo requiera y esté permitido.

## Superadmin

Puede ver información necesaria para administrar el sistema, validar usuarios y resolver acciones sensibles.

Debe quedar trazabilidad cuando acceda o modifique información sensible.

---

# 8. Permisos sobre Talleres

## Usuario / Hermano

Puede:

* Ver Talleres según lo permitido por el sistema.
* Ver su Taller principal.
* Ver sus otros Talleres.
* Buscar Talleres si está activo y validado.
* Solicitar o declarar pertenencia a un Taller, si se implementa.

No puede:

* Editar Talleres.
* Ver miembros de un Taller si la visibilidad no lo permite.
* Administrar pertenencias.
* Ejecutar crawler.

## Admin de Taller

Puede:

* Ver sus Talleres administrados.
* Gestionar miembros vinculados a sus Talleres.
* Aprobar o rechazar pertenencias a sus Talleres.
* Ver solicitudes de validación de usuarios que declaran su Taller como principal.
* Editar información básica del Taller si tiene permiso específico.

No puede:

* Administrar Talleres ajenos.
* Ver datos privados de miembros sin necesidad funcional.
* Ejecutar crawler global.

## Superadmin

Puede:

* Crear Talleres.
* Editar Talleres.
* Dar de baja lógica Talleres.
* Administrar zonas, provincias y localidades.
* Ejecutar crawler.
* Revisar diferencias del crawler.
* Confirmar cambios detectados por el crawler.

---

# 9. Permisos sobre relación Hermano-Taller

## Usuario / Hermano

Puede:

* Ver sus propias relaciones con Talleres.
* Solicitar cambios o pertenencias, si se implementa.
* Ver estado de sus solicitudes.

No puede:

* Aprobar su propia pertenencia.
* Modificar estado administrativo de la relación.
* Asignarse admin de Taller.
* Asignarse cargo institucional.

## Admin de Taller

Puede:

* Aprobar o rechazar pertenencias a sus Talleres.
* Cambiar estados de relación dentro de sus Talleres.
* Consultar relaciones históricas de sus Talleres según permiso.
* Gestionar miembros dentro de su alcance.

No puede:

* Modificar relaciones de Talleres ajenos.
* Aprobar cambios sensibles globales.
* Usar una relación de un Taller para acceder a información privada de otro.

## Superadmin

Puede administrar relaciones Hermano-Taller en todo el sistema.

---

# 10. Permisos sobre grados

## Usuario / Hermano

Puede:

* Ver su grado actual.
* Ver su historial de grados.
* Configurar visibilidad de su grado e historial, dentro de las reglas permitidas.

No puede:

* Cambiar directamente su grado institucional.
* Cargar ascensos propios sin validación.
* Eliminar historial de grados.

## Admin de Taller

Puede:

* Confirmar o corregir grado declarado durante validación, si tiene permiso.
* Ver grado de miembros cuando sea necesario para una acción administrativa del Taller.
* Participar en gestión de grados dentro de sus Talleres si se define permiso específico.

No puede:

* Cambiar grados fuera de sus Talleres.
* Usar el grado para acceder a datos privados no relacionados.

## Superadmin

Puede:

* Administrar grado actual.
* Corregir historial de grados.
* Resolver inconsistencias.

## Regla especial sobre Maestro

Publicar es libre para todo usuario registrado y ya no es un privilegio del grado Maestro (ver Decisión 16).

Ser Maestro no permite automáticamente:

* Administrar usuarios.
* Administrar Talleres.
* Ver datos privados.
* Aprobar cambios sensibles.
* Acceder a auditoría.
* Otorgar permisos.

---

# 11. Permisos sobre cargos

## Usuario / Hermano

Puede:

* Ver sus cargos actuales e históricos.
* Configurar visibilidad de sus cargos, dentro de las reglas permitidas.

No puede:

* Asignarse cargos.
* Modificar cargos institucionales.
* Eliminar historial de cargos.
* Usar un cargo vencido como permiso activo.

## Admin de Taller

Puede:

* Cargar o actualizar cargos dentro de sus Talleres, si tiene permiso.
* Ver cargos vinculados a sus Talleres.
* Cerrar cargos vigentes dentro de sus Talleres, si tiene permiso.
* Usar permisos contextuales derivados de cargo cuando estén definidos.

No puede:

* Administrar cargos en Talleres ajenos.
* Otorgar permisos globales mediante cargos.
* Usar cargos para ver datos privados sin necesidad funcional.

## Superadmin

Puede administrar cargos e historial de cargos en todo el sistema.

---

# 12. Permisos sobre servicios ofrecidos

## Propietario del servicio

Puede:

* Crear servicios.
* Editar servicios propios.
* Configurar visibilidad.
* Pausar servicios.
* Ocultar servicios.
* Dar de baja lógica servicios propios.
* Recibir solicitudes de contacto relacionadas.

## Otros Hermanos

Pueden:

* Ver servicios si la visibilidad lo permite.
* Encontrar servicios en búsqueda si están habilitados.
* Solicitar contacto si el servicio permite iniciar contacto.

No pueden:

* Ver identidad o contacto si el servicio solo permite coincidencia anónima.
* Contactar automáticamente si no hay contacto directo habilitado.
* Editar servicios ajenos.

## Admin de Taller

Puede:

* Ver servicios relacionados con sus Talleres solo si la visibilidad o una necesidad funcional lo permite.
* Moderar o intervenir solo si se define una regla administrativa específica.

## Superadmin

Puede intervenir servicios por razones administrativas, abuso, baja lógica, auditoría o soporte funcional.

---

# 13. Permisos sobre necesidades

## Propietario de la necesidad

Puede:

* Crear necesidades.
* Editar necesidades propias.
* Configurar visibilidad.
* Cancelar necesidades.
* Cerrar necesidades.
* Recibir coincidencias.
* Iniciar solicitudes de contacto.

## Otros Hermanos

Pueden:

* Ver necesidades si la visibilidad lo permite.
* Responder o solicitar contacto si la regla lo permite.

No pueden:

* Ver necesidades privadas.
* Ver identidad si la necesidad se publicó en modo anónimo o limitado.
* Editar necesidades ajenas.

## Admin de Taller

Puede intervenir o revisar necesidades solo cuando haya una regla funcional o administrativa clara.

## Superadmin

Puede intervenir por razones administrativas, soporte, abuso o auditoría.

---

# 14. Permisos sobre búsqueda

## Reglas generales

La búsqueda solo está disponible para usuarios activos y validados.

La búsqueda debe respetar:

* Visibilidad.
* Estado del usuario encontrado.
* Audiencia configurada.
* Talleres compartidos.
* Datos habilitados.
* Consentimiento de contacto.
* Restricciones administrativas.

## Usuario / Hermano activo

Puede buscar por:

* Hermano.
* Taller.
* Profesión.
* Oficio.
* Servicio.
* Necesidad.
* Categoría.
* Rubro.
* Ciudad.
* Provincia.
* País.
* Estado.
* Zona.
* Ubicación habilitada.
* Coincidencias anónimas.

## La búsqueda puede devolver

### Resultado identificado

Muestra nombre y datos habilitados por el Hermano encontrado.

### Resultado parcialmente identificado

Muestra información limitada, por ejemplo:

* Nombre de pila.
* Zona.
* Rubro.
* Taller, si fue habilitado.
* Datos no sensibles permitidos.

### Resultado anónimo

Muestra que existe una coincidencia, pero no revela identidad ni contacto.

Ejemplo:

`Hay un Hermano que coincide con tu búsqueda, pero no publica sus datos. Podés enviar una solicitud de contacto.`

### Resultado institucional

Muestra información de un Taller cuando la búsqueda apunta a Talleres y la información está permitida.

## La búsqueda no debe

* Revelar datos privados.
* Revelar contacto sin permiso.
* Mostrar identidad si solo se habilitó coincidencia anónima.
* Permitir inferir datos sensibles mediante filtros.
* Generar notificaciones por cada aparición común.
* Mostrar usuarios pendientes, rechazados, suspendidos o dados de baja como contactos disponibles.

---

# 15. Permisos sobre publicaciones internas

## Propietario de la publicación

Puede:

* Crear publicación.
* Editar publicación en borrador.
* Definir audiencia.
* Definir identidad visible.
* Ver vista previa.
* Publicar sin autorización previa.
* Pausar, cerrar o cancelar publicación propia según estado.

## Reglas por grado

El grado del autor no condiciona la posibilidad de publicar. Cualquier usuario registrado y validado publica sin autorización previa, sea Aprendiz, Compañero o Maestro (ver Decisión 16).

Publicar no otorga ni requiere permisos administrativos.

## Admin de Taller

La publicación no requiere aprobación previa, por lo que el Admin de Taller no tiene un paso de revisión obligatorio.

Puede, como intervención administrativa a posteriori y solo dentro de su alcance:

* Dar de baja o suspender publicaciones que infrinjan reglas de la comunidad.

No puede:

* Condicionar la publicación de un Hermano a su aprobación previa.
* Intervenir publicaciones de Talleres ajenos.
* Modificar visibilidad general de la ficha del autor.
* Ver datos privados no incluidos en la publicación.

## Superadmin

Puede:

* Intervenir o dar de baja publicaciones en cualquier contexto por razones administrativas o de seguridad.

Esta intervención es posterior y excepcional, no un paso previo del ciclo de publicar.

## Estados posibles

* Borrador.
* Activa.
* Rechazada.
* Pausada.
* Cerrada.
* Cancelada.
* Dada de baja lógica.

Estados legacy, solo en datos históricos y no seleccionables en nuevas publicaciones (ver Decisión 16): `Pendiente de autorización`, `Requiere corrección`.

---

# 16. Permisos sobre solicitudes de contacto

## Solicitante

Puede:

* Iniciar una solicitud de contacto cuando el sistema lo permita.
* Elegir qué datos propios compartir.
* Cancelar una solicitud pendiente.
* Ver estado de la solicitud.

No puede:

* Ver datos adicionales del destinatario antes de aceptación.
* Forzar contacto.
* Ver identidad si la coincidencia era anónima y no fue aceptada.

## Destinatario

Puede:

* Ver solicitud recibida.
* Ver los datos que el solicitante decidió compartir.
* Aceptar la solicitud.
* Rechazar la solicitud.
* Definir qué datos compartir si acepta.

No puede ser obligado a revelar información adicional.

## Sistema

Si el destinatario acepta:

* Habilita el contacto según datos acordados.
* Registra la resolución.
* Notifica al solicitante.

Si el destinatario rechaza:

* No revela información adicional.
* Registra la resolución.
* Notifica el rechazo sin exponer datos sensibles.

## Estados posibles

* Iniciada.
* Pendiente de respuesta.
* Aceptada.
* Rechazada.
* Cancelada.
* Cerrada.
* Expirada, si se define vencimiento.

---

# 17. Permisos sobre crawler de Talleres

## Usuario / Hermano

No puede ejecutar crawler.

## Admin de Taller

No puede ejecutar ni confirmar crawler global.

Puede, si se define, revisar información de sus Talleres luego de aplicada.

## Superadmin

Puede:

* Ejecutar crawler.
* Ver diferencias detectadas.
* Confirmar altas.
* Confirmar modificaciones.
* Confirmar posibles bajas.
* Rechazar cambios detectados.
* Ver fuente y fecha de última actualización.
* Auditar ejecución.

## Reglas

* El crawler solo lee fuentes públicas.
* No hace scraping de sitios con login.
* No crea usuarios.
* No crea fichas personales.
* No aplica cambios automáticamente sin revisión humana.
* Debe registrar fuente, fecha y usuario que confirmó cambios.

---

# 18. Permisos sobre notificaciones

## Reglas

Las notificaciones deben respetar:

* Rol.
* Estado del usuario.
* Taller.
* Cargo contextual.
* Visibilidad.
* Privacidad.
* Consentimiento.

## No deben

* Revelar datos sensibles innecesarios.
* Mostrar información interna a usuarios pendientes.
* Informar resultados de búsqueda comunes al usuario encontrado.
* Exponer identidad si la interacción todavía es anónima.
* Convertirse en canal indirecto para filtrar datos privados.

## Eventos notificables

* Registro pendiente de validación.
* Registro aprobado, rechazado o pendiente de corrección.
* Solicitud de cambio sensible.
* Resolución de cambio sensible.
* Solicitud de contacto recibida.
* Solicitud de contacto aceptada o rechazada.
* Necesidad con coincidencias relevantes.
* Servicio o publicación con solicitud de contacto.
* Intervención administrativa a posteriori sobre una publicación (baja o suspensión), cuando corresponda.
* Resultado del crawler con diferencias para revisar.

---

# 19. Permisos sobre auditoría

## Reglas

La auditoría solo puede ser consultada por roles autorizados.

La auditoría debe conservar trazabilidad, pero no debe funcionar como acceso alternativo a datos privados.

## Superadmin

Puede consultar auditoría funcional cuando corresponda.

## Admin de Taller

Puede consultar auditoría limitada a su Taller o contexto, solo si se define permiso específico.

## Usuario / Hermano

No puede consultar auditoría administrativa general.

Puede, si se implementa, consultar historial propio limitado, por ejemplo:

* Sus solicitudes de cambio.
* Sus solicitudes de contacto.
* Estados de sus publicaciones.
* Cambios visibles sobre su ficha.

## Eventos sensibles a auditar

* Registro de usuarios.
* Validación, rechazo o corrección de registros.
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
* Creación, baja o intervención administrativa a posteriori de publicaciones.
* Solicitudes de contacto y resoluciones.
* Acciones realizadas por permisos derivados de cargos.

---

# 20. Matriz resumida de permisos

| Acción                             | Usuario / Hermano                   | Admin de Taller                     | Superadmin               |
| ---------------------------------- | ----------------------------------- | ----------------------------------- | ------------------------ |
| Ver propia ficha                   | Sí                                  | Sí                                  | Sí                       |
| Editar propia ficha no sensible    | Sí                                  | Sí, propia                          | Sí                       |
| Modificar datos sensibles propios  | Solicitud                           | Solicitud                           | Sí                       |
| Aprobar cambios sensibles          | No                                  | No                                  | Sí                       |
| Validar nuevo usuario              | No                                  | Solo de sus Talleres                | Sí                       |
| Ver datos privados de otros        | No                                  | Solo con motivo funcional y alcance | Sí, con motivo funcional |
| Administrar Talleres               | No                                  | Solo sus Talleres, si tiene permiso | Sí                       |
| Ejecutar crawler                   | No                                  | No                                  | Sí                       |
| Confirmar cambios del crawler      | No                                  | No                                  | Sí                       |
| Asignar cargos                     | No                                  | En sus Talleres, si tiene permiso   | Sí                       |
| Cambiar grados                     | No                                  | En sus Talleres, si tiene permiso   | Sí                       |
| Crear servicio                     | Sí                                  | Sí                                  | Sí                       |
| Crear necesidad                    | Sí                                  | Sí                                  | Sí                       |
| Crear publicación                  | Sí                                  | Sí                                  | Sí                       |
| Publicar sin aprobación            | Sí                                  | Sí                                  | Sí                       |
| Intervenir/bajar publicaciones     | Solo propias                        | A posteriori, en su alcance         | Sí                       |
| Buscar Hermanos/servicios/Talleres | Sí, si activo                       | Sí, si activo                       | Sí                       |
| Iniciar solicitud de contacto      | Sí                                  | Sí                                  | Sí                       |
| Consultar auditoría                | No, salvo historial propio limitado | Limitado, si se define              | Sí                       |

---

# 21. Reglas de resolución de conflictos

## Si una regla de rol permite y una regla de visibilidad niega

Debe prevalecer la visibilidad, salvo que exista una acción administrativa explícita y auditada.

## Si una búsqueda encuentra una coincidencia privada

Debe mostrarse como coincidencia anónima o no mostrarse, según configuración.

## Si un usuario pertenece a varios Talleres

Los permisos deben evaluarse por Taller.

Ejemplo:

Un Admin de Taller puede administrar datos del Taller A, pero no del Taller B, aunque pertenezca a ambos.

## Si un cargo otorga permiso contextual

El permiso aplica solo mientras el cargo esté vigente y solo dentro del Taller correspondiente.

## Si el usuario está suspendido

Debe restringirse el acceso, aunque tenga roles, cargos o grado Maestro.

## Si el usuario no está validado

No debe acceder al sistema interno.

## Si hay duda

Debe priorizarse privacidad, mínima exposición y revisión humana.

---

# 21b. Búsqueda de Hermanos regida por visibilidad

La búsqueda de Hermanos ("Buscar hermanos") es independiente de la pertenencia
de talleres del que busca. Se rige por la visibilidad que cada Hermano definió
por sección de su perfil.

Campos buscables y sección que los gobierna:

* Nombre / apellido / matrícula / email → Identidad.
* Ciudad / provincia / país → Ubicación.
* Datos masónicos → Masónico.
* Taller → pertenencia institucional (siempre buscable).

Niveles de visibilidad respecto al que mira:

* `private` (Solo yo): nadie, salvo uno mismo.
* `workshop` (Mi taller principal): solo miembros del taller principal del dueño.
* `my_workshops` (Mis talleres): quien comparta cualquier taller con el dueño.
* `registered` (Masones registrados): cualquier usuario validado.
* `anonymous`: aparece en la búsqueda pero sin revelar identidad ("Un Hermano
  Registrado"); no se lo puede buscar por nombre/matrícula/email.

Default cuando no hay configuración: `workshop` (Mi taller principal).

Reglas de inclusión:

* Un criterio solo matchea si el que busca puede ver esa sección (Taller siempre).
* Un Hermano aparece si coincidís por un campo visible y, o bien podés ver su
  identidad (aparece con nombre), o marcó anónimo (aparece enmascarado).
* Si limitó su identidad y no calificás para verla, y no marcó anónimo, no
  aparece en los resultados.
* El taller principal se determina por la marca `is_principal` de la membresía
  (el taller de registro queda como principal por defecto).
* El Superadmin usa esta pantalla como un miembro normal según sus talleres; el
  acceso total es solo desde las pantallas de Administración.

---

# 22. Fuera de alcance de V1

Pontis V1 no debe implementar:

* Motor de permisos extremadamente dinámico.
* Reglas infinitamente configurables por usuario.
* Auditoría de cada visualización común.
* Consentimientos legales complejos.
* Perfiles públicos sin login.
* Contacto automático sin consentimiento.
* Permisos globales derivados automáticamente de cargos.
* Administración avanzada de oficialidad.
* Workflow complejo de moderación social.
* Marketplace, pagos, carrito o reviews.

---

# 23. Regla final

Pontis debe permitir que los Hermanos se encuentren, se ayuden y se contacten, pero sin convertir la búsqueda en exposición automática de información privada.

El sistema debe favorecer colaboración con privacidad, contacto con consentimiento y administración con trazabilidad.
