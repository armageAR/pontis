# Configuración de agentes

## Comportamiento general
- Actuar como desarrollador senior con criterio pragmático.
- Priorizar claridad, privacidad, trazabilidad y reglas simples por encima de flexibilidad innecesaria.
- Respetar la documentación funcional de `docs/project/` y las especificaciones de `openspec/specs/`.
- Mantener el alcance de Pontis V1 controlado y evitar agregar funcionalidades no solicitadas.
- Usar nombres claros, descriptivos y consistentes con el dominio del proyecto.
- Usar `Hermano` como término principal de interfaz y `Usuario` para autenticación, acceso, roles, permisos y estados del sistema.
- No crear un módulo funcional separado llamado `Personas`.

## Principios funcionales
- Todo dato debe ser privado por defecto.
- La visibilidad debe ser explícita y evaluarse desde la perspectiva del usuario que mira.
- La búsqueda nunca debe revelar información que no haya sido habilitada por visibilidad.
- El contacto entre Hermanos debe respetar consentimiento.
- Los usuarios pendientes de validación no deben acceder a información interna.
- Los roles administrativos, grados masónicos y cargos masónicos deben mantenerse separados.
- Los cargos masónicos son contextuales a un Taller y no otorgan permisos globales.
- Los grados no otorgan permisos administrativos generales.
- Los datos sensibles requieren solicitud, revisión humana y aprobación de Superadmin.
- Las acciones administrativas y sensibles relevantes deben quedar auditadas.
- Ante la duda, exponer menos información.

## Estilo de código
- Backend: usar Laravel API siguiendo convenciones del framework.
- Frontend: usar React con componentes funcionales y TypeScript.
- Mantener componentes, controladores, servicios y policies con responsabilidades claras.
- Preferir estructuras simples antes que abstracciones prematuras.
- Usar Laravel Sanctum para autenticación y acceso API cuando corresponda.
- Usar React Router para navegación frontend cuando corresponda.
- Usar lucide-react para íconos en la interfaz.
- Usar Axios para llamadas HTTP desde el frontend.
- Mantener validaciones, permisos y reglas de visibilidad cerca de los límites adecuados del sistema.
- Agregar comentarios solo cuando aclaren una regla de dominio o una decisión no obvia.

## Guía de interfaz
- Construir interfaces limpias, sobrias y funcionales, orientadas a una herramienta interna de comunidad.
- Priorizar legibilidad, jerarquía visual clara y flujos simples.
- Mostrar al usuario qué información quedará visible antes de publicar, activar o compartir datos.
- No mostrar fichas, Hermanos, Talleres internos, servicios, necesidades, publicaciones ni búsquedas sin login y validación.
- Evitar diseños de red social pública o marketplace.
- Usar controles claros para visibilidad, consentimiento, estados, aprobaciones y solicitudes.
- No usar textos o interfaces que sugieran contratación directa, pagos, carrito, reviews o calificaciones.

## Manejo de datos
- Modelar toda persona registrada como Hermano / Usuario con ficha asociada.
- Todo Hermano activo debe tener un grado actual.
- Conservar historial de grados y cargos.
- Asociar cargos a un Hermano y a un Taller específico.
- Permitir que un Hermano pertenezca a varios Talleres, con un Taller principal activo.
- No sobrescribir información histórica sin conservar trazabilidad cuando corresponda.
- Tratar datos personales, contacto, ubicación, datos masónicos, servicios, necesidades, publicaciones, grados, cargos y Talleres como privados por defecto.
- Evitar que auditoría, notificaciones o búsqueda funcionen como vías indirectas para acceder a datos privados.

## Control de alcance
- No implementar perfiles públicos sin login.
- No implementar marketplace, pagos, carrito, contratación directa, reviews, facturación ni gestión de disputas comerciales.
- No implementar chat interno completo en V1.
- No implementar reportes avanzados en V1.
- No implementar un motor de permisos extremadamente dinámico en V1.
- No automatizar decisiones sensibles que requieren revisión humana.
- No aplicar cambios del crawler sin revisión de Superadmin.
- No hacer scraping de sitios que requieran login.
- No agregar funcionalidades de operación completa de Talleres como calendario, asistencia, actas o gestión económica en V1.

## Comunicación
- Escribir código y documentación fáciles de entender para el equipo.
- Explicar decisiones técnicas cuando afecten privacidad, permisos, validación o alcance funcional.
- Si una solicitud contradice la privacidad por defecto o el alcance de V1, indicar el riesgo y proponer una alternativa segura.
- Mantener las respuestas concisas, concretas y orientadas a acción.
