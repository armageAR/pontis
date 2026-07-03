# Pontis - Overview

## Propósito

Pontis es una aplicación web para conectar Hermanos dentro de una comunidad registrada, validada y privada.

La idea central es que, antes de recurrir a un proveedor externo, un usuario pueda consultar si dentro de su comunidad existe un Hermano que pueda ayudar con un servicio, producto, oficio, profesión, conocimiento, recomendación, contacto o necesidad puntual.

Pontis no es un marketplace, una red social pública ni una plataforma transaccional. Es una herramienta interna de comunidad, búsqueda, contacto y colaboración, construida sobre privacidad, consentimiento y validación institucional.

## Objetivo de la V1

La V1 debe construir una base funcional clara para:

* Registrar y validar Hermanos.
* Administrar fichas personales.
* Administrar Talleres / Logias.
* Relacionar Hermanos con uno o varios Talleres.
* Registrar grados masónicos e historial de grados.
* Registrar cargos masónicos e historial de cargos.
* Permitir que los Hermanos publiquen servicios ofrecidos.
* Permitir que los Hermanos declaren necesidades o búsquedas.
* Permitir búsquedas internas por servicio, profesión, oficio, ubicación, Taller u otros datos habilitados.
* Habilitar contacto entre Hermanos respetando privacidad y consentimiento.
* Administrar roles básicos.
* Mantener trazabilidad de acciones relevantes.

La V1 debe ser simple de implementar, pero lo suficientemente sólida como para crecer sin romper el modelo funcional.

## Concepto principal

En Pontis, cada Hermano tiene una ficha propia.

Esa ficha puede contener información personal, masónica, profesional, de contacto, ubicación, servicios ofrecidos, necesidades, pertenencias a Talleres, grados, cargos y configuraciones de visibilidad.

El usuario decide qué información mostrar, ante quién mostrarla y bajo qué condiciones puede ser contactado.

Por defecto, toda la información es privada.

## Usuario, Persona y Hermano

En Pontis no existen entidades funcionales separadas llamadas `Usuario` y `Persona`.

Toda persona registrada en Pontis es un usuario del sistema, y todo usuario representa a una persona real.

Para evitar ambigüedades:

* En la interfaz general se usará preferentemente el término `Hermano`.
* `Usuario` se usará para autenticación, acceso, roles, permisos y estados del sistema.
* `Persona` puede usarse como lenguaje explicativo, pero no como módulo separado.
* No debe existir un menú separado llamado `Personas`.
* La ficha personal pertenece al mismo registro funcional del usuario/hermano.

## Principios funcionales

Pontis V1 debe respetar estos principios:

* Privacidad por defecto.
* Visibilidad configurable por el usuario.
* Contacto con consentimiento.
* Búsqueda sin exposición innecesaria de datos personales.
* Validación de usuarios antes de acceder al sistema interno.
* Separación entre grados masónicos, cargos masónicos y roles administrativos.
* Historial para grados y cargos.
* Trazabilidad de acciones relevantes.
* Alcance funcional simple y controlado.

## Alcance público sin login

Sin login, Pontis solo debe mostrar una homepage pública.

La homepage puede explicar qué es Pontis y permitir iniciar el registro.

Sin login no se deben mostrar:

* Fichas personales.
* Hermanos registrados.
* Talleres internos.
* Servicios.
* Necesidades.
* Publicaciones.
* Búsquedas.
* Datos de contacto.
* Información interna de la comunidad.

## Funcionalidades principales de V1

### Inicio / Dashboard

Vista inicial para usuarios activos.

Debe mostrar accesos rápidos a las acciones principales:

* Mi ficha.
* Búsqueda.
* Servicios.
* Necesidades.
* Publicaciones.
* Talleres, según permisos.
* Administración, si corresponde.
* Notificaciones o tareas pendientes.

No debe priorizar métricas avanzadas ni reportes complejos en V1.

### Hermanos

Sección para consultar y gestionar Hermanos registrados, siempre respetando permisos y visibilidad.

Cada Hermano es un usuario del sistema con ficha personal asociada.

La ficha puede incluir:

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
* Profesión, oficio o actividad.
* Servicios ofrecidos.
* Necesidades o intereses.
* Configuración de visibilidad.

