import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';

const yearLabel = (n) => `${n}er Año`;

export default function HeadDashboard() {
  const [catalog, setCatalog] = useState({
    docentes: [],
    materias: [],
    modulos: [],
    semestres: [],
    bloques: [],
    aulas: [],
    asignacionesActuales: [],
    activeModuloId: null,
  });
  const [loading, setLoading] = useState(true);
  const [feedback, setFeedback] = useState('');

  const [teacherFilter, setTeacherFilter] = useState('Todos');
  const [statusFilter, setStatusFilter] = useState('Todos');
  const [moduleFilter, setModuleFilter] = useState('Todos');
  const [semesterFilter, setSemesterFilter] = useState('Todos');

  const [selectedCell, setSelectedCell] = useState(null); // { yearNumber, semesterNumber, moduloNumber }
  const [form, setForm] = useState({ id_materia: '', id_docente: '', id_aula: '', id_bloque: '' });

  const loadCatalog = async () => {
    setLoading(true);
    try {
      const res = await axios.get('/portal/api/jefe/catalogo');
      setCatalog((prev) => ({ ...prev, ...(res.data?.data || {}) }));
    } catch (err) {
      setFeedback(err.response?.data?.message || 'Error cargando catalogo');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCatalog();
  }, []);

  const docentesById = useMemo(() => Object.fromEntries((catalog.docentes || []).map((d) => [d.id_docente, d])), [catalog.docentes]);
  const materiasById = useMemo(() => Object.fromEntries((catalog.materias || []).map((m) => [m.id_materia, m])), [catalog.materias]);
  const modulosById = useMemo(() => Object.fromEntries((catalog.modulos || []).map((m) => [m.id_modulo, m])), [catalog.modulos]);
  const bloquesById = useMemo(() => Object.fromEntries((catalog.bloques || []).map((b) => [b.id_bloque, b])), [catalog.bloques]);
  const aulasById = useMemo(() => Object.fromEntries((catalog.aulas || []).map((a) => [a.id_aula, a])), [catalog.aulas]);
  const semestresById = useMemo(() => Object.fromEntries((catalog.semestres || []).map((s) => [s.id_semestre, s])), [catalog.semestres]);

  const agendaItems = useMemo(() => {
    const raw = catalog.asignacionesActuales || [];
    return raw.map((a) => {
      const modulo = modulosById[a.id_modulo] || {};
      const semestre = semestresById[modulo.id_semestre] || {};
      const semesterNumber = Number(semestre.numero || semestre.id_semestre || 1);
      const yearNumber = Math.ceil(semesterNumber / 2);
      const moduloNumber = Number(modulo.numero_en_semestre || 1);

      const docente = docentesById[a.id_docente];
      const materia = materiasById[a.id_materia];
      const aula = aulasById[a.id_aula];

      return {
        id: a.id_dm,
        yearNumber,
        yearLabel: yearLabel(yearNumber),
        semesterNumber,
        moduloNumber,
        moduloLabel: `Modulo ${moduloNumber}`,
        materia: materia?.nombre || `Materia #${a.id_materia}`,
        docente: docente ? `${docente.nombre} ${docente.apellido || ''}`.trim() : 'Sin Docente',
        aula: aula?.nombre || 'Sin Aula',
        estado: docente ? 'Asignada' : 'Pendiente',
        id_materia: a.id_materia,
        id_docente: a.id_docente,
      };
    });
  }, [catalog.asignacionesActuales, modulosById, semestresById, docentesById, materiasById, aulasById]);

  const filteredAgendaItems = useMemo(() => {
    return agendaItems.filter((i) => {
      if (teacherFilter !== 'Todos' && i.docente !== teacherFilter) return false;
      if (statusFilter !== 'Todos' && i.estado !== statusFilter) return false;
      if (moduleFilter !== 'Todos' && i.moduloLabel !== moduleFilter) return false;
      if (semesterFilter !== 'Todos' && String(i.semesterNumber) !== semesterFilter) return false;
      return true;
    });
  }, [agendaItems, teacherFilter, statusFilter, moduleFilter, semesterFilter]);

  const years = useMemo(() => {
    const set = new Set(filteredAgendaItems.map((i) => i.yearNumber));
    const arr = [...set].sort((a, b) => a - b);
    if (!arr.length) return [1, 2, 3, 4, 5];
    return arr;
  }, [filteredAgendaItems]);

  const cellItems = (yearNumber, semesterNumber, moduloNumber) =>
    filteredAgendaItems.filter(
      (i) => i.yearNumber === yearNumber && i.semesterNumber === semesterNumber && i.moduloNumber === moduloNumber,
    );

  const pending = useMemo(() => {
    const assignedIds = new Set((catalog.asignacionesActuales || []).map((a) => a.id_materia));
    return (catalog.materias || [])
      .filter((m) => !assignedIds.has(m.id_materia))
      .map((m) => ({
        id: m.id_materia,
        materia: m.nombre,
        yearNumber: Number(m['año_academico'] || 1),
      }));
  }, [catalog.asignacionesActuales, catalog.materias]);

  const handleAssign = async () => {
    if (!selectedCell) return;
    if (!form.id_materia || !form.id_docente || !form.id_bloque || !form.id_aula) {
      setFeedback('Completa materia, docente, bloque y aula para asignar.');
      return;
    }

    try {
      const payload = {
        id_materia: Number(form.id_materia),
        id_docente: Number(form.id_docente),
        id_bloque: Number(form.id_bloque),
        id_aula: Number(form.id_aula),
      };
      const res = await axios.post('/portal/api/jefe/asignaciones', payload);
      setFeedback(res.data?.message || 'Asignación creada');
      setSelectedCell(null);
      await loadCatalog();
    } catch (err) {
      setFeedback(err.response?.data?.message || 'No se pudo asignar');
    }
  };

  const teacherOptions = useMemo(
    () => ['Todos', ...(catalog.docentes || []).filter((d) => !d.es_jefe_carrera).map((d) => `${d.nombre} ${d.apellido || ''}`.trim())],
    [catalog.docentes],
  );

  return (
    <div className="font-body-md overflow-hidden bg-black text-[#f6dddc] min-h-screen">
      <aside className="fixed left-0 top-0 h-full flex flex-col p-6 h-screen w-72 border-r border-neutral-800 bg-neutral-950 z-50 shadow-2xl shadow-rose-900/10">
        <div className="mb-10">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center">
              <span className="material-symbols-outlined text-white">school</span>
            </div>
            <div>
              <span className="text-2xl font-black bg-gradient-to-br from-rose-500 to-orange-400 bg-clip-text text-transparent">Academia Pro</span>
              <p className="font-medium text-xs tracking-tight text-neutral-500 mt-1">Head of Department</p>
            </div>
          </div>
        </div>
        <nav className="flex-1 flex flex-col gap-2">
          <a className="flex items-center gap-3 bg-neutral-900 text-white rounded-xl py-3 px-4 border-l-4 border-rose-500" href="#">
            <span className="material-symbols-outlined">dashboard</span><span className="font-semibold">Dashboard</span>
          </a>
          <div className="mt-8 pt-5 border-t border-neutral-800 space-y-4 px-2">
            <div><label className="text-[10px] text-neutral-600 px-2 uppercase font-bold">Por Docente</label><select value={teacherFilter} onChange={(e) => setTeacherFilter(e.target.value)} className="w-full bg-neutral-900 border-none rounded-lg text-xs text-neutral-300">{teacherOptions.map((t) => <option key={t}>{t}</option>)}</select></div>
            <div><label className="text-[10px] text-neutral-600 px-2 uppercase font-bold">Estado</label><select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="w-full bg-neutral-900 border-none rounded-lg text-xs text-neutral-300"><option>Todos</option><option>Asignada</option><option>Pendiente</option></select></div>
            <div><label className="text-[10px] text-neutral-600 px-2 uppercase font-bold">Por Modulo</label><select value={moduleFilter} onChange={(e) => setModuleFilter(e.target.value)} className="w-full bg-neutral-900 border-none rounded-lg text-xs text-neutral-300"><option>Todos</option><option>Modulo 1</option><option>Modulo 2</option><option>Modulo 3</option></select></div>
            <div><label className="text-[10px] text-neutral-600 px-2 uppercase font-bold">Por Semestre</label><select value={semesterFilter} onChange={(e) => setSemesterFilter(e.target.value)} className="w-full bg-neutral-900 border-none rounded-lg text-xs text-neutral-300"><option>Todos</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10</option></select></div>
          </div>
        </nav>
      </aside>

      <main className="ml-72 min-h-screen flex flex-col">
        <header className="fixed top-0 right-0 left-72 z-40 flex justify-between items-center px-8 h-16 border-b border-neutral-800 bg-black/80">
          <h1 className="font-bold text-lg text-white">Academic Management</h1>
          <button onClick={loadCatalog} className="bg-neutral-900 rounded-lg px-3 py-1.5 text-sm text-neutral-200">Recargar</button>
        </header>

        <div className="mt-16 p-8 flex gap-8 overflow-y-auto h-[calc(100vh-64px)]">
          <div className="flex-1 flex flex-col gap-6">
            <div>
              <h2 className="text-white text-3xl font-bold">Agenda por Carrera</h2>
              <p className="text-neutral-500">Conectada al backend: Año → Semestre → Módulo</p>
            </div>

            {feedback && <div className="px-4 py-3 rounded-xl border border-rose-500/40 bg-rose-500/10 text-rose-200 text-sm">{feedback}</div>}
            {loading && <div className="text-neutral-400 text-sm">Cargando datos...</div>}

            <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6 flex-1 min-h-0 overflow-y-auto">
              <div className="space-y-4">
                {years.map((yearNumber) => {
                  const semA = yearNumber * 2 - 1;
                  const semB = yearNumber * 2;
                  return (
                    <div key={yearNumber} className="rounded-2xl border border-neutral-800 bg-[#131313] p-4">
                      <div className="text-xs font-bold text-rose-400 uppercase tracking-widest mb-3">{yearLabel(yearNumber)}</div>
                      <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        {[semA, semB].map((semestre) => (
                          <div key={`${yearNumber}-s${semestre}`} className="rounded-xl border border-neutral-800 bg-[#161616] p-3">
                            <div className="text-[10px] font-bold text-orange-400 uppercase tracking-widest mb-2">Semestre {semestre}</div>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                              {[1, 2, 3].map((moduloNumber) => {
                                const slotItems = cellItems(yearNumber, semestre, moduloNumber);
                                return (
                                  <div key={`${yearNumber}-${semestre}-${moduloNumber}`} className="rounded-xl border border-neutral-800 bg-[#1a1a1a] p-3 min-h-[130px]">
                                    <div className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-2">Modulo {moduloNumber}</div>
                                    <div className="space-y-2">
                                      {slotItems.map((cell) => {
                                        const pendingStyle = cell.estado === 'Pendiente';
                                        return (
                                          <button key={cell.id} onClick={() => setSelectedCell({ yearNumber, semesterNumber: semestre, moduloNumber })} className={`w-full text-left p-2 rounded-lg border-l-4 ${pendingStyle ? 'border-orange-500/50 bg-orange-500/5' : 'border-rose-500 bg-rose-500/5'}`}>
                                            <span className={`text-[9px] px-1.5 py-0.5 rounded-full font-bold ${pendingStyle ? 'bg-orange-500/20 text-orange-400' : 'bg-rose-500/20 text-rose-400'}`}>{cell.estado}</span>
                                            <div className="text-white text-xs font-bold mt-1">{cell.materia}</div>
                                            <div className="text-neutral-400 text-[10px]">{cell.docente}</div>
                                            <div className="text-neutral-600 text-[10px]">{cell.aula}</div>
                                          </button>
                                        );
                                      })}
                                      {slotItems.length === 0 && (
                                        <button onClick={() => setSelectedCell({ yearNumber, semesterNumber: semestre, moduloNumber })} className="w-full border-2 border-dashed border-neutral-800 rounded-lg py-3 text-center text-neutral-600 hover:text-neutral-400">
                                          <span className="material-symbols-outlined text-base">add_circle</span>
                                          <div className="text-[10px] font-bold uppercase">Asignar</div>
                                        </button>
                                      )}
                                    </div>
                                  </div>
                                );
                              })}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            <div className="bg-[#1a1a1a] p-6 rounded-[24px] border border-[#2d2d2d]">
              <h3 className="text-xl font-bold mb-4">Asignar docente a materia (backend)</h3>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_materia} onChange={(e) => setForm((p) => ({ ...p, id_materia: e.target.value }))}>
                  <option value="">Materia</option>
                  {(catalog.materias || []).map((m) => <option key={m.id_materia} value={m.id_materia}>{m.nombre}</option>)}
                </select>
                <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_docente} onChange={(e) => setForm((p) => ({ ...p, id_docente: e.target.value }))}>
                  <option value="">Docente</option>
                  {(catalog.docentes || []).filter((d) => !d.es_jefe_carrera).map((d) => <option key={d.id_docente} value={d.id_docente}>{d.nombre} {d.apellido || ''}</option>)}
                </select>
                <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_bloque} onChange={(e) => setForm((p) => ({ ...p, id_bloque: e.target.value }))}>
                  <option value="">Bloque</option>
                  {(catalog.bloques || []).map((b) => <option key={b.id_bloque} value={b.id_bloque}>{b.nombre}</option>)}
                </select>
                <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_aula} onChange={(e) => setForm((p) => ({ ...p, id_aula: e.target.value }))}>
                  <option value="">Aula</option>
                  {(catalog.aulas || []).map((a) => <option key={a.id_aula} value={a.id_aula}>{a.nombre}</option>)}
                </select>
              </div>
              {selectedCell && (
                <button onClick={handleAssign} className="mt-4 px-8 py-3 bg-[#2d2d2d] text-white font-bold rounded-xl">
                  Confirmar Asignación (Año {selectedCell.yearNumber} - Sem {selectedCell.semesterNumber} - Mod {selectedCell.moduloNumber})
                </button>
              )}
            </div>
          </div>

          <div className="w-80 flex flex-col gap-6">
            <div className="bg-neutral-900 border border-neutral-800 rounded-[24px] p-6 flex flex-col h-full">
              <div className="flex items-center justify-between mb-6"><h3 className="text-white text-lg font-bold">Materias por Asignar</h3><span className="bg-orange-500 text-white text-[10px] font-black px-2 py-1 rounded-md">{pending.length}</span></div>
              <div className="space-y-4 overflow-y-auto">
                {pending.map((p) => (
                  <div key={p.id} className="p-4 rounded-xl bg-neutral-950 border border-neutral-800">
                    <div className="flex justify-between items-start mb-2"><h4 className="text-neutral-200 font-bold text-sm">{p.materia}</h4><span className="text-orange-500 material-symbols-outlined text-sm">warning</span></div>
                    <div className="flex items-center gap-2 mb-3"><span className="text-[10px] text-neutral-500 font-medium">Año {p.yearNumber}</span></div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}

