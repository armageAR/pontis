import type { UserDegree, UserPosition } from '@/api/profile'

export type ValidationRecordType = 'degree' | 'position'

const DEGREE_LABELS: Record<string, string> = {
  aprendiz: 'Aprendiz',
  companero: 'Compañero',
  maestro: 'Maestro',
}

/**
 * Modelo de fila unificado para la tabla de validaciones: permite mostrar
 * grados y cargos con las mismas columnas y acciones, preservando el tipo de
 * registro para las llamadas de aceptar/rechazar.
 */
export interface ValidationRow {
  key: string
  type: ValidationRecordType
  id: number
  hermano: string
  requestDate: string | null
  taller: string
  recordTypeLabel: string
  details: string
  notes: string | null
  status: 'declared' | 'validated' | 'rejected'
}

function hermanoName(user?: { name: string; last_name: string | null }): string {
  if (!user) return '-'
  return user.last_name ? `${user.last_name}, ${user.name}` : user.name
}

function tallerLabel(workshop?: { name: string; number: number }): string {
  return workshop ? `Taller Nº${workshop.number} ${workshop.name}` : 'Sin taller'
}

export function degreeToRow(d: UserDegree): ValidationRow {
  return {
    key: `degree-${d.id}`,
    type: 'degree',
    id: d.id,
    hermano: hermanoName(d.user),
    requestDate: d.created_at ?? d.start_date,
    taller: tallerLabel(d.workshop),
    recordTypeLabel: 'Grado',
    details: DEGREE_LABELS[d.degree] ?? d.degree,
    notes: d.validation_notes,
    status: d.validation_status,
  }
}

export function positionToRow(p: UserPosition): ValidationRow {
  return {
    key: `position-${p.id}`,
    type: 'position',
    id: p.id,
    hermano: hermanoName(p.user),
    requestDate: p.created_at ?? p.start_date,
    taller: tallerLabel(p.workshop),
    recordTypeLabel: 'Cargo',
    details: p.position?.name ?? 'Cargo',
    notes: p.validation_notes,
    status: p.validation_status,
  }
}
