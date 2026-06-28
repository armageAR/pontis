# Deep Interview Spec: Alineación de menú e IA (Pontis app ↔ documento V1)

## Metadata
- Interview ID: di-menu-align-2026-06-27
- Rounds: 4
- Final Ambiguity Score: ~11%
- Type: brownfield
- Generated: 2026-06-27
- Threshold: 0.2
- Threshold Source: default
- Initial Context Summarized: no
- Status: PASSED

## Clarity Breakdown
| Dimensión | Score | Peso | Ponderado |
|-----------|-------|------|-----------|
| Goal Clarity | 0.92 | 0.35 | 0.322 |
| Constraint Clarity | 0.88 | 0.25 | 0.220 |
| Success Criteria | 0.85 | 0.25 | 0.213 |
| Context Clarity | 0.90 | 0.15 | 0.135 |
| **Total Clarity** | | | **0.889** |
| **Ambiguity** | | | **0.111** |

## Topology
| Component | Status | Description | Coverage / Deferral Note |
|-----------|--------|-------------|--------------------------|
| Menú / Arquitectura de información | active | Reorganizar y alinear el menú app ↔ documento, ítem por ítem | Cubierto: Buscar unificado, Mis publicaciones, Bandeja, Administración, Perfil |
| Brechas de funcionalidad | active | Cerrar diferencias funcionales que surgen del alineamiento | Cubierto: Publicaciones resuelto por unificación (sin módulo nuevo) |

## Goal
Reorganizar el menú de la app Pontis (branch `development`) de ~10 entradas dispersas a una estructura de **6 entradas principales + 1 área de administración**, alineando los nombres y agrupaciones con el documento de alcance V1, y actualizando el documento donde la app definió una mejor solución. El objetivo es que la distribución del menú sea inmediatamente entendible para el usuario/hermano.

### Menú objetivo
**Principal (hermano):**
1. **Panel** (dashboard) — sin cambios
2. **Buscar** — unifica personas + servicios + necesidades en pestañas/filtros (reemplaza "Hermanos" + "Explorar"); mapea a doc 6.9 Búsqueda
3. **Talleres** — sin cambios
4. **Mis publicaciones** — agrupa Servicios (*Ofrezco*) + Necesidades (*Necesito*); conserva flujo de aprobación por grado
5. **Bandeja** — unifica Contactos + Trámites/Cambios sensibles + Notificaciones
6. **Perfil** (tu nombre) — ficha + grados + cargos + visibilidad + relación-taller (mismo registro usuario/hermano)

**Solo roles admin:**
7. **Administración** — gestión/validación de hermanos, talleres, catálogos, zonas, crawler de talleres

## Constraints
- Persona = Usuario (sin entidad separada); ya implementado, debe preservarse.
- Conservar el flujo de aprobación de publicaciones por grado (Maestro directo; Aprendiz/Compañero → pendiente).
- Conservar las reglas de visibilidad (6 niveles) en Buscar (búsqueda anónima, enmascaramiento de identidad).
- "Administración" solo visible para superadmin / admin de taller, según permisos existentes.
- V1: simplicidad — no agregar módulos nuevos si la funcionalidad ya existe.

## Non-Goals
- NO crear un módulo "Publicaciones" separado (se cubre con Servicios + Necesidades).
- NO red social pública / perfiles sin login.
- NO rediseño visual completo ni experiencia mobile completa (fuera de V1).
- NO motor de permisos dinámico.

## Acceptance Criteria
- [ ] El menú principal muestra exactamente: Panel, Buscar, Talleres, Mis publicaciones, Bandeja, Perfil.
- [ ] "Buscar" reemplaza "Hermanos" y "Explorar" con pestañas Personas / Servicios / Necesidades, respetando visibilidad y enmascaramiento anónimo.
- [ ] "Mis publicaciones" agrupa Servicios (Ofrezco) y Necesidades (Necesito) conservando estados y aprobación.
- [ ] "Bandeja" agrupa Contactos, Trámites/Cambios sensibles y Notificaciones en sub-secciones.
- [ ] "Administración" (solo admin) agrupa hermanos, talleres, catálogos, zonas y crawler; desaparece "Admin hermanos" del nivel principal.
- [ ] El documento Google se actualiza: 6.10 Publicaciones colapsa en 6.7/6.8; 6.9 refleja "Buscar" unificado; se documenta la IA del menú.
- [ ] No se rompe ninguna ruta existente (redirects desde /people y /explore hacia /buscar si aplica).

