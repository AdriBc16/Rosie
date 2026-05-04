import { useEffect, useState } from 'react';
import axios from 'axios';
import PortalLayout from '../layouts/PortalLayout';
import { useAuth } from '../AuthContext';
import { Link } from 'react-router-dom';

function StudentSidebar() {
  return (
    <>
      <Link to="/panel/estudiante">Home</Link>
      <Link className="active" to="/panel/estudiante/horario">Horario</Link>
    </>
  );
}

export default function StudentSchedule() {
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
      breadcrumbs="Portal / Estudiante / Horario"
      roleChip="Estudiante"
      pageTitle="Generación de Horario"
      pageSubtitle="Genera tu horario ideal basado en tus inscripciones actuales."
      Sidebar={StudentSidebar}
    >
      <div className="grid-1">
        <section className="card">
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '20px' }}>
             <div>
                <h2>Generar nuevo horario</h2>
                <p className="muted">Se validarán colisiones y límites de créditos (29 máx).</p>
                <p className="muted"><strong>Créditos actuales:</strong> {totalCredits} / 29</p>
             </div>
             <button 
                className="btn btn-primary" 
                onClick={handleGenerate}
                disabled={generating || totalCredits === 0 || totalCredits > 29}
                style={{ height: '50px', padding: '0 40px' }}
             >
                {generating ? 'Generando...' : '¡Generar ahora!'}
             </button>
          </div>
          {totalCredits > 29 && (
            <p style={{ color: '#ef4444', fontSize: '14px', marginTop: '12px', fontWeight: '600' }}>
              Atencion: Has excedido el límite de 29 créditos. Por favor retira alguna materia antes de generar.
            </p>
          )}
        </section>
      </div>

      <section style={{ marginTop: '32px' }}>
        <div className="section-top">
          <h2 style={{ margin: 0 }}>Vista Previa de mi Horario</h2>
          <span className="status">{horario ? 'Confirmado' : 'Pendiente de generación'}</span>
        </div>
        
        {!horario ? (
          <div className="empty" style={{ marginTop: '16px', padding: '60px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>[Calendario]</div>
            <h3>Aún no has generado tu horario</h3>
            <p>Haz clic en el botón superior para procesar tus inscripciones.</p>
          </div>
        ) : (
          <div className="subject-grid" style={{ marginTop: '16px' }}>
            {horario.detalles?.map((det, idx) => (
              <article key={idx} className="subject-card" style={{ animationDelay: `${idx * 0.1}s` }}>
                <span className="pill">{det.modulo_info?.nombre || 'Módulo'}</span>
                <span className="pill" style={{ background: '#4c1d95' }}>Horario {det.bloque?.nombre}</span>

                <strong>{det.materia?.nombre}</strong>
                <div style={{ marginTop: '8px', display: 'grid', gap: '4px' }}>
                    <span className="muted" style={{ fontSize: '12px', display: 'block', marginBottom: '4px' }}>
                        {det.modulo_info?.fecha_inicio} — {det.modulo_info?.fecha_final}
                    </span>
                    <span className="muted" style={{ fontSize: '13px' }}>
                        Docente: {det.docente?.nombre} {det.docente?.apellido}
                    </span>
                    <span className="muted" style={{ fontSize: '13px' }}>
                        Aula: {det.aula?.nombre || 'Por asignar'}
                    </span>
                    <span className="muted" style={{ fontSize: '13px', fontWeight: 'bold', color: 'var(--primary)' }}>
                        Hora: {det.bloque?.hora_inicio.substring(0,5)} - {det.bloque?.hora_fin.substring(0,5)}
                    </span>
                </div>
              </article>
            ))}

          </div>
        )}
      </section>
    </PortalLayout>
  );
}

