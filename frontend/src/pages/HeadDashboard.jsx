import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { useAuth } from '../AuthContext';
import { useNavigate } from 'react-router-dom';
import logoRosie from '../assets/Rosie1.png';
import ProfileModal from '../components/ProfileModal';

const INSCRIPCION_BASE_YEAR = 2022;
const cohortYearFromIndex = (n) => INSCRIPCION_BASE_YEAR + (Number(n) - 1);
const yearLabel = (n) => `Inscripción ${cohortYearFromIndex(n)}`;
const yearSemesterLabel = (absoluteSemesterNumber) => ((Number(absoluteSemesterNumber) - 1) % 2) + 1;
const formatDateShort = (value) => {
  if (!value) return '';
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

export default function HeadDashboard() {
  const { logout } = useAuth();
  const navigate = useNavigate();
  const [catalog, setCatalog] = useState({
    docentes: [],
    estudiantes: [],
    materias: [],
    modulos: [],
    semestres: [],
    bloques: [],
    aulas: [],
    asignacionesActuales: [],
    prerrequisitos: [],
    historialMaterias: [],
    inscripciones: [],
    activeModuloId: null,
    disponibilidadDocente: [],
  });

  const [loading, setLoading] = useState(true);
  const [feedback, setFeedback] = useState('');
  const [feedbackIsError, setFeedbackIsError] = useState(false);
  const [showCurricula, setShowCurricula] = useState(false);
  const [showProfile, setShowProfile] = useState(false);

  const [teacherFilter, setTeacherFilter] = useState('Todos');
  const [statusFilter, setStatusFilter] = useState('Todos');
  const [moduleFilter, setModuleFilter] = useState('Todos');
  const [semesterFilter, setSemesterFilter] = useState('Todos');

  const [activeTab, setActiveTab] = useState('agenda');
  const [selectedCell, setSelectedCell] = useState(null);

  // Crear docente
  const [docenteForm, setDocenteForm] = useState({ nombre: '', apellido: '', correo: '' });
  const [docenteFeedback, setDocenteFeedback] = useState('');
  const [savingDocente, setSavingDocente] = useState(false);

  // Pestaña estudiantes
  const [studentsData, setStudentsData] = useState(null);  // grupos por cohorte
  const [loadingStudents, setLoadingStudents] = useState(false);
  const [studentModal, setStudentModal] = useState(null);  // { estudiante, semestres }

  // Pestaña docentes-materias
  const [docentesMateriales, setDocentesMateriales] = useState([]);
  const [loadingDocentesMateriales, setLoadingDocentesMateriales] = useState(false);
  const [loadingStudentMaterias, setLoadingStudentMaterias] = useState(false);
  const [convalidandoId, setConvalidandoId] = useState(null);  // id_materia en proceso
  const [studentsYearModal, setStudentsYearModal] = useState(null);
  const MATERIAS_SEMESTRALES = ['English Beginners', 'English Intermediate', 'English High Intermediate', 'English Advanced'];
  const [form, setForm] = useState({ id_materia: '', id_semestre: '', id_modulo: '', id_docente: '', id_aula: '', id_bloque: '', enrollment_mode: 'none' });
  const [graphModal, setGraphModal] = useState(null); // { type: 'year'|'full', yearNumber?: number }
  const [graphZoom, setGraphZoom] = useState(1);
  const [hoveredYear, setHoveredYear] = useState(null);
  const [previewYear, setPreviewYear] = useState(1);

  const handleCreateDocente = async (e) => {
    e.preventDefault();
    setSavingDocente(true);
    setDocenteFeedback('');
    try {
      const res = await axios.post('/portal/api/jefe/docentes', docenteForm);
      setDocenteFeedback(`Docente "${res.data.docente.nombre} ${res.data.docente.apellido}" creado. Contraseña: UPB123`);
      setTimeout(() => setDocenteFeedback(''), 5000);
      setDocenteForm({ nombre: '', apellido: '', correo: '' });
      loadCatalog();
    } catch (err) {
      setDocenteFeedback('Error: ' + (err.response?.data?.message || err.message));
    } finally {
      setSavingDocente(false);
    }
  };

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

  useEffect(() => {
    if (activeTab === 'students' && studentsData === null) {
      loadStudents();
    }
    if (activeTab === 'docentes-materias') {
      loadDocentesMateriales();
    }
  }, [activeTab]);

  const docentesById = useMemo(() => Object.fromEntries((catalog.docentes || []).map((d) => [d.id_docente, d])), [catalog.docentes]);
  const materiasById = useMemo(() => Object.fromEntries((catalog.materias || []).map((m) => [m.id_materia, m])), [catalog.materias]);
  const modulosById = useMemo(() => Object.fromEntries((catalog.modulos || []).map((m) => [m.id_modulo, m])), [catalog.modulos]);
  const aulasById = useMemo(() => Object.fromEntries((catalog.aulas || []).map((a) => [a.id_aula, a])), [catalog.aulas]);
  const semestresById = useMemo(() => Object.fromEntries((catalog.semestres || []).map((s) => [s.id_semestre, s])), [catalog.semestres]);

  useEffect(() => {
    if (!selectedCell || !catalog.modulos?.length) return;

    const moduloEncontrado = catalog.modulos.find((m) => {
      const semestre = semestresById[m.id_semestre];

      return (
        Number(semestre?.numero) === Number(selectedCell.semesterNumber) &&
        Number(m.numero_en_semestre) === Number(selectedCell.moduloNumber)
      );
    });

    if (moduloEncontrado) {
      setForm((prev) => ({
        ...prev,
        id_modulo: String(moduloEncontrado.id_modulo),
      }));
    }
  }, [selectedCell, catalog.modulos, semestresById]);
  const agendaItems = useMemo(() => {
    const raw = catalog.asignacionesActuales || [];
    return raw.map((a) => {
      const materia = materiasById[a.id_materia] || {};
      const modulo = modulosById[a.id_modulo] || {};
      const semestre = semestresById[modulo.id_semestre] || {};
      const semesterFromTime = Number(semestre.numero || semestre.id_semestre || 1);
      const semesterNumber = Number(materia.semestre_academico || yearSemesterLabel(semesterFromTime) || 1);
      const yearNumber = Number(materia.anio_academico || Math.ceil(semesterFromTime / 2) || 1);
      const moduloNumber = Number(modulo.numero_en_semestre || 1);

      const docente = docentesById[a.id_docente];
      const aula = aulasById[a.id_aula];

      return {
        id: a.id_dm,
        yearNumber,
        semesterNumber,
        moduloNumber,
        moduloLabel: `Modulo ${moduloNumber}`,
        materia: materia?.nombre || `Materia #${a.id_materia}`,
        docente: docente ? `${docente.nombre} ${docente.apellido || ''}`.trim() : 'Sin Docente',
        aula: aula?.nombre || 'Sin Aula',
        estado: docente ? 'Asignada' : 'Pendiente',
      };
    });
  }, [catalog.asignacionesActuales, modulosById, semestresById, docentesById, materiasById, aulasById]);

  const filteredAgendaItems = useMemo(() => {
    return agendaItems.filter((i) => {
      if (teacherFilter !== 'Todos' && i.docente !== teacherFilter) return false;
      if (statusFilter !== 'Todos' && i.estado !== statusFilter) return false;
      if (moduleFilter !== 'Todos' && i.moduloLabel !== moduleFilter) return false;
      if (semesterFilter !== 'Todos' && String(yearSemesterLabel(i.semesterNumber)) !== semesterFilter) return false;
      return true;
    });
  }, [agendaItems, teacherFilter, statusFilter, moduleFilter, semesterFilter]);

  const years = useMemo(() => {
    const fromMaterias = (catalog.materias || [])
      .map((m) => Number(m.anio_academico || 0))
      .filter((n) => n > 0);

    const maxYearFromMaterias = fromMaterias.length ? Math.max(...fromMaterias) : 0;
    const maxYear = Math.max(maxYearFromMaterias, 1);

    return Array.from({ length: maxYear }, (_, idx) => idx + 1);
  }, [catalog.materias]);

  const cellItems = (yearNumber, semesterNumber, moduloNumber) =>
    filteredAgendaItems.filter(
      (i) => i.yearNumber === yearNumber && i.semesterNumber === semesterNumber && i.moduloNumber === moduloNumber,
    );

  const moduloDateRangeByCell = useMemo(() => {
    const map = new Map();
    const sortedModulos = [...(catalog.modulos || [])].sort((a, b) => {
      const sa = Number(semestresById[a.id_semestre]?.numero || 999);
      const sb = Number(semestresById[b.id_semestre]?.numero || 999);
      if (sa !== sb) return sa - sb;
      const ma = Number(a.numero_en_semestre || 99);
      const mb = Number(b.numero_en_semestre || 99);
      return ma - mb;
    });

    sortedModulos.forEach((modulo) => {
      const semestre = semestresById[modulo.id_semestre] || {};
      const semesterNumber = yearSemesterLabel(Number(semestre.numero || semestre.id_semestre || 1));
      const moduloNumber = Number(modulo.numero_en_semestre || 1);
      const key = `${semesterNumber}-${moduloNumber}`;
      const start = formatDateShort(modulo.fecha_inicio);
      const end = formatDateShort(modulo.fecha_final);
      if (!map.has(key)) {
        map.set(key, start && end ? `${start} - ${end}` : start || end || '');
      }
    });
    return map;
  }, [catalog.modulos, semestresById]);

  const pending = useMemo(() => {
    const assignedIds = new Set((catalog.asignacionesActuales || []).map((a) => a.id_materia));
    return (catalog.materias || [])
      .filter((m) => !assignedIds.has(m.id_materia))
      .map((m) => ({
        id: m.id_materia,
        materia: m.nombre,
        yearNumber: Number(m.anio_academico || 1),
      }));
  }, [catalog.asignacionesActuales, catalog.materias]);

  const studentCohortIndexById = useMemo(() => {
    const byStudent = new Map();

    (catalog.inscripciones || []).forEach((i) => {
      if (!i?.id_estudiante || !i?.fecha_inscripcion) return;
      const year = new Date(i.fecha_inscripcion).getFullYear();
      if (!Number.isFinite(year) || year < INSCRIPCION_BASE_YEAR) return;
      const idx = year - INSCRIPCION_BASE_YEAR + 1;
      const prev = byStudent.get(i.id_estudiante);
      if (!prev || idx < prev) byStudent.set(i.id_estudiante, idx);
    });

    // fallback para datos legacy sin fecha_inscripcion utilizable
    (catalog.estudiantes || []).forEach((e) => {
      if (byStudent.has(e.id_estudiante)) return;
      const m = String(e.apellido || '').match(/anio\s*(\d+)/i);
      if (!m) return;
      byStudent.set(e.id_estudiante, Number(m[1]));
    });

    return byStudent;
  }, [catalog.inscripciones, catalog.estudiantes]);

  // Bloques disponibles para el docente+módulo seleccionado en el formulario de asignación
  const bloquesDisponiblesParaAsignar = useMemo(() => {
    if (!form.id_docente || !form.id_modulo) return catalog.bloques || [];
    const idDoc = Number(form.id_docente);
    const idMod = Number(form.id_modulo);
    const disponibles = new Set(
      (catalog.disponibilidadDocente || [])
        .filter((d) => d.id_docente === idDoc && d.id_modulo === idMod)
        .map((d) => d.id_bloque),
    );
    return (catalog.bloques || []).filter((b) => disponibles.has(b.id_bloque));
  }, [form.id_docente, form.id_modulo, catalog.bloques, catalog.disponibilidadDocente]);

  const bloquesLibresParaDocente = useMemo(() => {
    if (!form.id_docente || !form.id_modulo) return [];

    const docenteId = Number(form.id_docente);
    const moduloId = Number(form.id_modulo);

    const bloquesDisponibles = new Set(
      (catalog.disponibilidadDocente || [])
        .filter(
          d =>
            d.id_docente === docenteId &&
            d.id_modulo === moduloId
        )
        .map(d => d.id_bloque)
    );

    const bloquesOcupados = new Set(
      (catalog.asignacionesActuales || [])
        .filter(
          a =>
            a.id_docente === docenteId &&
            a.id_modulo === moduloId
        )
        .map(a => a.id_bloque)
    );

    return (catalog.bloques || []).filter(
      b =>
        bloquesDisponibles.has(b.id_bloque) &&
        !bloquesOcupados.has(b.id_bloque)
    );
  }, [
    form.id_docente,
    form.id_modulo,
    catalog.disponibilidadDocente,
    catalog.asignacionesActuales,
    catalog.bloques,
  ]);

  // ¿La materia seleccionada en el form de asignación es semestral (inglés)?
  const isMateriaAsignarSemestral = useMemo(() => {
    if (!form.id_materia) return false;
    const mat = (catalog.materias || []).find(m => String(m.id_materia) === String(form.id_materia));
    return mat ? MATERIAS_SEMESTRALES.includes(mat.nombre) : false;
  }, [form.id_materia, catalog.materias]);

  // Para materias semestrales los bloques libres se calculan sobre todos los módulos del semestre
  const bloquesLibresSemestral = useMemo(() => {
    if (!isMateriaAsignarSemestral || !form.id_docente || !form.id_semestre) return [];
    const docenteId = Number(form.id_docente);
    const modSemestre = (catalog.modulos || []).filter(m => String(m.id_semestre) === String(form.id_semestre));
    if (modSemestre.length === 0) return [];

    // Un bloque es válido solo si el docente tiene disponibilidad en TODOS los módulos del semestre
    return (catalog.bloques || []).filter(b => {
      return modSemestre.every(mod => {
        return (catalog.disponibilidadDocente || []).some(
          d => d.id_docente === docenteId && d.id_modulo === mod.id_modulo && d.id_bloque === b.id_bloque
        );
      });
    });
  }, [isMateriaAsignarSemestral, form.id_docente, form.id_semestre, catalog.modulos, catalog.bloques, catalog.disponibilidadDocente]);

  const aulasDisponibles = useMemo(() => {
    const bloqueId = Number(form.id_bloque);
    if (!bloqueId) return catalog.aulas || [];

    // Para semestral verificar disponibilidad en todos los módulos del semestre
    if (isMateriaAsignarSemestral && form.id_semestre) {
      const modIds = (catalog.modulos || [])
        .filter(m => String(m.id_semestre) === String(form.id_semestre))
        .map(m => m.id_modulo);
      const aulasOcupadas = new Set(
        (catalog.asignacionesActuales || [])
          .filter(a => modIds.includes(a.id_modulo) && a.id_bloque === bloqueId)
          .map(a => a.id_aula)
      );
      return (catalog.aulas || []).filter(a => !aulasOcupadas.has(a.id_aula));
    }

    if (!form.id_modulo) return catalog.aulas || [];
    const moduloId = Number(form.id_modulo);
    const aulasOcupadas = new Set(
      (catalog.asignacionesActuales || [])
        .filter(a => a.id_modulo === moduloId && a.id_bloque === bloqueId)
        .map(a => a.id_aula)
    );
    return (catalog.aulas || []).filter(aula => !aulasOcupadas.has(aula.id_aula));
  }, [
    isMateriaAsignarSemestral,
    form.id_semestre,
    form.id_modulo,
    form.id_bloque,
    catalog.aulas,
    catalog.asignacionesActuales,
    catalog.modulos,
  ]);
  // Materias que tienen al menos un docente asignado
  const materiasConDocente = useMemo(() => {
    const ids = new Set((catalog.asignacionesActuales || []).map((a) => a.id_materia));
    return (catalog.materias || []).filter((m) => ids.has(m.id_materia));
  }, [catalog.materias, catalog.asignacionesActuales]);

  // Materias disponibles para inscribir al alumno seleccionado (con docente y sin inscripción activa)
  const materiasParaInscribir = useMemo(() => {
    if (!form.id_estudiante || String(form.id_estudiante).startsWith('cohort-')) return materiasConDocente;
    const idEst = Number(form.id_estudiante);
    const yaInscritas = new Set(
      (catalog.inscripciones || [])
        .filter((i) => i.id_estudiante === idEst && i.estado !== 'reprobada')
        .map((i) => i.id_materia),
    );
    return materiasConDocente.filter((m) => !yaInscritas.has(m.id_materia));
  }, [materiasConDocente, form.id_estudiante, catalog.inscripciones]);

  // Estudiantes agrupados por semestre académico actual (estimado por cohorte)
  const estudiantesPorSemestre = useMemo(() => {
    const map = new Map();
    (catalog.estudiantes || []).forEach((e) => {
      const cohort = e.cohorte_ingreso;
      if (!cohort) return;
      const nivelActual = Math.max(1, Math.min(5, (new Date().getFullYear() - cohort) + 1));
      const semActual = Math.max(1, Math.min(10, (nivelActual - 1) * 2 + 1));
      if (!map.has(semActual)) map.set(semActual, []);
      map.get(semActual).push(e);
    });
    return new Map([...map.entries()].sort((a, b) => a[0] - b[0]));
  }, [catalog.estudiantes]);

  const studentByYear = useMemo(() => {
    const map = new Map();
    (catalog.estudiantes || []).forEach((e) => {
      const year = Number(studentCohortIndexById.get(e.id_estudiante) || 0);
      if (year <= 0) return;
      if (!map.has(year)) map.set(year, e);
    });
    return map;
  }, [catalog.estudiantes, studentCohortIndexById]);

  const studentsByYear = useMemo(() => {
    const map = new Map();
    (catalog.estudiantes || []).forEach((e) => {
      const year = Number(studentCohortIndexById.get(e.id_estudiante) || 0);
      if (year <= 0) return;
      if (!map.has(year)) map.set(year, []);
      map.get(year).push(e);
    });
    return map;
  }, [catalog.estudiantes, studentCohortIndexById]);

  const prereqByMateriaId = useMemo(() => {
    const map = new Map();
    (catalog.prerrequisitos || []).forEach((p) => {
      if (!map.has(p.id_materia)) map.set(p.id_materia, []);
      map.get(p.id_materia).push(p.id_materia_prerrequisito);
    });
    return map;
  }, [catalog.prerrequisitos]);

  const dependentsByMateriaId = useMemo(() => {
    const map = new Map();
    (catalog.prerrequisitos || []).forEach((p) => {
      if (!map.has(p.id_materia_prerrequisito)) map.set(p.id_materia_prerrequisito, []);
      map.get(p.id_materia_prerrequisito).push(p.id_materia);
    });
    return map;
  }, [catalog.prerrequisitos]);

  const materiasPlanOrdenadas = useMemo(() => {
    return [...(catalog.materias || [])].sort((a, b) => {
      const ya = Number(a.anio_academico || 99);
      const yb = Number(b.anio_academico || 99);
      if (ya !== yb) return ya - yb;
      const sa = Number(a.semestre_academico || 99);
      const sb = Number(b.semestre_academico || 99);
      if (sa !== sb) return sa - sb;
      return a.nombre.localeCompare(b.nombre);
    });
  }, [catalog.materias]);

  const yearHoverPreview = useMemo(() => {
    const estimatedApprovedBeforeYear = (yearNumber) => {
      if (yearNumber <= 1) return 0;
      if (yearNumber === 2) return 6; // pedido: en 2do año se asume 6 bases ya cursadas
      if (yearNumber === 3) return 12;
      return 12 + (yearNumber - 3) * 12;
    };

    const buildCompletedSet = (targetApprovedCount) => {
      const completed = new Set();
      let madeProgress = true;
      while (madeProgress && completed.size < targetApprovedCount) {
        madeProgress = false;
        for (const materia of materiasPlanOrdenadas) {
          if (completed.has(materia.id_materia)) continue;
          const prereqs = prereqByMateriaId.get(materia.id_materia) || [];
          const canTake = prereqs.every((pid) => completed.has(pid));
          if (!canTake) continue;
          completed.add(materia.id_materia);
          madeProgress = true;
          if (completed.size >= targetApprovedCount) break;
        }
      }
      return completed;
    };

    const result = new Map();
    years.forEach((yearNumber) => {
      const approvedCount = estimatedApprovedBeforeYear(yearNumber);
      const completed = buildCompletedSet(approvedCount);

      const availableNow = materiasPlanOrdenadas.filter((m) => {
        if (completed.has(m.id_materia)) return false;
        const prereqs = prereqByMateriaId.get(m.id_materia) || [];
        return prereqs.every((pid) => completed.has(pid));
      });

      const availableIds = new Set(availableNow.map((m) => m.id_materia));
      const opensSet = new Set();
      availableNow.forEach((m) => {
        (dependentsByMateriaId.get(m.id_materia) || []).forEach((nextId) => {
          if (!completed.has(nextId) && !availableIds.has(nextId)) {
            opensSet.add(nextId);
          }
        });
      });

      const opensNext = [...opensSet]
        .map((id) => materiasById[id])
        .filter(Boolean)
        .sort((a, b) => {
          const ya = Number(a.anio_academico || 99);
          const yb = Number(b.anio_academico || 99);
          if (ya !== yb) return ya - yb;
          const sa = Number(a.semestre_academico || 99);
          const sb = Number(b.semestre_academico || 99);
          if (sa !== sb) return sa - sb;
          return a.nombre.localeCompare(b.nombre);
        });

      result.set(yearNumber, { approvedCount, availableNow, opensNext });
    });

    return result;
  }, [years, materiasPlanOrdenadas, prereqByMateriaId, dependentsByMateriaId, materiasById]);

  useEffect(() => {
    if (!years.length) return;
    if (!years.includes(previewYear)) setPreviewYear(years[0]);
  }, [years, previewYear]);

  const progressByYear = useMemo(() => {
    const res = new Map();
    years.forEach((year) => {
      const st = studentByYear.get(year);
      const completed = new Set();
      const current = new Set();
      if (st) {
        (catalog.historialMaterias || [])
          .filter((h) => h.id_estudiante === st.id_estudiante)
          .forEach((h) => completed.add(h.id_materia));
        (catalog.inscripciones || [])
          .filter((i) => i.id_estudiante === st.id_estudiante)
          .forEach((i) => {
            if (i.estado === 'aprobada') completed.add(i.id_materia);
            if (i.estado === 'cursando') current.add(i.id_materia);
          });
      }

      const available = new Set();
      (catalog.materias || []).forEach((m) => {
        if (completed.has(m.id_materia) || current.has(m.id_materia)) return;
        const prereqs = prereqByMateriaId.get(m.id_materia) || [];
        const unlocked = prereqs.every((pid) => completed.has(pid));
        if (unlocked) available.add(m.id_materia);
      });

      res.set(year, { completed, current, available });
    });
    return res;
  }, [years, studentByYear, catalog.historialMaterias, catalog.inscripciones, catalog.materias, prereqByMateriaId]);

  const materiaDependencias = useMemo(() => {
    const prereqs = catalog.prerrequisitos || [];
    return prereqs
      .map((p) => {
        const base = materiasById[p.id_materia_prerrequisito];
        const desbloquea = materiasById[p.id_materia];
        if (!base || !desbloquea) return null;
        return {
          id: p.id_prerrequisito,
          base: base.nombre,
          desbloquea: desbloquea.nombre,
        };
      })
      .filter(Boolean)
      .sort((a, b) => a.base.localeCompare(b.base));
  }, [catalog.prerrequisitos, materiasById]);

  const materiasSolas = useMemo(() => {
    const used = new Set();
    materiaDependencias.forEach((d) => {
      used.add(d.base);
      used.add(d.desbloquea);
    });
    return (catalog.materias || [])
      .map((m) => m.nombre)
      .filter((nombre) => !used.has(nombre))
      .sort((a, b) => a.localeCompare(b));
  }, [catalog.materias, materiaDependencias]);

  const semestreByMateriaId = useMemo(() => {
    const map = new Map();
    (catalog.materias || []).forEach((m) => map.set(m.id_materia, Number(m.semestre_academico || 1)));
    return map;
  }, [catalog.materias]);

  const mallaTracks = useMemo(() => {
    const materias = catalog.materias || [];
    const prereqs = catalog.prerrequisitos || [];
    const inByMateria = new Map();

    prereqs.forEach((p) => {
      if (!inByMateria.has(p.id_materia)) inByMateria.set(p.id_materia, []);
      inByMateria.get(p.id_materia).push(p.id_materia_prerrequisito);
    });

    const sorted = [...materias].sort((a, b) => {
      const sa = Number(a.semestre_academico || 99);
      const sb = Number(b.semestre_academico || 99);
      if (sa !== sb) return sa - sb;
      return a.nombre.localeCompare(b.nombre);
    });

    const rows = [];
    const rowByMateria = new Map();

    const canPlaceInRow = (row, sem) => !row.some((n) => n.semestre === sem);

    sorted.forEach((m) => {
      const id = m.id_materia;
      const sem = Number(m.semestre_academico || 1);
      const parents = (inByMateria.get(id) || []).filter((pid) => rowByMateria.has(pid));

      let preferredRow = null;
      if (parents.length) {
        preferredRow = rowByMateria.get(parents[0]);
      }

      let targetRow = -1;
      if (preferredRow !== null && preferredRow !== undefined && rows[preferredRow] && canPlaceInRow(rows[preferredRow], sem)) {
        targetRow = preferredRow;
      } else {
        targetRow = rows.findIndex((r) => canPlaceInRow(r, sem));
      }
      if (targetRow < 0) {
        rows.push([]);
        targetRow = rows.length - 1;
      }

      rows[targetRow].push({
        id,
        nombre: m.nombre,
        semestre: sem,
      });
      rowByMateria.set(id, targetRow);
    });

    return rows.map((r) => r.sort((a, b) => a.semestre - b.semestre));
  }, [catalog.materias, catalog.prerrequisitos]);

  const maxSemestre = useMemo(() => {
    return Math.max(
      1,
      ...(catalog.materias || []).map((m) => Number(m.semestre_academico || 1)),
      ...(catalog.semestres || []).map((s) => Number(s.numero || 1)),
    );
  }, [catalog.materias, catalog.semestres]);
  const edgeColor = '#6b7280';

  const conceptualGraph = useMemo(() => {
    const materias = catalog.materias || [];
    const prereqs = catalog.prerrequisitos || [];

    const normalize = (s) =>
      (s || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const groupOf = (name) => {
      const n = normalize(name);
      if (n.includes('english')) return 'Idiomas';
      if (n.includes('base de datos')) return 'Programacion';
      if (n.includes('ingenieria de software') || n.includes('patrones') || n.includes('proyecto de ingenieria de software') || n.includes('gestion de proyectos')) return 'Ingenieria de Software';
      if (n.includes('teleinformat') || n.includes('sistemas operativos') || n.includes('aplicaciones con redes') || n.includes('sistemas distribuidos')) return 'Sistemas';
      if (n.includes('programacion') || n.includes('algoritmica') || n.includes('programacion funcional') || n.includes('certificacion')) return 'Programacion';
      if (n.includes('logica') || n.includes('automatas') || n.includes('compilacion')) return 'Logica';
      if (n.includes('matemat') || n.includes('algebra') || n.includes('fisica') || n.includes('probabilidad') || n.includes('numericos') || n.includes('ecuaciones diferenciales')) return 'Matematicas';
      if (n.includes('inteligencia artificial') || n.includes('robotica') || n.includes('topicos selectos en tic') || n.includes('topicos selectos en inteligencia artificial')) return 'Ciencia Informatica';
      if (n.includes('investigacion') || n.includes('practica') || n.includes('seminario') || n.includes('preparacion y evaluacion de proyectos')) return 'Investigacion y Graduacion';
      if (n.includes('emprended') || n.includes('innovacion') || n.includes('liderazgo') || n.includes('analisis del entorno')) return 'Emprendimiento';
      if (n.includes('electiva')) return 'Electivas';
      return 'Otros';
    };

    const rowOrder = [
      'Matematicas',
      'Logica',
      'Programacion',
      'Ingenieria de Software',
      'Sistemas',
      'Ciencia Informatica',
      'Investigacion y Graduacion',
      'Idiomas',
      'Electivas',
      'Emprendimiento',
      'Otros',
    ];

    const groupColors = {
      Matematicas: { border: '#facc15', bg: 'rgba(250,204,21,0.10)', text: '#fde68a' },
      Logica: { border: '#9ca3af', bg: 'rgba(156,163,175,0.12)', text: '#e5e7eb' },
      'Ingenieria de Software': { border: '#38bdf8', bg: 'rgba(56,189,248,0.10)', text: '#bae6fd' },
      Programacion: { border: '#e5e7eb', bg: 'rgba(229,231,235,0.08)', text: '#f3f4f6' },
      Sistemas: { border: '#22c55e', bg: 'rgba(34,197,94,0.10)', text: '#bbf7d0' },
      'Ciencia Informatica': { border: '#f97316', bg: 'rgba(249,115,22,0.10)', text: '#fed7aa' },
      'Investigacion y Graduacion': { border: '#fca5a5', bg: 'rgba(252,165,165,0.10)', text: '#fecaca' },
      Idiomas: { border: '#e5e7eb', bg: 'rgba(229,231,235,0.10)', text: '#f3f4f6' },
      Emprendimiento: { border: '#f59e0b', bg: 'rgba(245,158,11,0.10)', text: '#fcd34d' },
      Electivas: { border: '#14b8a6', bg: 'rgba(20,184,166,0.10)', text: '#99f6e4' },
      Otros: { border: '#6b7280', bg: 'rgba(107,114,128,0.10)', text: '#d1d5db' },
    };

    const colWidth = 230;
    const groupGap = 56;
    const nodeW = 180;
    const nodeH = 64;
    const padX = 24;
    const padY = 24;
    const slotGap = 12;
    const slotH = nodeH + slotGap;

    const materiasSorted = materias
      .slice()
      .sort((a, b) => {
        const sa = Number(a.semestre_academico || 99);
        const sb = Number(b.semestre_academico || 99);
        if (sa !== sb) return sa - sb;
        return a.nombre.localeCompare(b.nombre);
      });

    const prereqIn = new Map();
    (catalog.prerrequisitos || []).forEach((p) => {
      if (!prereqIn.has(p.id_materia)) prereqIn.set(p.id_materia, []);
      prereqIn.get(p.id_materia).push(p.id_materia_prerrequisito);
    });

    const groupSizes = new Map();
    rowOrder.forEach((g) => groupSizes.set(g, 0));
    materias.forEach((m) => {
      const g = groupOf(m.nombre);
      const key = rowOrder.includes(g) ? g : 'Otros';
      groupSizes.set(key, groupSizes.get(key) + 1);
    });

    const groupBases = new Map();
    let cursorY = padY + 24; // deja espacio para headers de semestre
    rowOrder.forEach((g) => {
      groupBases.set(g, cursorY);
      const size = Math.max(1, groupSizes.get(g) || 1);
      const rowsNeeded = Math.min(4, size); // compacta pero sin encimar
      cursorY += rowsNeeded * slotH + groupGap;
    });

    const occupancy = new Map(); // key: group|sem -> used slots
    const posById = new Map();

    const getUsedSlots = (group, sem) => {
      const key = `${group}|${sem}`;
      if (!occupancy.has(key)) occupancy.set(key, new Set());
      return occupancy.get(key);
    };

    const pickSlot = (group, sem, preferred) => {
      const used = getUsedSlots(group, sem);
      const candidateOrder = [];
      if (preferred !== null && preferred !== undefined) {
        for (let d = 0; d < 12; d++) {
          const up = preferred - d;
          const down = preferred + d;
          if (up >= 0) candidateOrder.push(up);
          if (d > 0) candidateOrder.push(down);
        }
      }
      for (let i = 0; i < 20; i++) candidateOrder.push(i);
      const unique = [...new Set(candidateOrder)];
      const slot = unique.find((s) => !used.has(s)) ?? (used.size + 1);
      used.add(slot);
      return slot;
    };

    materiasSorted.forEach((m) => {
      const sem = Number(m.semestre_academico || 1);
      const groupRaw = groupOf(m.nombre);
      const group = rowOrder.includes(groupRaw) ? groupRaw : 'Otros';
      const col = sem - 1;

      const parentIds = prereqIn.get(m.id_materia) || [];
      const parentSlots = parentIds
        .map((id) => posById.get(id))
        .filter(Boolean)
        .map((p) => p.slot);
      const preferred = parentSlots.length
        ? Math.round(parentSlots.reduce((a, b) => a + b, 0) / parentSlots.length)
        : null;

      const slot = pickSlot(group, sem, preferred);
      const y = groupBases.get(group) + slot * slotH;

      posById.set(m.id_materia, {
        x: padX + col * colWidth,
        y,
        semestre: sem,
        nombre: m.nombre,
        grupo: group,
        slot,
      });
    });

    const edges = prereqs
      .map((p) => {
        const from = posById.get(p.id_materia_prerrequisito);
        const to = posById.get(p.id_materia);
        if (!from || !to) return null;
        return {
          id: p.id_prerrequisito,
          x1: from.x + nodeW,
          y1: from.y + nodeH / 2,
          x2: to.x,
          y2: to.y + nodeH / 2,
        };
      })
      .filter(Boolean);

    const nodes = materias
      .map((m) => {
        const p = posById.get(m.id_materia);
        if (!p) return null;
        return {
          id: m.id_materia,
          nombre: m.nombre,
          semestre: p.semestre,
          x: p.x,
          y: p.y,
          grupo: p.grupo,
          color: groupColors[p.grupo] || groupColors.Otros,
        };
      })
      .filter(Boolean);

    const maxRows = 1;
    const width = padX * 2 + Math.max(1, maxSemestre) * colWidth;
    const height = Math.max(900, cursorY + padY + maxRows * slotH);

    return { nodes, edges, width, height, rowOrder, groupBases, nodeW, nodeH, padY, groupColors };
  }, [catalog.materias, catalog.prerrequisitos, maxSemestre]);

  const handleAssign = async () => {
    const missingModuloOSemestre = isMateriaAsignarSemestral ? !form.id_semestre : !form.id_modulo;
    if (!form.id_materia || !form.id_docente || !form.id_aula || missingModuloOSemestre || !form.id_bloque) {
      setFeedback(`Selecciona materia, docente, aula, ${isMateriaAsignarSemestral ? 'semestre' : 'módulo'} y horario para vincular.`);
      return;
    }

    try {
      const payload = {
        id_materia: Number(form.id_materia),
        id_docente: Number(form.id_docente),
        id_aula: Number(form.id_aula),
        id_bloque: Number(form.id_bloque),
        ...(isMateriaAsignarSemestral
          ? { id_semestre: Number(form.id_semestre) }
          : { id_modulo: Number(form.id_modulo) }),
      };
      const res = await axios.post('/portal/api/jefe/asignaciones', payload);
      setFeedback(res.data?.message || 'Asignación creada');
      setTimeout(() => setFeedback(''), 5000);
      setSelectedCell(null);
      await loadCatalog();
    } catch (err) {
      setFeedback(err.response?.data?.message || 'No se pudo asignar');
    }
  };

  const setError = (msg) => { setFeedback(msg); setFeedbackIsError(true); };
  const setSuccess = (msg) => { setFeedback(msg); setFeedbackIsError(false); setTimeout(() => setFeedback(''), 5000); };

  const handleEnroll = async () => {
    if (!form.id_materia || !form.id_estudiante) {
      setError('Selecciona materia y alumno para inscribir.');
      return;
    }

    const buildPayload = (idEstudiante) => ({
      id_materia: Number(form.id_materia),
      id_estudiante: idEstudiante,
      ...(form.id_modulo ? { id_modulo: Number(form.id_modulo) } : {}),
    });

    // Inscripción masiva por semestre
    if (String(form.id_estudiante).startsWith('cohort-')) {
      const semNum = Number(String(form.id_estudiante).replace('cohort-', ''));
      const students = estudiantesPorSemestre.get(semNum) || [];
      if (students.length === 0) { setError('No hay alumnos en ese semestre.'); return; }
      let ok = 0; const creditErrors = []; const otherErrors = [];
      for (const st of students) {
        try {
          await axios.post('/portal/api/jefe/inscripciones', buildPayload(st.id_estudiante));
          ok++;
        } catch (err) {
          const isCreditError = err.response?.data?.credit_error;
          const msg = `${st.nombre}: ${err.response?.data?.message || 'error'}`;
          if (isCreditError) creditErrors.push(st.nombre);
          else otherErrors.push(msg);
        }
      }
      const parts = [];
      if (ok > 0) parts.push(`${ok} inscrito(s) correctamente`);
      if (creditErrors.length > 0) parts.push(`Sin créditos: ${creditErrors.join(', ')}`);
      if (otherErrors.length > 0) parts.push(otherErrors.join(' | '));
      const msg = parts.join('. ');
      const hasErrors = creditErrors.length > 0 || otherErrors.length > 0;
      hasErrors ? setError(msg) : setSuccess(msg);
      await loadCatalog();
      return;
    }

    // Inscripción individual
    try {
      const res = await axios.post('/portal/api/jefe/inscripciones', buildPayload(Number(form.id_estudiante)));
      setSuccess(res.data?.message || 'Inscripción realizada con éxito');
      await loadCatalog();
    } catch (err) {
      setError(err.response?.data?.message || 'Error en la inscripción');
    }
  };

  const teacherOptions = useMemo(
    () => ['Todos', ...(catalog.docentes || []).filter((d) => !d.es_jefe_carrera).map((d) => `${d.nombre} ${d.apellido || ''}`.trim())],
    [catalog.docentes],
  );

  const openGraphModal = (type, yearNumber = null) => {
    setGraphModal({ type, yearNumber });
    setGraphZoom(1);
  };

  const closeGraphModal = () => {
    setGraphModal(null);
    setGraphZoom(1);
  };

  const activePreviewYear = hoveredYear || previewYear;
  const activePreviewInfo = yearHoverPreview.get(activePreviewYear) || { approvedCount: 0, availableNow: [], opensNext: [] };

  const loadStudents = async () => {
    setLoadingStudents(true);
    try {
      const res = await axios.get('/portal/api/jefe/estudiantes');
      setStudentsData(res.data.data || []);
    } catch {
      setStudentsData([]);
    } finally {
      setLoadingStudents(false);
    }
  };

  const loadDocentesMateriales = async () => {
    setLoadingDocentesMateriales(true);
    try {
      const res = await axios.get('/portal/api/jefe/docentes-materias');
      setDocentesMateriales(res.data.data || []);
    } catch {
      setDocentesMateriales([]);
    } finally {
      setLoadingDocentesMateriales(false);
    }
  };

  const openStudentModal = async (estudiante) => {
    setStudentModal({ estudiante, semestres: [] });
    setLoadingStudentMaterias(true);
    try {
      const res = await axios.get(`/portal/api/jefe/estudiantes/${estudiante.id_estudiante}/materias`);
      const data = res.data.data;
      setStudentModal({ estudiante: data.estudiante, semestres: data.semestres });
    } catch {
      setStudentModal(null);
    } finally {
      setLoadingStudentMaterias(false);
    }
  };

  const refreshStudentModal = async () => {
    if (!studentModal) return;
    const idEst = studentModal.estudiante.id_estudiante;
    try {
      const res = await axios.get(`/portal/api/jefe/estudiantes/${idEst}/materias`);
      const data = res.data.data;
      setStudentModal({ estudiante: data.estudiante, semestres: data.semestres });
    } catch {}
  };

  const handleConvalidar = async (idMateria) => {
    if (!studentModal) return;
    const idEst = studentModal.estudiante.id_estudiante;
    setConvalidandoId(idMateria);
    try {
      await axios.post(`/portal/api/jefe/estudiantes/${idEst}/materias/${idMateria}/convalidar`);
      await refreshStudentModal();
    } catch (err) {
      alert(err.response?.data?.message || 'Error al convalidar');
    } finally {
      setConvalidandoId(null);
    }
  };

  const handleDesconvalidar = async (idMateria) => {
    if (!studentModal) return;
    const idEst = studentModal.estudiante.id_estudiante;
    setConvalidandoId(idMateria);
    try {
      await axios.delete(`/portal/api/jefe/estudiantes/${idEst}/materias/${idMateria}/convalidar`);
      await refreshStudentModal();
    } catch (err) {
      alert(err.response?.data?.message || 'Error al revertir convalidación');
    } finally {
      setConvalidandoId(null);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="font-body-md overflow-hidden bg-black text-[#f6dddc] min-h-screen">
      <aside className="fixed left-0 top-0 h-full flex flex-col p-6 h-screen w-72 border-r border-neutral-800 bg-neutral-950 z-50 shadow-2xl shadow-rose-900/10">
        <div className="mb-10">
          <div className="flex items-center gap-3">
            <img src={logoRosie} alt="Logo" className="w-12 h-12 object-contain" />
            <div>
              <span className="text-2xl font-black bg-gradient-to-br from-rose-500 to-orange-400 bg-clip-text text-transparent">Rosie</span>
              <p className="font-medium text-xs tracking-tight text-neutral-500 mt-1">Head of Department</p>
            </div>
          </div>
        </div>
        <nav className="flex-1 flex flex-col gap-2">
          <a className={`flex items-center gap-3 rounded-xl py-3 px-4 transition-all cursor-pointer ${activeTab === 'agenda' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-white hover:bg-neutral-900'}`} onClick={() => { setActiveTab('agenda'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>
            <span className="material-symbols-outlined">view_module</span><span className="font-semibold text-sm">Asignar por Módulo</span>
          </a>
          <a className={`flex items-center gap-3 rounded-xl py-3 px-4 transition-all cursor-pointer ${activeTab === 'assign' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-white hover:bg-neutral-900'}`} onClick={() => { setActiveTab('assign'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>
            <span className="material-symbols-outlined">person_add</span><span className="font-semibold text-sm">Asignar Docente</span>
          </a>
          <a className={`flex items-center gap-3 rounded-xl py-3 px-4 transition-all cursor-pointer ${activeTab === 'enroll' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-white hover:bg-neutral-900'}`} onClick={() => { setActiveTab('enroll'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>
            <span className="material-symbols-outlined">person_add_alt</span><span className="font-semibold text-sm">Inscribir Alumno</span>
          </a>
          <a className={`flex items-center gap-3 rounded-xl py-3 px-4 transition-all cursor-pointer ${activeTab === 'create-docente' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-white hover:bg-neutral-900'}`} onClick={() => { setActiveTab('create-docente'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>
            <span className="material-symbols-outlined">badge</span><span className="font-semibold text-sm">Crear Docente</span>
          </a>
          <a className={`flex items-center gap-3 rounded-xl py-3 px-4 transition-all cursor-pointer ${activeTab === 'students' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-white hover:bg-neutral-900'}`} onClick={() => { setActiveTab('students'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>
            <span className="material-symbols-outlined">school</span><span className="font-semibold text-sm">Estudiantes</span>
          </a>
          <a className={`flex items-center gap-3 rounded-xl py-3 px-4 transition-all cursor-pointer ${activeTab === 'docentes-materias' ? 'bg-neutral-900 text-white border-l-4 border-rose-500' : 'text-neutral-400 hover:text-white hover:bg-neutral-900'}`} onClick={() => { setActiveTab('docentes-materias'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}>
            <span className="material-symbols-outlined">groups</span><span className="font-semibold text-sm">Docentes y Materias</span>
          </a>
        </nav>

        <div className="mt-auto pt-6 border-t border-neutral-800">
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

      <main className="ml-72 min-h-screen flex flex-col">
        <header className="fixed top-0 right-0 left-72 z-40 flex justify-between items-center px-8 h-16 border-b border-neutral-800 bg-black/80">
          <h1 className="font-bold text-lg text-white">Academic Management</h1>
          <button onClick={loadCatalog} className="bg-neutral-900 rounded-lg px-3 py-1.5 text-sm text-neutral-200">Recargar</button>
        </header>

        <div className="mt-16 p-8 flex gap-8 overflow-y-auto h-[calc(100vh-64px)]">
          <div className="flex-1 flex flex-col gap-6">
            {feedback && (
              <div className={`px-4 py-3 rounded-xl border text-sm flex items-center justify-between shadow-lg ${feedbackIsError ? 'border-red-500/50 bg-red-500/10 text-red-300 shadow-red-900/10' : 'border-cyan-500/40 bg-cyan-500/10 text-cyan-200 shadow-cyan-500/5'}`}>
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-sm">{feedbackIsError ? 'error' : 'info'}</span>
                  {feedback}
                </div>
                <button onClick={() => { setFeedback(''); setFeedbackIsError(false); }} className={`material-symbols-outlined text-sm ${feedbackIsError ? 'text-red-500 hover:text-red-300' : 'text-cyan-500 hover:text-cyan-300'}`}>close</button>
              </div>
            )}
            {activeTab === 'agenda' && (
              <>
                <div>
                  <h2 className="text-white text-3xl font-bold">Agenda por Carrera</h2>
                  <p className="text-neutral-500">Inscripción → Semestre → Módulo</p>
                  <button
                    onClick={() => openGraphModal('full')}
                    className="mt-3 px-4 py-2 rounded-lg bg-neutral-900 border border-neutral-800 text-sm font-semibold hover:border-rose-500/50"
                  >
                    Ver malla curricular
                  </button>
                </div>

                <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6 flex-1 min-h-0 overflow-y-auto">
                  <div className="space-y-4">
                    {years.map((yearNumber) => {
                      const semA = 1;
                      const semB = 2;
                      return (
                        <div
                          key={yearNumber}
                          className="relative rounded-2xl border border-neutral-800 bg-[#131313] p-4 transition-colors hover:border-cyan-500/50"
                          onMouseEnter={() => { setHoveredYear(yearNumber); setPreviewYear(yearNumber); }}
                          onMouseLeave={() => setHoveredYear(null)}
                        >
                          <div className="flex items-center justify-between mb-3">
                            <div className="text-xs font-bold text-rose-400 uppercase tracking-widest">{yearLabel(yearNumber)}</div>
                            <div className="flex items-center gap-2">
                              <button
                                onClick={() => setStudentsYearModal(yearNumber)}
                                className="text-[10px] px-3 py-1.5 rounded-md border border-neutral-700 text-neutral-300 hover:border-cyan-500/60"
                              >
                                Ver estudiantes de la inscripción
                              </button>
                              <button
                                onClick={() => openGraphModal('year', yearNumber)}
                                className="text-[10px] px-3 py-1.5 rounded-md border border-neutral-700 text-neutral-300 hover:border-rose-500/60"
                              >
                                Ver mapa de la inscripción
                              </button>
                            </div>
                          </div>

                          <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            {[semA, semB].map((semestre) => (
                              <div key={`${yearNumber}-s${semestre}`} className="rounded-xl border border-neutral-800 bg-[#161616] p-3">
                                <div className="text-[10px] font-bold text-orange-400 uppercase tracking-widest mb-2">Semestre {yearSemesterLabel(semestre)}</div>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                  {[1, 2, 3].map((moduloNumber) => {
                                    const slotItems = cellItems(yearNumber, semestre, moduloNumber);
                                    const moduloDateRange = moduloDateRangeByCell.get(`${semestre}-${moduloNumber}`) || '';
                                    return (
                                      <div key={`${yearNumber}-${semestre}-${moduloNumber}`} className="rounded-xl border border-neutral-800 bg-[#1a1a1a] p-3 min-h-[130px]">
                                        <div className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-2">Modulo {moduloNumber}</div>
                                        {moduloDateRange && (
                                          <div className="text-[10px] text-neutral-500 mb-2">{moduloDateRange}</div>
                                        )}
                                        <div className="space-y-2">
                                          {slotItems.map((cell) => {
                                            const pendingStyle = cell.estado === 'Pendiente';
                                            return (
                                              <button key={cell.id} onClick={() => { setActiveTab('assign'); setSelectedCell({ yearNumber, semesterNumber: semestre, moduloNumber }); window.scrollTo({ top: 0, behavior: 'smooth' }); }} className={`w-full text-left p-2 rounded-lg border-l-4 ${pendingStyle ? 'border-orange-500/50 bg-orange-500/5' : 'border-rose-500 bg-rose-500/5'}`}>
                                                <span className={`text-[9px] px-1.5 py-0.5 rounded-full font-bold ${pendingStyle ? 'bg-orange-500/20 text-orange-400' : 'bg-rose-500/20 text-rose-400'}`}>{cell.estado}</span>
                                                <div className="text-white text-xs font-bold mt-1">{cell.materia}</div>
                                                <div className="text-neutral-400 text-[10px]">{cell.docente}</div>
                                                <div className="text-neutral-600 text-[10px]">{cell.aula}</div>
                                              </button>
                                            );
                                          })}
                                          {slotItems.length === 0 && (
                                            <button onClick={() => { setActiveTab('assign'); setSelectedCell({ yearNumber, semesterNumber: semestre, moduloNumber }); window.scrollTo({ top: 0, behavior: 'smooth' }); }} className="w-full border-2 border-dashed border-neutral-800 rounded-lg py-3 text-center text-neutral-600 hover:text-neutral-400">
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
              </>
            )}

            {activeTab === 'assign' && (
              <div id="assign-section" className="bg-[#1a1a1a] p-6 rounded-[24px] border border-[#2d2d2d]">
                <h3 className="text-xl font-bold mb-4">Vincular Docente y Aula</h3>
                <p className="text-xs text-neutral-500 mb-6">
                  {isMateriaAsignarSemestral
                    ? 'Materia semestral (inglés): selecciona el semestre completo. El docente se asignará a todos los módulos automáticamente.'
                    : 'Selecciona módulo y horario específicos. Solo aparecen horarios donde el docente tiene disponibilidad registrada.'}
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                  <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_materia} onChange={(e) => setForm((p) => ({ ...p, id_materia: e.target.value, id_modulo: '', id_semestre: '', id_bloque: '' }))}>
                    <option value="">Seleccionar Materia</option>
                    {(catalog.materias || []).map((m) => <option key={m.id_materia} value={m.id_materia}>{m.nombre}</option>)}
                  </select>
                  <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_docente} onChange={(e) => setForm((p) => ({ ...p, id_docente: e.target.value, id_bloque: '' }))}>
                    <option value="">Seleccionar Docente</option>
                    {(catalog.docentes || []).filter((d) => !d.es_jefe_carrera).map((d) => <option key={d.id_docente} value={d.id_docente}>{d.nombre} {d.apellido || ''}</option>)}
                  </select>
                  <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_aula} onChange={(e) => setForm((p) => ({ ...p, id_aula: e.target.value }))}>
                    <option value="">Seleccionar Aula</option>
                    {aulasDisponibles.map((a) => <option key={a.id_aula} value={a.id_aula}>{a.nombre}</option>)}
                  </select>
                  {isMateriaAsignarSemestral ? (
                    <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_semestre} onChange={(e) => setForm((p) => ({ ...p, id_semestre: e.target.value, id_bloque: '' }))}>
                      <option value="">Seleccionar Semestre</option>
                      {(catalog.semestres || []).map((s) => <option key={s.id_semestre} value={s.id_semestre}>{s.nombre}</option>)}
                    </select>
                  ) : (
                    <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_modulo} onChange={(e) => setForm((p) => ({ ...p, id_modulo: e.target.value, id_bloque: '' }))}>
                      <option value="">Seleccionar Módulo</option>
                      {(catalog.modulos || []).map((m) => <option key={m.id_modulo} value={m.id_modulo}>{m.nombre} ({formatDateShort(m.fecha_inicio)})</option>)}
                    </select>
                  )}
                  <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_bloque} onChange={(e) => setForm((p) => ({ ...p, id_bloque: e.target.value }))}>
                    <option value="">
                      {isMateriaAsignarSemestral
                        ? (form.id_docente && form.id_semestre ? (bloquesLibresSemestral.length === 0 ? 'Sin disponibilidad en todos los módulos' : 'Seleccionar Horario') : 'Selecciona docente y semestre primero')
                        : (form.id_docente && form.id_modulo ? (bloquesDisponiblesParaAsignar.length === 0 ? 'Sin disponibilidad en este módulo' : 'Seleccionar Horario') : 'Selecciona docente y módulo primero')}
                    </option>
                    {(isMateriaAsignarSemestral ? bloquesLibresSemestral : bloquesLibresParaDocente).map((b) => (
                      <option key={b.id_bloque} value={b.id_bloque}>
                        Bloque {b.nombre} — {b.hora_inicio?.slice(0, 5)} a {b.hora_fin?.slice(0, 5)}
                      </option>
                    ))}
                  </select>
                </div>
                <button onClick={handleAssign} className="mt-4 px-8 py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-500">
                  Vincular Docente
                </button>
              </div>
            )}

            {activeTab === 'create-docente' && (
              <div className="bg-[#1a1a1a] p-6 rounded-[24px] border border-[#2d2d2d] max-w-lg">
                <h3 className="text-xl font-bold mb-2">Crear cuenta de docente</h3>
                <p className="text-sm text-neutral-400 mb-6">La contraseña inicial será <span className="font-mono bg-neutral-800 px-2 py-0.5 rounded text-rose-300">UPB123</span>. El docente puede cambiarla desde su perfil.</p>
                <form onSubmit={handleCreateDocente} className="grid gap-4">
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="text-xs text-neutral-500 uppercase font-bold mb-1 block">Nombre</label>
                      <input
                        type="text"
                        required
                        value={docenteForm.nombre}
                        onChange={e => setDocenteForm(p => ({ ...p, nombre: e.target.value }))}
                        placeholder="Ana"
                        className="w-full bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3 text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500"
                      />
                    </div>
                    <div>
                      <label className="text-xs text-neutral-500 uppercase font-bold mb-1 block">Apellido</label>
                      <input
                        type="text"
                        value={docenteForm.apellido}
                        onChange={e => setDocenteForm(p => ({ ...p, apellido: e.target.value }))}
                        placeholder="García"
                        className="w-full bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3 text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="text-xs text-neutral-500 uppercase font-bold mb-1 block">Correo electrónico</label>
                    <input
                      type="email"
                      required
                      value={docenteForm.correo}
                      onChange={e => setDocenteForm(p => ({ ...p, correo: e.target.value }))}
                      placeholder="ana.garcia@upb.edu.bo"
                      className="w-full bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3 text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500"
                    />
                  </div>
                  {docenteFeedback && (
                    <div className={`px-4 py-3 rounded-xl text-sm border ${docenteFeedback.startsWith('Error') ? 'bg-red-500/10 border-red-500/30 text-red-300' : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'}`}>
                      {docenteFeedback}
                    </div>
                  )}
                  <button
                    type="submit"
                    disabled={savingDocente}
                    className="px-8 py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-500 disabled:opacity-50 transition-all"
                  >
                    {savingDocente ? 'Creando...' : 'Crear Docente'}
                  </button>
                </form>
              </div>
            )}

            {activeTab === 'students' && (
              <StudentsTab
                studentsData={studentsData}
                loading={loadingStudents}
                onRefresh={loadStudents}
                onSelectStudent={openStudentModal}
              />
            )}

            {activeTab === 'enroll' && (
              <div className="bg-[#1a1a1a] p-6 rounded-[24px] border border-[#2d2d2d]">
                <h3 className="text-xl font-bold mb-4">Inscribir alumno a una materia</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_modulo} onChange={(e) => setForm((p) => ({ ...p, id_modulo: e.target.value }))}>
                    <option value="">Selección Automática (Mejor opción)</option>
                    {(catalog.modulos || []).map((m) => <option key={`enroll-mod-${m.id_modulo}`} value={m.id_modulo}>{m.nombre} ({formatDateShort(m.fecha_inicio)})</option>)}
                  </select>
                  <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_materia} onChange={(e) => setForm((p) => ({ ...p, id_materia: e.target.value }))}>
                    <option value="">Seleccionar Materia</option>
                    {materiasParaInscribir.map((m) => <option key={`enroll-mat-${m.id_materia}`} value={m.id_materia}>{m.nombre}</option>)}
                  </select>
                  <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_estudiante} onChange={(e) => setForm((p) => ({ ...p, id_estudiante: e.target.value, id_materia: '' }))}>
                    <option value="">Seleccionar Estudiante</option>
                    {Array.from(estudiantesPorSemestre.entries()).map(([sem, students]) => (
                      <optgroup key={`sem-${sem}`} label={`Semestre ${sem}`}>
                        {students.map((est) => <option key={`enroll-est-${est.id_estudiante}`} value={est.id_estudiante}>{est.nombre} {est.apellido}</option>)}
                      </optgroup>
                    ))}
                  </select>
                </div>
                <button onClick={handleEnroll} className="mt-4 px-8 py-3 bg-cyan-600 text-white font-bold rounded-xl hover:bg-cyan-500">
                  Inscribir Alumno
                </button>
              </div>
            )}

            {activeTab === 'docentes-materias' && (
              <div className="bg-[#1a1a1a] p-6 rounded-[24px] border border-[#2d2d2d] flex-1 overflow-y-auto">
                <div className="flex items-center justify-between mb-6">
                  <div>
                    <h3 className="text-2xl font-bold text-white">Docentes y Materias</h3>
                    <p className="text-neutral-400 text-sm mt-1">Vista de docentes con sus materias asignadas y estudiantes inscritos</p>
                  </div>
                  <button
                    onClick={loadDocentesMateriales}
                    className="px-4 py-2 bg-neutral-900 border border-neutral-800 rounded-lg hover:border-rose-500/50 text-sm font-medium text-white"
                  >
                    Recargar
                  </button>
                </div>

                {loadingDocentesMateriales ? (
                  <div className="flex items-center justify-center py-12 text-neutral-400">
                    <span>Cargando...</span>
                  </div>
                ) : docentesMateriales.length === 0 ? (
                  <div className="flex items-center justify-center py-12 text-neutral-400">
                    <span>No hay docentes con materias asignadas</span>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {docentesMateriales.map((docente) => (
                      <div key={docente.id_docente} className="bg-neutral-900 border border-neutral-800 rounded-xl p-4 overflow-hidden">
                        <div className="bg-gradient-to-r from-rose-600 to-orange-500 px-4 py-3 rounded-lg mb-4">
                          <h4 className="text-lg font-bold text-white">{docente.nombre} {docente.apellido}</h4>
                          <p className="text-xs text-white/80">{docente.correo}</p>
                          <p className="text-xs text-white/60 mt-1">{docente.materias.length} materia(s)</p>
                        </div>

                        {docente.materias.length === 0 ? (
                          <div className="text-neutral-500 text-sm p-3">Sin materias asignadas</div>
                        ) : (
                          <div className="space-y-3">
                            {docente.materias.map((materia) => (
                              <div key={materia.id_materia} className="bg-neutral-800/50 border border-neutral-700 rounded-lg p-3">
                                <div className="flex items-start justify-between mb-2">
                                  <div>
                                    <h5 className="font-semibold text-white text-sm">{materia.nombre}</h5>
                                    <p className="text-xs text-neutral-400">{materia.creditos} créditos</p>
                                  </div>
                                </div>

                                {materia.modulos.length > 0 && (
                                  <div className="mb-3">
                                    <p className="text-xs font-medium text-neutral-300 mb-2">Módulos:</p>
                                    <div className="flex flex-wrap gap-2">
                                      {materia.modulos.map((mod, idx) => (
                                        <div key={idx} className="bg-neutral-700/50 rounded px-2 py-1 text-xs text-neutral-200">
                                          <div className="font-medium">{mod.nombre}</div>
                                          {mod.bloque && mod.aula && (
                                            <>
                                              <div className="text-neutral-400">{mod.horario}</div>
                                              <div className="text-neutral-400">Aula: {mod.aula}</div>
                                            </>
                                          )}
                                        </div>
                                      ))}
                                    </div>
                                  </div>
                                )}

                                {materia.estudiantes.length > 0 && (
                                  <div>
                                    <p className="text-xs font-medium text-neutral-300 mb-2">
                                      Estudiantes ({materia.estudiantes.length}):
                                    </p>
                                    <div className="grid grid-cols-1 gap-1 max-h-40 overflow-y-auto">
                                      {materia.estudiantes.map((est) => (
                                        <div key={est.id_estudiante} className="bg-neutral-900/50 rounded px-2 py-1 text-xs flex items-center justify-between">
                                          <div>
                                            <div className="text-neutral-100">{est.nombre} {est.apellido}</div>
                                            <div className="text-neutral-500">{est.correo}</div>
                                          </div>
                                          <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                            est.estado === 'aprobada' ? 'bg-emerald-500/20 text-emerald-300' :
                                            est.estado === 'cursando' ? 'bg-cyan-500/20 text-cyan-300' :
                                            est.estado === 'pendiente' ? 'bg-amber-500/20 text-amber-300' :
                                            'bg-neutral-700 text-neutral-300'
                                          }`}>
                                            {est.estado}
                                          </span>
                                        </div>
                                      ))}
                                    </div>
                                  </div>
                                )}

                                {materia.estudiantes.length === 0 && (
                                  <div className="text-neutral-500 text-xs py-2">Sin estudiantes inscritos</div>
                                )}
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>

          <div className="w-80 flex flex-col gap-6">
            <div className="bg-neutral-900 border border-neutral-800 rounded-[24px] p-6 flex flex-col h-full">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-white text-lg font-bold">Materias Habilitadas</h3>
                <span className="bg-cyan-500/20 text-cyan-300 text-[10px] font-black px-2 py-1 rounded-md">{activePreviewInfo.availableNow.length}</span>
              </div>
              <div className="mb-4">
                <label className="text-[10px] text-neutral-500 uppercase font-bold px-1">Inscripción</label>
                <select value={previewYear} onChange={(e) => setPreviewYear(Number(e.target.value))} className="mt-1 w-full bg-neutral-950 border border-neutral-800 rounded-lg text-xs text-neutral-300 px-3 py-2">
                  {years.map((y) => <option key={`preview-year-${y}`} value={y}>{yearLabel(y)}</option>)}
                </select>
                <p className="text-[10px] text-neutral-500 mt-2">Simulación: {activePreviewInfo.approvedCount} materias aprobadas antes de iniciar.</p>
              </div>
              <div className="space-y-4 overflow-y-auto">
                {(activePreviewInfo.availableNow || []).map((m) => (
                  <div key={`avail-${m.id_materia}`} className="p-4 rounded-xl bg-neutral-950 border border-neutral-800">
                    <div className="flex justify-between items-start mb-2">
                      <h4 className="text-neutral-200 font-bold text-sm">{m.nombre}</h4>
                      <span className="text-cyan-400 material-symbols-outlined text-sm">check_circle</span>
                    </div>
                    <div className="flex items-center gap-2 mb-1"><span className="text-[10px] text-neutral-500 font-medium">Nivel {m.anio_academico}</span></div>
                    <div className="text-[10px] text-neutral-600">Semestre {m.semestre_academico}</div>
                  </div>
                ))}
                {activePreviewInfo.availableNow.length === 0 && (
                  <div className="text-sm text-neutral-400">No hay materias habilitadas para esta inscripción con la simulación actual.</div>
                )}
              </div>
              <div className="mt-4 pt-4 border-t border-neutral-800">
                <div className="flex items-center justify-between mb-3">
                  <h4 className="text-xs font-bold text-orange-300 uppercase tracking-widest">Materias que abre</h4>
                  <span className="bg-orange-500/20 text-orange-300 text-[10px] font-black px-2 py-1 rounded-md">{activePreviewInfo.opensNext.length}</span>
                </div>
                <div className="space-y-2 max-h-40 overflow-y-auto">
                  {activePreviewInfo.opensNext.slice(0, 20).map((m) => (
                    <div key={`opens-${m.id_materia}`} className="text-[11px] text-neutral-300 rounded-md border border-neutral-800 bg-neutral-950 px-3 py-2">
                      {m.nombre}
                    </div>
                  ))}
                  {activePreviewInfo.opensNext.length === 0 && <div className="text-[11px] text-neutral-500">Sin aperturas directas.</div>}
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      {graphModal && (
        <div className="fixed inset-0 z-[100] bg-black/75 backdrop-blur-sm p-6">
          <div className="w-full h-full rounded-2xl border border-neutral-800 bg-[#101010] flex flex-col">
            <div className="flex items-center justify-between p-4 border-b border-neutral-800">
              <div>
                <h3 className="text-white text-lg font-bold">
                  {graphModal.type === 'year' ? `Malla de ${yearLabel(graphModal.yearNumber)}` : 'Malla Curricular Completa'}
                </h3>
                <p className="text-xs text-neutral-400">
                  Zoom: {(graphZoom * 100).toFixed(0)}%
                </p>
              </div>
              <div className="flex items-center gap-2">
                <button onClick={() => setGraphZoom((z) => Math.max(0.5, z - 0.1))} className="px-3 py-1.5 rounded-md bg-neutral-900 border border-neutral-700 text-sm">-</button>
                <button onClick={() => setGraphZoom(1)} className="px-3 py-1.5 rounded-md bg-neutral-900 border border-neutral-700 text-sm">100%</button>
                <button onClick={() => setGraphZoom((z) => Math.min(2.2, z + 0.1))} className="px-3 py-1.5 rounded-md bg-neutral-900 border border-neutral-700 text-sm">+</button>
                <button onClick={closeGraphModal} className="ml-2 px-3 py-1.5 rounded-md bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm">Cerrar</button>
              </div>
            </div>
            <div
              className="flex-1 overflow-auto p-4"
              onWheel={(e) => {
                if (!e.ctrlKey) return;
                e.preventDefault();
                setGraphZoom((z) => {
                  const next = e.deltaY > 0 ? z - 0.05 : z + 0.05;
                  return Math.max(0.5, Math.min(2.2, next));
                });
              }}
            >
              <div style={{ width: conceptualGraph.width * graphZoom, minHeight: conceptualGraph.height * graphZoom }} className="relative origin-top-left">
                <div style={{ transform: `scale(${graphZoom})`, transformOrigin: 'top left', width: conceptualGraph.width, height: conceptualGraph.height }} className="relative">
                  {graphModal.type === 'full' && (
                    <div className="absolute top-0 left-0 right-0 z-10 px-2 py-1 grid" style={{ gridTemplateColumns: `repeat(${Math.max(1, maxSemestre)}, minmax(180px, 1fr))` }}>
                      {Array.from({ length: Math.max(1, maxSemestre) }, (_, i) => (
                        <div key={i} className="text-center text-[11px] font-bold uppercase tracking-widest text-neutral-500">Sem {yearSemesterLabel(i + 1)}</div>
                      ))}
                    </div>
                  )}
                  <svg width={conceptualGraph.width} height={conceptualGraph.height} className="absolute inset-0 pointer-events-none">
                    <defs>
                      <marker id="arrowhead-modal" markerWidth="8" markerHeight="8" refX="7" refY="3.5" orient="auto">
                        <polygon points="0 0, 8 3.5, 0 7" fill={edgeColor} />
                      </marker>
                    </defs>
                    {conceptualGraph.edges.map((e) => (
                      <line key={`modal-${e.id}`} x1={e.x1} y1={e.y1} x2={e.x2} y2={e.y2} stroke={edgeColor} strokeOpacity="0.5" strokeWidth="1.4" markerEnd="url(#arrowhead-modal)" />
                    ))}
                  </svg>
                  {conceptualGraph.nodes.map((n) => {
                    let style = {
                      borderColor: n.color.border,
                      background: n.color.bg,
                      color: n.color.text,
                      opacity: 1,
                      boxShadow: 'none',
                    };
                    if (graphModal.type === 'year') {
                      const p = progressByYear.get(graphModal.yearNumber) || { completed: new Set(), current: new Set(), available: new Set() };
                      const isCompleted = p.completed.has(n.id);
                      const isCurrent = p.current.has(n.id);
                      const isAvailable = p.available.has(n.id);
                      style = isCompleted
                        ? { borderColor: '#34d399', background: 'rgba(16,185,129,0.16)', color: '#d1fae5', opacity: 1, boxShadow: '0 0 14px rgba(16,185,129,0.25)' }
                        : isCurrent
                          ? { borderColor: '#22d3ee', background: 'rgba(6,182,212,0.16)', color: '#cffafe', opacity: 1, boxShadow: '0 0 14px rgba(34,211,238,0.25)' }
                          : isAvailable
                            ? { borderColor: '#f59e0b', background: 'rgba(245,158,11,0.14)', color: '#fde68a', opacity: 1, boxShadow: '0 0 14px rgba(245,158,11,0.22)' }
                            : { borderColor: n.color.border, background: n.color.bg, color: n.color.text, opacity: 0.30, boxShadow: 'none' };
                    }
                    return (
                      <div
                        key={`modal-node-${n.id}`}
                        className="absolute rounded-lg border px-3 py-2 text-xs font-semibold leading-tight"
                        style={{ width: conceptualGraph.nodeW, height: conceptualGraph.nodeH, left: n.x, top: n.y + 18, ...style }}
                      >
                        {n.nombre}
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
            <div className="p-3 border-t border-neutral-800 flex flex-wrap gap-2">
              {graphModal.type === 'year' ? (
                <>
                  <span className="text-[11px] text-neutral-300"><span className="inline-block w-2 h-2 rounded-full bg-emerald-400 mr-2" />Ya cursada</span>
                  <span className="text-[11px] text-neutral-300"><span className="inline-block w-2 h-2 rounded-full bg-cyan-400 mr-2" />Cursando</span>
                  <span className="text-[11px] text-neutral-300"><span className="inline-block w-2 h-2 rounded-full bg-amber-400 mr-2" />Habilitada</span>
                  <span className="text-[11px] text-neutral-300"><span className="inline-block w-2 h-2 rounded-full bg-neutral-600 mr-2" />Falta cursar</span>
                </>
              ) : (
                Object.entries(conceptualGraph.groupColors).map(([name, c]) => (
                  <span key={name} className="text-[11px] px-2 py-1 rounded-md border" style={{ borderColor: c.border, color: c.text, background: c.bg }}>
                    {name}
                  </span>
                ))
              )}
            </div>
          </div>
        </div>
      )}

      {studentsYearModal && (
        <div className="fixed inset-0 z-[110] bg-black/75 backdrop-blur-sm p-6">
          <div className="w-full max-w-3xl mx-auto rounded-2xl border border-neutral-800 bg-[#101010] flex flex-col max-h-[85vh]">
            <div className="flex items-center justify-between p-4 border-b border-neutral-800">
              <div>
                <h3 className="text-white text-lg font-bold">Estudiantes de {yearLabel(studentsYearModal)}</h3>
                <p className="text-xs text-neutral-400">{(studentsByYear.get(studentsYearModal) || []).length} registrados</p>
              </div>
              <button onClick={() => setStudentsYearModal(null)} className="px-3 py-1.5 rounded-md bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm">Cerrar</button>
            </div>
            <div className="p-4 overflow-y-auto">
              {(studentsByYear.get(studentsYearModal) || []).length === 0 ? (
                <div className="text-sm text-neutral-400">No hay estudiantes identificados en esta inscripción.</div>
              ) : (
                <div className="space-y-2">
                  {(studentsByYear.get(studentsYearModal) || []).map((s) => (
                    <div key={s.id_estudiante} className="rounded-lg border border-neutral-800 bg-neutral-950 p-3">
                      <div className="text-sm font-semibold text-neutral-100">{s.nombre} {s.apellido || ''}</div>
                      <div className="text-xs text-neutral-400">{s.correo}</div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {showProfile && (
        <ProfileModal onClose={() => setShowProfile(false)} />
      )}

      {studentModal && (
        <StudentMateriasModal
          data={studentModal}
          loading={loadingStudentMaterias}
          onClose={() => setStudentModal(null)}
          onConvalidar={handleConvalidar}
          onDesconvalidar={handleDesconvalidar}
          convalidandoId={convalidandoId}
        />
      )}
    </div>
  );
}

/* ─── Componentes auxiliares ─────────────────────────── */

const ESTADO_STYLES = {
  aprobada:    { bg: 'bg-emerald-500/20', text: 'text-emerald-300', border: 'border-emerald-500/30', dot: 'bg-emerald-400' },
  cursando:    { bg: 'bg-cyan-500/20',    text: 'text-cyan-300',    border: 'border-cyan-500/30',    dot: 'bg-cyan-400' },
  reprobada:   { bg: 'bg-red-500/20',     text: 'text-red-300',     border: 'border-red-500/30',     dot: 'bg-red-400' },
  convalidada: { bg: 'bg-violet-500/20',  text: 'text-violet-300',  border: 'border-violet-500/30',  dot: 'bg-violet-400' },
  habilitada:  { bg: 'bg-amber-500/20',   text: 'text-amber-300',   border: 'border-amber-500/30',   dot: 'bg-amber-400' },
  bloqueada:   { bg: 'bg-neutral-700/30', text: 'text-neutral-500', border: 'border-neutral-700/30', dot: 'bg-neutral-600' },
  pendiente:   { bg: 'bg-neutral-700/30', text: 'text-neutral-400', border: 'border-neutral-700/30', dot: 'bg-neutral-500' },
};

function EstadoBadge({ estado }) {
  const s = ESTADO_STYLES[estado] || ESTADO_STYLES.pendiente;
  return (
    <span className={`inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold border ${s.bg} ${s.text} ${s.border}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${s.dot}`} />
      {estado}
    </span>
  );
}

function StudentsTab({ studentsData, loading, onRefresh, onSelectStudent }) {
  const [search, setSearch] = useState('');

  if (loading) return (
    <div className="py-20 text-center text-neutral-400">Cargando estudiantes...</div>
  );

  if (!studentsData) return null;

  const filteredGroups = studentsData.map(group => ({
    ...group,
    estudiantes: group.estudiantes.filter(e =>
      !search || e.nombre.toLowerCase().includes(search.toLowerCase()) || e.correo.toLowerCase().includes(search.toLowerCase())
    ),
  })).filter(g => g.estudiantes.length > 0);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-white">Estudiantes por cohorte</h2>
          <p className="text-sm text-neutral-400 mt-0.5">Haz clic en un estudiante para ver su historial de materias</p>
        </div>
        <div className="flex gap-2">
          <input
            type="text"
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="Buscar por nombre o correo..."
            className="bg-neutral-900 border border-neutral-700 rounded-xl px-4 py-2 text-sm text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500 w-64"
          />
          <button onClick={onRefresh} className="bg-neutral-900 border border-neutral-700 rounded-xl px-3 py-2 text-xs text-neutral-300 hover:border-neutral-600">
            <span className="material-symbols-outlined text-sm">refresh</span>
          </button>
        </div>
      </div>

      {filteredGroups.length === 0 && (
        <div className="py-20 text-center text-neutral-500">No se encontraron estudiantes.</div>
      )}

      {filteredGroups.map(group => (
        <div key={group.cohorte ?? 'sin-cohorte'} className="bg-[#1a1a1a] rounded-[20px] border border-[#2d2d2d] overflow-hidden">
          <div className="flex items-center gap-3 px-6 py-4 border-b border-[#2d2d2d]">
            <span className="material-symbols-outlined text-rose-400">calendar_today</span>
            <h3 className="text-white font-bold">
              {group.cohorte ? `Cohorte ${group.cohorte}` : 'Sin cohorte registrada'}
            </h3>
            <span className="ml-auto px-2 py-0.5 rounded-md bg-neutral-800 text-neutral-400 text-xs font-bold">
              {group.estudiantes.length} estudiante{group.estudiantes.length !== 1 ? 's' : ''}
            </span>
          </div>
          <div className="divide-y divide-[#2d2d2d]">
            {group.estudiantes.map(est => (
              <button
                key={est.id_estudiante}
                onClick={() => onSelectStudent(est)}
                className="w-full flex items-center gap-4 px-6 py-4 hover:bg-neutral-800/50 transition-all text-left group"
              >
                <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-600 to-orange-500 flex items-center justify-center flex-shrink-0">
                  <span className="text-white font-black text-sm">{est.nombre.charAt(0)}</span>
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-semibold text-neutral-100 truncate group-hover:text-white">{est.nombre}</p>
                  <p className="text-xs text-neutral-500 truncate">{est.correo}</p>
                </div>
                {est.es_traspaso && (
                  <span className="flex-shrink-0 px-2.5 py-1 rounded-full text-[10px] font-black bg-violet-500/20 text-violet-300 border border-violet-500/40">
                    TRASPASO
                  </span>
                )}
                <span className="material-symbols-outlined text-neutral-700 group-hover:text-neutral-400 transition-colors text-sm">chevron_right</span>
              </button>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

function StudentMateriasModal({ data, loading, onClose, onConvalidar, onDesconvalidar, convalidandoId }) {
  const { estudiante, semestres } = data;
  const esTraspaso = estudiante?.es_traspaso;

  return (
    <div
      className="fixed inset-0 z-[120] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
      onClick={e => { if (e.target === e.currentTarget) onClose(); }}
    >
      <div className="w-full max-w-3xl rounded-2xl border border-neutral-800 bg-[#101010] flex flex-col max-h-[90vh]">
        {/* Header */}
        <div className="flex items-center justify-between p-5 border-b border-neutral-800 flex-shrink-0">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-600 to-orange-500 flex items-center justify-center">
              <span className="text-white font-black">{estudiante?.nombre?.charAt(0)}</span>
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-white font-bold text-lg leading-tight">{estudiante?.nombre}</h3>
                {esTraspaso && (
                  <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-violet-500/20 text-violet-300 border border-violet-500/40">
                    TRASPASO
                  </span>
                )}
              </div>
              <p className="text-xs text-neutral-400">{estudiante?.correo} · Cohorte {estudiante?.cohorte_ingreso ?? '—'}</p>
            </div>
          </div>
          <button onClick={onClose} className="px-3 py-1.5 rounded-lg bg-neutral-800 border border-neutral-700 text-neutral-300 text-sm hover:bg-neutral-700">
            Cerrar
          </button>
        </div>

        {/* Leyenda */}
        <div className="px-5 py-2.5 border-b border-neutral-800 flex flex-wrap gap-3 flex-shrink-0">
          {Object.entries(ESTADO_STYLES).map(([estado, s]) => (
            <span key={estado} className={`inline-flex items-center gap-1 text-[10px] font-semibold ${s.text}`}>
              <span className={`w-1.5 h-1.5 rounded-full ${s.dot}`} />{estado}
            </span>
          ))}
          {esTraspaso && (
            <span className="ml-auto text-[10px] text-violet-400 font-semibold">
              · Haz clic en una materia para convalidarla
            </span>
          )}
        </div>

        {/* Content */}
        <div className="overflow-y-auto flex-1 p-5 space-y-5">
          {loading && <p className="text-center text-neutral-400 py-10">Cargando materias...</p>}

          {!loading && semestres.map(sem => (
            <div key={sem.semestre}>
              <div className="flex items-center gap-2 mb-3">
                <span className="text-xs font-black text-neutral-500 uppercase tracking-widest">Semestre {sem.semestre}</span>
                <div className="flex-1 h-px bg-neutral-800" />
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {sem.materias.map(mat => {
                  const isConvalidada = mat.estado === 'convalidada';
                  const canConvalidar = esTraspaso && (mat.estado === 'habilitada' || mat.estado === 'bloqueada' || mat.estado === 'pendiente');
                  const isProcessing = convalidandoId === mat.id_materia;

                  return (
                    <div
                      key={mat.id_materia}
                      className={`rounded-xl border p-3 transition-all ${
                        (canConvalidar || isConvalidada) && esTraspaso
                          ? 'cursor-pointer hover:border-violet-500/60 hover:bg-violet-500/5'
                          : ''
                      } ${ESTADO_STYLES[mat.estado]?.border || 'border-neutral-800'} bg-neutral-950`}
                      onClick={() => {
                        if (!esTraspaso) return;
                        if (isProcessing) return;
                        if (isConvalidada) {
                          onDesconvalidar(mat.id_materia);
                        } else if (canConvalidar) {
                          onConvalidar(mat.id_materia);
                        }
                      }}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <p className="text-sm font-semibold text-neutral-100 leading-tight">{mat.nombre}</p>
                        {isProcessing ? (
                          <span className="text-[10px] text-neutral-500 flex-shrink-0">...</span>
                        ) : (
                          <EstadoBadge estado={mat.estado} />
                        )}
                      </div>
                      {esTraspaso && (isConvalidada || canConvalidar) && (
                        <p className="text-[10px] text-neutral-600 mt-1">
                          {isConvalidada ? 'Clic para revertir convalidación' : 'Clic para convalidar'}
                        </p>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}