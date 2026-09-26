import { type FormEvent, useState } from 'react'
import { useAuth } from '@/context/useAuth'
import type { WorkshopSearchResult } from '@/api/workshops'
import Button from '@/components/Button'
import Input from '@/components/Input'
import FormField from '@/components/FormField'
import Alert from '@/components/Alert'
import WorkshopPicker from '@/components/WorkshopPicker'
import type { AxiosError } from 'axios'
import './RegisterForm.css'

interface FieldErrors {
  name?: string[]
  email?: string[]
  password?: string[]
  workshop_id?: string[]
}

export default function RegisterForm() {
  const { register } = useAuth()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [workshop, setWorkshop] = useState<WorkshopSearchResult | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setFieldErrors({})

    if (!workshop) {
      setFieldErrors({ workshop_id: ['Seleccioná un taller.'] })
      return
    }

    setLoading(true)

    try {
      await register(name, email, password, passwordConfirmation, workshop.id)
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string; errors?: FieldErrors }>
      if (axiosErr.response?.status === 422) {
        setFieldErrors(axiosErr.response.data.errors ?? {})
        setError(axiosErr.response.data.message ?? 'Datos inválidos.')
      } else {
        setError('Error al crear la cuenta. Intentá de nuevo.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="register-form" onSubmit={handleSubmit}>
      {error && <Alert>{error}</Alert>}

      <FormField label="Nombre" error={fieldErrors.name?.[0]}>
        <Input
          type="text"
          placeholder="Tu nombre completo"
          value={name}
          onChange={(e) => setName(e.target.value)}
          error={!!fieldErrors.name}
          required
          autoComplete="name"
          autoFocus
        />
      </FormField>

      <FormField label="Email" error={fieldErrors.email?.[0]}>
        <Input
          type="email"
          placeholder="tu@email.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          error={!!fieldErrors.email}
          required
          autoComplete="email"
        />
      </FormField>

      <FormField label="Contraseña" error={fieldErrors.password?.[0]}>
        <Input
          type="password"
          placeholder="Mínimo 8 caracteres"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          error={!!fieldErrors.password}
          required
          autoComplete="new-password"
        />
      </FormField>

      <FormField label="Confirmar contraseña">
        <Input
          type="password"
          placeholder="Repetí la contraseña"
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
          required
          autoComplete="new-password"
        />
      </FormField>

      <FormField label="Taller al que pertenecés" error={fieldErrors.workshop_id?.[0]}>
        <WorkshopPicker
          value={workshop}
          onChange={setWorkshop}
          error={!!fieldErrors.workshop_id}
        />
      </FormField>

      <Button type="submit" loading={loading}>
        Crear cuenta
      </Button>
    </form>
  )
}
