import { useState } from 'react';
import { useAuth } from '../AuthContext';
import { useNavigate } from 'react-router-dom';

export default function Login() {
  const [role, setRole] = useState('estudiante');
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
    <div style={{ 
        minHeight: '100vh', 
        display: 'flex', 
        alignItems: 'center', 
        justifyContent: 'center',
        background: 'var(--bg-deep)',
        backgroundImage: 'var(--grad-glow)'
    }}>
      <section className="card login-card" style={{ animation: 'fadeIn 0.8s ease-out' }}>
        <div style={{ textAlign: 'center', marginBottom: '32px' }}>
            <h1 style={{ 
                fontSize: '48px', 
                background: 'var(--grad-rosie)', 
                WebkitBackgroundClip: 'text', 
                WebkitTextFillColor: 'transparent',
                margin: 0,
                fontWeight: 900
            }}>ROSIE</h1>
            <p className="muted" style={{ letterSpacing: '0.1em', textTransform: 'uppercase', fontSize: '12px', fontWeight: 700 }}>
                Academic Management Portal
            </p>
        </div>

        {error && (
            <div style={{ 
                background: 'rgba(239, 68, 68, 0.1)', 
                border: '1px solid rgba(239, 68, 68, 0.2)', 
                color: '#f87171', 
                padding: '12px', 
                borderRadius: '12px', 
                fontSize: '14px',
                marginBottom: '20px',
                textAlign: 'center'
            }}>
                {error}
            </div>
        )}

        <form onSubmit={handleSubmit} style={{ display: 'grid', gap: '20px' }}>
          <div className="field">
            <label htmlFor="role">Selecciona tu Perfil</label>
            <select id="role" value={role} onChange={(e) => setRole(e.target.value)} required>
              <option value="estudiante">Estudiante</option>
              <option value="docente">Docente</option>
              <option value="jefe">Jefe de Carrera</option>
            </select>
          </div>

          <div className="field">
            <label htmlFor="correo">Correo Institucional</label>
            <input
              id="correo"
              type="email"
              value={correo}
              onChange={(e) => setCorreo(e.target.value)}
              placeholder="ejemplo@u.edu"
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
              placeholder="••••••••"
              required
            />
          </div>

          <button className="btn btn-primary" type="submit" style={{ marginTop: '10px', height: '50px' }}>
            Iniciar Sesión
          </button>
          
          <div style={{ textAlign: 'center', marginTop: '16px' }}>
            <p className="muted" style={{ fontSize: '11px' }}>
                ¿Problemas para entrar? Contacta a soporte técnico.
            </p>
          </div>
        </form>
      </section>
    </div>
  );
}