### Talleres / Logias

Sección para administrar los Talleres / Logias disponibles en el sistema.

Debe permitir:

* Alta, edición, consulta y baja lógica.
* Asociación con zona masónica, provincia y localidad.
* Consulta de miembros vinculados según permisos.
* Edición manual posterior a la carga inicial.
* Actualización mediante crawler supervisado por superadmin.

La gestión de Talleres en V1 no incluye calendario de reuniones, asistencia, actas ni gestión económica.

### Relación Hermano-Taller

Representa el vínculo entre un Hermano y un Taller.

Reglas principales:

* Un Hermano puede pertenecer a varios Talleres.
* Todo Hermano debe tener un Taller principal.
* La relación con cada Taller puede tener estado propio.
* Un Hermano puede ser admin en un Taller y usuario común en otro.
* Un Hermano puede tener cargo en un Taller, otro cargo en otro Taller o no tener cargo.
* La relación debe conservar historial básico cuando corresponda.

### Grados

Pontis debe registrar el grado actual y el historial de grados del Hermano.

Grados disponibles en V1:

* Aprendiz.
* Compañero.
* Maestro.

Reglas principales:

* El grado actual es obligatorio.
* Maestro es el grado mayor dentro de V1.
* Cada cambio de grado debe conservar fecha de inicio.
* La fecha de fin es opcional y sirve para cerrar períodos anteriores.
* El cambio de grado puede asociarse al Taller correspondiente cuando aplique.
* La información de grados es privada por defecto.

### Cargos

Pontis debe registrar cargos masónicos ocupados por un Hermano dentro de uno o más Talleres.

Reglas principales:

* El cargo es opcional.
* Un Hermano puede no tener cargo.
* Un Hermano puede tener cargos distintos en Talleres distintos.
* Cada cargo debe estar asociado a un Hermano y a un Taller.
* Cada cargo debe tener fecha de inicio y fecha de fin opcional.
* El historial de cargos debe conservarse.
* Los cargos pueden funcionar como capa contextual de permisos, pero no reemplazan los roles administrativos.

### Servicios ofrecidos

Permite que un Hermano indique qué productos, servicios, conocimientos, oficios o ayuda puede ofrecer.

Un servicio puede incluir:

* Título.
* Descripción.
* Categoría o rubro.
* Zona o alcance geográfico.
* Modalidad presencial, remota o ambas.
* Disponibilidad general.
* Visibilidad.
* Estado.

Los servicios son privados por defecto. El usuario decide si los hace visibles, para quién y bajo qué condiciones.

Pontis no debe incluir pagos, carrito, contratación directa ni calificaciones en V1.

### Necesidades

Permite que un Hermano indique qué necesita, qué busca o en qué tema requiere ayuda.

Una necesidad puede incluir:

* Título.
* Descripción.
* Categoría o rubro.
* Zona o localidad relevante.
* Urgencia o prioridad, si corresponde.
* Alcance de visibilidad.
* Estado.

Una necesidad puede ser masónica, profesional, personal o general.

Una necesidad no implica obligación comercial ni contratación dentro del sistema.

### Búsqueda

La búsqueda permite consultar si existe un Hermano, Taller, servicio, profesión, oficio, producto, contacto o ayuda relacionada antes de publicar una necesidad o recurrir a alguien externo.

La búsqueda no debe limitarse solamente a datos del Hermano.

Debe permitir buscar por:

* Nombre u otros datos visibles del Hermano.
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

La búsqueda solo debe mostrar información que el usuario encontrado haya decidido mostrar.

Puede indicar que existe una coincidencia sin revelar identidad ni contacto.

Cuando corresponda, debe permitir iniciar una solicitud de contacto.

### Publicaciones internas

Las publicaciones permiten que un Hermano comunique una necesidad, búsqueda, aviso u ofrecimiento a una audiencia definida.

Una publicación puede representar:

* Una necesidad.
* Una búsqueda.
* Un aviso interno.
* Un ofrecimiento.
* Una recomendación o pedido de ayuda.

Las publicaciones solo serán visibles para usuarios activos y validados.

