import { useState } from 'react';
import { useAuth } from '../AuthContext';
import { useNavigate, Link } from 'react-router-dom';

export default function Login() {
  const [role, setRole] = useState('docente');
  const [correo, setCorreo] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    try {
      const data = await login(role, correo, password);
      if (data.role === 'jefe') navigate('/panel/jefe-carrera');
      else if (data.role === 'docente') navigate('/panel/docente');
      else navigate('/panel/estudiante');
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div className="shell">
      <aside className="sidebar">
        <div className="brand">
          <div className="brand-icon">R</div>
          <h1>Rosie</h1>
        </div>

        <div style={{ marginTop: '40px' }}>
          <div className="menu-label">Recents</div>
          <nav className="menu">
            <Link to="/login" className="active">Login</Link>
            <Link to="#">Examen</Link>
            <Link to="#">Docente</Link>
            <Link to="#">Estudiante</Link>
          </nav>
        </div>
      </aside>

      <main className="content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <section className="card login-card" style={{ animation: 'fadeIn 0.8s ease-out' }}>
          <div style={{ textAlign: 'center', marginBottom: '32px' }}>
            <h1 style={{ fontSize: '32px', margin: '0 0 8px', fontWeight: 800 }}>Acceso al portal</h1>
            <p className="muted" style={{ fontSize: '14px' }}>
              Ingresa con tu perfil para abrir el workspace.
            </p>
          </div>

          {error && (
            <div style={{ 
              background: 'rgba(239, 68, 68, 0.1)', 
              border: '1px solid rgba(239, 68, 68, 0.2)', 
              color: '#f87171', 
              padding: '12px', 
              borderRadius: '12px', 
              fontSize: '13px',
              marginBottom: '20px',
              textAlign: 'center'
            }}>
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} style={{ display: 'grid', gap: '24px' }}>
            <div className="field">
              <label htmlFor="role">Perfil</label>
              <select id="role" value={role} onChange={(e) => setRole(e.target.value)} required>
                <option value="estudiante">Estudiante</option>
                <option value="docente">Docente</option>
                <option value="jefe">Jefe de Carrera</option>
              </select>
            </div>

            <div className="field">
              <label htmlFor="correo">Correo</label>
              <input
                id="correo"
                type="email"
                value={correo}
                onChange={(e) => setCorreo(e.target.value)}
                placeholder="ejemplo@goodorder.edu.bo"
                required
              />
            </div>

            <div className="field">
              <label htmlFor="password">Contraseña</label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••"
                required
              />
            </div>

            <div style={{ marginTop: '8px' }}>
              <button className="btn btn-primary" type="submit" style={{ width: '100%', height: '52px', fontSize: '15px' }}>
                Entrar
              </button>
              
              <div style={{ textAlign: 'center', marginTop: '16px' }}>
                <p className="muted" style={{ fontSize: '11px', fontWeight: 600 }}>
                  Password default: UPB123
                </p>
              </div>
            </div>
          </form>
        </section>
      </main>
    </div>
  );
}
