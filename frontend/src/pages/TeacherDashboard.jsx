import { useEffect, useState } from 'react';
import axios from 'axios';
import { useAuth } from '../AuthContext';
import { useNavigate } from 'react-router-dom';
import ProfileModal from '../components/ProfileModal';
import logoRosie from '../assets/Rosie1.png';

const BLOQUE_COLORS = {
  A: 'from-indigo-600 to-violet-500',
  B: 'from-sky-600 to-cyan-500',
  C: 'from-emerald-600 to-teal-500',
  D: 'from-cyan-600 to-blue-500',
  E: 'from-blue-600 to-indigo-500',
  F: 'from-violet-600 to-fuchsia-500',
};

const STATUS_STYLES = {
  cursando: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
  pendiente: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
  aprobada: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
  reprobada: 'bg-red-500/20 text-red-300 border-red-500/30',
  bloqueada: 'bg-neutral-500/20 text-neutral-300 border-neutral-500/30',
};

export default function TeacherDashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const [assignments, setAssignments] = useState([]);
  const [loadingAssignments, setLoadingAssignments] = useState(true);

  const [allBlocks, setAllBlocks] = useState([]);
  const [myBlocks, setMyBlocks] = useState([]);
  const [savingDisp, setSavingDisp] = useState(false);
  const [dispFeedback, setDispFeedback] = useState('');
  const [modulos, setModulos] = useState([]);
  const [selectedModulo, setSelectedModulo] = useState('');

  const [studentsModal, setStudentsModal] = useState(null); // { title, meta, students, idDm }
  const [loadingStudents, setLoadingStudents] = useState(false);
  const [showProfile, setShowProfile] = useState(false);

  // Bulk status edit modal
  const [editModal, setEditModal] = useState(false);
  const [selectedStudents, setSelectedStudents] = useState({}); // { id_inscripcion: estado }
  const [bulkStatus, setBulkStatus] = useState('aprobada');
  const [savingStatus, setSavingStatus] = useState(false);
  const [statusFeedback, setStatusFeedback] = useState('');

  useEffect(() => {
    loadAssignments();
    loadDisponibilidad();
  }, []);

  const loadAssignments = async () => {
    setLoadingAssignments(true);
    try {
      const res = await axios.get('/portal/api/docente/materias');
      setAssignments(res.data.data || []);
    } catch {
      setAssignments([]);
    } finally {
      setLoadingAssignments(false);
    }
  };

  const loadDisponibilidad = async (moduloId = '') => {
    try {
      const url = moduloId ? `/portal/api/docente/disponibilidad?id_modulo=${moduloId}` : '/portal/api/docente/disponibilidad';
      const res = await axios.get(url);
      setAllBlocks(res.data.bloques || []);
      setMyBlocks(res.data.misBloques ? res.data.misBloques.map(Number) : []);
      if (res.data.modulos) {
        setModulos(res.data.modulos);
      }
      if (res.data.id_modulo_activo && !moduloId) {
        setSelectedModulo(res.data.id_modulo_activo);
      }
    } catch (err) {
      console.error('Error cargando disponibilidad', err);
    }
  };

  const handleModuloChange = (e) => {
    const newModuloId = e.target.value;
    setSelectedModulo(newModuloId);
    loadDisponibilidad(newModuloId);
  };

  const toggleBlock = (id) => {
    setMyBlocks((prev) => prev.includes(id) ? prev.filter((b) => b !== id) : [...prev, id]);
  };

  const saveDisponibilidad = async () => {
    setSavingDisp(true);
    setDispFeedback('');
    try {
      await axios.post('/portal/api/docente/disponibilidad', {
        id_modulo: selectedModulo,
        bloques: myBlocks
      });
      setDispFeedback('Disponibilidad guardada correctamente.');
      setTimeout(() => setDispFeedback(''), 5000);
    } catch (err) {
      setDispFeedback('Error: ' + (err.response?.data?.message || err.message));
    } finally {
      setSavingDisp(false);
    }
  };

  const showStudents = async (idDm) => {
    setStudentsModal({ title: 'Cargando...', meta: '', students: [], idDm });
    setLoadingStudents(true);
    try {
      const res = await axios.get(`/portal/api/docente/materias/${idDm}/estudiantes`);
      const { asignacion, estudiantes } = res.data.data;
      setStudentsModal({
        title: asignacion.materia || 'Materia',
        meta: `Módulo ${asignacion.modulo || '?'} · ${asignacion.fecha_inicio || '--'} → ${asignacion.fecha_fin || '--'}`,
        students: estudiantes || [],
        idDm,
      });
    } catch (err) {
      setStudentsModal({ title: 'Error', meta: err.response?.data?.message || err.message, students: [], idDm });
    } finally {
      setLoadingStudents(false);
    }
  };

  const openEditModal = () => {
    setSelectedStudents({});
    setBulkStatus('aprobada');
    setStatusFeedback('');
    setEditModal(true);
  };

  const toggleSelectStudent = (idInscripcion) => {
    setSelectedStudents(prev => {
      const next = { ...prev };
      if (next[idInscripcion] !== undefined) {
        delete next[idInscripcion];
      } else {
        next[idInscripcion] = bulkStatus;
      }
      return next;
    });
  };

  const selectAll = () => {
    const all = {};
    studentsModal.students.forEach(s => { all[s.id_inscripcion] = bulkStatus; });
    setSelectedStudents(all);
  };

  const clearSelection = () => setSelectedStudents({});

  const applyBulkStatus = () => {
    setSelectedStudents(prev => {
      const next = {};
      Object.keys(prev).forEach(id => { next[id] = bulkStatus; });
      return next;
    });
  };

  const saveStatusChanges = async () => {
    const updates = Object.entries(selectedStudents).map(([id, estado]) => ({
      id_inscripcion: Number(id),
      estado,
    }));
    if (updates.length === 0) return;
    setSavingStatus(true);
    setStatusFeedback('');
    try {
      await axios.patch(`/portal/api/docente/materias/${studentsModal.idDm}/estudiantes/estados`, { updates });
      setStatusFeedback('Estados actualizados correctamente.');
      setTimeout(() => setStatusFeedback(''), 5000);
      // Refresh student list
      const res = await axios.get(`/portal/api/docente/materias/${studentsModal.idDm}/estudiantes`);
      const { asignacion, estudiantes } = res.data.data;
      setStudentsModal(prev => ({ ...prev, students: estudiantes || [] }));
      setSelectedStudents({});
    } catch (err) {
      setStatusFeedback('Error: ' + (err.response?.data?.message || err.message));
    } finally {
      setSavingStatus(false);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="bg-black text-[#f6dddc] min-h-screen overflow-hidden">
      {/* Sidebar */}
      <aside className="fixed left-0 top-0 h-screen w-72 border-r border-neutral-800 bg-neutral-950 z-50 flex flex-col p-6 shadow-2xl shadow-rose-900/10">
        <div className="mb-10">
          <div className="flex items-center gap-3">
            <img src={logoRosie} alt="Logo" className="w-12 h-12 object-contain" />
            <div>
              <span className="text-2xl font-black bg-gradient-to-br from-rose-500 to-orange-400 bg-clip-text text-transparent">Rosie</span>
              <p className="text-xs text-neutral-500 mt-0.5">Portal Docente</p>
            </div>
          </div>
        </div>

        <nav className="flex-1 flex flex-col gap-2">
          <a className="flex items-center gap-3 bg-indigo-500/10 text-indigo-300 rounded-xl py-3 px-4 border-l-4 border-indigo-500" href="#">
            <span className="material-symbols-outlined text-indigo-400">dashboard</span>
            <span className="font-semibold text-sm">Mi Panel</span>
          </a>
        </nav>

        <div className="mt-auto pt-6 border-t border-neutral-800">
          <div className="mb-3 px-2">
            <p className="text-xs text-neutral-500 mb-1">Sesión activa</p>
            <p className="text-sm font-semibold text-neutral-200 truncate">{user?.name}</p>
            <p className="text-xs text-neutral-500 truncate">{user?.email}</p>
          </div>
          <button
            onClick={() => setShowProfile(true)}
            className="w-full flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-neutral-300 hover:bg-neutral-800 border border-transparent hover:border-neutral-700 transition-all mb-1"
          >
            <span className="material-symbols-outlined text-base">manage_accounts</span>
            Editar perfil
          </button>
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-red-400 hover:bg-red-500/10 border border-transparent hover:border-red-500/30 transition-all"
          >
            <span className="material-symbols-outlined text-base">logout</span>
            Cerrar sesión
          </button>
        </div>
      </aside>

      {/* Main */}
      <main className="ml-72 min-h-screen flex flex-col">
        {/* Topbar */}
        <header className="fixed top-0 right-0 left-72 z-40 flex justify-between items-center px-8 h-16 border-b border-neutral-800 bg-black/80 backdrop-blur-sm">
          <div>
            <h1 className="font-bold text-lg text-white">Panel Docente</h1>
            <p className="text-xs text-neutral-500">Gestiona tus horarios y estudiantes</p>
          </div>
          <div className="flex items-center gap-3">
            <span className="px-3 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
              {user?.is_head ? 'Jefe de Carrera' : 'Docente'}
            </span>
            <button onClick={loadAssignments} className="bg-neutral-900 border border-neutral-800 rounded-lg px-3 py-1.5 text-xs text-neutral-300 hover:border-neutral-700">
              Recargar
            </button>
          </div>
        </header>

        <div className="mt-16 p-8 overflow-y-auto h-[calc(100vh-64px)] space-y-6">
          {/* Top row: Profile + Disponibilidad */}
          <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
            {/* Profile */}
            <div className="bg-neutral-900/40 rounded-[24px] border border-neutral-800 p-6">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center">
                  <span className="material-symbols-outlined text-white text-2xl">person</span>
                </div>
                <div>
                  <h2 className="text-xl font-bold text-white">{user?.name || 'Docente'}</h2>
                  <p className="text-sm text-neutral-400">{user?.email}</p>
                  <span className="mt-1 inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                    ID: {user?.id}
                  </span>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div className="bg-neutral-900 rounded-xl p-4 border border-neutral-800">
                  <p className="text-[10px] text-neutral-500 uppercase font-bold mb-1">Materias asignadas</p>
                  <p className="text-2xl font-black text-white">{assignments.length}</p>
                </div>
                <div className="bg-neutral-900 rounded-xl p-4 border border-neutral-800">
                  <p className="text-[10px] text-neutral-500 uppercase font-bold mb-1">Bloques disponibles</p>
                  <p className="text-2xl font-black text-white">{myBlocks.length}</p>
                </div>
              </div>
            </div>

            {/* Disponibilidad */}
            <div className="bg-neutral-900/40 rounded-[24px] border border-neutral-800 p-6">
              <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3">
                <h2 className="text-lg font-bold text-white">Mis Horarios Disponibles</h2>
                <div className="flex items-center gap-3 w-full sm:w-auto">
                  <select 
                    value={selectedModulo} 
                    onChange={handleModuloChange}
                    className="bg-neutral-900 border border-neutral-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-rose-500 w-full sm:w-auto"
                  >
                    {modulos.map((m) => {
                      const inicio = m.fecha_inicio ? new Date(`${m.fecha_inicio}T00:00:00`).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }) : null;
                      const fin = m.fecha_final ? new Date(`${m.fecha_final}T00:00:00`).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }) : null;
                      const fecha = inicio && fin ? ` · ${inicio} – ${fin}` : inicio ? ` · ${inicio}` : '';
                      return (
                        <option key={m.id_modulo} value={m.id_modulo}>
                          {m.nombre}{fecha}
                        </option>
                      );
                    })}
                  </select>
                  <button
                    onClick={saveDisponibilidad}
                    disabled={savingDisp}
                    className="px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-rose-600 to-orange-500 text-white hover:brightness-110 disabled:opacity-50 transition-all whitespace-nowrap"
                  >
                    {savingDisp ? 'Guardando...' : 'Guardar'}
                  </button>
                </div>
              </div>
              {dispFeedback && (
                <div className={`mb-3 px-3 py-2 rounded-lg text-xs border ${dispFeedback.includes('Error') ? 'border-red-500/40 bg-red-500/10 text-red-300' : 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300'}`}>
                  {dispFeedback}
                </div>
              )}
              <div className="grid grid-cols-2 gap-2">
                {allBlocks.map((b) => {
                  const selected = myBlocks.includes(b.id_bloque);
                  const colorClass = BLOQUE_COLORS[b.nombre] || 'from-neutral-600 to-neutral-500';
                  return (
                    <button
                      key={b.id_bloque}
                      onClick={() => toggleBlock(b.id_bloque)}
                      className={`relative flex items-center gap-3 p-3 rounded-xl border transition-all text-left ${                        selected
                          ? 'border-rose-500/60 bg-rose-500/10'
                          : 'border-neutral-800 bg-neutral-900 hover:border-neutral-700'
                      }`}                    >
                      <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${colorClass} flex items-center justify-center flex-shrink-0`}>
                        <span className="text-white font-black text-sm">{b.nombre}</span>
                      </div>
                      <div>
                        <p className={`text-xs font-bold ${selected ? 'text-white' : 'text-neutral-300'}`}>Bloque {b.nombre}</p>
                        <p className="text-[10px] text-neutral-500">{b.hora_inicio?.substring(0, 5)} – {b.hora_fin?.substring(0, 5)}</p>
                      </div>
                      {selected && (
                        <span className="absolute top-2 right-2 w-2 h-2 rounded-full bg-rose-500" />
                      )}
                    </button>
                  );
                })}
                {allBlocks.length === 0 && (
                  <div className="col-span-2 py-8 text-center text-sm text-neutral-500">Cargando bloques horarios...</div>
                )}
              </div>
            </div>
          </div>

          {/* Materias Asignadas */}
          <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h2 className="text-xl font-bold text-white">Materias Asignadas</h2>
                <p className="text-sm text-neutral-400 mt-0.5">{assignments.length} {assignments.length === 1 ? 'asignación' : 'asignaciones'} encontradas</p>
              </div>
            </div>

            {loadingAssignments && (
              <div className="text-sm text-neutral-400 py-8 text-center">Cargando materias...</div>
            )}

            {!loadingAssignments && assignments.length === 0 && (
              <div className="py-16 border-2 border-dashed border-neutral-800 rounded-2xl text-center">
                <span className="material-symbols-outlined text-4xl text-neutral-700 mb-3 block">school</span>
                <p className="text-neutral-400 font-semibold">No tienes materias asignadas</p>
                <p className="text-xs text-neutral-600 mt-1">El jefe de carrera te asignará materias próximamente</p>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              {assignments.map((item) => (
                <div key={item.id_dm} className="bg-[#131313] rounded-2xl border border-neutral-800 p-4 hover:border-rose-500/40 transition-all group">
                  <div className="flex items-start justify-between mb-3">
                    <span className="px-2 py-1 rounded-lg text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/20">
                      {item.modulo?.nombre || 'Módulo'}
                    </span>
                    <span className="px-2 py-1 rounded-lg text-[10px] font-bold bg-neutral-800 text-neutral-400">
                      {item.estudiantes_count ?? 0} estudiantes
                    </span>
                  </div>

                  <h3 className="text-white font-bold text-sm mb-2 group-hover:text-rose-300 transition-colors">
                    {item.materia?.nombre || 'Materia sin nombre'}
                  </h3>

                  <p className="text-xs text-neutral-500 mb-4">
                    {item.modulo?.fecha_inicio} — {item.modulo?.fecha_final}
                  </p>

                  <button
                    onClick={() => showStudents(item.id_dm)}
                    className="w-full py-2 rounded-xl text-xs font-bold bg-neutral-800 text-neutral-300 hover:bg-rose-500/20 hover:text-rose-300 hover:border-rose-500/30 border border-neutral-700 transition-all"
                  >
                    Ver estudiantes
                  </button>
                </div>
              ))}
            </div>
          </div>
        </div>
      </main>

      {/* Modal Estudiantes */}
      {studentsModal && (
        <div
          className="fixed inset-0 z-[100] bg-black/75 backdrop-blur-sm flex items-center justify-center p-6"
          onClick={(e) => { if (e.target === e.currentTarget) setStudentsModal(null); }}
        >
          <div className="w-full max-w-lg rounded-2xl border border-neutral-800 bg-[#101010] flex flex-col max-h-[80vh]">
            <div className="flex items-center justify-between p-5 border-b border-neutral-800">
              <div>
                <h3 className="text-white font-bold text-lg">{studentsModal.title}</h3>
                <p className="text-xs text-neutral-400 mt-0.5">{studentsModal.meta}</p>
              </div>
              <div className="flex gap-2">
                <button
                  onClick={openEditModal}
                  className="px-3 py-1.5 rounded-lg bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 text-sm font-semibold hover:bg-indigo-500/30 transition-all"
                >
                  Modificar
                </button>
                <button onClick={() => setStudentsModal(null)} className="px-3 py-1.5 rounded-lg bg-rose-500/20 border border-rose-500/40 text-rose-300 text-sm">Cerrar</button>
              </div>
            </div>
            <div className="p-4 overflow-y-auto flex-1">
              {loadingStudents && <p className="text-sm text-neutral-400 text-center py-8">Cargando...</p>}
              {!loadingStudents && studentsModal.students.length === 0 && (
                <p className="text-sm text-neutral-500 text-center py-8">No hay estudiantes inscritos.</p>
              )}
              <div className="space-y-2">
                {studentsModal.students.map((s, idx) => (
                  <div key={idx} className="rounded-xl border border-neutral-800 bg-neutral-950 p-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-semibold text-neutral-100">{s.nombre || 'Sin nombre'}</p>
                        <p className="text-xs text-neutral-400">{s.correo}</p>
                      </div>
                      <span className={`px-2 py-1 rounded-md text-[10px] font-bold border ${STATUS_STYLES[s.estado] || STATUS_STYLES.pendiente}`}>
                        {s.estado}
                      </span>
                    </div>
                    <p className="text-[10px] text-neutral-600 mt-1">ID: {s.id_estudiante}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
      {/* Modal Modificar Estados */}
      {editModal && studentsModal && (
        <div
          className="fixed inset-0 z-[110] bg-black/80 backdrop-blur-sm flex items-center justify-center p-6"
          onClick={(e) => { if (e.target === e.currentTarget) setEditModal(false); }}
        >
          <div className="w-full max-w-xl rounded-2xl border border-neutral-800 bg-[#101010] flex flex-col max-h-[85vh]">
            <div className="flex items-center justify-between p-5 border-b border-neutral-800">
              <div>
                <h3 className="text-white font-bold text-lg">Modificar estados</h3>
                <p className="text-xs text-neutral-400 mt-0.5">{studentsModal.title}</p>
              </div>
              <button onClick={() => setEditModal(false)} className="px-3 py-1.5 rounded-lg bg-neutral-800 border border-neutral-700 text-neutral-300 text-sm">Cerrar</button>
            </div>

            {/* Controls */}
            <div className="p-4 border-b border-neutral-800 flex flex-wrap gap-3 items-center">
              <div className="flex items-center gap-2">
                <label className="text-xs text-neutral-400 font-semibold">Estado a aplicar:</label>
                <select
                  value={bulkStatus}
                  onChange={(e) => setBulkStatus(e.target.value)}
                  className="bg-neutral-900 border border-neutral-700 text-white rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-500"
                >
                  <option value="aprobada">Aprobada</option>
                  <option value="reprobada">Reprobada</option>
                  <option value="cursando">Cursando</option>
                  <option value="pendiente">Pendiente</option>
                </select>
              </div>
              <button onClick={selectAll} className="px-3 py-1.5 rounded-lg text-xs font-bold bg-neutral-800 text-neutral-300 border border-neutral-700 hover:border-neutral-600 transition-all">
                Seleccionar todos
              </button>
              <button onClick={clearSelection} className="px-3 py-1.5 rounded-lg text-xs font-bold bg-neutral-800 text-neutral-300 border border-neutral-700 hover:border-neutral-600 transition-all">
                Limpiar
              </button>
              {Object.keys(selectedStudents).length > 0 && (
                <button onClick={applyBulkStatus} className="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 hover:bg-indigo-500/30 transition-all">
                  Aplicar estado a seleccionados
                </button>
              )}
            </div>

            {/* Student list */}
            <div className="p-4 overflow-y-auto flex-1 space-y-2">
              {studentsModal.students.map((s) => {
                const isSelected = selectedStudents[s.id_inscripcion] !== undefined;
                const displayEstado = selectedStudents[s.id_inscripcion] ?? s.estado;
                return (
                  <div
                    key={s.id_inscripcion}
                    onClick={() => toggleSelectStudent(s.id_inscripcion)}
                    className={`rounded-xl border p-3 cursor-pointer transition-all ${
                      isSelected
                        ? 'border-indigo-500/60 bg-indigo-500/10'
                        : 'border-neutral-800 bg-neutral-950 hover:border-neutral-700'
                    }`}
                  >
                    <div className="flex items-center justify-between gap-3">
                      <div className="flex items-center gap-3">
                        <div className={`w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-all ${
                          isSelected ? 'border-indigo-500 bg-indigo-500' : 'border-neutral-600'
                        }`}>
                          {isSelected && <span className="text-white text-xs font-black">✓</span>}
                        </div>
                        <div>
                          <p className="text-sm font-semibold text-neutral-100">{s.nombre || 'Sin nombre'}</p>
                          <p className="text-xs text-neutral-500">{s.correo}</p>
                        </div>
                      </div>
                      <span className={`px-2 py-1 rounded-md text-[10px] font-bold border ${STATUS_STYLES[displayEstado] || STATUS_STYLES.pendiente}`}>
                        {displayEstado}
                      </span>
                    </div>
                  </div>
                );
              })}
              {studentsModal.students.length === 0 && (
                <p className="text-sm text-neutral-500 text-center py-8">No hay estudiantes inscritos.</p>
              )}
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-neutral-800 flex items-center justify-between gap-3">
              {statusFeedback && (
                <p className={`text-xs ${statusFeedback.includes('Error') ? 'text-red-400' : 'text-emerald-400'}`}>
                  {statusFeedback}
                </p>
              )}
              {!statusFeedback && <span className="text-xs text-neutral-500">{Object.keys(selectedStudents).length} seleccionado(s)</span>}
              <button
                onClick={saveStatusChanges}
                disabled={savingStatus || Object.keys(selectedStudents).length === 0}
                className="px-5 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-indigo-600 to-violet-500 text-white hover:brightness-110 disabled:opacity-40 transition-all"
              >
                {savingStatus ? 'Guardando...' : 'Guardar cambios'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Perfil */}
      {showProfile && (
        <ProfileModal onClose={() => setShowProfile(false)} />
      )}
    </div>
  );
}