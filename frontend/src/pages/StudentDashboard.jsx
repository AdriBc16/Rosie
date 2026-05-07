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

const BLOQUE_COLORS = ['from-rose-500 to-pink-400','from-orange-500 to-amber-400','from-yellow-500 to-lime-400','from-teal-500 to-cyan-400','from-blue-500 to-violet-400','from-purple-500 to-fuchsia-400'];

const CREDIT_LIMIT = 29;

export default function StudentDashboard() {
  const { user, dashboardData, logout, refreshDashboard } = useAuth();
  const navigate = useNavigate();

  const inscripciones = dashboardData?.inscripciones || [];
  const totalCredits  = dashboardData?.totalCredits || 0;
  const horario       = dashboardData?.horario || null;
  const mallaCursada  = dashboardData?.mallaCursada || [];

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
              <h2 className="text-xl font-bold text-white mb-6">Mis Inscripciones Actuales</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                {inscripciones.map((item) => (
                  <div key={item.id_inscripcion} className="bg-[#131313] rounded-2xl border border-neutral-800 p-4">
                    <h3 className="text-white font-bold text-sm mb-2">{item.materia || 'Sin nombre'}</h3>
                    <div className="flex items-center gap-2 mt-3">
                      <span className="material-symbols-outlined text-sm text-neutral-600">grade</span>
                      <span className="text-xs text-neutral-400">{item.modulo?.creditos_materia ?? '5'} créditos</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'horario' && (
            <div className="space-y-8">
              {/* Confirmed Schedule */}
              {horario && (
                <div className="bg-neutral-900/30 rounded-[24px] border border-emerald-500/30 p-6">
                   <h3 className="text-lg font-bold text-white mb-6">Tu Horario Confirmado</h3>
                   <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    {(horario.detalles || []).map((det, idx) => (
                      <div key={idx} className="bg-[#131313] rounded-2xl border border-neutral-800 p-4">
                        <div className="text-xs font-bold text-rose-400 mb-1">Bloque {det.bloque?.nombre}</div>
                        <h4 className="text-white font-bold text-sm mb-2">{det.materia?.nombre}</h4>
                        <div className="text-[10px] text-neutral-500">{det.docente?.nombre}</div>
                      </div>
                    ))}
                   </div>
                </div>
              )}

              {/* 3 Options Section */}
              {opciones.length > 0 && (
                <div className="grid grid-cols-1 gap-8">
                  {opciones.map((opc) => (
                    <div key={opc.id_opcion} className="bg-neutral-950 border border-neutral-800 rounded-3xl p-6 relative overflow-hidden group">
                      <div className="absolute top-0 right-0 p-6">
                        <button 
                          onClick={() => handleConfirmarOpcion(opc)}
                          className="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition-all shadow-lg shadow-emerald-900/20"
                        >
                          Elegir {opc.label}
                        </button>
                      </div>
                      <h3 className="text-2xl font-black text-white mb-6 flex items-center gap-3">
                        <span className="w-8 h-8 rounded-full bg-rose-500 flex items-center justify-center text-sm">{opc.id_opcion}</span>
                        {opc.label}
                      </h3>
                      
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {opc.items.map((item, i) => (
                          <div key={i} className="bg-neutral-900/50 rounded-2xl p-4 border border-neutral-800/50">
                            <div className="flex items-center justify-between mb-2">
                              <span className="text-[10px] font-bold text-rose-400 uppercase tracking-wider">{item.modulo_nombre}</span>
                              {item.fijo && <span className="text-[9px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-1.5 rounded">Fijado</span>}
                            </div>
                            <h4 className="text-sm font-bold text-white mb-1">{item.materia_nombre}</h4>
                            <p className="text-[10px] text-neutral-400 mb-3">{item.docente_nombre}</p>
                            <div className="flex items-center gap-2 text-[10px] text-cyan-400 font-bold bg-cyan-500/5 p-2 rounded-lg border border-cyan-500/10">
                              <span className="material-symbols-outlined text-xs">schedule</span>
                              Bloque {item.bloque_nombre} ({item.bloque_hora})
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
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
