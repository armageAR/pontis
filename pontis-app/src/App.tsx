import './App.css'

function App() {
  return (
    <div className="layout">
      <nav className="nav">
        <span className="nav-logo">Pontis</span>
        <button className="btn btn-outline">Ingresar</button>
      </nav>

      <main>
        <section className="hero">
          <div className="hero-badge">En desarrollo</div>
          <h1 className="hero-title">
            La plataforma que<br />
            <span className="accent">conecta todo</span>
          </h1>
          <p className="hero-sub">
            Gestioná operaciones, usuarios y datos desde un solo lugar.
            Simple, rápido y escalable.
          </p>
          <div className="hero-actions">
            <button className="btn btn-primary">Comenzar</button>
            <button className="btn btn-ghost">Ver más</button>
          </div>
        </section>

        <section className="features">
          <div className="feature-card">
            <div className="feature-icon">⬡</div>
            <h3>Gestión centralizada</h3>
            <p>Todos los módulos del sistema accesibles desde un panel unificado.</p>
          </div>
          <div className="feature-card">
            <div className="feature-icon">⟳</div>
            <h3>Tiempo real</h3>
            <p>Datos actualizados al instante para tomar mejores decisiones.</p>
          </div>
          <div className="feature-card">
            <div className="feature-icon">⬤</div>
            <h3>Seguro y confiable</h3>
            <p>Autenticación robusta y trazabilidad completa de cada acción.</p>
          </div>
        </section>
      </main>

      <footer className="footer">
        <span>Pontis &copy; {new Date().getFullYear()}</span>
      </footer>
    </div>
  )
}

export default App
