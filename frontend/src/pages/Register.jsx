import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import axios from 'axios';

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 10 }, (_, i) => currentYear - i);

export default function Register() {
  const [formData, setFormData] = useState({
    nombre: '',
    apellido: '',
    correo: '',
    password: '',
    password_confirmation: '',
    cohorte_ingreso: '',
    es_traspaso: false,
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    if (formData.password !== formData.password_confirmation) {
      setError('Las contraseñas no coinciden.');
      setLoading(false);
      return;
    }

    try {
      await axios.post('/portal/api/register', {
        nombre: formData.nombre,
        apellido: formData.apellido,
        correo: formData.correo,
        password: formData.password,
        password_confirmation: formData.password_confirmation,
        cohorte_ingreso: formData.cohorte_ingreso || null,
        es_traspaso: formData.es_traspaso,
      });
      setSuccess(true);
      setTimeout(() => navigate('/login'), 2000);
    } catch (err) {
      setError(err.response?.data?.message || 'Error al registrarse. Intenta de nuevo.');
    } finally {
      setLoading(false);
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
          <div className="menu-label">Navegación</div>
          <nav className="menu">
            <Link to="/login">Login</Link>
            <Link to="/register" className="active">Registro</Link>
          </nav>
        </div>
      </aside>

      <main className="content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <section className="card login-card" style={{ animation: 'fadeIn 0.8s ease-out', maxWidth: '480px' }}>
          <div style={{ textAlign: 'center', marginBottom: '32px' }}>
            <h1 style={{ fontSize: '32px', margin: '0 0 8px', fontWeight: 800 }}>Crear cuenta</h1>
            <p className="muted" style={{ fontSize: '14px' }}>
              Únete a la comunidad académica de Rosie.
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

          {success && (
            <div style={{
              background: 'rgba(34, 197, 94, 0.1)',
              border: '1px solid rgba(34, 197, 94, 0.2)',
              color: '#4ade80',
              padding: '12px',
              borderRadius: '12px',
              fontSize: '13px',
              marginBottom: '20px',
              textAlign: 'center'
            }}>
              ¡Registro exitoso! Redirigiendo al login...
            </div>
          )}

          <form onSubmit={handleSubmit} style={{ display: 'grid', gap: '16px' }}>
            <div className="grid-2" style={{ gap: '16px' }}>
              <div className="field">
                <label htmlFor="nombre">Nombre</label>
                <input
                  id="nombre"
                  type="text"
                  value={formData.nombre}
                  onChange={(e) => setFormData({ ...formData, nombre: e.target.value })}
                  placeholder="Juan"
                  required
                />
              </div>
              <div className="field">
                <label htmlFor="apellido">Apellido</label>
                <input
                  id="apellido"
                  type="text"
                  value={formData.apellido}
                  onChange={(e) => setFormData({ ...formData, apellido: e.target.value })}
                  placeholder="Pérez"
                  required
                />
              </div>
            </div>

            <div className="field">
              <label htmlFor="correo">Correo electrónico</label>
              <input
                id="correo"
                type="email"
                value={formData.correo}
                onChange={(e) => setFormData({ ...formData, correo: e.target.value })}
                placeholder="juan.perez@goodorder.edu.bo"
                required
              />
            </div>

            <div className="grid-2" style={{ gap: '16px' }}>
              <div className="field">
                <label htmlFor="password">Contraseña</label>
                <input
                  id="password"
                  type="password"
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  placeholder="••••••"
                  required
                />
              </div>
              <div className="field">
                <label htmlFor="password_confirmation">Confirmar contraseña</label>
                <input
                  id="password_confirmation"
                  type="password"
                  value={formData.password_confirmation}
                  onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
                  placeholder="••••••"
                  required
                />
              </div>
            </div>

            <div className="grid-2" style={{ gap: '16px' }}>
              <div className="field">
                <label htmlFor="cohorte_ingreso">Año de ingreso a la Universidad</label>
                <select
                  id="cohorte_ingreso"
                  value={formData.cohorte_ingreso}
                  onChange={(e) => setFormData({ ...formData, cohorte_ingreso: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '10px 14px',
                    borderRadius: '10px',
                    border: '1px solid rgba(255,255,255,0.1)',
                    background: 'rgba(255,255,255,0.04)',
                    color: formData.cohorte_ingreso ? 'inherit' : '#94a3b8',
                    fontSize: '14px',
                    cursor: 'pointer',
                  }}
                >
                  <option value="">Selecciona un año</option>
                  {years.map(y => (
                    <option key={y} value={y}>{y}</option>
                  ))}
                </select>
              </div>

              <div className="field" style={{ display: 'flex', flexDirection: 'column', justifyContent: 'flex-end' }}>
                <label style={{ marginBottom: '10px' }}>¿Eres estudiante de traspaso?</label>
                <div style={{ display: 'flex', gap: '10px' }}>
                  {[{ label: 'No', value: false }, { label: 'Sí', value: true }].map(opt => {
                    const selected = formData.es_traspaso === opt.value;
                    return (
                      <button
                        key={String(opt.value)}
                        type="button"
                        onClick={() => setFormData({ ...formData, es_traspaso: opt.value })}
                        style={{
                          flex: 1,
                          padding: '10px',
                          borderRadius: '10px',
                          border: selected ? '2px solid var(--primary)' : '2px solid rgba(255,255,255,0.1)',
                          background: selected ? 'rgba(99,102,241,0.15)' : 'rgba(255,255,255,0.04)',
                          color: selected ? 'var(--primary, #818cf8)' : '#94a3b8',
                          fontSize: '14px',
                          fontWeight: selected ? 700 : 500,
                          cursor: 'pointer',
                          transition: 'all 0.15s',
                        }}
                      >
                        {opt.label}
                      </button>
                    );
                  })}
                </div>
              </div>
            </div>

            <div style={{ marginTop: '12px' }}>
              <button
                className="btn btn-primary"
                type="submit"
                disabled={loading || success}
                style={{ width: '100%', height: '52px', fontSize: '15px' }}
              >
                {loading ? 'Procesando...' : 'Registrarse como Estudiante'}
              </button>

              <div style={{ textAlign: 'center', marginTop: '20px' }}>
                <Link to="/login" className="muted" style={{ fontSize: '13px', textDecoration: 'none' }}>
                  ¿Ya tienes una cuenta? <span style={{ color: 'var(--primary)', fontWeight: 600 }}>Inicia sesión</span>
                </Link>
              </div>
            </div>
          </form>
        </section>
      </main>
    </div>
  );
}
