import { useEffect, useState } from 'react';
import axios from 'axios';
import PortalLayout from '../layouts/PortalLayout';
import { useAuth } from '../AuthContext';
import { Link } from 'react-router-dom';

function StudentSidebar() {
  return (
    <>
      <Link className="active" to="/panel/estudiante">Home</Link>
      <Link to="/panel/estudiante/horario">Horario</Link>
    </>
  );
}

export default function StudentDashboard() {
  const { user, dashboardData } = useAuth();
  
  const inscripciones = dashboardData?.inscripciones || [];
  const totalCredits  = dashboardData?.totalCredits || 0;

  return (
    <PortalLayout
      roleTitle="Estudiante"
      breadcrumbs="Portal / Estudiante"
      roleChip="Estudiante"
      pageTitle="Panel de estudiante"
      pageSubtitle="Consulta tu estado académico y resumen de materias."
      Sidebar={StudentSidebar}
    >
      <div className="grid-2">
        <section className="card">
          <h2>Perfil Académico</h2>
          <div style={{ marginTop: '12px' }}>
            <p className="muted"><strong>Nombre:</strong> {user?.name}</p>
            <p className="muted"><strong>Correo:</strong> {user?.email}</p>
            <p className="muted"><strong>ID Estudiante:</strong> {user?.id}</p>
            <p className="muted"><strong>Modalidad:</strong> {user?.es_traspaso ? 'Traspaso / Convalidación' : 'Regular'}</p>
          </div>
        </section>

        <section className="card">
          <h2>Resumen de Créditos</h2>
          <div style={{ marginTop: '12px' }}>
            <p className="muted"><strong>Materias activas:</strong> {inscripciones.length}</p>
            <p className="muted"><strong>Créditos acumulados:</strong> {totalCredits} / 29</p>
            <div style={{ width: '100%', height: '10px', background: 'rgba(255,255,255,0.05)', borderRadius: '5px', marginTop: '16px', overflow: 'hidden' }}>
               <div style={{ 
                  width: `${Math.min((totalCredits / 29) * 100, 100)}%`, 
                  height: '100%', 
                  background: totalCredits > 29 ? '#ef4444' : 'var(--grad-rosie)', 
                  borderRadius: '5px' 
               }} />
            </div>
          </div>
          {totalCredits > 29 && (
            <p style={{ color: '#ef4444', fontSize: '12px', marginTop: '12px', fontWeight: 'bold' }}>
              Atencion: Has excedido el límite de 29 créditos por semestre.
            </p>
          )}
        </section>
      </div>

      <section style={{ marginTop: '32px' }}>
        <div className="section-top">
          <h2 style={{ margin: 0 }}>Mis inscripciones actuales</h2>
          <span className="status">{inscripciones.length} materias</span>
        </div>
        <div className="subject-grid" style={{ marginTop: '16px' }}>
          {inscripciones.length === 0 && (
            <div className="empty" style={{ gridColumn: '1 / -1', padding: '40px' }}>
              No tienes inscripciones activas todavía.
            </div>
          )}
          {inscripciones.map((item, idx) => (
            <article key={item.id_inscripcion} className="subject-card">
              <span className="pill">
                {item.modulo?.nombre || 'Módulo'}
              </span>

              <strong>{item.materia || 'Sin nombre'}</strong>
              <div className="label">
                <span className="muted" style={{ display: 'block', marginBottom: '4px' }}>
                    {item.modulo?.fecha_inicio} — {item.modulo?.fecha_final}
                </span>
                <span className="muted" style={{ fontSize: '12px' }}>Créditos: {item.modulo?.creditos}</span>
              </div>
              <span className="badge" style={{ marginTop: '8px' }}>{item.estado}</span>
            </article>
          ))}
        </div>
      </section>
    </PortalLayout>
  );
}


