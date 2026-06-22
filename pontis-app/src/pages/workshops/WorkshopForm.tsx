import { type FormEvent, useState, useEffect } from 'react'
import type { Workshop, WorkshopFormData } from '@/api/workshops'
import Input from '@/components/Input'
import Select from '@/components/Select'
import FormField from '@/components/FormField'
import Button from '@/components/Button'
import Alert from '@/components/Alert'
import type { AxiosError } from 'axios'
import './WorkshopForm.css'

const WORK_DAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']

interface WorkshopFormProps {
  workshop?: Workshop | null
  onSubmit: (data: WorkshopFormData) => Promise<void>
  onCancel: () => void
  submitLabel: string
}

type FieldErrors = Record<string, string[]>

export default function WorkshopForm({ workshop, onSubmit, onCancel, submitLabel }: WorkshopFormProps) {
  const [form, setForm] = useState<WorkshopFormData>({
    name: '',
    number: 0,
    zone_number: null,
    zone_name: null,
    work_day: null,
    work_frequency: null,
    address: null,
    city: null,
    province: null,
    country: 'Argentina',
    language: null,
    status: 'active',
    notes: null,
  })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})

  useEffect(() => {
    if (workshop) {
      setForm({
        name: workshop.name,
        number: workshop.number,
        zone_number: workshop.zone_number,
        zone_name: workshop.zone_name,
        work_day: workshop.work_day,
        work_frequency: workshop.work_frequency,
        address: workshop.address,
        city: workshop.city,
        province: workshop.province,
        country: workshop.country,
        language: workshop.language,
        status: workshop.status,
        notes: workshop.notes,
      })
    }
  }, [workshop])

  function set<K extends keyof WorkshopFormData>(key: K, value: WorkshopFormData[K]) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setFieldErrors({})
    setLoading(true)

    try {
      await onSubmit(form)
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string; errors?: FieldErrors }>
      if (axiosErr.response?.status === 422) {
        setFieldErrors(axiosErr.response.data.errors ?? {})
        setError(axiosErr.response.data.message ?? 'Datos inválidos.')
      } else {
        setError('Ocurrió un error. Intentá de nuevo.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="workshop-form" onSubmit={handleSubmit}>
      {error && <Alert>{error}</Alert>}

      <div className="workshop-form-row">
        <FormField label="Nombre *" error={fieldErrors.name?.[0]}>
          <Input
            value={form.name}
            onChange={(e) => set('name', e.target.value)}
            error={!!fieldErrors.name}
            placeholder="UNION DEL PLATA"
            required
          />
        </FormField>
        <FormField label="Número *" error={fieldErrors.number?.[0]}>
          <Input
            type="number"
            value={form.number || ''}
            onChange={(e) => set('number', Number(e.target.value))}
            error={!!fieldErrors.number}
            placeholder="1"
            required
            min={1}
          />
        </FormField>
      </div>

      <div className="workshop-form-row">
        <FormField label="Nro. de zona" error={fieldErrors.zone_number?.[0]}>
          <Input
            type="number"
            value={form.zone_number ?? ''}
            onChange={(e) => set('zone_number', e.target.value ? Number(e.target.value) : null)}
            placeholder="1"
            min={1}
          />
        </FormField>
        <FormField label="Nombre de zona" error={fieldErrors.zone_name?.[0]}>
          <Input
            value={form.zone_name ?? ''}
            onChange={(e) => set('zone_name', e.target.value || null)}
            placeholder="Logias de CABA"
          />
        </FormField>
      </div>

      <div className="workshop-form-row">
        <FormField label="Día de trabajo" error={fieldErrors.work_day?.[0]}>
          <Select
            value={form.work_day ?? ''}
            onChange={(e) => set('work_day', e.target.value || null)}
          >
            <option value="">Sin especificar</option>
            {WORK_DAYS.map((d) => (
              <option key={d} value={d}>{d}</option>
            ))}
          </Select>
        </FormField>
        <FormField label="Frecuencia" error={fieldErrors.work_frequency?.[0]}>
          <Input
            value={form.work_frequency ?? ''}
            onChange={(e) => set('work_frequency', e.target.value || null)}
            placeholder="1ro 3ro 5to"
          />
        </FormField>
      </div>

      <FormField label="Dirección" error={fieldErrors.address?.[0]}>
        <Input
          value={form.address ?? ''}
          onChange={(e) => set('address', e.target.value || null)}
          placeholder="TTE. GRAL. J. D. PERON 1242"
        />
      </FormField>

      <div className="workshop-form-row workshop-form-row-3">
        <FormField label="Ciudad" error={fieldErrors.city?.[0]}>
          <Input
            value={form.city ?? ''}
            onChange={(e) => set('city', e.target.value || null)}
            placeholder="CABA"
          />
        </FormField>
        <FormField label="Provincia" error={fieldErrors.province?.[0]}>
          <Input
            value={form.province ?? ''}
            onChange={(e) => set('province', e.target.value || null)}
            placeholder="Buenos Aires"
          />
        </FormField>
        <FormField label="País" error={fieldErrors.country?.[0]}>
          <Input
            value={form.country ?? ''}
            onChange={(e) => set('country', e.target.value || null)}
            placeholder="Argentina"
          />
        </FormField>
      </div>

      <div className="workshop-form-row">
        <FormField label="Idioma" error={fieldErrors.language?.[0]}>
          <Input
            value={form.language ?? ''}
            onChange={(e) => set('language', e.target.value || null)}
            placeholder="alemán"
          />
        </FormField>
        <FormField label="Estado" error={fieldErrors.status?.[0]}>
          <Select
            value={form.status ?? 'active'}
            onChange={(e) => set('status', e.target.value)}
          >
            <option value="active">Activo</option>
            <option value="disabled">Deshabilitado</option>
          </Select>
        </FormField>
      </div>

      <FormField label="Notas" error={fieldErrors.notes?.[0]}>
        <textarea
          className="input workshop-form-textarea"
          value={form.notes ?? ''}
          onChange={(e) => set('notes', e.target.value || null)}
          placeholder="Observaciones adicionales..."
          rows={3}
        />
      </FormField>

      <div className="workshop-form-actions">
        <Button variant="outline" type="button" onClick={onCancel} disabled={loading}>
          Cancelar
        </Button>
        <Button type="submit" loading={loading}>
          {submitLabel}
        </Button>
      </div>
    </form>
  )
}
