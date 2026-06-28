# Pontis - Domain Model

## Propósito del documento

Este documento define el modelo de dominio conceptual de Pontis V1.

Su objetivo es describir las entidades principales del sistema, sus relaciones, reglas funcionales y límites de alcance.

Este documento no define migrations, nombres finales de tablas, endpoints, controladores, componentes frontend ni implementación técnica definitiva. Es una guía conceptual para mantener coherencia funcional durante el desarrollo.

## Principios generales del modelo

Pontis V1 se basa en estos principios:

* Persona y Usuario son la misma entidad funcional.
* En la interfaz se usará preferentemente el término `Hermano`.
* Toda ficha pertenece a un Hermano registrado.
* Toda información es privada por defecto.
* La visibilidad se configura explícitamente.
* Un Hermano puede pertenecer a varios Talleres.
* Un Hermano debe tener un Taller principal.
* Los grados tienen historial.
* Los cargos tienen historial.
* Los cargos son contextuales a un Taller.
* Los roles administrativos no son lo mismo que los cargos masónicos.
* Las búsquedas deben respetar privacidad y consentimiento.
* Las acciones sensibles deben conservar trazabilidad.

## Entidades principales

Las entidades principales de Pontis V1 son:

* Hermano / Usuario
* Ficha personal
* Taller / Logia
* Relación Hermano-Taller
* Grado
* Historial de grados
* Cargo
* Historial de cargos
* Servicio ofrecido
* Necesidad
* Publicación interna
* Solicitud de contacto
* Configuración de visibilidad
* Rol administrativo
* Permiso contextual
* Notificación
* Auditoría funcional
* Catálogos base

---

# 1. Hermano / Usuario

## Definición

Representa a una persona real registrada en Pontis.

En Pontis no existen entidades funcionales separadas llamadas `Usuario` y `Persona`.

Todo Hermano es un usuario del sistema, y todo usuario representa a una persona real con ficha personal asociada.

## Términos

* `Hermano`: término preferido en la interfaz general.
* `Usuario`: término técnico para autenticación, acceso, roles, permisos y estados.
* `Persona`: puede usarse como explicación humana, pero no como módulo separado.

## Reglas

* No puede existir una persona sin usuario.
* No puede existir un usuario sin ficha personal.
* No debe existir un menú separado llamado `Personas`.
* La ficha personal, datos masónicos, servicios, necesidades, talleres, grados, cargos y visibilidad pertenecen al mismo registro funcional del Hermano.
* Un Hermano debe estar validado para acceder al sistema interno.
* Un Hermano pendiente no puede ver información interna.

## Datos iniciales obligatorios

Para el registro inicial, el Hermano debe informar:

* Nombre.
* Apellido.
* Email de acceso.
* Documento / DNI.
* Matrícula masónica nacional.
* Taller principal o Taller de pertenencia inicial.
* Grado actual declarado.

## Datos sensibles

El Hermano no puede modificar libremente ciertos datos sensibles:

* Nombre.
* Apellido, si se considera parte de la identidad legal.
* Documento / DNI.
* Matrícula masónica nacional.
* Taller principal inicial, cuando afecte la validación institucional.

Los cambios sobre estos datos deben gestionarse mediante una solicitud de cambio sensible y aprobación de superadmin.

## Estados posibles

Estados funcionales del Hermano:

* Pendiente de verificación de email (`verifying`): registrado, email no verificado aún.
* Pendiente de validación (`pending`): email verificado, esperando aprobación de admin.
* Pendiente de corrección: el revisor solicitó correcciones.
* Activo: aprobado, con acceso al sistema interno.
* Suspendido: suspendido temporalmente.
* Dado de baja: cuenta dada de baja definitivamente.
* Rechazado: solicitud rechazada.
* O∴ Eterno, si corresponde conservar ficha histórica con tratamiento especial.

Nota: en la interfaz masónica debe evitarse usar `Fallecido` como etiqueta principal si se decide usar la expresión `O∴ Eterno`.

---

# 2. Ficha personal

## Definición

Representa el conjunto de información propia del Hermano.

La ficha es privada por defecto y el Hermano decide qué bloques o campos mostrar, a quién y bajo qué condiciones.

