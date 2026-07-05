# Proyecto: Pontis

## Resumen
Pontis es una aplicación web privada para conectar Hermanos dentro de una comunidad registrada y validada.

El proyecto ayuda a un usuario a descubrir si otro Hermano dentro de la comunidad puede asistir con un servicio, producto, oficio, profesión, conocimiento, recomendación, contacto o necesidad específica antes de buscar fuera de la comunidad.

Pontis no es una red social pública, un marketplace ni una plataforma transaccional. Es una herramienta interna de comunidad para búsqueda, descubrimiento, contacto y colaboración, construida sobre validación institucional, privacidad por defecto, visibilidad explícita y consentimiento.

## Funcionalidades principales
- Homepage pública con acceso al registro
- Flujo de registro con verificación de email y validación institucional
- Ficha personal para cada Hermano
- Configuración de visibilidad privada por defecto, por bloque y, cuando sea necesario, por campo
- Administración de Talleres / Logias
- Relación entre Hermanos y uno o más Talleres
- Grado actual obligatorio e historial de grados
- Cargos opcionales asociados a un Taller, con historial
- Búsqueda interna sobre Hermanos, Talleres, profesiones, oficios, categorías, ubicaciones y datos habilitados
- Solicitudes de contacto con consentimiento cuando el contacto directo no esté explícitamente habilitado
- Notificaciones para eventos relevantes de validación, contacto, crawler y cambios sensibles
- Auditoría funcional para acciones administrativas y sensibles relevantes
- Roles básicos: Superadmin, Admin de Taller y Usuario / Hermano
- Soporte de crawler supervisado para importar o actualizar Talleres / Logias

## Comportamiento de usuarios
- Un visitante público solo puede ver la homepage e iniciar el registro.
- Un usuario registrado debe verificar su email y ser aprobado antes de acceder a información interna.
- Un Hermano pendiente puede completar datos requeridos, seleccionar un Taller inicial, declarar un grado y corregir datos enviados cuando se le solicite.
- Un Hermano activo puede administrar su ficha, visibilidad y preferencias de contacto.
- Un Hermano activo puede buscar ayuda o contactos, pero los resultados deben respetar la visibilidad y pueden ser identificados, parcialmente identificados, anónimos o institucionales.
- Un Hermano puede iniciar una solicitud de contacto cuando un resultado habilitado no expone datos de contacto directo.
- El destinatario de una solicitud de contacto puede aceptarla o rechazarla, controlando qué datos se comparten.
- Un Admin de Taller puede validar y administrar usuarios, relaciones y cargos solo dentro de sus Talleres autorizados.
- Un Superadmin puede administrar el sistema completo, incluyendo usuarios, Talleres, catálogos, cambios sensibles, revisión del crawler, roles y auditoría funcional.

## Stack tecnico
- Backend: API Laravel
- El Backend esta ubicado en el directorio pontis-api
- Lenguaje/runtime backend: PHP 8.3+
- Autenticación / acceso API: Laravel Sanctum
- Frontend: React
- El Frontend esta ubicado en el directorio pontis-app
- Lenguaje frontend: TypeScript
- Herramientas frontend: Vite
- Ruteo frontend: React Router
- Cliente HTTP: Axios
- Iconos: lucide-react
- Soporte de estilos/build: Tailwind CSS e integración Laravel Vite cuando corresponda
- Testing/herramientas: PHPUnit, Laravel Pint, ESLint, TypeScript

## Objetivos de diseno
- Priorizar privacidad, consentimiento y validación institucional por encima de exposición o automatización.
- Mantener el alcance funcional de V1 simple, claro y mantenible.
- Usar el término Hermano en la interfaz general y reservar Usuario para autenticación, acceso, roles, permisos y estado del sistema.
- Evitar un módulo separado de Personas; toda persona registrada es funcionalmente el mismo registro que el usuario del sistema con ficha asociada.
- Hacer que toda información personal, de contacto, profesional, masónica, grados, cargos y Talleres sea privada por defecto.
- Hacer que la visibilidad sea explícita y comprensible antes de que un usuario active o exponga información.
- Permitir una búsqueda útil sin revelar datos que no hayan sido habilitados explícitamente.
- Preservar registros históricos de grados y cargos.
- Mantener separados conceptualmente los roles administrativos, los grados masónicos y los cargos masónicos.
- Requerir revisión humana para acciones sensibles en lugar de automatizar completamente decisiones institucionales.
- Mantener trazabilidad de acciones relevantes sin convertir la auditoría en una vía indirecta para saltear la privacidad.

## Limitaciones de alcance
- Sin perfiles públicos sin login
- Sin listado público de Hermanos, Talleres, servicios, necesidades o publicaciones
- Sin publicaciones, ofertas ni necesidades publicadas en V1; el modulo queda diferido a V2 y no debe ser visible ni accesible aunque exista codigo dormido.
- Sin acceso a datos internos para usuarios pendientes de validación
- Sin comportamiento de marketplace
- Sin pagos
- Sin carrito
- Sin flujo de contratación directa
- Sin calificaciones, reviews, facturación ni gestión de disputas comerciales
- Sin chat interno completo en V1
- Sin reportes avanzados en V1
- Sin motor de reglas de permisos altamente dinámico en V1
- Sin automatizaciones sofisticadas para decisiones sensibles
- Sin integraciones externas salvo que sean estrictamente necesarias
- Sin calendario de Logia, asistencia, actas, gestión económica ni operación completa de Talleres en V1
- Sin aplicar cambios del crawler sin revisión de Superadmin
- Sin scraping de sitios que requieran login
- Sin experiencia mobile avanzada ni requisito de diseño visual final en V1

## Objetivo del proyecto
Pontis V1 tiene como objetivo establecer una base funcional sólida para una comunidad privada y validada de Hermanos.

La primera versión debe soportar registro, validación, fichas personales, relaciones con Talleres, grados, cargos, búsqueda interna, contacto controlado, roles, visibilidad, notificaciones y auditabilidad, manteniendo limitada la complejidad de implementación. Las publicaciones, ofertas y necesidades publicadas quedan diferidas a V2.

Cuando exista duda entre exponer información o protegerla, Pontis debe protegerla. Cuando exista duda entre automatizar una acción sensible o requerir confirmación, Pontis debe requerir revisión humana. Cuando exista duda entre agregar complejidad flexible o mantener reglas claras, V1 debe mantener reglas claras.
