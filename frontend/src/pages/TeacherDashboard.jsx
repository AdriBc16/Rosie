import { useEffect, useState } from 'react';
import axios from 'axios';
import PortalLayout from '../layouts/PortalLayout';
import { useAuth } from '../AuthContext';

function TeacherSidebar() {
  return (
    <>
      <a href="/panel/docente" className="active">Home</a>
    </>
  );
}

export default function TeacherDashboard() {
  const { user } = useAuth();
  const [assignments, setAssignments] = useState([]);
  const [status, setStatus] = useState('Cargando...');

  const [modalShow, setModalShow]       = useState(false);
  const [modalTitle, setModalTitle]     = useState('');
  const [modalMeta, setModalMeta]       = useState('');
  const [students, setStudents]         = useState([]);
  const [studentsLoading, setStudentsLoading] = useState(false);
  const [studentsError, setStudentsError]     = useState('');

  const [allBlocks, setAllBlocks] = useState([]);
  const [myBlocks, setMyBlocks]   = useState([]);
  const [savingDisp, setSavingDisp] = useState(false);

  useEffect(() => {
    loadAssignments();
    loadDisponibilidad();
  }, []);

  const loadAssignments = async () => {
    setStatus('Cargando...');
    try {
      const res  = await axios.get('/portal/api/docente/materias');
      const data = res.data.data || [];
      setAssignments(data);
      setStatus(data.length === 0 ? 'Sin asignaciones' : `${data.length} asignaciones`);
    } catch {
      setStatus('Error al cargar asignaciones');
    }
  };

  const loadDisponibilidad = async () => {
    try {
      const res = await axios.get('/portal/api/docente/disponibilidad');
      setAllBlocks(res.data.bloques || []);
      setMyBlocks(res.data.misBloques || []);
    } catch (err) {
      console.error("Error cargando disponibilidad", err);
    }
  };

  const toggleBlock = (id) => {
    if (myBlocks.includes(id)) {
      setMyBlocks(myBlocks.filter(b => b !== id));
    } else {
      setMyBlocks([...myBlocks, id]);
    }
  };

  const saveDisponibilidad = async () => {
    setSavingDisp(true);
    try {
      await axios.post('/portal/api/docente/disponibilidad', { bloques: myBlocks });
      alert("Disponibilidad guardada correctamente.");
    } catch (err) {
      alert("Error al guardar: " + (err.response?.data?.message || err.message));
    } finally {
      setSavingDisp(false);
    }
  };

  const showStudents = async (idDm) => {
    setModalShow(true);
    setStudentsLoading(true);
    setStudentsError('');
    setStudents([]);
    try {
      const res        = await axios.get(`/portal/api/docente/materias/${idDm}/estudiantes`);
      const { asignacion, estudiantes } = res.data.data;
      setModalTitle(asignacion.materia || 'Materia');
      setModalMeta(`Módulo ${asignacion.modulo || '?'} | ${asignacion.fecha_inicio || '--'} → ${asignacion.fecha_fin || '--'}`);
      setStudents(estudiantes || []);
    } catch (err) {
      setStudentsError(err.response?.data?.message || err.message);
    } finally {
      setStudentsLoading(false);
    }
  };

  return (
    <PortalLayout
      roleTitle="Docente"
      breadcrumbs="Portal / Docente"
      roleChip="Docente"
      pageTitle="Panel docente"
      pageSubtitle="Consulta tus asignaciones y estudiantes inscritos."
      Sidebar={TeacherSidebar}
    >
      <div className="grid-2">
        <section className="card">
          <h2>Perfil</h2>
          <p className="muted"><strong>Nombre:</strong> {user?.name}</p>
          <p className="muted"><strong>ID:</strong> {user?.id}</p>
          <p className="muted"><strong>Correo:</strong> {user?.email}</p>
        </section>

        <section className="card">
          <h2>Mis Horarios Disponibles</h2>
          <p className="muted" style={{ marginBottom: '10px' }}>Selecciona los horarios en los que puedes dar clases:</p>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
            {allBlocks.map(b => (
              <label key={b.id_bloque} style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer', fontSize: '14px' }}>
                <input 
                  type="checkbox" 
                  checked={myBlocks.includes(b.id_bloque)} 
                  onChange={() => toggleBlock(b.id_bloque)}
                />
                <div>
                  <strong>{b.nombre}</strong>
                  <br />
                  <small className="muted">{b.hora_inicio.substring(0,5)} - {b.hora_fin.substring(0,5)}</small>
                </div>
              </label>
            ))}
          </div>
          <button 
            className="btn btn-primary" 
            style={{ marginTop: '15px', width: '100%' }}
            onClick={saveDisponibilidad}
            disabled={savingDisp}
          >
            {savingDisp ? 'Guardando...' : 'Guardar Horarios'}
          </button>
        </section>

      </div>

      <section style={{ marginTop: '12px' }}>
        <div className="section-top">
          <h2 style={{ margin: 0 }}>Materias asignadas</h2>
          <span className="status">{status}</span>
        </div>
        <div className="subject-grid">
          {assignments.length === 0 && status !== 'Cargando...' && (
            <div className="empty" style={{ gridColumn: '1 / -1' }}>
              No tienes materias asignadas todavía.
            </div>
          )}
          {assignments.map(item => (
            <article key={item.id_dm} className="subject-card">
              <span className="pill">
                {item.modulo?.nombre || 'Módulo'}
              </span>

              <strong>{item.materia?.nombre || 'Materia sin nombre'}</strong>
              <div className="label">
                <span className="muted" style={{ display: 'block', marginBottom: '4px' }}>
                    {item.modulo?.fecha_inicio} — {item.modulo?.fecha_final}
                </span>
                Estudiantes inscritos: {item.estudiantes_count ?? 0}
              </div>
              <button
                className="btn btn-primary"
                type="button"
                style={{ marginTop: '10px' }}
                onClick={() => showStudents(item.id_dm)}
              >
                Ver estudiantes
              </button>
            </article>
          ))}

        </div>
      </section>

      {/* Modal Estudiantes */}
      <div
        className={`modal ${modalShow ? 'show' : ''}`}
        onClick={(e) => { if (e.target === e.currentTarget) setModalShow(false); }}
      >
        <div className="modal-card">
          <div className="modal-header">
            <h3 style={{ margin: 0 }}>{modalTitle || 'Estudiantes'}</h3>
            <button className="btn" type="button" onClick={() => setModalShow(false)}>Cerrar</button>
          </div>
          <div className="label" style={{ marginBottom: '10px' }}>{modalMeta}</div>
          <div className="students">
            {studentsLoading && <div className="student-row">Cargando...</div>}
            {studentsError   && <div className="student-row">{studentsError}</div>}
            {!studentsLoading && !studentsError && students.length === 0 && (
              <div className="student-row">No hay estudiantes inscritos.</div>
            )}
            {!studentsLoading && students.map((s, idx) => (
              <div key={idx} className="student-row">
                <strong>{s.nombre || 'Sin nombre'}</strong>
                <br />
                <span className="label">ID: {s.id_estudiante} | {s.correo} | {s.estado}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </PortalLayout>
  );
}
