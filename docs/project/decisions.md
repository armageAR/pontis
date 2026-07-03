# Pontis - Decisions

## Propósito del documento

Este documento registra decisiones funcionales y de producto ya tomadas para Pontis V1.

Su objetivo es evitar ambigüedades durante el desarrollo y servir como referencia para Claude Code, documentación futura y revisión del proyecto.

Cada decisión debe indicar:

* Estado.
* Fecha.
* Decisión tomada.
* Motivo.
* Consecuencias.
* Documentos relacionados, si corresponde.

## Estados posibles

* `Accepted`: decisión aceptada y vigente.
* `Proposed`: decisión propuesta, pero todavía no cerrada.
* `Deprecated`: decisión anterior que ya no debe usarse.
* `Superseded`: decisión reemplazada por otra decisión posterior.

---

# 1. Persona y Usuario son la misma entidad funcional

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis no tendrá entidades funcionales separadas llamadas `Persona` y `Usuario`.

Toda persona registrada en Pontis es un usuario del sistema, y todo usuario representa a una persona real con ficha personal asociada.

En la interfaz general se usará preferentemente el término `Hermano`.

## Motivo

Separar `Persona` y `Usuario` generaría ambigüedad innecesaria para V1.

La aplicación necesita que autenticación, ficha personal, datos masónicos, pertenencias, servicios, necesidades, visibilidad y permisos pertenezcan al mismo registro funcional.

## Consecuencias

* No debe existir un módulo separado llamado `Personas`.
* No debe existir un menú separado llamado `Personas`.
* No debe poder existir una persona sin cuenta de usuario.
* No debe poder existir un usuario sin ficha personal.
* El desarrollo puede usar modelos técnicos separados si hiciera falta, pero conceptualmente debe tratarse como una única entidad funcional.
* La interfaz debe priorizar el término `Hermano`.

## Documentos relacionados

* overview.md
* domain-model.md
* permissions.md

---

# 2. La privacidad es privada por defecto

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Toda información del Hermano será privada por defecto.

Ningún dato de ficha, contacto, actividad, servicio, grado, cargo, Taller, publicación o información personal/profesional se hará visible automáticamente.

## Motivo

Pontis trabaja con información sensible de una comunidad privada. La confianza del sistema depende de que el usuario controle qué comparte, con quién y bajo qué condiciones.

## Consecuencias

* La visibilidad debe ser opt-in.
* La búsqueda debe respetar configuración de visibilidad.
* Las publicaciones no modifican automáticamente la visibilidad general de la ficha.
* Los servicios visibles no modifican automáticamente la visibilidad general del Hermano.
* Las necesidades visibles no modifican automáticamente la visibilidad general del Hermano.
* Ante la duda, el sistema debe exponer menos información.

## Documentos relacionados

* overview.md
* permissions.md
* workflows.md

---

# 3. La V1 no tendrá perfiles públicos sin login

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

En V1, el acceso sin login se limitará a una homepage pública y al inicio del registro.

No habrá perfiles públicos, fichas públicas, Talleres internos públicos, servicios públicos, necesidades públicas ni publicaciones públicas sin autenticación.

## Motivo

El objetivo de V1 es construir una comunidad validada y privada, no una red social pública ni un directorio abierto.

## Consecuencias

* La homepage puede explicar qué es Pontis.
* La homepage puede permitir iniciar registro.
* Toda funcionalidad interna requiere autenticación y validación.
* Las búsquedas solo estarán disponibles para usuarios activos y validados.
* Los datos internos no deben indexarse públicamente.

## Documentos relacionados

* overview.md
* permissions.md
* workflows.md

---

# 4. Todo Hermano debe estar validado antes de acceder al sistema interno

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Un usuario registrado no puede acceder al sistema interno hasta ser validado por un Admin de Taller autorizado o un Superadmin.

## Motivo

Pontis requiere comunidad registrada y validada. El registro por sí solo no debe habilitar acceso a información interna.

## Consecuencias

* El usuario recién registrado queda en estado `Pendiente de validación`.
* El usuario pendiente solo puede acceder a funciones mínimas del proceso de registro.
* Puede consultar o seleccionar un catálogo mínimo de Talleres necesario para registro.
* No puede buscar Hermanos.
* No puede ver servicios, necesidades ni publicaciones.
* No puede contactar Hermanos.
* La aprobación, rechazo o corrección debe quedar auditada.

## Documentos relacionados

