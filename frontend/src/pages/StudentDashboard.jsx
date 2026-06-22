import { useEffect, useState } from 'react';
import axios from 'axios';
import { useAuth } from '../AuthContext';
import { useNavigate } from 'react-router-dom';
import ProfileModal from '../components/ProfileModal';
import logoRosie from '../assets/Rosie1.png';

const ESTADO_STYLES = {
  cursando:  'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
  pendiente: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
  aprobada:  'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
  reprobada: 'bg-red-500/20 text-red-300 border-red-500/30',
  bloqueada: 'bg-neutral-500/20 text-neutral-300 border-neutral-500/30',
  incompleta:'bg-orange-500/20 text-orange-300 border-orange-500/30',
};

const BLOQUE_COLORS = ['from-indigo-600 to-violet-500', 'from-sky-600 to-cyan-500', 'from-emerald-600 to-teal-500', 'from-cyan-600 to-blue-500', 'from-blue-600 to-indigo-500', 'from-violet-600 to-fuchsia-500'];

const CREDIT_LIMIT = 29;

export default function StudentDashboard() {
  const { user, dashboardData, logout, refreshDashboard } = useAuth();
  const navigate = useNavigate();

  const semestresData     = dashboardData?.semestresData || [];
  const currentSemestreId = dashboardData?.currentSemestreId || null;
  const horario           = dashboardData?.horario || null;
  const mallaCursada      = dashboardData?.mallaCursada || [];

  const [selectedSemestreId, setSelectedSemestreId] = useState(null);

  useEffect(() => {
    if (currentSemestreId && selectedSemestreId === null) {
      setSelectedSemestreId(currentSemestreId);
    }
  }, [currentSemestreId]);

  const selectedSemestreData = semestresData.find(s => s.id_semestre === selectedSemestreId) || semestresData[0] || null;
  const inscripciones = selectedSemestreData?.inscripciones || [];
  const totalCredits  = selectedSemestreData?.totalCredits || 0;

  const [generando, setGenerando] = useState(false);
  const [horarioFeedback, setHorarioFeedback] = useState('');
  const [activeTab, setActiveTab] = useState('materias'); 
  const [showProfile, setShowProfile] = useState(false);
  const [showMallaCursada, setShowMallaCursada] = useState(false);
  
  const [opciones, setOpciones] = useState([]);
  const [cargandoOpciones, setCargandoOpciones] = useState(false);
  const [opcionesError, setOpcionesError] = useState('');
  const [confirmando, setConfirmando] = useState(false);

  const handleGenerarOpciones = async () => {
    setGenerando(true);
    setHorarioFeedback('');
    try {
      await cargarOpciones();
      setHorarioFeedback('Sugerencias de horario generadas.');
      setActiveTab('horario');
    } catch (err) {
      setHorarioFeedback('Error al generar opciones.');
    } finally {
      setGenerando(false);
    }
  };

  const cargarOpciones = async () => {
    setCargandoOpciones(true);
    setOpcionesError('');
    try {
      const res = await axios.get('/portal/api/estudiante/horario/sugerencias');
      setOpciones(res.data?.data?.opciones || []);
    } catch (err) {
      setOpciones([]);
      setOpcionesError(err.response?.data?.message || 'No se pudieron cargar opciones.');
    } finally {
      setCargandoOpciones(false);
    }
  };

  const handleConfirmarOpcion = async (opcion) => {
    if (!window.confirm(`¿Estás seguro de elegir la ${opcion.label}? Esto fijará el horario de tus materias.`)) return;
    
    setConfirmando(true);
    try {
      await axios.post('/portal/api/estudiante/horario/confirmar', {
        items: opcion.items
      });
      setHorarioFeedback('¡Horario confirmado con éxito!');
      setOpciones([]);
      refreshDashboard(); // Recargar datos para ver el horario confirmado
    } catch (err) {
      alert(err.response?.data?.message || 'Error al confirmar horario.');
    } finally {
      setConfirmando(false);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const creditPct = Math.min((totalCredits / CREDIT_LIMIT) * 100, 100);
  const creditOver = totalCredits > CREDIT_LIMIT;

  return (
    <div className="bg-black text-[#f6dddc] min-h-screen overflow-hidden">
      {/* Sidebar */}
      <aside className="fixed left-0 top-0 h-screen w-72 border-r border-neutral-800 bg-neutral-950 z-50 flex flex-col p-6 shadow-2xl shadow-rose-900/10">
        <div className="mb-10">
          <div className="flex items-center gap-3">
            <img src={logoRosie} alt="Logo" className="w-12 h-12 object-contain" />
            <div>
              <span className="text-2xl font-black bg-gradient-to-br from-rose-500 to-orange-400 bg-clip-text text-transparent">Rosie</span>
              <p className="text-xs text-neutral-500 mt-0.5">Portal Estudiante</p>
            </div>
          </div>
        </div>

        <nav className="flex-1 flex flex-col gap-2">
          <button
            onClick={() => setActiveTab('materias')}
            className={`flex items-center gap-3 rounded-xl py-3 px-4 text-left transition-all ${activeTab === 'materias' ? 'bg-indigo-500/10 text-indigo-300 border-l-4 border-indigo-500' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-900/50'}`}
          >
            <span className={`material-symbols-outlined ${activeTab === 'materias' ? 'text-indigo-400' : 'text-neutral-400'}`}>menu_book</span>
            <span className="font-semibold text-sm">Mis Materias</span>
          </button>
          <button
            onClick={() => setActiveTab('horario')}
            className={`flex items-center gap-3 rounded-xl py-3 px-4 text-left transition-all ${activeTab === 'horario' ? 'bg-indigo-500/10 text-indigo-300 border-l-4 border-indigo-500' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-900/50'}`}
          >
            <span className={`material-symbols-outlined ${activeTab === 'horario' ? 'text-indigo-400' : 'text-neutral-400'}`}>calendar_month</span>
            <span className="font-semibold text-sm">Mi Horario</span>
          </button>
        </nav>

        {/* Credits progress in sidebar */}
        <div className="mb-6 bg-neutral-900 rounded-2xl p-4 border border-neutral-800">
          <div className="flex items-center justify-between mb-1">
            <p className="text-[10px] text-neutral-500 uppercase font-bold">Créditos</p>
            <span className={`text-sm font-black ${creditOver ? 'text-red-400' : 'text-white'}`}>{totalCredits}/{CREDIT_LIMIT}</span>
          </div>
          {selectedSemestreData && inscripciones.length > 0 && (
            <p className="text-[9px] text-neutral-600 mb-2 truncate">{selectedSemestreData.nombre}</p>
          )}
          <div className="w-full h-2 bg-neutral-800 rounded-full overflow-hidden">
            <div
              className={`h-full rounded-full transition-all ${creditOver ? 'bg-red-500' : 'bg-gradient-to-r from-rose-500 to-orange-400'}`}
              style={{ width: `${creditPct}%` }}
            />
          </div>
          {semestresData.length > 1 && (
            <select
              value={selectedSemestreId || ''}
              onChange={(e) => setSelectedSemestreId(Number(e.target.value))}
              className="mt-3 w-full bg-neutral-950 border border-neutral-800 rounded-lg text-[10px] text-neutral-400 px-2 py-1.5 focus:outline-none focus:border-rose-500"
            >
              {semestresData.map(s => (
                <option key={s.id_semestre} value={s.id_semestre}>
                  {s.nombre}
                </option>
              ))}
            </select>
          )}
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
            onClick={() => handleLogout()}
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
          </div>
        </header>

        <div className="mt-16 p-8 overflow-y-auto h-[calc(100vh-64px)] space-y-6">
          {/* Top Compact Section */}
          <div className="flex items-center justify-between bg-neutral-900/40 rounded-2xl border border-neutral-800 p-4 mb-4">
            <div className="flex items-center gap-4">
              <div className="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center border border-rose-500/20">
                <span className="material-symbols-outlined text-rose-400">auto_awesome</span>
              </div>
              <div>
                <h2 className="text-base font-bold text-white">Generar Opciones de Horario</h2>
                <p className="text-[10px] text-neutral-500">Te daremos 3 combinaciones posibles basadas en tus inscripciones.</p>
              </div>
            </div>
            
            <div className="flex items-center gap-4">
              {horarioFeedback && (
                <span className={`text-[10px] font-bold px-2 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20`}>
                  {horarioFeedback}
                </span>
              )}
              <button
                onClick={handleGenerarOpciones}
                disabled={generando}
                className="px-6 py-2 rounded-xl font-bold text-white text-xs bg-gradient-to-r from-rose-600 to-orange-500 hover:brightness-110 disabled:opacity-50 transition-all shadow-lg shadow-rose-900/20"
              >
                {generando ? '...' : 'Generar 3 opciones'}
              </button>
            </div>
          </div>

          {/* Tabs content */}
          {activeTab === 'materias' && (
            <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h2 className="text-xl font-bold text-white">Mis Inscripciones</h2>
                  {selectedSemestreData && inscripciones.length > 0 && (
                    <p className="text-xs text-neutral-500 mt-0.5">
                      {selectedSemestreData.nombre}
                      {selectedSemestreData.fecha_inicio ? ` · desde ${new Date(selectedSemestreData.fecha_inicio + 'T00:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })}` : ''}
                    </p>
                  )}
                </div>
                {semestresData.length > 1 && (
                  <select
                    value={selectedSemestreId || ''}
                    onChange={(e) => setSelectedSemestreId(Number(e.target.value))}
                    className="bg-neutral-900 border border-neutral-800 rounded-xl text-xs text-neutral-300 px-3 py-2 focus:outline-none focus:border-rose-500"
                  >
                    {semestresData.map(s => (
                      <option key={s.id_semestre} value={s.id_semestre}>
                        {s.nombre}
                      </option>
                    ))}
                  </select>
                )}
              </div>
              {inscripciones.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                  <span className="material-symbols-outlined text-4xl text-neutral-700 mb-3">menu_book</span>
                  <p className="text-neutral-500 text-sm">No tienes materias inscritas en este semestre.</p>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                  {inscripciones.map((item) => (
                    <div key={item.id_inscripcion} className="bg-[#131313] rounded-2xl border border-neutral-800 p-4">
                      <h3 className="text-white font-bold text-sm mb-2">{item.materia || 'Sin nombre'}</h3>
                      <div className="flex items-center gap-2 mt-3">
                        <span className="material-symbols-outlined text-sm text-neutral-600">grade</span>
                        <span className="text-xs text-neutral-400">{item.modulo?.creditos_materia ?? '3'} créditos</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {activeTab === 'horario' && (
            <div className="space-y-8">

              {/* Error banner */}
              {opcionesError && (
                <div className="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 flex items-center gap-3">
                  <span className="material-symbols-outlined text-red-400">error</span>
                  <p className="text-sm text-red-400 font-semibold">{opcionesError}</p>
                </div>
              )}

              {/* 3 Options Section — shown prominently when options are generated */}
              {opciones.length > 0 && (
                <div className="space-y-6">
                  <div className="flex items-center gap-3">
                    <span className="material-symbols-outlined text-rose-400">auto_awesome</span>
                    <h2 className="text-xl font-bold text-white">Elige una opcion de horario</h2>
                    <button
                      onClick={() => setOpciones([])}
                      className="ml-auto text-[10px] text-neutral-500 hover:text-neutral-300 underline transition-colors"
                    >
                      Cancelar
                    </button>
                  </div>
                  <div className="grid grid-cols-1 gap-6">
                    {opciones.map((opc) => (
                      <div key={opc.id_opcion} className="bg-neutral-950 border border-rose-500/20 rounded-3xl p-6 relative overflow-hidden group hover:border-rose-500/40 transition-all">
                        <div className="flex items-center justify-between mb-6">
                          <h3 className="text-2xl font-black text-white flex items-center gap-3">
                            <span className="w-9 h-9 rounded-full bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center text-sm font-black">{opc.id_opcion}</span>
                            {opc.label}
                          </h3>
                          <button
                            onClick={() => handleConfirmarOpcion(opc)}
                            disabled={confirmando}
                            className="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:brightness-110 disabled:opacity-50 text-white font-bold text-sm transition-all shadow-lg shadow-emerald-900/30"
                          >
                            {confirmando ? 'Confirmando...' : `Elegir ${opc.label}`}
                          </button>
                        </div>
                        {['Modulo 1', 'Modulo 2', 'Modulo 3'].map((modNombre) => {
                          const modItems = opc.items.filter(it => it.modulo_nombre === modNombre);
                          if (modItems.length === 0) return null;
                          return (
                            <div key={modNombre} className="mb-5">
                              <div className="flex items-center gap-2 mb-3">
                                <span className="text-[10px] font-black text-rose-400 uppercase tracking-widest">{modNombre}</span>
                                <div className="h-px flex-1 bg-neutral-800" />
                              </div>
                              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                {modItems.map((item, i) => (
                                  <div key={i} className="bg-neutral-900/60 rounded-2xl p-4 border border-neutral-800/50 hover:border-neutral-700 transition-all">
                                    <div className="flex items-center justify-between mb-2">
                                      <span className="text-[10px] font-bold text-rose-400 bg-rose-500/5 px-2 py-0.5 rounded border border-rose-500/10">Bloque {item.bloque_nombre || '?'}</span>
                                      {item.fijo && <span className="text-[9px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-1.5 rounded">Fijado</span>}
                                    </div>
                                    <h4 className="text-sm font-bold text-white mb-1">{item.materia_nombre}</h4>
                                    <p className="text-[10px] text-neutral-400 mb-3">{item.docente_nombre}</p>
                                    <div className="flex items-center gap-2 text-[10px] text-cyan-400 font-bold bg-cyan-500/5 p-2 rounded-lg border border-cyan-500/10">
                                      <span className="material-symbols-outlined text-xs">schedule</span>
                                      {item.bloque_hora || '??:?? - ??:??'}
                                    </div>
                                  </div>
                                ))}
                              </div>
                            </div>
                          );
                        })}
                        {opc.items.filter(it => !['Modulo 1','Modulo 2','Modulo 3'].includes(it.modulo_nombre)).length > 0 && (
                          <div className="mb-5">
                            <div className="flex items-center gap-2 mb-3">
                              <span className="text-[10px] font-black text-rose-400 uppercase tracking-widest">Otras Materias</span>
                              <div className="h-px flex-1 bg-neutral-800" />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                              {opc.items.filter(it => !['Modulo 1','Modulo 2','Modulo 3'].includes(it.modulo_nombre)).map((item, i) => (
                                <div key={i} className="bg-neutral-900/60 rounded-2xl p-4 border border-neutral-800/50">
                                  <div className="flex items-center justify-between mb-2">
                                    <span className="text-[10px] font-bold text-rose-400 bg-rose-500/5 px-2 py-0.5 rounded border border-rose-500/10">Bloque {item.bloque_nombre || '?'}</span>
                                    {item.fijo && <span className="text-[9px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-1.5 rounded">Fijado</span>}
                                  </div>
                                  <h4 className="text-sm font-bold text-white mb-1">{item.materia_nombre}</h4>
                                  <p className="text-[10px] text-neutral-400 mb-3">{item.docente_nombre}</p>
                                  <div className="flex items-center gap-2 text-[10px] text-cyan-400 font-bold bg-cyan-500/5 p-2 rounded-lg border border-cyan-500/10">
                                    <span className="material-symbols-outlined text-xs">schedule</span>
                                    {item.bloque_hora || '??:?? - ??:??'}
                                  </div>
                                </div>
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Confirmed Schedule - only shown when no options are being selected */}
              {horario && opciones.length === 0 && (
                <div className="bg-neutral-900/30 rounded-[24px] border border-emerald-500/30 p-6">
                  <div className="flex items-center gap-3 mb-8">
                    <div className="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                      <span className="material-symbols-outlined">event_available</span>
                    </div>
                    <div>
                      <h3 className="text-xl font-bold text-white">Tu Horario Confirmado</h3>
                      <p className="text-xs text-neutral-500">Gestión académica actual</p>
                    </div>
                  </div>

                  <div className="space-y-10">
                    {[1, 2, 3, 'Otros'].map((num) => {
                      const moduloItems = (horario.items || []).filter((d) => {
                        if (num === 'Otros') {
                          const mName = d.modulo_nombre || "";
                          const isKnown = [1, 2, 3].some(n => mName.includes(String(n)));
                          return !isKnown;
                        }
                        const mName = d.modulo_nombre || "";
                        return mName.includes(String(num));
                      });

                      if (moduloItems.length === 0) return null;

                      return (
                        <div key={`mod-group-${num}`} className="space-y-4">
                          <div className="flex items-center gap-2 px-2">
                            <span className="text-xs font-black text-emerald-400 uppercase tracking-widest">
                              {num === 'Otros' ? 'Otras Materias' : (moduloItems[0].modulo_nombre || `Módulo ${num}`)}
                            </span>
                            <div className="h-px flex-1 bg-neutral-800" />
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {moduloItems.map((det, idx) => (
                              <div key={idx} className="bg-neutral-950/50 rounded-2xl border border-neutral-800 p-5 hover:border-emerald-500/30 transition-all group">
                                <div className="flex justify-between items-start mb-3">
                                  <div className="text-[10px] font-bold text-rose-400 bg-rose-500/5 px-2 py-0.5 rounded border border-rose-500/10">Bloque {det.bloque_nombre || '?'}</div>
                                  <span className="material-symbols-outlined text-neutral-700 group-hover:text-emerald-500/50 transition-colors">calendar_today</span>
                                </div>
                                <h4 className="text-white font-bold text-base mb-1">{det.materia_nombre}</h4>
                                <div className="flex items-center gap-2 mb-3">
                                  <div className="w-5 h-5 rounded-full bg-neutral-800 flex items-center justify-center text-[10px] text-neutral-400 font-bold uppercase">
                                    {det.docente_nombre?.charAt(0) || 'D'}
                                  </div>
                                  <div className="text-xs text-neutral-400 truncate">{det.docente_nombre || 'Docente'}</div>
                                </div>
                                <div className="pt-3 border-t border-neutral-800/50 flex items-center justify-between text-[10px] text-neutral-500 font-medium">
                                  <span>{det.bloque_hora || '00:00 - 00:00'}</span>
                                  <span className="text-neutral-600 truncate ml-2">{det.aula_nombre || 'Aula'}</span>
                                </div>
                              </div>
                            ))}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Empty state when no schedule and no options */}
              {!horario && opciones.length === 0 && !opcionesError && (
                <div className="flex flex-col items-center justify-center py-20 text-center">
                  <span className="material-symbols-outlined text-5xl text-neutral-700 mb-4">calendar_month</span>
                  <p className="text-neutral-500 text-sm">Aun no tienes un horario asignado.</p>
                  <p className="text-neutral-600 text-xs mt-1">Usa el boton <strong className="text-neutral-400">"Generar 3 opciones"</strong> para ver combinaciones posibles.</p>
                </div>
              )}

            </div>
          )}
        </div>
      </main>
      {showProfile && (
        <ProfileModal onClose={() => setShowProfile(false)} />
      )}
    </div>
  );
}
