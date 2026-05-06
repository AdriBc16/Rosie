import { useEffect, useState } from 'react';
import axios from 'axios';
import { useAuth } from '../AuthContext';
import { useNavigate } from 'react-router-dom';
import ProfileModal from '../components/ProfileModal';

const BLOQUE_COLORS = {
  A: 'from-rose-500 to-pink-400',
  B: 'from-orange-500 to-amber-400',
  C: 'from-yellow-500 to-lime-400',
  D: 'from-teal-500 to-cyan-400',
  E: 'from-blue-500 to-violet-400',
  F: 'from-purple-500 to-fuchsia-400',
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

  const [studentsModal, setStudentsModal] = useState(null); // { title, meta, students }
  const [loadingStudents, setLoadingStudents] = useState(false);
  const [showProfile, setShowProfile] = useState(false);

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

  const loadDisponibilidad = async () => {
    try {
      const res = await axios.get('/portal/api/docente/disponibilidad');
      setAllBlocks(res.data.bloques || []);
      setMyBlocks(res.data.misBloques || []);
    } catch (err) {
      console.error('Error cargando disponibilidad', err);
    }
  };

  const toggleBlock = (id) => {
    setMyBlocks((prev) => prev.includes(id) ? prev.filter((b) => b !== id) : [...prev, id]);
  };

  const saveDisponibilidad = async () => {
    setSavingDisp(true);
    setDispFeedback('');
    try {
      await axios.post('/portal/api/docente/disponibilidad', { bloques: myBlocks });
      setDispFeedback('Disponibilidad guardada correctamente.');
    } catch (err) {
      setDispFeedback('Error: ' + (err.response?.data?.message || err.message));
    } finally {
      setSavingDisp(false);
    }
  };

  const showStudents = async (idDm) => {
    setStudentsModal({ title: 'Cargando...', meta: '', students: [] });
    setLoadingStudents(true);
    try {
      const res = await axios.get(`/portal/api/docente/materias/${idDm}/estudiantes`);
      const { asignacion, estudiantes } = res.data.data;
      setStudentsModal({
        title: asignacion.materia || 'Materia',
        meta: `Módulo ${asignacion.modulo || '?'} · ${asignacion.fecha_inicio || '--'} → ${asignacion.fecha_fin || '--'}`,
        students: estudiantes || [],
      });
    } catch (err) {
      setStudentsModal({ title: 'Error', meta: err.response?.data?.message || err.message, students: [] });
    } finally {
      setLoadingStudents(false);
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
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center font-black text-white text-lg">R</div>
            <div>
              <span className="text-2xl font-black bg-gradient-to-br from-rose-500 to-orange-400 bg-clip-text text-transparent">Rosie</span>
              <p className="text-xs text-neutral-500 mt-0.5">Portal Docente</p>
            </div>
          </div>
        </div>

        <nav className="flex-1 flex flex-col gap-2">
          <a className="flex items-center gap-3 bg-neutral-900 text-white rounded-xl py-3 px-4 border-l-4 border-rose-500" href="#">
            <span className="material-symbols-outlined text-rose-400">dashboard</span>
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
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-bold text-white">Mis Horarios Disponibles</h2>
                <button
                  onClick={saveDisponibilidad}
                  disabled={savingDisp}
                  className="px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-rose-600 to-orange-500 text-white hover:brightness-110 disabled:opacity-50 transition-all"
                >
                  {savingDisp ? 'Guardando...' : 'Guardar'}
                </button>
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
              <button onClick={() => setStudentsModal(null)} className="px-3 py-1.5 rounded-lg bg-rose-500/20 border border-rose-500/40 text-rose-300 text-sm">Cerrar</button>
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
      {/* Modal de Perfil */}
      {showProfile && (
        <ProfileModal onClose={() => setShowProfile(false)} />
      )}
    </div>
  );
}