## Puede contener

* Identidad básica.
* Datos masónicos.
* Taller principal.
* Otros Talleres.
* Grado actual.
* Historial de grados.
* Cargo actual, si corresponde.
* Historial de cargos.
* Datos de contacto.
* Ubicación.
* Profesión.
* Oficio.
* Actividad.
* Servicios ofrecidos.
* Necesidades.
* Intereses.
* Configuración de visibilidad.

## Reglas

* La ficha pertenece a un único Hermano.
* La ficha no debe ser pública sin login.
* La ficha no debe exponer datos automáticamente.
* El Hermano puede editar su ficha, excepto datos sensibles restringidos.
* Los datos sensibles requieren solicitud y aprobación.
* La visibilidad puede configurarse por bloque y, cuando sea necesario, por campo.
* La ficha puede aparecer en búsquedas solo si la configuración de visibilidad lo permite.

---

# 3. Taller / Logia

## Definición

Representa un Taller o Logia dentro del sistema.

Los Talleres sirven para organizar pertenencias, validar usuarios, contextualizar cargos, limitar permisos y permitir búsquedas.

## Puede contener

* Nombre.
* Número o identificador, si corresponde.
* Zona masónica.
* Provincia.
* Localidad.
* País.
* Datos de contacto o ubicación, si están disponibles.
* Estado.
* Fuente de importación, si proviene del crawler.
* Fecha de última actualización desde fuente externa.

## Reglas

* Un Taller puede tener muchos Hermanos asociados.
* Un Taller puede tener administradores.
* Un Taller puede tener miembros activos, pendientes, históricos o suspendidos.
* Un Taller puede ser cargado manualmente.
* Un Taller puede ser importado o actualizado mediante crawler supervisado.
* El crawler no debe crear usuarios ni fichas personales.
* El crawler solo debe importar o actualizar Talleres / Logias.
* Las diferencias detectadas por el crawler deben ser confirmadas por superadmin antes de aplicarse.
* No debe hacerse scraping de sitios con login.

## Fuera de alcance V1

La entidad Taller no incluye en V1:

* Calendario de reuniones.
* Gestión económica.
* Gestión de asistencia.
* Actas.
* Gestión completa de actividades internas del Taller.

---

# 4. Relación Hermano-Taller

## Definición

Representa el vínculo entre un Hermano y un Taller.

Esta entidad es central porque un Hermano puede pertenecer a varios Talleres y tener distinto rol, estado o cargo según el Taller.

## Reglas

* Un Hermano puede pertenecer a varios Talleres.
* Todo Hermano debe tener un Taller principal.
* Un Taller puede tener muchos Hermanos.
* La relación con cada Taller puede tener estado propio.
* Un Hermano puede ser admin en un Taller y usuario común en otro.
* Un Hermano puede tener cargo en un Taller, otro cargo en otro Taller o no tener cargo.
* La relación debe conservar historial básico cuando corresponda.
* La pertenencia a un Taller no expone automáticamente todos los datos privados del Hermano.
* La pertenencia puede usarse como criterio de visibilidad y búsqueda.

## Puede contener

* Hermano.
* Taller.
* Estado de relación.
* Indicación de Taller principal.
* Fecha de inicio.
* Fecha de fin, si corresponde.
* Motivo de finalización o cambio, si corresponde.
* Observaciones administrativas.
* Usuario que aprobó o modificó la relación, si corresponde.

## Estados posibles

* Pendiente.
* Activa.
* Inactiva.
* Suspendida.
* Finalizada.
* Histórica.

---

# 5. Grado

## Definición

Representa un grado masónico disponible en Pontis V1.

## Grados disponibles en V1

* Aprendiz.
* Compañero.
* Maestro.

## Reglas

* El grado actual del Hermano es obligatorio.
* Maestro es el grado mayor dentro de V1.
* El grado debe conservar historial.
* El grado no equivale automáticamente a un rol administrativo.
* Ser Maestro puede habilitar reglas funcionales específicas, por ejemplo publicar sin autorización previa.
* Ser Maestro no convierte automáticamente al usuario en admin.

---

# 6. Historial de grados

## Definición

Representa los cambios de grado de un Hermano a lo largo del tiempo.

