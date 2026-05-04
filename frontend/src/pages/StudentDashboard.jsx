import { useEffect, useState } from 'react';
import axios from 'axios';
import PortalLayout from '../layouts/PortalLayout';
import { useAuth } from '../AuthContext';

function StudentSidebar() {
  return (
    <>
      <a className="active" href="/panel/estudiante">Home</a>
    </>
  );
}

export default function StudentDashboard() {
  const [generating, setGenerating] = useState(false);
  const { user, dashboardData, refreshDashboard } = useAuth();
  
  const inscripciones = dashboardData?.inscripciones || [];
  const totalCredits  = dashboardData?.totalCredits || 0;
  const horario       = dashboardData?.horario || null;

  const handleGenerate = async () => {
    setGenerating(true);
    try {
      await axios.post('/portal/api/estudiante/horario/generar');
      alert("Horario generado correctamente.");
      refreshDashboard();
    } catch (err) {
      alert("Error: " + (err.response?.data?.message || err.message));
    } finally {
      setGenerating(false);
    }
  };

  return (
    <PortalLayout
      roleTitle="Estudiante"
      breadcrumbs="Portal / Estudiante"
      roleChip="Estudiante"
      pageTitle="Panel de estudiante"
      pageSubtitle="Consulta tu estado académico y genera tu horario."
      Sidebar={StudentSidebar}
    >
      <div className="grid-2">
        <section className="card">
          <h2>Perfil</h2>
          <p className="muted"><strong>Nombre:</strong> {user?.name}</p>
          <p className="muted"><strong>Correo:</strong> {user?.email}</p>
          <p className="muted"><strong>ID:</strong> {user?.id}</p>
        </section>

        <section className="card">
          <h2>Resumen de Créditos</h2>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
             <div>
                <p className="muted"><strong>Materias activas:</strong> {inscripciones.length}</p>
                <p className="muted"><strong>Créditos acumulados:</strong> {totalCredits} / 29</p>
                <div style={{ width: '100%', height: '8px', background: '#eee', borderRadius: '4px', marginTop: '8px' }}>
                   <div style={{ 
                      width: `${Math.min((totalCredits / 29) * 100, 100)}%`, 
                      height: '100%', 
                      background: totalCredits > 29 ? '#ef4444' : '#10b981', 
                      borderRadius: '4px' 
                   }} />
                </div>
             </div>
             <button 
                className="btn btn-primary" 
                onClick={handleGenerate}
                disabled={generating || totalCredits === 0 || totalCredits > 29}
             >
                {generating ? 'Generando...' : 'Generar Horario'}
             </button>
          </div>
          {totalCredits > 29 && (
            <p style={{ color: '#ef4444', fontSize: '12px', marginTop: '8px' }}>Has excedido el límite de 29 créditos por semestre.</p>
          )}
        </section>
      </div>

      <section style={{ marginTop: '24px' }}>
        <div className="section-top">
          <h2 style={{ margin: 0 }}>Mi Horario</h2>
          <span className="status">{horario ? 'Generado' : 'No generado'}</span>
        </div>
        
        {!horario ? (
          <div className="empty" style={{ marginTop: '10px' }}>
            Aún no has generado tu horario para este módulo.
          </div>
        ) : (
          <div className="subject-grid" style={{ marginTop: '10px' }}>
            {horario.detalles?.map((det, idx) => (
              <article key={idx} className="subject-card">
                <span className="pill">Bloque {det.bloque?.nombre}</span>
                <strong>{det.materia?.nombre}</strong>
                <span className="label">Docente: {det.docente?.nombre} {det.docente?.apellido}</span>
                <span className="label">Aula: {det.aula?.nombre || 'Por asignar'}</span>
                <span className="label">{det.bloque?.hora_inicio.substring(0,5)} - {det.bloque?.hora_fin.substring(0,5)}</span>
              </article>
            ))}
          </div>
        )}
      </section>

      <section style={{ marginTop: '24px' }}>
        <div className="section-top">
          <h2 style={{ margin: 0 }}>Mis inscripciones</h2>
          <span className="status">{inscripciones.length} materias</span>
        </div>
        <div className="subject-grid">
          {inscripciones.length === 0 && (
            <div className="empty" style={{ gridColumn: '1 / -1' }}>
              No tienes inscripciones activas todavía.
            </div>
          )}
          {inscripciones.map((item, idx) => (
            <article key={idx} className="subject-card">
              <span className="pill">
                {item.modulo?.fecha_inicio
                  ? `${item.modulo.fecha_inicio} → ${item.modulo.fecha_final}`
                  : 'Sin módulo'}
              </span>
              <strong>{item.materia || 'Materia sin nombre'}</strong>
              <span className="label">Créditos: {item.modulo?.creditos}</span>
              <span className="label">Estado: {item.estado}</span>
            </article>
          ))}
        </div>
      </section>
    </PortalLayout>
  );
}
