import { useEffect, useState } from 'react';
import axios from 'axios';
import { useAuth } from '../AuthContext';
import { useNavigate } from 'react-router-dom';
import ProfileModal from '../components/ProfileModal';

const ESTADO_STYLES = {
  cursando:  'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
  pendiente: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
  aprobada:  'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
  reprobada: 'bg-red-500/20 text-red-300 border-red-500/30',
  bloqueada: 'bg-neutral-500/20 text-neutral-300 border-neutral-500/30',
  incompleta:'bg-orange-500/20 text-orange-300 border-orange-500/30',
};

const BLOQUE_COLORS = ['from-rose-500 to-pink-400','from-orange-500 to-amber-400','from-yellow-500 to-lime-400','from-teal-500 to-cyan-400','from-blue-500 to-violet-400','from-purple-500 to-fuchsia-400'];

const CREDIT_LIMIT = 40;

export default function StudentDashboard() {
  const { user, dashboardData, logout, refreshDashboard } = useAuth();
  const navigate = useNavigate();

  const inscripciones = dashboardData?.inscripciones || [];
  const totalCredits  = dashboardData?.totalCredits || 0;
  const horario       = dashboardData?.horario || null;
  const mallaCursada  = dashboardData?.mallaCursada || [];

  const [generando, setGenerando] = useState(false);
  const [horarioFeedback, setHorarioFeedback] = useState('');
  const [activeTab, setActiveTab] = useState('materias'); // 'materias' | 'horario'
  const [showProfile, setShowProfile] = useState(false);
  const [showMallaCursada, setShowMallaCursada] = useState(false);
  const [sugerencias, setSugerencias] = useState([]);
  const [cargandoSugerencias, setCargandoSugerencias] = useState(false);
  const [sugerenciasError, setSugerenciasError] = useState('');

  const handleGenerarSugerencia = async () => {
    setGenerando(true);
    setHorarioFeedback('');
    try {
      await cargarSugerencias();
      setHorarioFeedback('Sugerencias de horario actualizadas.');
      setActiveTab('horario');
    } catch (err) {
      setHorarioFeedback(err.response?.data?.message || 'Error al generar sugerencias.');
    } finally {
      setGenerando(false);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const cargarSugerencias = async () => {
    setCargandoSugerencias(true);
    setSugerenciasError('');
    try {
      const res = await axios.get('/portal/api/estudiante/horario/sugerencias', { params: { top: 3 } });
      setSugerencias(res.data?.data?.suggestions || []);
    } catch (err) {
      setSugerencias([]);
      setSugerenciasError(err.response?.data?.message || 'No se pudo cargar sugerencias de horario.');
    } finally {
      setCargandoSugerencias(false);
    }
  };

  useEffect(() => {
    if (activeTab === 'horario') {
      cargarSugerencias();
    }
  }, [activeTab]);

  const creditPct = Math.min((totalCredits / CREDIT_LIMIT) * 100, 100);
  const creditOver = totalCredits > CREDIT_LIMIT;

  return (
    <div className="bg-black text-[#f6dddc] min-h-screen overflow-hidden">
      {/* Sidebar */}
      <aside className="fixed left-0 top-0 h-screen w-72 border-r border-neutral-800 bg-neutral-950 z-50 flex flex-col p-6 shadow-2xl shadow-rose-900/10">
        <div className="mb-10">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center font-black text-white text-lg">R</div>
            <div>
              <span className="text-2xl font-black bg-gradient-to-br from-rose-500 to-orange-400 bg-clip-text text-transparent">Rosie</span>
              <p className="text-xs text-neutral-500 mt-0.5">Portal Estudiante</p>
            </div>
          </div>
        </div>

        <nav className="flex-1 flex flex-col gap-2">
          <button
            onClick={() => setActiveTab('materias')}
            className={`flex items-center gap-3 rounded-xl py-3 px-4 text-left transition-all ${activeTab === 'materias' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-900/50'}`}
          >
            <span className="material-symbols-outlined text-rose-400">menu_book</span>
            <span className="font-semibold text-sm">Mis Materias</span>
          </button>
          <button
            onClick={() => setActiveTab('horario')}
            className={`flex items-center gap-3 rounded-xl py-3 px-4 text-left transition-all ${activeTab === 'horario' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-900/50'}`}
          >
            <span className="material-symbols-outlined text-neutral-400">calendar_month</span>
            <span className="font-semibold text-sm">Mi Horario</span>
          </button>
        </nav>

        {/* Credits progress in sidebar */}
        <div className="mb-6 bg-neutral-900 rounded-2xl p-4 border border-neutral-800">
          <div className="flex items-center justify-between mb-2">
            <p className="text-[10px] text-neutral-500 uppercase font-bold">Créditos</p>
            <span className={`text-sm font-black ${creditOver ? 'text-red-400' : 'text-white'}`}>{totalCredits}/{CREDIT_LIMIT}</span>
          </div>
          <div className="w-full h-2 bg-neutral-800 rounded-full overflow-hidden">
            <div
              className={`h-full rounded-full transition-all ${creditOver ? 'bg-red-500' : 'bg-gradient-to-r from-rose-500 to-orange-400'}`}
              style={{ width: `${creditPct}%` }}
            />
          </div>
          {creditOver && <p className="text-[10px] text-red-400 mt-1 font-semibold">Límite excedido</p>}
        </div>

        <div className="pt-4 border-t border-neutral-800">
          <div className="mb-4 px-2">
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
            <h1 className="font-bold text-lg text-white">Panel Estudiante</h1>
            <p className="text-xs text-neutral-500">Consulta tus materias y horario asignado</p>
          </div>
          <div className="flex items-center gap-3">
            <span className="px-3 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">Estudiante</span>
            {user?.es_traspaso && (
              <span className="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">Traspaso</span>
            )}
          </div>
        </header>

        <div className="mt-16 p-8 overflow-y-auto h-[calc(100vh-64px)] space-y-6">
          {/* Profile cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-neutral-900/40 rounded-2xl border border-neutral-800 p-5 flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center flex-shrink-0">
                <span className="material-symbols-outlined text-white text-xl">person</span>
              </div>
              <div>
                <p className="text-[10px] text-neutral-500 uppercase font-bold mb-0.5">Estudiante</p>
                <p className="text-white font-bold text-sm leading-tight">{user?.name}</p>
                <p className="text-xs text-neutral-400 truncate">{user?.email}</p>
              </div>
            </div>

            <div className="bg-neutral-900/40 rounded-2xl border border-neutral-800 p-5">
              <p className="text-[10px] text-neutral-500 uppercase font-bold mb-2">Materias activas</p>
              <p className="text-4xl font-black text-white">{inscripciones.length}</p>
              <p className="text-xs text-neutral-500 mt-1">inscripciones en curso</p>
            </div>

            <div className={`rounded-2xl border p-5 ${creditOver ? 'border-red-500/40 bg-red-500/5' : 'bg-neutral-900/40 border-neutral-800'}`}>
              <p className="text-[10px] text-neutral-500 uppercase font-bold mb-2">Total créditos</p>
              <p className={`text-4xl font-black ${creditOver ? 'text-red-400' : 'text-white'}`}>{totalCredits}<span className="text-xl text-neutral-500">/{CREDIT_LIMIT}</span></p>
              {creditOver && <p className="text-xs text-red-400 mt-1 font-semibold">Excede el límite semestral</p>}
            </div>
          </div>

          {/* Tabs content */}
          {activeTab === 'materias' && (
            <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h2 className="text-xl font-bold text-white">Mis Inscripciones Actuales</h2>
                  <p className="text-sm text-neutral-400 mt-0.5">{inscripciones.length} materias registradas</p>
                </div>
                <button
                  onClick={() => setShowMallaCursada(true)}
                  className="px-4 py-2 rounded-xl border border-neutral-700 text-xs font-semibold text-neutral-200 hover:border-cyan-500/60"
                >
                  Ver malla cursada
                </button>
              </div>

              {inscripciones.length === 0 && (
                <div className="py-16 border-2 border-dashed border-neutral-800 rounded-2xl text-center">
                  <span className="material-symbols-outlined text-4xl text-neutral-700 mb-3 block">menu_book</span>
                  <p className="text-neutral-400 font-semibold">No tienes inscripciones activas</p>
                  <p className="text-xs text-neutral-600 mt-1">El jefe de carrera te inscribirá en materias</p>
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                {inscripciones.map((item) => (
                  <div key={item.id_inscripcion} className="bg-[#131313] rounded-2xl border border-neutral-800 p-4 hover:border-rose-500/40 transition-all">
                    <div className="flex items-start justify-between mb-3">
                      <span className="px-2 py-1 rounded-lg text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/20">
                        {item.modulo?.nombre || 'Módulo'}
                      </span>
                      <span className={`px-2 py-1 rounded-md text-[10px] font-bold border ${ESTADO_STYLES[item.estado] || ESTADO_STYLES.pendiente}`}>
                        {item.estado}
                      </span>
                    </div>
                    <h3 className="text-white font-bold text-sm mb-2">{item.materia || 'Sin nombre'}</h3>
                    <p className="text-xs text-neutral-500 mb-1">{item.modulo?.fecha_inicio} — {item.modulo?.fecha_final}</p>
                    <div className="flex items-center gap-2 mt-3">
                      <span className="material-symbols-outlined text-sm text-neutral-600">grade</span>
                      <span className="text-xs text-neutral-400">{item.modulo?.creditos ?? '?'} créditos</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'horario' && (
            <div className="space-y-6">
              {/* Generate button */}
              <div className="bg-neutral-900/40 rounded-[24px] border border-neutral-800 p-6">
                <div className="flex items-center justify-between flex-wrap gap-4">
                  <div>
                    <h2 className="text-xl font-bold text-white">Sugerencia de Horario</h2>
                    <p className="text-sm text-neutral-400 mt-1">
                      Genera una sugerencia con materias habilitadas (sin cruces de bloque). Max. {CREDIT_LIMIT} creditos.
                    </p>
                    {horarioFeedback && (
                      <p className={`text-sm mt-2 font-semibold ${horarioFeedback.includes('Error') || horarioFeedback.includes('error') ? 'text-red-400' : 'text-emerald-400'}`}>
                        {horarioFeedback}
                      </p>
                    )}
                  </div>
                  <button
                    onClick={handleGenerarSugerencia}
                    disabled={generando}
                    className="px-8 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-rose-600 to-orange-500 hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-rose-900/30"
                  >
                    {generando ? 'Generando...' : 'Generar sugerencia'}
                  </button>
                </div>
                {creditOver && (
                  <div className="mt-4 px-4 py-3 rounded-xl border border-red-500/40 bg-red-500/10 text-red-300 text-sm">
                    Excediste el límite de {CREDIT_LIMIT} creditos. Contacta al jefe de carrera para ajustar tus inscripciones.
                  </div>
                )}
              </div>

              {/* Schedule display */}
              {!horario ? (
                <div className="py-20 border-2 border-dashed border-neutral-800 rounded-2xl text-center">
                  <span className="material-symbols-outlined text-5xl text-neutral-700 mb-4 block">calendar_month</span>
                  <p className="text-neutral-400 font-semibold text-lg">Aun no generaste sugerencias</p>
                  <p className="text-xs text-neutral-600 mt-2">Haz clic en el boton superior para sugerir materias habilitadas</p>
                </div>
              ) : (
                <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6">
                  <div className="flex items-center justify-between mb-6">
                    <div>
                      <h3 className="text-lg font-bold text-white">Horario Confirmado</h3>
                      <span className="px-2 py-1 rounded-md text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        {horario.estado}
                      </span>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    {(horario.detalles || []).map((det, idx) => {
                      const colorClass = BLOQUE_COLORS[idx % BLOQUE_COLORS.length];
                      return (
                        <div key={idx} className="bg-[#131313] rounded-2xl border border-neutral-800 p-4 hover:border-neutral-700 transition-all">
                          <div className="flex items-center gap-2 mb-3">
                            <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${colorClass} flex items-center justify-center flex-shrink-0`}>
                              <span className="text-white font-black text-xs">{det.bloque?.nombre}</span>
                            </div>
                            <div>
                              <p className="text-[10px] font-bold text-neutral-400">Bloque {det.bloque?.nombre}</p>
                              <p className="text-[10px] text-neutral-500">{det.bloque?.hora_inicio?.substring(0,5)} – {det.bloque?.hora_fin?.substring(0,5)}</p>
                            </div>
                          </div>

                          <h4 className="text-white font-bold text-sm mb-2">{det.materia?.nombre}</h4>

                          <div className="space-y-1 text-xs text-neutral-400">
                            <div className="flex items-center gap-1.5">
                              <span className="material-symbols-outlined text-xs text-neutral-600">person</span>
                              <span>{det.docente?.nombre} {det.docente?.apellido}</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                              <span className="material-symbols-outlined text-xs text-neutral-600">room</span>
                              <span>{det.aula?.nombre || 'Aula por asignar'}</span>
                            </div>
                            {det.modulo_info && (
                              <div className="flex items-center gap-1.5">
                                <span className="material-symbols-outlined text-xs text-neutral-600">calendar_today</span>
                                <span>{det.modulo_info.nombre}</span>
                              </div>
                            )}
                          </div>
                        </div>
                      );
                    })}

                    {(horario.detalles || []).length === 0 && (
                      <div className="col-span-3 text-center py-8 text-neutral-500">
                        El horario fue generado pero no tiene materias con bloque asignado aún.
                      </div>
                    )}
                  </div>
                </div>
              )}

              <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6">
                <h3 className="text-lg font-bold text-white mb-4">Horario Sugerido</h3>

                {cargandoSugerencias && (
                  <p className="text-sm text-neutral-400">Cargando sugerencias...</p>
                )}

                {!cargandoSugerencias && sugerenciasError && (
                  <p className="text-sm text-red-400">{sugerenciasError}</p>
                )}

                {!cargandoSugerencias && !sugerenciasError && sugerencias.length === 0 && (
                  <p className="text-sm text-neutral-500">No hay sugerencias disponibles con tus materias habilitadas.</p>
                )}

                <div className="space-y-4">
                  {sugerencias.map((s, idx) => (
                    <div key={`sug-${idx}`} className="rounded-xl border border-neutral-800 bg-[#131313] p-4">
                      <div className="flex items-center justify-between mb-3">
                        <p className="text-sm font-bold text-neutral-100">Sugerencia #{idx + 1}</p>
                        <p className="text-xs text-neutral-400">{s.subjects_count} materias · {s.total_credits} créditos</p>
                      </div>
                      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        {(s.items || []).map((it) => (
                          <div key={`sug-${idx}-${it.id_dm}`} className="rounded-lg border border-neutral-800 bg-neutral-950 p-3">
                            <div className="text-sm font-semibold text-white">{it.materia?.nombre}</div>
                            <div className="text-xs text-neutral-400 mt-1">{it.docente?.nombre}</div>
                            <div className="text-xs text-cyan-300 mt-1">
                              Bloque {it.bloque?.nombre} ({String(it.bloque?.hora_inicio || '').slice(0, 5)} - {String(it.bloque?.hora_fin || '').slice(0, 5)})
                            </div>
                            <div className="text-xs text-neutral-500 mt-1">{it.aula?.nombre || 'Sin aula'}</div>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
        </div>
      </main>
      {showProfile && (
        <ProfileModal onClose={() => setShowProfile(false)} />
      )}

      {showMallaCursada && (
        <div className="fixed inset-0 z-[120] bg-black/75 backdrop-blur-sm p-6">
          <div className="w-full max-w-4xl mx-auto rounded-2xl border border-neutral-800 bg-[#101010] flex flex-col max-h-[85vh]">
            <div className="flex items-center justify-between p-4 border-b border-neutral-800">
              <div>
                <h3 className="text-white text-lg font-bold">Malla Curricular Cursada</h3>
                <p className="text-xs text-neutral-400">{mallaCursada.length} materias cursadas</p>
              </div>
              <button onClick={() => setShowMallaCursada(false)} className="px-3 py-1.5 rounded-md bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm">Cerrar</button>
            </div>
            <div className="p-4 overflow-y-auto">
              {mallaCursada.length === 0 ? (
                <div className="text-sm text-neutral-400">No hay materias cursadas registradas para este estudiante.</div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {mallaCursada.map((m) => (
                    <div key={m.id_materia} className="rounded-lg border border-neutral-800 bg-neutral-950 p-3">
                      <div className="text-sm font-semibold text-neutral-100">{m.nombre}</div>
                      <div className="text-xs text-neutral-400 mt-1">Año {m.anio_academico || '-'} · Semestre {m.semestre_academico || '-'}</div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}