## Reglas

* Todo Hermano activo debe tener un grado actual.
* Cada cambio de grado debe registrar fecha de inicio.
* La fecha de fin es opcional y sirve para cerrar períodos anteriores.
* El historial debe conservarse desde V1.
* El cambio de grado puede asociarse a un Taller cuando aplique.
* La información de grados es privada por defecto.
* La visibilidad del grado actual y del historial debe respetar permisos y configuración del usuario.

## Puede contener

* Hermano.
* Grado.
* Taller asociado, si corresponde.
* Fecha de inicio.
* Fecha de fin.
* Estado.
* Observaciones.
* Usuario que cargó o validó el cambio, si corresponde.

---

# 7. Cargo

## Definición

Representa una función masónica que un Hermano puede ocupar dentro de un Taller.

El cargo es contextual al Taller.

## Reglas

* El cargo es opcional.
* Un Hermano puede no tener cargo.
* Un Hermano puede tener cargos distintos en Talleres distintos.
* Un cargo debe estar asociado a un Hermano y a un Taller.
* El cargo debe tener historial.
* El cargo puede funcionar como capa contextual de permisos.
* El cargo no reemplaza los roles administrativos base.
* Un cargo en un Taller no otorga permisos sobre otros Talleres.
* Si el cargo finaliza, los permisos derivados de ese cargo deben dejar de aplicar.

## Ejemplos conceptuales

* Venerable Maestro.
* Secretario.
* Tesorero.
* Orador.
* Hospitalario.
* Experto.
* Guarda Templo.
* Otro cargo definido por catálogo.

La lista definitiva de cargos debe manejarse como catálogo configurable.

---

# 8. Historial de cargos

## Definición

Representa los períodos durante los cuales un Hermano ocupó un cargo en un Taller.

## Reglas

* Cada cargo asignado debe tener fecha de inicio.
* La fecha de fin es opcional.
* Al cerrar un cargo, debe conservarse el registro histórico.
* Un Hermano puede tener más de un cargo histórico.
* Un Hermano puede tener cargos en diferentes Talleres.
* Los permisos derivados de un cargo solo aplican mientras el cargo esté vigente.
* Las acciones sensibles realizadas por permisos derivados de cargo deben auditarse.

## Puede contener

* Hermano.
* Taller.
* Cargo.
* Fecha de inicio.
* Fecha de fin.
* Estado.
* Observaciones.
* Usuario que asignó o modificó el cargo, si corresponde.

---

# 9. Servicio ofrecido

## Definición

Representa algo que un Hermano puede ofrecer a otros Hermanos.

Puede ser un producto, servicio, conocimiento, oficio, profesión, contacto, ayuda o recomendación.

## Puede contener

* Hermano propietario.
* Título.
* Descripción breve.
* Categoría o rubro.
* Zona o alcance geográfico.
* Modalidad: presencial, remoto o ambas.
* Disponibilidad general.
* Visibilidad.
* Estado.
* Fecha de creación.
* Fecha de actualización.

## Reglas

* Un Hermano puede cargar varios servicios.
* Los servicios son privados por defecto.
* El Hermano decide si los hace visibles.
* El Hermano decide ante qué audiencia son visibles.
* Un servicio puede aparecer en búsquedas sin revelar identidad ni contacto.
* El contacto debe avanzar por aceptación o consentimiento cuando corresponda.
* No hay pagos, carrito, contratación directa ni reviews en V1.

## Estados posibles

* Borrador.
* Activo.
* Pausado.
* Oculto.
* Dado de baja lógica.

---

# 10. Necesidad

## Definición

Representa algo que un Hermano necesita, busca o quiere resolver.

Puede ser una necesidad masónica, profesional, personal o general.

## Puede contener

* Hermano propietario.
* Título.
* Descripción.
* Categoría o rubro.
* Zona o localidad relevante.
* Urgencia o prioridad, si corresponde.
* Alcance de visibilidad.
* Estado.
* Fecha de creación.
* Fecha de actualización.

## Reglas

