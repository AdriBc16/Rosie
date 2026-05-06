import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';

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
  });

  const [loading, setLoading] = useState(true);
  const [feedback, setFeedback] = useState('');
  const [showCurricula, setShowCurricula] = useState(false);

  const [teacherFilter, setTeacherFilter] = useState('Todos');
  const [statusFilter, setStatusFilter] = useState('Todos');
  const [moduleFilter, setModuleFilter] = useState('Todos');
  const [semesterFilter, setSemesterFilter] = useState('Todos');

  const [selectedCell, setSelectedCell] = useState(null);
  const [studentsYearModal, setStudentsYearModal] = useState(null);
  const [form, setForm] = useState({ id_materia: '', id_semestre: '', id_modulo: '', id_docente: '', id_aula: '', id_bloque: '', enrollment_mode: 'none' });
  const [graphModal, setGraphModal] = useState(null); // { type: 'year'|'full', yearNumber?: number }
  const [graphZoom, setGraphZoom] = useState(1);
  const [hoveredYear, setHoveredYear] = useState(null);
  const [previewYear, setPreviewYear] = useState(1);

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
  const aulasById = useMemo(() => Object.fromEntries((catalog.aulas || []).map((a) => [a.id_aula, a])), [catalog.aulas]);
  const semestresById = useMemo(() => Object.fromEntries((catalog.semestres || []).map((s) => [s.id_semestre, s])), [catalog.semestres]);

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
        id_modulo: Number(form.id_modulo),
        enrollment_mode: form.enrollment_mode || 'none',
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
            <div><label className="text-[10px] text-neutral-600 px-2 uppercase font-bold">Por Semestre</label><select value={semesterFilter} onChange={(e) => setSemesterFilter(e.target.value)} className="w-full bg-neutral-900 border-none rounded-lg text-xs text-neutral-300"><option>Todos</option><option>1</option><option>2</option></select></div>
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
              <p className="text-neutral-500">Conectada al backend: Inscripción → Semestre → Módulo</p>
              <button
                onClick={() => openGraphModal('full')}
                className="mt-3 px-4 py-2 rounded-lg bg-neutral-900 border border-neutral-800 text-sm font-semibold hover:border-rose-500/50"
              >
                Ver malla curricular
              </button>
            </div>

            {feedback && <div className="px-4 py-3 rounded-xl border border-rose-500/40 bg-rose-500/10 text-rose-200 text-sm">{feedback}</div>}
            {loading && <div className="text-neutral-400 text-sm">Cargando datos...</div>}

            <div className="bg-neutral-900/30 rounded-[24px] border border-neutral-800 p-6 flex-1 min-h-0 overflow-y-auto">
              <div className="space-y-4">
                {years.map((yearNumber) => {
                  const semA = 1;
                  const semB = 2;
                  const hoverInfo = yearHoverPreview.get(yearNumber) || { approvedCount: 0, availableNow: [], opensNext: [] };
                  const availablePreview = hoverInfo.availableNow.slice(0, 8);
                  const opensPreview = hoverInfo.opensNext.slice(0, 6);
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
              <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-7 gap-3">
                <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_semestre} onChange={(e) => setForm((p) => ({ ...p, id_semestre: e.target.value }))}>
                  <option value="">Semestre</option>
                  {(catalog.semestres || []).map((s) => <option key={s.id_semestre} value={s.id_semestre}>{s.nombre}</option>)}
                </select>
                <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.id_modulo} onChange={(e) => setForm((p) => ({ ...p, id_modulo: e.target.value }))}>
                  <option value="">Modulo</option>
                  {(catalog.modulos || []).filter(m => !form.id_semestre || m.id_semestre == form.id_semestre).map((m) => <option key={m.id_modulo} value={m.id_modulo}>{m.nombre}</option>)}
                </select>
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
                <select className="bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-3" value={form.enrollment_mode} onChange={(e) => setForm((p) => ({ ...p, enrollment_mode: e.target.value }))}>
                  <option value="none">Solo asignar docente</option>
                  <option value="all">Inscribir a todos (All)</option>
                  {(catalog.estudiantes || []).map((est) => <option key={`est-${est.id_estudiante}`} value={est.id_estudiante}>{est.nombre} {est.apellido} (Especial)</option>)}
                </select>
              </div>
              {selectedCell && (
                <button onClick={handleAssign} className="mt-4 px-8 py-3 bg-[#2d2d2d] text-white font-bold rounded-xl">
                  Confirmar Asignación ({yearLabel(selectedCell.yearNumber)} - Sem {yearSemesterLabel(selectedCell.semesterNumber)} - Mod {selectedCell.moduloNumber})
                </button>
              )}
            </div>
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
    </div>
  );
}