* permissions.md
* workflows.md

---

# 5. Un Hermano puede pertenecer a varios Talleres

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Un Hermano puede pertenecer a más de un Taller.

Además, debe tener un Taller principal.

## Motivo

La pertenencia masónica puede no limitarse a un único Taller. También es necesario que permisos, cargos, administración y visibilidad puedan evaluarse según el Taller correspondiente.

## Consecuencias

* Debe existir una relación Hermano-Taller.
* La relación debe poder tener estado propio.
* Un Hermano puede ser admin en un Taller y usuario común en otro.
* Un Hermano puede tener cargo en un Taller, otro cargo en otro Taller o no tener cargo.
* Los permisos deben evaluarse por Taller.
* La pertenencia no debe exponer automáticamente todos los datos privados del Hermano.

## Documentos relacionados

* domain-model.md
* permissions.md
* workflows.md

---

# 6. Todo Hermano debe tener un Taller principal

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Todo Hermano debe tener un Taller principal.

Durante el registro, el usuario debe seleccionar su Taller principal o Taller inicial de pertenencia desde un catálogo mínimo.

## Motivo

El Taller principal sirve para validación inicial, contexto institucional, administración básica y reglas de visibilidad.

## Consecuencias

* El registro debe solicitar Taller principal.
* La validación puede ser realizada por un Admin de ese Taller o por un Superadmin.
* El Taller principal puede usarse como audiencia de visibilidad.
* El cambio de Taller principal inicial puede considerarse dato sensible si afecta validación institucional.
* Salvo decisión futura, solo debe existir un Taller principal activo a la vez.

## Documentos relacionados

* domain-model.md
* permissions.md
* workflows.md

---

# 7. Los grados disponibles en V1 son Aprendiz, Compañero y Maestro

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis V1 manejará tres grados:

* Aprendiz.
* Compañero.
* Maestro.

Maestro será el grado mayor dentro de V1.

## Motivo

La V1 debe mantenerse simple y alineada con las necesidades iniciales del sistema.

## Consecuencias

* El grado actual será obligatorio.
* El historial de grados debe conservarse desde V1.
* Cada cambio de grado debe registrar fecha de inicio.
* La fecha de fin será opcional para cerrar períodos anteriores.
* No se incluirá ritualística, contenido reservado, evaluaciones ni promoción automática de grados.
* El grado no equivale a rol administrativo.

## Documentos relacionados

* domain-model.md
* permissions.md
* workflows.md

---

# 8. Ser Maestro no otorga permisos administrativos generales

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

El grado Maestro puede habilitar reglas funcionales específicas, pero no otorga por sí solo permisos administrativos generales.

## Motivo

El grado masónico y el rol administrativo son conceptos distintos. Mezclarlos generaría permisos incorrectos y exposición innecesaria de información.

## Consecuencias

* Publicar no depende del grado: cualquier usuario registrado publica sin autorización previa (ver Decisión 16). Ser Maestro no agrega un privilegio de publicación.
* Un Maestro no es automáticamente Admin de Taller.
* Un Maestro no puede validar usuarios por el solo hecho de ser Maestro.
* Un Maestro no puede administrar Talleres por el solo hecho de ser Maestro.
* Un Maestro no puede ver datos privados por el solo hecho de ser Maestro.
* Cualquier permiso adicional debe definirse explícitamente.

## Documentos relacionados

* permissions.md
* workflows.md

---

# 9. Los cargos masónicos son contextuales a un Taller

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Un cargo masónico siempre estará asociado a un Hermano y a un Taller.

Los permisos derivados de un cargo, si existen, aplican solo dentro del Taller correspondiente.

## Motivo

Un Hermano puede tener cargo en un Taller y no tener cargo en otro, o tener cargos diferentes en Talleres diferentes.

## Consecuencias

* El cargo debe asociarse a la relación Hermano-Taller o al par Hermano + Taller.
* El cargo debe tener historial.
* El cargo puede generar permisos contextuales.
* Un cargo no otorga permisos globales.
* Un cargo vencido no debe seguir otorgando permisos.
* Un cargo en el Taller A no aplica sobre el Taller B.
* Las acciones sensibles realizadas por permisos derivados de cargo deben auditarse.

## Documentos relacionados

* domain-model.md
* permissions.md
* workflows.md

---

# 10. Los cargos y grados deben conservar historial

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis V1 debe conservar historial de grados y cargos.