No habrá publicaciones públicas sin login en V1.

Antes de publicar, el sistema debe mostrar una vista previa de qué datos quedarán visibles.

La publicación no modifica automáticamente la visibilidad general de la ficha.

## Privacidad y visibilidad

La privacidad es una condición central del sistema.

Por defecto, ningún dato de ficha, contacto, actividad, servicio, grado, cargo, Taller, publicación o información personal/profesional se hace visible automáticamente.

La visibilidad debe poder configurarse por bloque y, cuando sea necesario, por campo.

Audiencias posibles en V1:

* Privado.
* Taller principal.
* Mis Talleres.
* Usuarios activos/validados.
* Disponible en búsquedas sin revelar identidad.

La V1 debe evitar un motor de reglas extremadamente dinámico. Se priorizan reglas predefinidas, claras y mantenibles.

## Roles administrativos base

Roles base de V1:

* Superadmin.
* Admin de Taller.
* Usuario/Hermano.

### Superadmin

Puede administrar el sistema completo, validar usuarios, administrar Talleres y catálogos, resolver cambios sensibles, ejecutar o confirmar actualizaciones del crawler, suspender usuarios y consultar auditoría funcional cuando corresponda.

### Admin de Taller

Puede administrar información y solicitudes relacionadas con los Talleres donde tiene permisos.

No puede administrar usuarios ajenos a sus Talleres, validar usuarios de otros Talleres, aprobar cambios sensibles globales ni ver datos privados sin relación funcional.

### Usuario/Hermano

Puede administrar su propia ficha, configurar visibilidad, cargar servicios, crear necesidades, publicar, buscar y solicitar contacto según permisos.

No puede validar usuarios, administrar Talleres, modificar datos institucionales ni acceder a datos privados de otros Hermanos sin autorización.

## Cargos y permisos contextuales

Los cargos masónicos pueden habilitar acciones específicas dentro de un Taller, pero no reemplazan los roles administrativos base.

Un cargo siempre está asociado a un Hermano y a un Taller.

Los permisos derivados de un cargo son contextuales a ese Taller.

Un cargo no otorga permisos sobre otros Talleres.

Si el cargo finaliza, los permisos asociados deben dejar de aplicar.

## Notificaciones

Las notificaciones deben acompañar eventos relevantes sin revelar datos sensibles innecesarios.

En V1 pueden existir notificaciones para:

* Registro pendiente de validación.
* Registro aprobado, rechazado o pendiente de corrección.
* Solicitud de cambio sensible.
* Resolución de cambio sensible.
* Solicitud de contacto recibida.
* Solicitud de contacto aceptada o rechazada.
* Necesidad con coincidencias relevantes.
* Intervención administrativa a posteriori sobre una publicación (baja o suspensión), cuando corresponda.
* Resultado del crawler con diferencias para revisar.

## Auditoría funcional

Pontis debe conservar trazabilidad de acciones relevantes.

La auditoría debe permitir saber:

* Qué ocurrió.
* Quién lo hizo.
* Cuándo lo hizo.
* Sobre qué usuario, Taller, dato o entidad actuó.
* Con qué rol, cargo o permiso actuó.
* Qué decisión tomó, cuando aplique.

La auditoría no debe convertirse en una forma indirecta de acceder a datos privados.

## Fuera de alcance de V1

Pontis V1 no debe priorizar:

* Marketplace transaccional.
* Pagos.
* Carrito.
* Contratación directa.
* Reviews o calificaciones.
* Chat interno completo.
* Red social pública.
* Perfiles públicos sin login.
* Reportes avanzados.
* Automatizaciones sofisticadas.
* Motor de permisos extremadamente dinámico.
* Integraciones externas no indispensables.
* Diseño visual final completo.
* Experiencia mobile avanzada.

## Regla general para desarrollo

Cuando haya dudas entre exponer información o protegerla, Pontis debe priorizar la privacidad.

Cuando haya dudas entre automatizar una acción sensible o pedir confirmación, Pontis debe priorizar revisión humana.

Cuando haya dudas entre agregar flexibilidad compleja o mantener reglas simples, V1 debe priorizar reglas simples y claras.