* Una necesidad es privada por defecto.
* El Hermano decide si la muestra y a qué audiencia.
* Una necesidad puede buscar coincidencias con servicios, profesiones, oficios, intereses o datos habilitados por otros Hermanos.
* Una necesidad no implica obligación comercial.
* Una necesidad no implica contratación dentro del sistema.
* Una necesidad puede derivar en solicitud de contacto.

## Estados posibles

* Borrador.
* Abierta.
* En búsqueda.
* Con coincidencias.
* Contacto solicitado.
* Vinculada.
* Cerrada.
* Cancelada.

---

# 11. Búsqueda

## Definición

La búsqueda es una acción funcional que permite consultar si existe un Hermano, Taller, servicio, necesidad, profesión, oficio, producto, ubicación o contacto relevante.

No necesariamente debe existir como entidad persistida en V1, salvo que se decida registrar historial de búsquedas o métricas en una etapa posterior.

## Reglas

* La búsqueda solo está disponible para usuarios activos y validados.
* La búsqueda debe respetar visibilidad y permisos.
* La búsqueda no debe revelar datos que el Hermano encontrado no haya decidido mostrar.
* Puede mostrar cantidad de coincidencias sin revelar identidad.
* Puede mostrar coincidencias anónimas.
* Puede permitir iniciar una solicitud de contacto.
* La búsqueda no obliga al usuario encontrado a publicar datos.
* La búsqueda no debe generar notificaciones por cada aparición común.

## Criterios posibles

La búsqueda puede operar sobre:

* Nombre visible del Hermano.
* Profesión.
* Oficio.
* Servicio ofrecido.
* Necesidad.
* Categoría o rubro.
* Ciudad.
* Provincia.
* País.
* Estado.
* Zona.
* Taller.
* Datos de ubicación habilitados.
* Coincidencias anónimas permitidas por visibilidad.

## Tipos de resultado

* Resultado identificado: muestra datos habilitados por el Hermano encontrado.
* Resultado parcialmente identificado: muestra información limitada, como nombre de pila, zona, rubro o Taller si fue habilitado.
* Resultado anónimo: indica que existe una coincidencia, sin revelar identidad ni contacto.
* Resultado institucional: permite encontrar o contactar un Taller si la búsqueda apunta al Taller y no a un Hermano específico.

---

# 12. Publicación interna

## Definición

Representa un aviso interno creado por un Hermano para comunicar una necesidad, búsqueda, ofrecimiento o información a una audiencia definida.

## Relación con otros conceptos

* Servicio ofrecido: lo que un Hermano puede brindar.
* Necesidad: lo que un Hermano necesita resolver.
* Búsqueda: acción rápida para encontrar coincidencias sin publicar nada.
* Publicación: aviso interno visible para una audiencia cuando el Hermano quiere comunicar algo o cuando la búsqueda no alcanza.

## Puede contener

* Hermano autor.
* Tipo de publicación.
* Título.
* Descripción.
* Categoría o rubro.
* Zona o alcance.
* Audiencia.
* Identidad visible.
* Estado.
* Vista previa de datos visibles.
* Fecha de creación.
* Fecha de publicación.
* Fecha de cierre, si corresponde.

## Tipos posibles

* Necesidad.
* Búsqueda.
* Aviso.
* Ofrecimiento.
* Recomendación o pedido de ayuda.

## Reglas

* Las publicaciones solo son visibles para usuarios activos y validados.
* No hay publicaciones públicas sin login en V1.
* El usuario debe definir categoría, rubro, zona o alcance cuando corresponda.
* El usuario debe elegir cómo quiere mostrarse.
* El sistema debe mostrar una vista previa antes de publicar.
* La publicación no modifica automáticamente la visibilidad general de la ficha.
* El contacto derivado de una publicación debe respetar consentimiento y privacidad.

## Aprobación

* Si el Hermano es Maestro, puede publicar sin autorización previa.
* Si el Hermano es Aprendiz o Compañero, la publicación requiere autorización antes de quedar visible.
* La autorización puede realizarla un admin del Taller principal o un superadmin.
* Ser Maestro no otorga por sí solo permisos administrativos generales.

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

---

# 13. Solicitud de contacto

## Definición

Representa el pedido de un Hermano para contactar a otro Hermano a partir de una búsqueda, servicio, necesidad o publicación.

## Reglas