## Motivo

La evolución masónica del Hermano y los cargos ocupados forman parte de la ficha histórica e institucional.

## Consecuencias

* Los cambios de grado no deben sobrescribir sin conservar historial.
* Los cargos no deben eliminarse al finalizar.
* Cada registro debe tener fecha de inicio.
* La fecha de fin será opcional y se usará para cerrar períodos.
* El historial debe respetar privacidad y visibilidad.
* Las modificaciones deben quedar auditadas cuando corresponda.

## Documentos relacionados

* domain-model.md
* workflows.md

---

# 11. Los datos sensibles requieren solicitud y aprobación

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

El Hermano no podrá modificar libremente ciertos datos sensibles.

Los cambios deberán gestionarse mediante una solicitud y aprobación de un revisor autorizado (Superadmin o Admin de Taller con alcance, ver más abajo).

## Datos sensibles iniciales

* Nombre.
* Apellido, si se considera identidad legal.
* Documento / DNI.
* Matrícula masónica nacional.
* Taller principal inicial, cuando afecte validación institucional.

## Motivo

Estos datos impactan identidad, validación institucional y trazabilidad.

## Consecuencias

* La ficha debe bloquear edición directa de estos campos.
* El usuario debe poder solicitar cambios.
* El Superadmin puede aprobar, rechazar o pedir más información para cualquier solicitud.
* El Admin de Taller puede aprobar, rechazar o pedir más información **solo** para solicitudes de Hermanos que pertenezcan a un Taller que administra (ver enmienda 2026-07-03).
* La resolución debe quedar auditada, registrando al revisor real.

## Enmienda 2026-07-03: alcance de revisión del Admin de Taller

Se amplía la autoridad de revisión de nombre, apellido, DNI y matrícula masónica.

* El Admin de Taller ya valida datos institucionales de los Hermanos de sus Talleres, por lo que también puede revisar estos cambios sensibles de identidad cuando el Hermano solicitante pertenece a un Taller que administra.
* El alcance es acotado: el Admin de Taller solo ve y resuelve solicitudes de Hermanos con membresía activa en alguno de sus Talleres administrados; no puede ver ni resolver solicitudes de Hermanos fuera de esos Talleres.
* El Superadmin mantiene autoridad global sobre todas las solicitudes.
* Al crear una solicitud se notifica a los Superadmin y a los Admin de Taller de los Talleres del solicitante, sin duplicar destinatarios.

## Documentos relacionados

* permissions.md
* workflows.md

---

# 12. La búsqueda debe poder devolver resultados identificados, parciales o anónimos

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

La búsqueda puede mostrar distintos tipos de resultado según visibilidad:

* Identificado.
* Parcialmente identificado.
* Anónimo.
* Institucional.

## Motivo

Pontis debe permitir descubrir ayuda disponible sin exponer automáticamente identidad ni datos de contacto.

## Consecuencias

* La búsqueda puede indicar que existe una coincidencia sin revelar quién es.
* La búsqueda puede mostrar cantidad de coincidencias.
* La búsqueda puede mostrar datos parciales si fueron habilitados.
* El contacto debe avanzar mediante solicitud cuando corresponda.
* La búsqueda no debe revelar datos privados ni permitir inferirlos mediante filtros.

## Documentos relacionados

* overview.md
* permissions.md
* workflows.md

---

# 13. La búsqueda no se limita a Hermanos por nombre

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

La búsqueda debe ser amplia.

No debe limitarse a buscar Hermanos por nombre o datos personales.

Debe permitir buscar por:

* Hermano.
* Taller.
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
* Datos de ubicación habilitados.

## Motivo

El caso de uso principal es encontrar ayuda dentro de la comunidad. A veces el usuario no sabe a quién busca, sino qué necesita o dónde necesita contacto.

Ejemplo:

* Buscar un contador.
* Buscar alguien en una ciudad a la que voy a viajar.
* Buscar un Taller.
* Buscar un oficio.
* Buscar un servicio.
* Buscar una necesidad relacionada.

## Consecuencias

* El modelo debe contemplar datos de ubicación normalizados.
* Los Talleres deben ser buscables según permisos.
* Las profesiones, oficios, servicios y necesidades deben alimentar la búsqueda.
* La búsqueda debe respetar visibilidad en todos los casos.
* No debe mostrarse información privada por el solo hecho de coincidir.

## Documentos relacionados

