import type { Workshop } from '@/api/workshops'
import Badge from '@/components/Badge'
import './WorkshopDetail.css'

interface WorkshopDetailProps {
  workshop: Workshop
}

function Field({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="detail-field">
      <span className="detail-label">{label}</span>
      <span className="detail-value">{value ?? '—'}</span>
    </div>
  )
}

export default function WorkshopDetail({ workshop }: WorkshopDetailProps) {
  return (
    <div className="workshop-detail">
      <div className="detail-row">
        <Field label="Número" value={<strong>{workshop.number}</strong>} />
        <Field label="Nombre" value={workshop.name} />
      </div>
      <div className="detail-row">
        <Field label="Nro. de zona" value={workshop.zone_number} />
        <Field label="Nombre de zona" value={workshop.zone_name} />
      </div>
      <div className="detail-row">
        <Field label="Día de trabajo" value={workshop.work_day} />
        <Field label="Frecuencia" value={workshop.work_frequency} />
      </div>
      <Field label="Dirección" value={workshop.address} />
      <div className="detail-row detail-row-3">
        <Field label="Ciudad" value={workshop.city} />
        <Field label="Provincia" value={workshop.province} />
        <Field label="País" value={workshop.country} />
      </div>
      <div className="detail-row">
        <Field label="Idioma" value={workshop.language} />
        <Field
          label="Estado"
          value={
            <Badge variant={workshop.status === 'active' ? 'success' : 'error'}>
              {workshop.status === 'active' ? 'Activo' : 'Deshabilitado'}
            </Badge>
          }
        />
      </div>
      {workshop.notes && <Field label="Notas" value={workshop.notes} />}
    </div>
  )
}