* Debe respetar la configuración de visibilidad del destinatario.
* Puede iniciarse cuando existe una coincidencia directa, parcial o anónima.
* El solicitante debe definir qué datos propios quiere compartir.
* El destinatario puede aceptar o rechazar.
* Si acepta, se habilita el contacto según los datos acordados.
* Si rechaza, no se revela información adicional.
* El rechazo no debe exponer datos privados.
* La solicitud y su resolución deben auditarse.

## Puede contener

* Hermano solicitante.
* Hermano destinatario, si está identificado.
* Referencia a servicio, necesidad, publicación o búsqueda.
* Mensaje inicial, si corresponde.
* Datos que el solicitante acepta compartir.
* Datos que el destinatario acepta compartir.
* Estado.
* Fecha de creación.
* Fecha de respuesta.
* Fecha de cierre o expiración.

## Estados posibles

* Iniciada.
* Pendiente de respuesta.
* Aceptada.
* Rechazada.
* Cancelada.
* Cerrada.
* Expirada, si se define vencimiento.

---

# 14. Configuración de visibilidad

## Definición

Representa las reglas que determinan qué información puede ver cada audiencia.

## Reglas

* Todo es privado por defecto.
* La visibilidad debe ser opt-in.
* La visibilidad puede configurarse por bloque.
* Cuando sea necesario, puede configurarse por campo.
* La V1 debe evitar un motor de reglas ilimitado.
* La visibilidad debe aplicarse a ficha, contacto, servicios, necesidades, publicaciones, grados, cargos, Talleres y búsquedas.
* La visibilidad no debe ser ignorada por búsqueda, auditoría ni notificaciones.

## Audiencias posibles en V1

* Privado.
* Taller principal.
* Mis Talleres.
* Talleres seleccionados.
* Usuarios activos / validados.
* Disponible en búsquedas sin revelar identidad.

## Ejemplos de bloques visibles

* Datos personales.
* Datos de contacto.
* Ubicación.
* Datos masónicos.
* Profesión / oficio.
* Servicios.
* Necesidades.
* Talleres.
* Grados.
* Cargos.

---

# 15. Rol administrativo

## Definición

Representa una capacidad administrativa dentro del sistema.

Los roles administrativos no son lo mismo que los cargos masónicos.

## Roles base V1

* Superadmin.
* Admin de Taller.
* Usuario / Hermano.

## Superadmin

Puede:

* Administrar todo el sistema.
* Validar usuarios de cualquier Taller.
* Administrar Talleres, zonas, provincias, localidades y catálogos.
* Ejecutar y confirmar actualizaciones del crawler.
* Administrar roles administrativos.
* Resolver cambios sensibles.
* Suspender o dar de baja usuarios.
* Consultar auditoría funcional cuando corresponda.

## Admin de Taller

Puede:

* Administrar información de los Talleres donde tiene permiso.
* Validar usuarios que declaran como principal uno de sus Talleres.
* Aprobar o rechazar pertenencias a su Taller.
* Gestionar estados de relación Hermano-Taller dentro de su Taller.
* Cargar o actualizar cargos masónicos dentro de su Taller, si tiene permiso.
* Ver solicitudes administrativas vinculadas a su Taller.

No puede:

* Administrar usuarios ajenos a sus Talleres.
* Validar usuarios de otros Talleres.
* Ejecutar o confirmar el crawler global.
* Aprobar cambios sensibles globales como documento, matrícula o identidad legal.
* Ver datos privados sin relación funcional con su Taller.

## Usuario / Hermano

Puede:

* Ver y editar su propia ficha, excepto datos sensibles restringidos.
* Solicitar cambios sensibles.
* Configurar visibilidad.
* Cargar servicios ofrecidos.
* Crear necesidades y publicaciones.
* Buscar Talleres, servicios o Hermanos según permisos.
* Iniciar, aceptar o rechazar solicitudes de contacto.
* Pausar, ocultar o dar de baja sus propios servicios, necesidades o publicaciones.

No puede:

* Ver datos privados de otros Hermanos sin autorización.
* Contactar automáticamente a otro Hermano sin consentimiento cuando la visibilidad sea anónima o restringida.
* Validar nuevos usuarios.
* Administrar Talleres, catálogos o roles.
* Modificar cargos, grados o datos institucionales salvo que tenga rol o permiso adicional.