* overview.md
* domain-model.md
* permissions.md
* workflows.md

---

# 14. El contacto requiere consentimiento cuando no está habilitado directamente

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Cuando un resultado de búsqueda, servicio, necesidad o publicación no habilita contacto directo, el usuario debe iniciar una solicitud de contacto.

El destinatario puede aceptar o rechazar.

## Motivo

Pontis debe permitir colaboración sin exponer información personal automáticamente.

## Consecuencias

* El solicitante debe definir qué datos propios quiere compartir.
* El destinatario decide si acepta.
* Si acepta, se habilita el contacto según datos acordados.
* Si rechaza, no se revela información adicional.
* La solicitud y resolución deben quedar auditadas.
* Los resultados anónimos no deben revelar identidad salvo aceptación.

## Documentos relacionados

* permissions.md
* workflows.md

---

# 15. Los servicios, necesidades y publicaciones no forman un marketplace

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis V1 no será un marketplace transaccional.

Servicios, necesidades y publicaciones sirven para encontrar ayuda, ofrecer colaboración y facilitar contacto, pero no para cerrar operaciones dentro del sistema.

## Motivo

La V1 debe enfocarse en comunidad, búsqueda, privacidad y contacto, no en pagos ni comercio interno.

## Consecuencias

No se implementará en V1:

* Pagos.
* Carrito.
* Contratación directa.
* Reviews.
* Calificaciones.
* Facturación.
* Gestión de disputas comerciales.

## Documentos relacionados

* overview.md
* domain-model.md
* workflows.md

---

# 16. Las publicaciones son libres para todo usuario registrado, sin aprobación previa

## Estado

Accepted

## Fecha

2026-07-03

## Reemplaza a

Decisión previa "Las publicaciones de Aprendices y Compañeros requieren aprobación" (2026-06-28), que exigía autorización según el grado del autor.

## Decisión

Cualquier usuario registrado y validado puede crear publicaciones (servicios y necesidades) sin autorización administrativa previa.

El grado del autor (Aprendiz, Compañero o Maestro) no condiciona la posibilidad de publicar. La publicación queda disponible de inmediato en Mis Publicaciones con el estado elegido por el autor.

No existe estado `Pendiente de autorización` ni acción "Pedir corrección" en el ciclo de vida de una publicación nueva.

## Motivo

El requerimiento de producto indica que publicar debe ser libre dentro de la comunidad ya validada. Mantener un paso de moderación previa por grado generaba una experiencia contradictoria y agregaba complejidad de workflow que no se justifica en V1.

La validación de ingreso al sistema sigue siendo el control principal: solo usuarios validados acceden al sistema interno y, por lo tanto, pueden publicar.

## Consecuencias

* El sistema no necesita evaluar el grado del autor para permitir publicar.
* Una publicación nueva no pasa por `Pendiente de autorización`; queda en el estado elegido por el autor (por ejemplo `Activa` o `Borrador`).
* Se retira la acción "Pedir corrección" y el estado `Requiere corrección` del flujo activo de publicaciones.
* Los datos históricos con estados `Pendiente de autorización` o `Requiere corrección` se tratan como legacy: no se generan nuevos y no se ofrecen como estado seleccionable en la UI.
* El moderar publicaciones a posteriori (por seguridad o abuso) queda como acción administrativa opcional fuera del ciclo básico de publicar, no como requisito previo.
* Esta decisión afecta solo a publicaciones. La validación de usuarios, la aprobación de pertenencia a Talleres y la aprobación de cambios de datos sensibles se mantienen sin cambios.

## Documentos relacionados

* permissions.md
* workflows.md

---

# 17. El crawler solo actualiza Talleres desde fuente pública y con revisión humana

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis podrá tener un crawler para cargar o actualizar Talleres / Logias desde una fuente pública definida.

El crawler no aplicará cambios automáticamente sin confirmación humana.

## Motivo

Los Talleres pueden estar publicados en una fuente externa. Automatizar lectura ayuda a mantener datos actualizados, pero aplicar cambios sin revisión puede introducir errores.

## Consecuencias

* Solo Superadmin puede ejecutar o confirmar cambios globales del crawler.
* El crawler lee solo fuentes públicas.
* No hace scraping de sitios con login.
* No crea usuarios.
* No crea fichas personales.
* Solo importa o actualiza Talleres / Logias.
* Debe mostrar diferencias antes de aplicar cambios.
* El Superadmin confirma altas, modificaciones o posibles bajas.
* Se registra fuente y fecha de última actualización.
* La ejecución y confirmación deben auditarse.

