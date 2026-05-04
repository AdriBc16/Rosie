import { useEffect, useState } from 'react';
import axios from 'axios';
import PortalLayout from '../layouts/PortalLayout';
import { useAuth } from '../AuthContext';

function HeadSidebar({ active, setActive }) {
  const items = [
    ['resumen',  'Resumen'],
    ['docentes', 'Docentes'],
    ['personas', 'Buscar persona'],
    ['materia',  'Crear materia'],
    ['asignar',  'Asignar docente'],
    ['inscripcion', 'Inscribir estudiante'],
  ];
  return (
    <>
      {items.map(([id, label]) => (
        <a
          key={id}
          className={active === id ? 'active' : ''}
          onClick={(e) => { e.preventDefault(); setActive(id); }}
          href="#"
        >
          {label}
        </a>
      ))}
    </>
  );
}

export default function HeadDashboard() {
  const { user, dashboardData } = useAuth();
  const teachersCount = dashboardData?.teachersCount ?? 0;
  const studentsCount = dashboardData?.studentsCount ?? 0;
  const modulosCount  = dashboardData?.modulosCount  ?? 0;

  const [active, setActive] = useState('resumen');
  const [catalog, setCatalog] = useState({ docentes: [], materias: [], modulos: [], estudiantes: [] });
  const [catalogLoaded, setCatalogLoaded] = useState(false);

  // Forms states
  const [assignForm, setAssignForm] = useState({ id_docente: '', id_materia: '', id_modulo: '', id_bloque: '', id_aula: '' });
  const [assignFeedback, setAssignFeedback] = useState({ text: '', ok: false });
  const [teacherDisp, setTeacherDisp] = useState([]);

  const [enrollForm, setEnrollForm] = useState({ id_estudiante: '', id_materia: '', id_modulo: '' });
  const [enrollFeedback, setEnrollFeedback] = useState({ text: '', ok: false });

  const [newMateria, setNewMateria] = useState({ nombre: '', horas_semanales: 1, año_academico: 1 });
  const [materiaFeedback, setMateriaFeedback] = useState({ text: '', ok: false });

  // Person search
  const [searchType, setSearchType]     = useState('docente');
  const [searchPerson, setSearchPerson] = useState('');
  const [searchModulo, setSearchModulo] = useState('');
  const [searchResult, setSearchResult] = useState(null);
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchError, setSearchError]   = useState('');

  useEffect(() => {
    if (!catalogLoaded) {
      loadCatalog();
    }
  }, [catalogLoaded]);

  useEffect(() => {
    if (assignForm.id_docente) {
      loadTeacherAvailability(assignForm.id_docente);
    } else {
      setTeacherDisp([]);
    }
  }, [assignForm.id_docente]);

  const loadCatalog = async () => {
    try {
      const res = await axios.get('/portal/api/jefe/catalogo');
      setCatalog(res.data.data);
      setCatalogLoaded(true);
    } catch (err) {
      console.error('Error cargando catalogo', err);
    }
  };

  const loadTeacherAvailability = async (id) => {
    try {
      const res = await axios.get(`/portal/api/jefe/docentes/${id}/disponibilidad`);
      setTeacherDisp(res.data.disponibilidad || []);
    } catch {
      setTeacherDisp([]);
    }
  };

  const handleAssign = async (e) => {
    e.preventDefault();
    try {
      const res = await axios.post('/portal/api/jefe/asignaciones', assignForm);
      setAssignFeedback({ text: res.data.message, ok: true });
      setAssignForm({ id_docente: '', id_materia: '', id_modulo: '', id_bloque: '', id_aula: '' });
      loadCatalog();
    } catch (err) {
      setAssignFeedback({ text: err.response?.data?.message || err.message, ok: false });
    }
  };

  const handleEnroll = async (e) => {
    e.preventDefault();
    try {
      const res = await axios.post('/portal/api/jefe/inscripciones', enrollForm);
      setEnrollFeedback({ text: res.data.message, ok: true });
      setEnrollForm({ id_estudiante: '', id_materia: '', id_modulo: '' });
      loadCatalog();
    } catch (err) {
      setEnrollFeedback({ text: err.response?.data?.message || err.message, ok: false });
    }
  };

  const handleCreateMateria = async (e) => {
    e.preventDefault();
    try {
      const res = await axios.post('/portal/api/jefe/materias', newMateria);
      setMateriaFeedback({ text: res.data.message, ok: true });
      setNewMateria({ nombre: '', horas_semanales: 1, año_academico: 1 });
      loadCatalog();
    } catch (err) {
      setMateriaFeedback({ text: err.response?.data?.message || err.message, ok: false });
    }
  };

  const handleSearch = async () => {
    if (!searchPerson) {
      setSearchError('Selecciona una persona.');
      return;
    }
    setSearchLoading(true);
    setSearchError('');
    setSearchResult(null);
    try {
      const url = `/portal/api/jefe/personas/${searchType}/${searchPerson}/materias${searchModulo ? `?id_modulo=${searchModulo}` : ''}`;
      const res = await axios.get(url);
      setSearchResult(res.data.data);
    } catch (err) {
      setSearchError(err.response?.data?.message || err.message);
    } finally {
      setSearchLoading(false);
    }
  };

  const peopleList = searchType === 'docente' ? catalog.docentes : catalog.estudiantes;
  const personKey  = searchType === 'docente' ? 'id_docente' : 'id_estudiante';

  return (
    <PortalLayout
      roleTitle="Jefe de Carrera"
      breadcrumbs="Portal / Jefe de Carrera"
      roleChip="Jefe"
      pageTitle="Panel de coordinación"
      pageSubtitle="Gestión académica integral."
      Sidebar={() => <HeadSidebar active={active} setActive={setActive} />}
    >

      {/* ── RESUMEN ── */}
      {active === 'resumen' && (
        <div className="grid-3">
          <section className="card">
            <h2>Docentes</h2>
            <p className="big">{teachersCount}</p>
            <p className="muted">registrados</p>
          </section>
          <section className="card">
            <h2>Estudiantes</h2>
            <p className="big">{studentsCount}</p>
            <p className="muted">registrados</p>
          </section>
          <section className="card">
            <h2>Módulos</h2>
            <p className="big">{modulosCount}</p>
            <p className="muted">en total</p>
          </section>
        </div>
      )}

      {/* ── DOCENTES ── */}
      {active === 'docentes' && (
        <section className="card">
          <h2>Docentes registrados</h2>
          {catalog.docentes.length === 0
            ? <p className="muted">Sin docentes registrados.</p>
            : (
              <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: '10px' }}>
                <thead>
                  <tr>
                    <th style={{ textAlign: 'left', padding: '8px', borderBottom: '1px solid #2d2d3d' }}>Nombre</th>
                    <th style={{ textAlign: 'left', padding: '8px', borderBottom: '1px solid #2d2d3d' }}>Correo</th>
                    <th style={{ textAlign: 'left', padding: '8px', borderBottom: '1px solid #2d2d3d' }}>Jefe</th>
                  </tr>
                </thead>
                <tbody>
                  {catalog.docentes.map(d => (
                    <tr key={d.id_docente}>
                      <td style={{ padding: '8px', borderBottom: '1px solid #1e1e2e' }}>{d.nombre} {d.apellido || ''}</td>
                      <td style={{ padding: '8px', borderBottom: '1px solid #1e1e2e', color: '#8f8fb0' }}>{d.correo}</td>
                      <td style={{ padding: '8px', borderBottom: '1px solid #1e1e2e' }}>{d.es_jefe_carrera ? '✓' : ''}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )
          }
        </section>
      )}

      {/* ── BUSCAR PERSONA ── */}
      {active === 'personas' && (
        <section className="card">
          <h2>Buscar por persona</h2>
          <div className="grid-3" style={{ marginBottom: '12px' }}>
            <div className="field">
              <label htmlFor="searchType">Perfil</label>
              <select id="searchType" value={searchType} onChange={(e) => { setSearchType(e.target.value); setSearchPerson(''); setSearchResult(null); }}>
                <option value="docente">Docente</option>
                <option value="estudiante">Estudiante</option>
              </select>
            </div>
            <div className="field">
              <label htmlFor="searchPerson">Persona</label>
              <select id="searchPerson" value={searchPerson} onChange={(e) => setSearchPerson(e.target.value)}>
                <option value="">Selecciona</option>
                {peopleList.map(p => (
                  <option key={p[personKey]} value={p[personKey]}>
                    {p.nombre} {p.apellido || ''} ({p.correo})
                  </option>
                ))}
              </select>
            </div>
            <div className="field">
              <label htmlFor="searchModulo">Módulo (opcional)</label>
              <select id="searchModulo" value={searchModulo} onChange={(e) => setSearchModulo(e.target.value)}>
                <option value="">Todos</option>
                {catalog.modulos.map(m => (
                  <option key={m.id_modulo} value={m.id_modulo}>
                    Módulo {m.id_modulo} ({m.fecha_inicio} → {m.fecha_final})
                  </option>
                ))}
              </select>
            </div>
          </div>
          <button className="btn btn-primary" type="button" onClick={handleSearch}>Buscar</button>

          {searchLoading && <p className="muted" style={{ marginTop: '12px' }}>Cargando...</p>}
          {searchError   && <p style={{ color: '#ff6b93', marginTop: '12px' }}>{searchError}</p>}

          {searchResult && (
            <div style={{ marginTop: '16px' }}>
              <p className="muted"><strong>{searchResult.persona?.nombre}</strong> — {searchResult.persona?.correo}</p>
              <div className="subject-grid" style={{ marginTop: '12px' }}>
                {searchResult.materias?.length === 0
                  ? <div className="empty">Sin materias para este filtro.</div>
                  : searchResult.materias?.map((item, idx) => (
                    <article key={idx} className="subject-card">
                      <span className="pill">{item.fecha_inicio || '--'} → {item.fecha_final || '--'}</span>
                      <span className="pill" style={{ background: '#4c1d95' }}>{item.bloque || 'No bloque'}</span>
                      <strong>{item.materia || 'Sin nombre'}</strong>
                      {item.aula && <span className="label">Aula: {item.aula}</span>}
                      {item.estado && <span className="label">Estado: {item.estado}</span>}
                      {item.estudiantes_count !== undefined && <span className="label">Inscritos: {item.estudiantes_count}</span>}
                    </article>
                  ))
                }
              </div>
            </div>
          )}
        </section>
      )}

      {/* ── CREAR MATERIA ── */}
      {active === 'materia' && (
        <section className="card">
          <h2>Crear nueva materia</h2>
          <form onSubmit={handleCreateMateria}>
            <div className="field">
              <label htmlFor="materiaName">Nombre</label>
              <input id="materiaName" type="text" value={newMateria.nombre} onChange={(e) => setNewMateria(p => ({ ...p, nombre: e.target.value }))} placeholder="Ej: Programación Web" required />
            </div>
            <div className="grid-2">
              <div className="field">
                <label htmlFor="horasSemanales">Horas semanales</label>
                <input id="horasSemanales" type="number" min="1" max="20" value={newMateria.horas_semanales} onChange={(e) => setNewMateria(p => ({ ...p, horas_semanales: e.target.value }))} />
              </div>
              <div className="field">
                <label htmlFor="anoAcademico">Año académico</label>
                <input id="anoAcademico" type="number" min="1" max="10" value={newMateria.año_academico} onChange={(e) => setNewMateria(p => ({ ...p, año_academico: e.target.value }))} />
              </div>
            </div>
            <button className="btn btn-primary" type="submit">Crear materia</button>
          </form>
          {materiaFeedback.text && (
            <div className={`feedback ${materiaFeedback.ok ? 'ok' : 'error'}`} style={{ marginTop: '12px' }}>
              {materiaFeedback.text}
            </div>
          )}
        </section>
      )}

      {/* ── ASIGNAR DOCENTE ── */}
      {active === 'asignar' && (
        <section className="card">
          <h2>Asignar docente a materia</h2>
          <form onSubmit={handleAssign}>
            <div className="field">
              <label htmlFor="assignDocente">Docente</label>
              <select id="assignDocente" value={assignForm.id_docente} onChange={(e) => setAssignForm(p => ({ ...p, id_docente: e.target.value }))} required>
                <option value="">Selecciona docente</option>
                {catalog.docentes.filter(d => !d.es_jefe_carrera).map(d => (
                  <option key={d.id_docente} value={d.id_docente}>{d.nombre} {d.apellido || ''}</option>
                ))}
              </select>
              {teacherDisp.length > 0 && (
                <small className="muted" style={{ display: 'block', marginTop: '4px' }}>
                  Disponible en bloques: {teacherDisp.join(', ')}
                </small>
              )}
            </div>
            <div className="field">
              <label htmlFor="assignMateria">Materia</label>
              <select id="assignMateria" value={assignForm.id_materia} onChange={(e) => setAssignForm(p => ({ ...p, id_materia: e.target.value }))} required>
                <option value="">Selecciona materia</option>
                {catalog.materias.map(m => (
                  <option key={m.id_materia} value={m.id_materia}>{m.nombre}</option>
                ))}
              </select>
            </div>
            <div className="field">
              <label htmlFor="assignModulo">Módulo</label>
              <select id="assignModulo" value={assignForm.id_modulo} onChange={(e) => setAssignForm(p => ({ ...p, id_modulo: e.target.value }))} required>
                <option value="">Selecciona módulo</option>
                {catalog.modulos.map(m => (
                  <option key={m.id_modulo} value={m.id_modulo}>
                    Módulo {m.id_modulo} ({m.creditos} cr) — {m.fecha_inicio} → {m.fecha_final}
                  </option>
                ))}
              </select>
            </div>
            
            <div className="grid-2">
              <div className="field">
                <label htmlFor="assignBloque">Bloque Horario</label>
                <select id="assignBloque" value={assignForm.id_bloque} onChange={(e) => setAssignForm(p => ({ ...p, id_bloque: e.target.value }))} required>
                  <option value="">Selecciona Bloque</option>
                  {catalog.bloques?.map(b => (
                    <option key={b.id_bloque} value={b.id_bloque}>
                      Bloque {b.nombre} ({b.hora_inicio.substring(0,5)} - {b.hora_fin.substring(0,5)})
                    </option>
                  ))}
                </select>
              </div>
              <div className="field">
                <label htmlFor="assignAula">Aula</label>
                <select id="assignAula" value={assignForm.id_aula} onChange={(e) => setAssignForm(p => ({ ...p, id_aula: e.target.value }))} required>
                  <option value="">Selecciona Aula</option>
                  {catalog.aulas?.map(a => (
                    <option key={a.id_aula} value={a.id_aula}>{a.nombre} (Cap: {a.capacidad})</option>
                  ))}
                </select>
              </div>
            </div>

            <button className="btn btn-primary" type="submit" style={{ marginTop: '10px' }}>Asignar</button>
          </form>
          {assignFeedback.text && (
            <div className={`feedback ${assignFeedback.ok ? 'ok' : 'error'}`} style={{ marginTop: '12px' }}>
              {assignFeedback.text}
            </div>
          )}
        </section>
      )}

      {/* ── INSCRIBIR ESTUDIANTE ── */}
      {active === 'inscripcion' && (
        <section className="card">
          <h2>Inscribir estudiante en materia</h2>
          <form onSubmit={handleEnroll}>
            <div className="field">
              <label htmlFor="enrollEstudiante">Estudiante</label>
              <select id="enrollEstudiante" value={enrollForm.id_estudiante} onChange={(e) => setEnrollForm(p => ({ ...p, id_estudiante: e.target.value }))} required>
                <option value="">Selecciona estudiante</option>
                {catalog.estudiantes.map(e => (
                  <option key={e.id_estudiante} value={e.id_estudiante}>{e.nombre} {e.apellido || ''} ({e.correo})</option>
                ))}
              </select>
            </div>
            <div className="field">
              <label htmlFor="enrollMateria">Materia</label>
              <select id="enrollMateria" value={enrollForm.id_materia} onChange={(e) => setEnrollForm(p => ({ ...p, id_materia: e.target.value }))} required>
                <option value="">Selecciona materia</option>
                {catalog.materias.map(m => (
                  <option key={m.id_materia} value={m.id_materia}>{m.nombre}</option>
                ))}
              </select>
            </div>
            <div className="field">
              <label htmlFor="enrollModulo">Módulo</label>
              <select id="enrollModulo" value={enrollForm.id_modulo} onChange={(e) => setEnrollForm(p => ({ ...p, id_modulo: e.target.value }))} required>
                <option value="">Selecciona módulo</option>
                {catalog.modulos.map(m => (
                  <option key={m.id_modulo} value={m.id_modulo}>
                    Módulo {m.id_modulo} ({m.creditos} cr) — {m.fecha_inicio} → {m.fecha_final}
                  </option>
                ))}
              </select>
            </div>
            <button className="btn btn-primary" type="submit">Inscribir</button>
          </form>
          {enrollFeedback.text && (
            <div className={`feedback ${enrollFeedback.ok ? 'ok' : 'error'}`} style={{ marginTop: '12px' }}>
              {enrollFeedback.text}
            </div>
          )}
        </section>
      )}

    </PortalLayout>
  );
}