---

# 16. Permiso contextual

## Definición

Representa una capacidad derivada de un contexto específico, normalmente un Taller o un cargo masónico.

## Reglas

* Un permiso contextual aplica solo dentro del contexto que lo origina.
* Un cargo en un Taller no otorga permisos sobre otros Talleres.
* Un permiso contextual no debe exponer automáticamente datos privados.
* Si termina la relación, cargo o contexto que originó el permiso, el permiso debe dejar de aplicar.
* Las acciones sensibles realizadas por permisos contextuales deben auditarse.

## Ejemplo

Un Hermano puede tener un cargo en el Taller A que le permite realizar ciertas acciones dentro de ese Taller, pero no puede usar ese cargo para administrar datos del Taller B.

---

# 17. Notificación

## Definición

Representa un aviso funcional generado por un evento relevante.

## Reglas

* Las notificaciones no deben revelar datos sensibles innecesarios.
* Deben respetar roles, Talleres, cargos y visibilidad.
* Pueden resolverse dentro del sistema.
* Si se define, también pueden enviarse por email.
* No deben generarse por cada aparición en una búsqueda común.
* Un usuario pendiente no debe recibir avisos sobre contenido interno que todavía no puede consultar.

## Eventos notificables en V1

* Registro pendiente de validación.
* Registro aprobado, rechazado o pendiente de corrección.
* Solicitud de cambio sensible.
* Resolución de cambio sensible.
* Solicitud de contacto recibida.
* Solicitud de contacto aceptada o rechazada.
* Necesidad con coincidencias relevantes.
* Servicio o publicación con solicitud de contacto.
* Publicación pendiente de autorización.
* Publicación aprobada, rechazada o con correcciones requeridas.
* Resultado del crawler con diferencias para revisar.

---

# 18. Auditoría funcional

## Definición

Representa el registro de acciones relevantes del sistema.

La auditoría conserva trazabilidad funcional, pero no busca registrar cada interacción menor.

## Debe permitir saber

* Qué ocurrió.
* Quién lo hizo.
* Cuándo lo hizo.
* Sobre qué Hermano, Taller, dato o entidad actuó.
* Con qué rol, cargo o permiso actuó.
* Qué decisión tomó, cuando aplique.

## Eventos a auditar en V1

* Registro de nuevos usuarios.
* Aprobación, rechazo o corrección de registros.
* Cambio de estado de Hermano.
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
* Solicitudes de contacto y sus resoluciones.
* Acciones realizadas por permisos derivados de cargos.

## Reglas

* Solo roles autorizados pueden consultar auditoría.
* La auditoría no debe convertirse en una vía indirecta para acceder a datos privados.
* Las acciones sensibles deben quedar registradas.
* La auditoría debe conservar contexto de rol, cargo o permiso usado.

---

# 19. Catálogos base

## Definición

Representan listas controladas utilizadas por el sistema para evitar texto libre inconsistente y permitir búsquedas confiables.

## Catálogos necesarios en V1

* Zonas masónicas.
* Países.
* Provincias.
* Localidades.
* Talleres / Logias.
* Grados.
* Cargos.
* Categorías de servicios.
* Categorías de necesidades.
* Estados de Hermano.
* Estados de relación Hermano-Taller.
* Estados de servicios.
* Estados de necesidades.
* Estados de publicaciones.
* Estados de solicitudes de contacto.

## Reglas

* Zonas, provincias y localidades deben estar normalizadas.
* Los Talleres deben poder asociarse a zona, provincia y localidad.
* Las categorías deben ayudar a buscar y filtrar.
* Los catálogos deben poder administrarse por superadmin cuando corresponda.
* Los catálogos no deben romper historial si un valor cambia de nombre.

---

# 20. Crawler de Talleres

## Definición

Proceso que permite cargar o actualizar Talleres / Logias desde una fuente pública definida.

## Reglas