## Documentos relacionados

* domain-model.md
* workflows.md

---

# 18. Los catálogos base deben estar normalizados

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Zonas, países, provincias, localidades, Talleres, grados, cargos, categorías y estados deben manejarse como catálogos o valores controlados cuando corresponda.

## Motivo

La búsqueda y la administración necesitan datos consistentes. El texto libre dificultaría filtrar por ubicación, Taller, zona, cargo o categoría.

## Consecuencias

* Zonas, provincias y localidades no deben quedar como texto libre sin normalización.
* Los Talleres deben asociarse a zona, provincia y localidad.
* Cargos y categorías deben poder administrarse como catálogo.
* Los cambios de nombre en catálogos no deben romper historial.
* La búsqueda debe apoyarse en estos datos normalizados.

## Documentos relacionados

* domain-model.md
* workflows.md

---

# 19. El menú debe usar lenguaje funcional y evitar ambigüedad

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

La interfaz debe evitar términos ambiguos o duplicados.

En particular:

* Usar `Hermanos` en lugar de `Personas`.
* Evitar un menú separado `Personas`.
* Usar `Usuario` solo cuando se hable de acceso, autenticación, roles, permisos o estado técnico.
* En estados, reemplazar `Fallecido` por `O∴ Eterno` si se adopta ese tratamiento.

## Motivo

El lenguaje de la interfaz debe reflejar correctamente el modelo funcional y el contexto de la comunidad.

## Consecuencias

* Los módulos deben nombrarse según conceptos reales del dominio.
* `Hermanos` será el término principal de interfaz.
* `Usuarios` puede aparecer en administración técnica.
* No se deben duplicar menús que representen el mismo concepto.
* Las etiquetas deben respetar el lenguaje definido para Pontis.

## Documentos relacionados

* overview.md
* domain-model.md

---

# 20. El menú lateral debe poder ocultarse en pantallas chicas

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

El menú lateral debe poder ocultarse, especialmente en pantallas chicas.

## Motivo

La interfaz debe ser usable en resoluciones menores. Aunque la experiencia mobile completa no sea prioridad de V1, el layout no debe quedar inutilizable.

## Consecuencias

* El menú lateral debe soportar estado colapsado u oculto.
* En pantallas chicas debe poder cerrarse.
* Las pantallas principales deben seguir siendo utilizables sin depender de menú fijo.
* Esta decisión afecta UX/frontend, no el modelo funcional.

## Documentos relacionados

* overview.md

---

# 21. V1 debe priorizar simplicidad sobre flexibilidad extrema

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis V1 debe priorizar reglas claras y mantenibles antes que motores extremadamente flexibles.

## Motivo

Un motor demasiado dinámico de permisos, visibilidad o automatizaciones haría más lenta y riesgosa la primera versión.

## Consecuencias

V1 no debe priorizar:

* Motor de permisos ilimitado.
* Reglas infinitamente configurables por usuario.
* Automatizaciones sofisticadas.
* Reportes avanzados.
* Moderación compleja.
* Workflows administrativos excesivamente configurables.

La flexibilidad puede crecer en versiones futuras sobre una base simple.

## Documentos relacionados

* overview.md
* permissions.md
* workflows.md

---

# 22. La auditoría debe registrar acciones relevantes, no cada interacción menor

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis debe auditar acciones funcionales relevantes, pero no registrar cada visualización o interacción menor en V1.

## Motivo

La auditoría debe dar trazabilidad sin convertir el sistema en una carga técnica excesiva ni en una vía indirecta para acceder a datos privados.

## Consecuencias

Deben auditarse acciones como:

* Registro y validación de usuarios.
* Cambios de estado.
* Cambios sensibles.
* Cambios de visibilidad relevantes.
* Cambios de grado.
* Cambios de cargo.
* Solicitudes de contacto.
* Resoluciones administrativas.
* Ejecución y confirmación del crawler.
* Baja o suspensión de usuarios.
* Creación y baja de publicaciones, y cualquier intervención administrativa a posteriori sobre ellas.

No debe auditarse cada aparición en una búsqueda común ni cada visualización normal de un dato permitido.

## Documentos relacionados

* permissions.md
* workflows.md

---

