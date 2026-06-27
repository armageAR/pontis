import { useEffect, useRef, useState } from 'react'
import { Bell } from 'lucide-react'
import * as api from '@/api/notifications'
import type { Notification } from '@/api/notifications'
import './NotificationBell.css'

const TYPE_ICONS: Record<string, string> = {
  join_request: '🔔', join_approved: '✅', join_rejected: '❌',
  contact_received: '💬', contact_accepted: '✅', contact_rejected: '❌',
  change_request: '📝', change_approved: '✅', change_rejected: '❌',
}

export default function NotificationBell() {
  const [open, setOpen] = useState(false)
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [unread, setUnread] = useState(0)
  const ref = useRef<HTMLDivElement>(null)

  async function load() {
    try {
      const r = await api.getNotifications()
      setNotifications(r.notifications)
      setUnread(r.unread)
    } catch { /* silent */ }
  }

  useEffect(() => {
    load()
    const interval = setInterval(load, 60000) // poll every minute
    return () => clearInterval(interval)
  }, [])

  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', handleClick)
    return () => document.removeEventListener('mousedown', handleClick)
  }, [])

  async function handleOpen() {
    setOpen(o => !o)
    if (!open && unread > 0) {
      await api.markAllRead()
      setUnread(0)
      setNotifications(ns => ns.map(n => ({ ...n, read_at: n.read_at ?? new Date().toISOString() })))
    }
  }

  return (
    <div className="notif-bell" ref={ref}>
      <button className="notif-bell-btn" onClick={handleOpen} aria-label="Notificaciones">
        <Bell size={18} />
        {unread > 0 && <span className="notif-badge">{unread > 9 ? '9+' : unread}</span>}
      </button>
      {open && (
        <div className="notif-dropdown">
          <div className="notif-dropdown-header">
            <span>Notificaciones</span>
          </div>
          {notifications.length === 0 ? (
            <div className="notif-empty">Sin notificaciones</div>
          ) : (
            <div className="notif-list">
              {notifications.map(n => (
                <div key={n.id} className={`notif-item ${n.read_at ? 'notif-read' : 'notif-unread'}`}>
                  <span className="notif-icon">{TYPE_ICONS[n.type] ?? '🔔'}</span>
                  <div>
                    <div className="notif-title">{n.title}</div>
                    {n.body && <div className="notif-body">{n.body}</div>}
                    <div className="notif-time">{new Date(n.created_at).toLocaleDateString('es-AR')}</div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  )
}
