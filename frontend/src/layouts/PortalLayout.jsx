import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../AuthContext';

export default function PortalLayout({ roleTitle, breadcrumbs, roleChip, pageTitle, pageSubtitle, Sidebar, children }) {
  const { logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async (e) => {
    e.preventDefault();
    await logout();
    navigate('/login');
  };

  return (
    <div className="shell">
      <aside className="sidebar">
        <div className="brand">
          <h1>ROSIE</h1>
          <small>Scheduling AI</small>
        </div>

        <div>
          <div className="menu-title">Navegacion</div>
          <nav className="menu">
            {Sidebar && <Sidebar />}
          </nav>
        </div>
      </aside>

      <main className="content">
        <header className="topbar">
          <div className="breadcrumbs">{breadcrumbs || 'Portal / Dashboard'}</div>
          <div className="top-actions">
            {roleChip && <span className="chip">{roleChip}</span>}
            <button onClick={handleLogout} className="btn btn-danger">Cerrar sesion</button>
          </div>
        </header>

        <div className="page-wrap">
          <section className="page">
            <div className="page-header">
              <h1>{pageTitle || 'Portal'}</h1>
              <p>{pageSubtitle}</p>
            </div>

            <div style={{ marginTop: '18px' }}>
              {children}
            </div>
          </section>
        </div>
      </main>
    </div>
  );
}