## Assumptions Exposed & Resolved
| Assumption | Challenge | Resolution |
|------------|-----------|------------|
| Hermanos y Explorar son cosas distintas | Doc 6.9 los piensa como una sola Búsqueda | Unificar en "Buscar" con pestañas |
| El doc exige un módulo Publicaciones | 6.10 lista tipos = Servicio + Necesidad; aprobación ya existe | No crear módulo; unificar bajo "Mis publicaciones"; actualizar doc |
| Cada utilidad necesita su slot (Contrarian) | Contactos/Solicitudes/Notif. son todas "requieren atención" | Unificar en "Bandeja" |
| Admin = solo "Admin hermanos" | Catálogos/zonas/crawler estaban sueltos | Un solo área "Administración" |

## Technical Context (brownfield)
- Frontend: `pontis-app/src/components/AppLayout.tsx` (nav), `src/App.tsx` (rutas). Páginas: `pages/people` (Hermanos), `pages/explore` (Explorar), `pages/services`, `pages/needs`, `pages/contact-requests`, `pages/change-requests`, `pages/users` (Admin hermanos).
- Backend: rutas en `pontis-api/routes/api.php`. Existen `ExploreController`, `PeopleController`, controladores de servicios/necesidades/contactos. No requiere cambios de datos: es reagrupación de UI + posible unificación de páginas de búsqueda.
- "Buscar" puede componerse reutilizando PeoplePage + ExplorePage como pestañas de una nueva `SearchPage`.

## Ontology (Key Entities)
| Entity | Type | Fields | Relationships |
|--------|------|--------|---------------|
| Hermano/Usuario | core domain | ficha, grados, cargos, visibilidad | pertenece a Talleres; publica Servicios/Necesidades |
| Buscar | supporting (acción) | pestañas: personas/servicios/necesidades | filtra por visibilidad |
| Publicación | core domain | tipo (Servicio=Ofrezco / Necesidad=Necesito), estado, aprobación | creada por Hermano |
| Bandeja | supporting (vista) | Contactos, Trámites, Notificaciones | agrupa items accionables del Hermano |
| Administración | supporting (área rol) | hermanos, talleres, catálogos, zonas, crawler | visible a superadmin/admin taller |

## Ontology Convergence
| Round | Entity Count | New | Changed | Stable | Stability Ratio |
|-------|-------------|-----|---------|--------|----------------|
| 1 | 3 (Hermano, Buscar, Publicación) | 3 | - | - | N/A |
| 2 | 4 (+Publicación refinada) | 0 | 1 | 3 | 100% |
| 3 | 5 (+Administración) | 1 | 0 | 4 | 80% |
| 4 | 5 (+Bandeja, -overlap) | 1 | 0 | 4 | 100% |

## Interview Transcript
<details>
<summary>Full Q&A (4 rounds + Round 0)</summary>

### Round 0 — Topología
**Q:** ¿2 componentes (mejorar funcionalidades + reorganizar menú)?
**A:** "Discutir cada opción del menú de la app y del documento para entender la mejor opción y alinear todo."

### Round 1 — Clúster de descubrimiento
**Q:** ¿Cómo organizar Hermanos + Explorar vs Búsqueda (6.9)?
**A:** Un solo "Buscar" con pestañas. **Ambigüedad: 46%**

### Round 2 — Publicaciones
**Q:** ¿Qué hacer con Publicaciones internas (6.10)?
**A:** "Que recomiendes vos" → recomendación: unificar en Servicios+Necesidades → confirmado "Sí, unificar". **Ambigüedad: 28%**

### Round 3 — Administración
**Q:** ¿Cómo ordenar el área administrativa dispersa?
**A:** Un solo "Administración". **Ambigüedad: 20.5%**

### Round 4 — Bandeja (Contrarian)
**Q:** ¿Unificar Contactos + Solicitudes + Notificaciones?
**A:** Bandeja unificada. **Ambigüedad: 11%**

</details>