# 23. Notificaciones simples y sin exposición innecesaria

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis V1 tendrá notificaciones funcionales simples para eventos relevantes, sin revelar datos sensibles innecesarios.

## Motivo

Las notificaciones deben ayudar al usuario a actuar, no transformarse en un canal alternativo de exposición de información privada.

## Consecuencias

Las notificaciones deben respetar:

* Estado del usuario.
* Rol.
* Taller.
* Cargo contextual.
* Visibilidad.
* Privacidad.
* Consentimiento.

No deben generarse por cada aparición en búsqueda común.

## Documentos relacionados

* permissions.md
* workflows.md

---

# 24. Estados principales de V1

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis V1 manejará estados funcionales simples para las entidades principales.

## Estados de Hermano

* Pendiente de verificación de email (`verifying`): el usuario se registró pero aún no verificó su dirección de email. No puede acceder al sistema interno. Los administradores no son notificados hasta que el email sea verificado.
* Pendiente de validación (`pending`): el email fue verificado. El usuario espera aprobación por un Admin de Taller o Superadmin.
* Pendiente de corrección (`correction`): el revisor solicitó correcciones al usuario.
* Activo (`active`): el usuario fue aprobado y puede acceder al sistema interno.
* Suspendido (`suspended`): cuenta suspendida temporalmente por un administrador.
* Dado de baja (`inactive`): cuenta dada de baja definitivamente.
* Rechazado (`rejected`): solicitud rechazada. El usuario no puede acceder al sistema.
* O∴ Eterno: ficha histórica conservada con tratamiento especial.

## Estados de relación Hermano-Taller

* Pendiente.
* Activa.
* Inactiva.
* Suspendida.
* Finalizada.
* Histórica.

## Estados de servicio ofrecido

* Borrador.
* Activo.
* Pausado.
* Oculto.
* Dado de baja lógica.

## Estados de necesidad

* Borrador.
* Abierta.
* En búsqueda.
* Con coincidencias.
* Contacto solicitado.
* Vinculada.
* Cerrada.
* Cancelada.

## Estados de publicación

* Borrador.
* Activa.
* Rechazada.
* Pausada.
* Cerrada.
* Cancelada.
* Dada de baja lógica.

Estados legacy, presentes solo en datos históricos y no seleccionables en nuevas publicaciones (ver Decisión 16): `Pendiente de autorización`, `Requiere corrección`.

## Estados de solicitud de contacto

* Iniciada.
* Pendiente de respuesta.
* Aceptada.
* Rechazada.
* Cancelada.
* Cerrada.
* Expirada, si se define vencimiento.

## Motivo

Definir estados comunes evita inconsistencias durante implementación y testing.

## Consecuencias

* Los estados deben usarse de forma consistente.
* No se deben inventar nuevos estados sin registrarlos en este documento.
* Si un estado cambia o se elimina, debe actualizarse este documento y los documentos relacionados.

## Documentos relacionados

* domain-model.md
* permissions.md
* workflows.md

---

# 25. Fuera de alcance confirmado para V1

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Pontis V1 no incluirá:

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
* Gestión económica de Talleres.
* Actas.
* Calendario completo de reuniones.
* Gestión de asistencia.
* Votaciones.
* Promoción automática de grados.
* Renovación automática de cargos.

## Motivo

La V1 debe ser implementable y estable. El foco es comunidad validada, fichas, Talleres, grados, cargos, servicios, necesidades, búsqueda, contacto, privacidad y trazabilidad.

## Consecuencias

* Cualquier tarea que agregue estos elementos debe pasar a backlog futuro.
* Claude Code no debe implementar estos módulos salvo instrucción explícita posterior.
* Si alguno de estos puntos cambia, debe registrarse una nueva decisión.

## Documentos relacionados

* overview.md
* workflows.md

---

# 26. Regla general para nuevas decisiones

## Estado

Accepted

## Fecha

2026-06-28

## Decisión

Toda decisión funcional importante debe agregarse a este documento.

## Motivo

Pontis va a crecer y es fácil perder decisiones tomadas en conversaciones, prompts o código.

## Consecuencias

Antes de implementar cambios importantes, revisar:

* overview.md
* domain-model.md
* permissions.md
* workflows.md
* decisions.md

Si el cambio contradice una decisión existente, se debe:

1. Marcar la decisión anterior como `Superseded` o `Deprecated`.
2. Crear una nueva decisión.
3. Explicar el motivo del cambio.
4. Actualizar los documentos relacionados.
