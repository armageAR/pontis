import { useEffect, useState, type FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import Alert from '@/components/Alert'
import Spinner from '@/components/Spinner'
import Modal from '@/components/Modal'
import PrivacyIndicator from '@/components/PrivacyIndicator'
import Tabs, { type TabItem } from '@/components/Tabs'
import { useAuth } from '@/context/AuthContext'
import * as profileApi from '@/api/profile'
import type { Profile, UserDegree, UserPosition, ContactConsentSettings, ContactSource } from '@/api/profile'
import * as provinceApi from '@/api/provinces'
import * as visibilityApi from '@/api/visibility'
import type { VisibilityLevel, VisibilityBlock, VisibilityMap, VisibilitySetting } from '@/api/visibility'
import client from '@/api/client'
import * as workshopsApi from '@/api/workshops'
import { type ProfileWorkshop } from '@/api/workshops'
import { useProfileAutosave } from './useProfileAutosave'
import IdentidadTab from './IdentidadTab'
import ContactoTab from './ContactoTab'
import PerfilProfesionalTab from './PerfilProfesionalTab'
import UbicacionTab from './UbicacionTab'
import VidaMasonicaTab from './VidaMasonicaTab'
import PrivacidadTab from './PrivacidadTab'
import './ProfilePage.css'

interface PositionCatalogItem { id: number; name: string }
interface WorkshopItem { id: number; name: string; number: number }

// Opciones de audiencia por sección (quiénes pueden verla).
const PRIVACY_OPTIONS = Object.entries(visibilityApi.VISIBILITY_LABELS) as [VisibilityLevel, string][]

const TABS: TabItem[] = [
  { key: 'identidad', label: 'Identidad' },
  { key: 'contacto', label: 'Contacto' },
  { key: 'profesional', label: 'Perfil profesional' },
  { key: 'ubicacion', label: 'Ubicación' },
  { key: 'masonica', label: 'Vida masónica' },
  { key: 'privacidad', label: 'Privacidad' },
]

export default function ProfilePage() {
  const { user: authUser } = useAuth()
  const isSuperAdmin = authUser?.role === 'superadmin'
  const [searchParams] = useSearchParams()
  const [activeTab, setActiveTab] = useState<string>(() => {
    const tab = searchParams.get('tab')
    return TABS.some(t => t.key === tab) ? (tab as string) : 'identidad'
  })

  const [profile, setProfile] = useState<Profile | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const [degrees, setDegrees] = useState<UserDegree[]>([])
  const [positions, setPositions] = useState<UserPosition[]>([])
  const [positionCatalog, setPositionCatalog] = useState<PositionCatalogItem[]>([])
  const [myWorkshops, setMyWorkshops] = useState<WorkshopItem[]>([])
  const [provinces, setProvinces] = useState<provinceApi.Province[]>([])
  const [localities, setLocalities] = useState<{ id: number; name: string }[]>([])
  const [visibility, setVisibility] = useState<VisibilityMap>({})
  const [savingVisibility, setSavingVisibility] = useState(false)
  const [contactConsent, setContactConsent] = useState<ContactConsentSettings | null>(null)
  const [savingContactConsent, setSavingContactConsent] = useState(false)
  const [profileWorkshops, setProfileWorkshops] = useState<ProfileWorkshop[]>([])

  const [showEmailModal, setShowEmailModal] = useState(false)
  const [newEmail, setNewEmail] = useState('')
  const [emailSubmitting, setEmailSubmitting] = useState(false)
  const [emailModalError, setEmailModalError] = useState('')
  const [emailModalSuccess, setEmailModalSuccess] = useState('')

  const { statuses, commitField } = useProfileAutosave(profile, setProfile)

  useEffect(() => {
    Promise.all([
      profileApi.getProfile(),
      profileApi.getDegrees(),
      profileApi.getPositions(),
      client.get<PositionCatalogItem[]>('/positions').then(r => r.data),
      client.get('/my-workshops').then(r => r.data),
      provinceApi.getProvinces(),
      visibilityApi.getVisibility(),
      workshopsApi.getProfileWorkshops(),
      profileApi.getContactConsent(),
    ]).then(([p, d, pos, catalog, ws, prov, vis, pws, consent]) => {
      setProfile(p)
      setDegrees(d)
      setPositions(pos)
      setPositionCatalog(catalog)
      const wsArr = Array.isArray(ws) ? ws : (ws.data ?? [])
      setMyWorkshops(wsArr)
      setProvinces(prov)
      setVisibility(vis)
      setProfileWorkshops(pws)
      setContactConsent(consent)
    }).catch(() => setError('Error cargando el perfil.')).finally(() => setLoading(false))
  }, [])

  // Load localities when province changes
  useEffect(() => {
    const province = provinces.find(p => p.name === profile?.province)
    if (!province) { setLocalities([]); return }
    client.get<{ id: number; name: string }[]>(`/localities?province_id=${province.id}`)
      .then(r => setLocalities(r.data))
      .catch(() => setLocalities([]))
  }, [profile?.province, provinces])

  // Provincia: se confirma en el cambio y se limpia la localidad (que depende de
  // la provincia) para no persistir una localidad obsoleta.
  function handleProvinceChange(value: string) {
    const hadLocality = !!profile?.locality
    setProfile(p => (p ? { ...p, province: value, locality: null } : p))
    commitField('province', value)
    // La localidad depende de la provincia: si había una, se limpia también en
    // backend para no persistir una localidad ajena a la nueva provincia.
    if (hadLocality) commitField('locality', null)
  }

  function openEmailModal() {
    setNewEmail('')
    setEmailModalError('')
    setEmailModalSuccess('')
    setShowEmailModal(true)
  }

  async function handleRequestEmailChange(e: FormEvent) {
    e.preventDefault()
    setEmailSubmitting(true); setEmailModalError(''); setEmailModalSuccess('')
    try {
      const res = await profileApi.requestEmailChange(newEmail.trim())
      setEmailModalSuccess(res.message)
      setProfile(p => p ? { ...p, pending_email: res.pending_email } : p)
    } catch (err) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message
        ?? 'No se pudo solicitar el cambio de email.'
      setEmailModalError(msg)
    } finally {
      setEmailSubmitting(false)
    }
  }

  async function handleVisibilityChange(block: VisibilityBlock, level: VisibilityLevel) {
    const prev = visibility
    setVisibility(v => ({ ...v, [block]: { ...(v[block] as object), block, visibility: level } }))
    setSavingVisibility(true); setError(''); setSuccess('')
    try {
      const updated = await visibilityApi.updateVisibility([{ block, visibility: level }])
      setVisibility(updated)
      setSuccess('Privacidad actualizada.')
    } catch {
      setVisibility(prev)
      setError('No se pudo actualizar la privacidad.')
    }
    finally { setSavingVisibility(false) }
  }

  // Identidad: guarda audiencia y aparición anónima como decisiones independientes.
  // Se envían ambos valores (el cambiado y el actual) para preservar el que no se toca.
  async function handleIdentityChange(patch: { visibility?: VisibilityLevel; anonymous_search?: boolean }) {
    const prev = visibility
    const next = {
      block: 'identity' as const,
      visibility: patch.visibility ?? visibility.identity?.visibility ?? 'workshop',
      anonymous_search: patch.anonymous_search ?? visibility.identity?.anonymous_search ?? false,
    }
    setVisibility(v => ({ ...v, identity: { ...(v.identity as VisibilitySetting), ...next } }))
    setSavingVisibility(true); setError(''); setSuccess('')
    try {
      const updated = await visibilityApi.updateVisibility([next])
      setVisibility(updated)
      setSuccess('Privacidad actualizada.')
    } catch {
      setVisibility(prev)
      setError('No se pudo actualizar la privacidad.')
    }
    finally { setSavingVisibility(false) }
  }

  // El backend exige los tres arrays; mantenemos el objeto completo y enviamos
  // siempre los tres, cambiando sólo las fuentes permitidas.
  async function toggleContactSource(value: ContactSource) {
    if (!contactConsent) return
    const currentSources = contactConsent.allowed_sources
    const nextSources = currentSources.includes(value) ? currentSources.filter(v => v !== value) : [...currentSources, value]
    const next: ContactConsentSettings = { ...contactConsent, allowed_sources: nextSources }
    setContactConsent(next)
    setSavingContactConsent(true); setError(''); setSuccess('')
    try {
      const updated = await profileApi.updateContactConsent(next)
      setContactConsent(updated)
      setSuccess('Preferencias de contacto actualizadas.')
    } catch {
      setContactConsent(contactConsent)
      setError('No se pudieron actualizar las preferencias de contacto.')
    } finally { setSavingContactConsent(false) }
  }

  async function reloadProfileWorkshops() {
    const [pws, ws] = await Promise.all([
      workshopsApi.getProfileWorkshops(),
      client.get('/my-workshops').then(r => r.data),
    ])
    setProfileWorkshops(pws)
    setMyWorkshops(Array.isArray(ws) ? ws : (ws.data ?? []))
  }

  function renderSectionPrivacy(block: VisibilityBlock) {
    const value: VisibilityLevel = visibility[block]?.visibility ?? 'workshop'
    return (
      <div className="profile-privacy-box">
        <label className="profile-privacy-label">¿Quiénes pueden ver esta sección?</label>
        <select
          className="profile-select profile-select-sm"
          value={value}
          disabled={savingVisibility}
          onChange={e => handleVisibilityChange(block, e.target.value as VisibilityLevel)}
        >
          {PRIVACY_OPTIONS.map(([level, label]) => <option key={level} value={level}>{label}</option>)}
        </select>
        <PrivacyIndicator
          summary={`Esta sección será visible para: ${visibilityApi.VISIBILITY_LABELS[value]}.`}
          details={[
            'El cambio se aplica al guardar la selección.',
            'No cambia tus preferencias para solicitudes de contacto.',
            'La decisión queda asociada a tu cuenta para controles internos.',
          ]}
        />
      </div>
    )
  }

  function renderIdentityPrivacy() {
    const audience: VisibilityLevel = visibility.identity?.visibility ?? 'workshop'
    const anonSearch = visibility.identity?.anonymous_search ?? false
    return (
      <div className="profile-privacy-box profile-privacy-box-col">
        <div className="profile-privacy-row">
          <label className="profile-privacy-label">¿Quiénes pueden ver mi identidad?</label>
          <select
            className="profile-select profile-select-sm"
            value={audience}
            disabled={savingVisibility}
            onChange={e => handleIdentityChange({ visibility: e.target.value as VisibilityLevel })}
          >
            {PRIVACY_OPTIONS.map(([level, label]) => <option key={level} value={level}>{label}</option>)}
          </select>
        </div>
        <label className="profile-anon-check">
          <input
            type="checkbox"
            checked={anonSearch}
            disabled={savingVisibility}
            onChange={e => handleIdentityChange({ anonymous_search: e.target.checked })}
          />
          <span>Aparecer en las búsquedas sin revelar mi identidad</span>
        </label>
        <p className="profile-anon-note">
          Quienes formen parte de la audiencia elegida ven tu identidad. Si activás esta opción,
          el resto de los Hermanos habilitados pueden encontrarte como <strong>Hermano registrado</strong>,
          sin ver tu nombre ni tus datos de identidad. Ambas opciones se combinan: cambiar la audiencia
          no desactiva la aparición anónima.
        </p>
        <PrivacyIndicator
          summary={anonSearch ? 'Podés aparecer en búsquedas con identidad reservada.' : `Tu identidad queda limitada a: ${visibilityApi.VISIBILITY_LABELS[audience]}.`}
          details={[
            `Audiencia con identidad visible: ${visibilityApi.VISIBILITY_LABELS[audience]}.`,
            anonSearch ? 'Fuera de esa audiencia pueden encontrarte como Hermano registrado.' : 'Fuera de esa audiencia no se habilita aparición anónima por este control.',
            'La decisión queda asociada a tu cuenta para controles internos.',
          ]}
        />
      </div>
    )
  }

  if (loading) return <AppLayout><div className="profile-loading"><Spinner /></div></AppLayout>
  if (!profile) return <AppLayout><div className="profile-page"><Alert>{error || 'Error cargando el perfil.'}</Alert></div></AppLayout>

  const wide = activeTab === 'masonica'

  return (
    <AppLayout>
      <div className="profile-page">
        <h1 className="profile-title">Mi Perfil</h1>
        <p className="profile-page-desc">
          Gestioná tu información personal, de contacto y masónica. Los cambios se guardan automáticamente.
        </p>

        {error && <Alert>{error}</Alert>}
        {success && <Alert variant="success">{success}</Alert>}

        <Tabs tabs={TABS} active={activeTab} onChange={setActiveTab} />

        <div
          role="tabpanel"
          id={`panel-${activeTab}`}
          aria-labelledby={`tab-${activeTab}`}
          tabIndex={0}
          className={`profile-panel${wide ? ' profile-panel-wide' : ''}`}
        >
          {activeTab === 'identidad' && (
            <IdentidadTab
              profile={profile}
              isSuperAdmin={isSuperAdmin}
              statuses={statuses}
              commitField={commitField}
              onOpenEmailModal={openEmailModal}
              identityPrivacy={renderIdentityPrivacy}
            />
          )}
          {activeTab === 'contacto' && (
            <ContactoTab
              profile={profile}
              statuses={statuses}
              commitField={commitField}
              sectionPrivacy={renderSectionPrivacy}
              contactConsent={contactConsent}
              savingContactConsent={savingContactConsent}
              toggleContactSource={toggleContactSource}
            />
          )}
          {activeTab === 'profesional' && (
            <PerfilProfesionalTab
              profile={profile}
              statuses={statuses}
              commitField={commitField}
              sectionPrivacy={renderSectionPrivacy}
            />
          )}
          {activeTab === 'ubicacion' && (
            <UbicacionTab
              profile={profile}
              statuses={statuses}
              commitField={commitField}
              provinces={provinces}
              localities={localities}
              onProvinceChange={handleProvinceChange}
              sectionPrivacy={renderSectionPrivacy}
            />
          )}
          {activeTab === 'masonica' && (
            <VidaMasonicaTab
              profile={profile}
              isSuperAdmin={isSuperAdmin}
              statuses={statuses}
              commitField={commitField}
              sectionPrivacy={renderSectionPrivacy}
              degrees={degrees}
              setDegrees={setDegrees}
              positions={positions}
              setPositions={setPositions}
              positionCatalog={positionCatalog}
              myWorkshops={myWorkshops}
              profileWorkshops={profileWorkshops}
              reloadProfileWorkshops={reloadProfileWorkshops}
              onError={setError}
              onSuccess={setSuccess}
            />
          )}
          {activeTab === 'privacidad' && (
            <PrivacidadTab visibility={visibility} onEdit={setActiveTab} />
          )}
        </div>
      </div>

      <Modal open={showEmailModal} onClose={() => setShowEmailModal(false)} title="Cambiar email">
        {emailModalSuccess ? (
          <div className="profile-email-modal">
            <Alert variant="success">{emailModalSuccess}</Alert>
            <p className="profile-email-modal-note">
              Tu email actual seguirá vigente hasta que confirmes el nuevo desde el enlace que te enviamos.
            </p>
            <div className="profile-email-modal-actions">
              <Button type="button" onClick={() => setShowEmailModal(false)}>Entendido</Button>
            </div>
          </div>
        ) : (
          <form onSubmit={handleRequestEmailChange} className="profile-email-modal">
            <p className="profile-email-modal-note">
              Por seguridad, el cambio no es inmediato. Enviaremos un correo a la nueva dirección
              y el email recién se actualizará cuando confirmes desde ese enlace.
            </p>
            <FormField label="Nuevo email">
              <Input
                type="email"
                required
                value={newEmail}
                onChange={e => setNewEmail(e.target.value)}
                placeholder="nuevo@email.com"
              />
            </FormField>
            {emailModalError && <Alert variant="error">{emailModalError}</Alert>}
            <div className="profile-email-modal-actions">
              <Button type="button" variant="outline" onClick={() => setShowEmailModal(false)}>Cancelar</Button>
              <Button type="submit" loading={emailSubmitting}>Enviar confirmación</Button>
            </div>
          </form>
        )}
      </Modal>
    </AppLayout>
  )
}