* Solo puede ser ejecutado o confirmado por superadmin.
* Lee una fuente pública.
* No debe hacer scraping de sitios con login.
* No crea Hermanos.
* No crea fichas personales.
* Solo importa o actualiza Talleres / Logias.
* Debe mostrar diferencias antes de aplicar cambios.
* El superadmin debe confirmar altas, modificaciones o posibles bajas.
* No debe aplicar cambios automáticamente sin revisión humana.
* Debe registrar fuente y fecha de última actualización.

## Cambios posibles

* Nuevo Taller detectado.
* Taller existente modificado.
* Taller posiblemente removido de la fuente.
* Cambio de zona, localidad, provincia o dato descriptivo.
* Error de lectura o inconsistencia de fuente.

---

# 21. Relaciones principales

## Hermano / Usuario

* Tiene una ficha personal.
* Tiene un grado actual.
* Tiene historial de grados.
* Puede tener varios servicios ofrecidos.
* Puede tener varias necesidades.
* Puede crear publicaciones.
* Puede iniciar solicitudes de contacto.
* Puede recibir solicitudes de contacto.
* Puede pertenecer a varios Talleres.
* Debe tener un Taller principal.
* Puede tener cargos en uno o más Talleres.
* Puede tener roles administrativos.

## Taller / Logia

* Tiene muchos Hermanos asociados.
* Puede tener admins de Taller.
* Puede tener cargos asignados a Hermanos.
* Pertenece a una zona masónica.
* Puede estar asociado a provincia y localidad.
* Puede provenir de carga manual o crawler.

## Relación Hermano-Taller

* Une un Hermano con un Taller.
* Define estado de pertenencia.
* Puede indicar Taller principal.
* Puede contextualizar permisos.
* Puede tener historial.

## Cargo

* Pertenece a un catálogo.
* Se asigna a un Hermano dentro de un Taller.
* Tiene fecha de inicio y fecha de fin opcional.
* Puede generar permisos contextuales.

## Grado

* Pertenece a un catálogo fijo en V1.
* Se asigna a un Hermano.
* Tiene historial.
* Puede influir en reglas funcionales específicas.

## Servicio

* Pertenece a un Hermano.
* Tiene visibilidad propia.
* Puede aparecer en búsquedas.
* Puede originar solicitudes de contacto.

## Necesidad

* Pertenece a un Hermano.
* Tiene visibilidad propia.
* Puede generar coincidencias.
* Puede originar solicitudes de contacto o publicaciones.

## Publicación

* Pertenece a un Hermano.
* Tiene audiencia definida.
* Puede requerir autorización.
* Puede originar solicitudes de contacto.

## Solicitud de contacto

* Une a un Hermano solicitante con un destinatario identificado o parcialmente identificado.
* Puede originarse en búsqueda, servicio, necesidad o publicación.
* Respeta consentimiento.
* Debe auditarse.

---

# 22. Reglas de diseño funcional

## Privacidad primero

Si una regla no está clara, el sistema debe elegir la opción que exponga menos información.

## Consentimiento antes de contacto

Si el destinatario no habilitó contacto directo, debe existir una solicitud de contacto antes de revelar datos adicionales.

## Separación de conceptos

No mezclar:

* Usuario con Persona como entidades separadas.
* Cargo masónico con rol administrativo.
* Grado masónico con permiso administrativo general.
* Publicación con servicio.
* Necesidad con búsqueda.
* Visibilidad con autorización administrativa.

## Historial obligatorio

Deben conservar historial:

* Grados.
* Cargos.
* Relaciones Hermano-Taller cuando corresponda.
* Cambios sensibles.
* Acciones administrativas relevantes.
* Cambios importantes de visibilidad.

## Revisión humana para acciones sensibles

Deben requerir revisión humana:

* Validación de usuarios.
* Cambios sensibles.
* Confirmación de cambios del crawler.
* Aprobación de publicaciones cuando corresponda.
* Suspensión o baja de usuarios.

---

# 23. Fuera de alcance técnico de este documento

Este documento no define:

* Nombres finales de tablas.
* Nombres finales de modelos.
* Estructura exacta de migrations.
* Endpoints.
* Policies.
* Requests.
* Resources.
* Jobs.
* Eventos técnicos.
* Componentes frontend.
* Diseño visual.
* Tests específicos.

Esos elementos deben derivarse de este modelo, pero documentarse aparte.
