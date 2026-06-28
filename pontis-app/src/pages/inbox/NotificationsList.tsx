import { useEffect, useState } from 'react'
import Spinner from '@/components/Spinner'
import EmptyState from '@/components/EmptyState'
import Button from '@/components/Button'
import Alert from '@/components/Alert'
import * as api from '@/api/notifications'
import type { Notification } from '@/api/notifications'
import './NotificationsList.css'

export default function NotificationsList() {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [marking, setMarking] = useState(false)

  async function load() {
    setLoading(true)
    try {
      const r = await api.getNotifications()
      setNotifications(r.notifications)
    } catch {
      setError('Error cargando notificaciones.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  async function handleMarkAllRead() {
    setMarking(true)
    try {
      await api.markAllRead()
      await load()
    } catch {
      setError('Error al marcar como leídas.')
    } finally {
      setMarking(false)
    }
  }

  async function handleMarkOneRead(n: Notification) {
    if (n.read_at) return
    try {
      await api.markOneRead(n.id)
      setNotifications(prev =>
        prev.map(x => x.id === n.id ? { ...x, read_at: new Date().toISOString() } : x)
      )
    } catch {
      // silently ignore single-mark errors
    }
  }

  const hasUnread = notifications.some(n => !n.read_at)

  return (
    <div className="notif-root">
      {error && <Alert>{error}</Alert>}
      {!loading && notifications.length > 0 && (
        <div className="notif-toolbar">
          {hasUnread && (
            <Button variant="outline" onClick={handleMarkAllRead} loading={marking}>
              Marcar todas como leídas
            </Button>
          )}
        </div>
      )}
      {loading ? (
        <div className="notif-loading"><Spinner /></div>
      ) : notifications.length === 0 ? (
        <EmptyState title="Sin notificaciones" description="No tenés notificaciones." />
      ) : (
        <div className="notif-list">
          {notifications.map(n => (
            <div
              key={n.id}
              className={`notif-item${n.read_at ? '' : ' notif-item-unread'}`}
              onClick={() => handleMarkOneRead(n)}
            >
              <div className="notif-item-header">
                <span className="notif-title">{n.title}</span>
                <span className="notif-date">
                  {new Date(n.created_at).toLocaleDateString('es-AR')}
                </span>
              </div>
              {n.body && <p className="notif-body">{n.body}</p>}
              {!n.read_at && <span className="notif-unread-dot" aria-label="No leída" />}
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
