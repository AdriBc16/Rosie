@extends('portal.layout')

@section('title', 'Panel Jefe de Carrera')
@section('breadcrumbs', 'Portal / Jefe de carrera')
@section('role_chip', 'Jefe')
@section('page_title', 'Panel de coordinacion')
@section('page_subtitle', 'Operational Overview & Academic Management')

@section('sidebar')
    <a href="{{ route('portal.head') }}" class="active">Home</a>
    <a href="/api/horarios/configuracion-ideal" target="_blank">Configuracion ideal</a>
    <a href="/api/horarios/exportar?formato=csv" target="_blank">Exportar CSV</a>
    <a href="/api/horarios/exportar?formato=json" target="_blank">Exportar JSON</a>
@endsection

@section('top_actions')
    <form method="POST" action="{{ route('portal.logout') }}">
        @csrf
        <button class="btn btn-danger" type="submit">Cerrar sesion</button>
    </form>
@endsection

@section('page_styles')
<style>
    .big { margin: 0; font-size: 2rem; font-weight: 900; letter-spacing: -0.02em; }
    .availability, .subject-list { display:grid; gap:8px; max-height: 320px; overflow:auto; padding-right: 2px; }
    .slot {
        border: 2px solid var(--line);
        border-radius: 7px 12px 8px 10px;
        background: #fff;
        padding: 9px 10px;
    }
    .slot .tag {
        display:inline-flex;
        border-radius:999px;
        padding:3px 8px;
        font-size:.73rem;
        font-weight:700;
        margin-bottom:5px;
    }
    .tag.busy { background: #1a1a1a; color: #f4f4f4; }
    .tag.pref { background: var(--postit); color: #242424; border: 2px solid var(--line); }
    .empty-state {
        border: 2px dashed var(--line);
        border-radius: 8px 11px 7px 12px;
        background: #fffef8;
        color: var(--muted);
        padding: 12px;
        text-align: center;
        font-weight: 600;
    }
    .specific-layout { display:grid; grid-template-columns: 320px 1fr; gap: 12px; }
    .specific-sidebar { display:grid; align-content:start; gap:8px; }
    .actions { margin-top: 20px; display:flex; gap:10px; flex-wrap:wrap; }
    @media (max-width: 1040px) { .specific-layout { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
    <div class="grid-3">
        <section class="card">
            <h2>Teachers</h2>
            <p class="big">{{ $teachersCount }}</p>
            <p class="muted">Registrados en tu universidad.</p>
        </section>

        <section class="card">
            <h2>Students</h2>
            <p class="big">{{ $studentsCount }}</p>
            <p class="muted">Activos en tu universidad.</p>
        </section>

        <section class="card">
            <h2>Modules</h2>
            <p class="big">{{ $modulosCount }}</p>
            <p class="muted">Total de modulos cargados.</p>
        </section>
    </div>

    <div class="grid-2" style="margin-top:12px;">
        <section class="card">
            <h2>Crear nueva materia</h2>
            <form id="createMateriaForm">
                <div class="field">
                    <label for="materiaNombre">Nombre de la materia</label>
                    <input id="materiaNombre" name="nombre" type="text" placeholder="Ej: Arquitectura de Software" required>
                </div>
                <button class="btn btn-primary" type="submit">Crear materia</button>
            </form>
            <div id="createMateriaFeedback" class="feedback"></div>
        </section>

        <section class="card">
            <h2>Datos institucionales</h2>
            <p class="muted"><strong>Universidad:</strong> {{ $universidad?->nombre ?? 'Sin dato' }}</p>
            <p class="muted"><strong>Correo:</strong> {{ $portalUser['email'] }}</p>
            <p class="muted"><strong>ID jefe:</strong> {{ $portalUser['id'] }}</p>
        </section>
    </div>

    <div class="grid-2" style="margin-top:12px;">
        <section class="card">
            <h2>Asignar materia a docente</h2>
            <form id="assignForm">
                <div class="field">
                    <label for="assignMateria">Materia</label>
                    <select id="assignMateria" name="id_materia" required></select>
                </div>

                <div class="field">
                    <label for="assignDocente">Docente</label>
                    <select id="assignDocente" name="id_docente" required></select>
                </div>

                <div class="field">
                    <label for="assignModulo">Modulo (mes)</label>
                    <select id="assignModulo" name="id_modulo" required></select>
                </div>

                <div class="field">
                    <label for="assignHorario">Horario</label>
                    <select id="assignHorario" name="id_horario" required></select>
                </div>

                <button class="btn btn-primary" type="submit">Asignar materia</button>
            </form>
            <div id="assignFeedback" class="feedback"></div>
        </section>

        <section class="card">
            <h2>Disponibilidad del docente</h2>
            <p class="muted" style="margin-bottom:8px;">Al elegir docente se muestran horarios ocupados y preferencias por modulo.</p>
            <div id="availabilityStatus" class="feedback">Selecciona un docente para cargar disponibilidad.</div>
            <div id="availabilityList" class="availability"></div>
        </section>
    </div>

    <div class="specific-layout" style="margin-top:12px;">
        <section class="card specific-sidebar">
            <h2>Busqueda especifica</h2>

            <div class="field">
                <label for="specificType">Perfil</label>
                <select id="specificType">
                    <option value="docente">Docente</option>
                    <option value="estudiante">Estudiante</option>
                </select>
            </div>

            <div class="field">
                <label for="specificPerson">Seleccionar especifico</label>
                <select id="specificPerson"></select>
            </div>

            <div class="field">
                <label for="specificModulo">Modulo (opcional)</label>
                <select id="specificModulo"></select>
            </div>

            <button id="specificLoadBtn" class="btn btn-primary" type="button">Ver materias</button>
        </section>

        <section class="card">
            <h2>Materias por persona</h2>
            <p class="muted" id="specificMeta">Selecciona docente o estudiante para ver su carga academica exacta.</p>
            <div id="specificFeedback" class="feedback"></div>
            <div id="specificSubjects" class="subject-list"></div>
        </section>
    </div>

    <div class="actions">
        <a class="btn" href="/api/horarios/configuracion-ideal" target="_blank">Ver recomendacion de horarios</a>
        <a class="btn" href="/api/horarios/exportar?formato=csv" target="_blank">Exportar horarios CSV</a>
    </div>
@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const createMateriaForm = document.getElementById('createMateriaForm');
    const createMateriaFeedback = document.getElementById('createMateriaFeedback');
    const assignForm = document.getElementById('assignForm');
    const assignFeedback = document.getElementById('assignFeedback');
    const availabilityStatus = document.getElementById('availabilityStatus');
    const availabilityList = document.getElementById('availabilityList');

    const assignMateria = document.getElementById('assignMateria');
    const assignDocente = document.getElementById('assignDocente');
    const assignModulo = document.getElementById('assignModulo');
    const assignHorario = document.getElementById('assignHorario');
    const specificType = document.getElementById('specificType');
    const specificPerson = document.getElementById('specificPerson');
    const specificModulo = document.getElementById('specificModulo');
    const specificLoadBtn = document.getElementById('specificLoadBtn');
    const specificFeedback = document.getElementById('specificFeedback');
    const specificSubjects = document.getElementById('specificSubjects');
    const specificMeta = document.getElementById('specificMeta');

    let catalogData = {
        docentes: [],
        estudiantes: [],
        modulos: [],
    };

    function setFeedback(el, message, ok = false) {
        el.textContent = message || '';
        el.className = `feedback ${ok ? 'ok' : 'error'}`;
    }

    function fillSelect(select, items, valueKey, labelBuilder, placeholder) {
        const options = [`<option value="">${placeholder}</option>`];
        for (const item of items) {
            options.push(`<option value="${item[valueKey]}">${labelBuilder(item)}</option>`);
        }
        select.innerHTML = options.join('');
    }

    async function loadCatalog() {
        const response = await fetch('/portal/api/jefe/catalogo', {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error('No se pudo cargar el catalogo para asignaciones.');
        }

        const payload = await response.json();
        const data = payload.data || {};

        catalogData = {
            docentes: data.docentes || [],
            estudiantes: data.estudiantes || [],
            modulos: data.modulos || [],
        };

        fillSelect(
            assignMateria,
            data.materias || [],
            'id_materia',
            (item) => item.nombre,
            'Selecciona materia'
        );

        fillSelect(
            assignDocente,
            data.docentes || [],
            'id_docente',
            (item) => `${item.nombre} (${item.correo})`,
            'Selecciona docente'
        );

        fillSelect(
            assignModulo,
            data.modulos || [],
            'id_modulo',
            (item) => `${item.nombre} (${item.fecha_inicio} a ${item.fecha_final})`,
            'Selecciona modulo'
        );

        fillSelect(
            assignHorario,
            data.horarios || [],
            'id_horario',
            (item) => `${item.nombre} (${item.hora_inicio} - ${item.hora_fin})`,
            'Selecciona horario'
        );

        fillSelect(
            specificModulo,
            data.modulos || [],
            'id_modulo',
            (item) => `${item.nombre} (${item.fecha_inicio} a ${item.fecha_final})`,
            'Todos los modulos'
        );

        renderSpecificPeopleOptions();
    }

    function renderSpecificPeopleOptions() {
        const type = specificType.value;
        const people = type === 'docente' ? catalogData.docentes : catalogData.estudiantes;

        if (!people.length) {
            specificPerson.innerHTML = '<option value="">Sin registros</option>';
            return;
        }

        const valueKey = type === 'docente' ? 'id_docente' : 'id_estudiante';
        const options = ['<option value="">Selecciona persona</option>'];

        for (const person of people) {
            options.push(`<option value="${person[valueKey]}">${person.nombre} (${person.correo})</option>`);
        }

        specificPerson.innerHTML = options.join('');
    }

    async function loadSpecificSubjects() {
        const type = specificType.value;
        const personId = specificPerson.value;
        const moduloId = specificModulo.value;

        specificSubjects.innerHTML = '';

        if (!personId) {
            setFeedback(specificFeedback, 'Selecciona un docente o estudiante especifico.', false);
            specificMeta.textContent = 'Selecciona docente o estudiante para ver su carga academica exacta.';
            return;
        }

        setFeedback(specificFeedback, 'Cargando materias...', true);

        const moduleQuery = moduloId ? `?id_modulo=${moduloId}` : '';

        try {
            const response = await fetch(`/portal/api/jefe/personas/${type}/${personId}/materias${moduleQuery}`, {
                headers: { 'Accept': 'application/json' }
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'No se pudo cargar la informacion solicitada.');
            }

            const data = payload.data || {};
            const materias = data.materias || [];

            specificMeta.textContent = `${data.persona?.nombre || 'Persona'} | ${data.persona?.correo || 'Sin correo'}`;

            if (!materias.length) {
                specificSubjects.innerHTML = '<div class="empty-state">No tiene materias registradas para ese filtro.</div>';
                setFeedback(specificFeedback, 'Sin resultados.', true);
                return;
            }

            specificSubjects.innerHTML = materias.map((item) => {
                const docenteInfo = type === 'estudiante'
                    ? `<div class="muted">Docente: ${item.docente || 'Sin dato'} (${item.docente_correo || 'sin correo'})</div>`
                    : `<div class="muted">Estudiantes inscritos: ${item.estudiantes_count ?? 0}</div>`;

                return `
                    <article class="slot">
                        <span class="tag pref">${item.modulo || 'Sin modulo'}</span>
                        <div><strong>${item.materia || 'Materia sin nombre'}</strong></div>
                        <div class="muted">${item.fecha_inicio || '--'} a ${item.fecha_final || '--'}</div>
                        <div class="muted">${item.horario || 'Sin horario'} (${item.hora_inicio || '--'} - ${item.hora_fin || '--'})</div>
                        ${docenteInfo}
                    </article>
                `;
            }).join('');

            setFeedback(specificFeedback, `${materias.length} materias encontradas.`, true);
        } catch (error) {
            specificMeta.textContent = 'Selecciona docente o estudiante para ver su carga academica exacta.';
            specificSubjects.innerHTML = '<div class="empty-state">No se pudo obtener la informacion.</div>';
            setFeedback(specificFeedback, error.message, false);
        }
    }

    async function loadTeacherAvailability(idDocente) {
        availabilityList.innerHTML = '';

        if (!idDocente) {
            availabilityStatus.textContent = 'Selecciona un docente para cargar disponibilidad.';
            availabilityStatus.className = 'feedback';
            return;
        }

        availabilityStatus.textContent = 'Cargando disponibilidad...';
        availabilityStatus.className = 'feedback';

        try {
            const response = await fetch(`/portal/api/jefe/docentes/${idDocente}/disponibilidad`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar la disponibilidad del docente.');
            }

            const payload = await response.json();
            const data = payload.data || {};
            const ocupados = data.ocupados || [];
            const preferencias = data.preferencias || [];

            const blocks = [];

            if (ocupados.length) {
                for (const slot of ocupados) {
                    blocks.push(`
                        <article class="slot">
                            <span class="tag busy">Ocupado</span>
                            <div><strong>${slot.materia || 'Materia'}</strong></div>
                            <div class="muted">${slot.modulo || 'Sin modulo'} (${slot.fecha_inicio || '--'} a ${slot.fecha_final || '--'})</div>
                            <div class="muted">${slot.horario || 'Sin horario'} (${slot.hora_inicio || '--'} - ${slot.hora_fin || '--'})</div>
                        </article>
                    `);
                }
            }

            if (preferencias.length) {
                for (const slot of preferencias) {
                    blocks.push(`
                        <article class="slot">
                            <span class="tag pref">Preferencia</span>
                            <div><strong>${slot.modulo || 'Sin modulo'}</strong></div>
                            <div class="muted">${slot.fecha_inicio || '--'} a ${slot.fecha_final || '--'}</div>
                            <div class="muted">${slot.horario || 'Sin horario'} (${slot.hora_inicio || '--'} - ${slot.hora_fin || '--'})</div>
                        </article>
                    `);
                }
            }

            availabilityList.innerHTML = blocks.length ? blocks.join('') : '<article class="slot"><div class="muted">El docente no tiene horarios ocupados ni preferencias registradas.</div></article>';
            availabilityStatus.textContent = `Mostrando disponibilidad de ${data.docente?.nombre || 'docente'}.`;
            availabilityStatus.className = 'feedback ok';
        } catch (error) {
            availabilityStatus.textContent = error.message;
            availabilityStatus.className = 'feedback error';
        }
    }

    createMateriaForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(createMateriaForm);

        try {
            const response = await fetch('/portal/api/jefe/materias', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    nombre: formData.get('nombre'),
                })
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'No se pudo crear la materia.');
            }

            setFeedback(createMateriaFeedback, payload.message || 'Materia creada.', true);
            createMateriaForm.reset();
            await loadCatalog();
        } catch (error) {
            setFeedback(createMateriaFeedback, error.message, false);
        }
    });

    assignDocente.addEventListener('change', (event) => {
        loadTeacherAvailability(event.target.value);
    });

    specificType.addEventListener('change', () => {
        renderSpecificPeopleOptions();
        specificSubjects.innerHTML = '';
        specificMeta.textContent = 'Selecciona docente o estudiante para ver su carga academica exacta.';
        specificFeedback.textContent = '';
        specificFeedback.className = 'feedback';
    });

    specificLoadBtn.addEventListener('click', () => {
        loadSpecificSubjects();
    });

    assignForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(assignForm);

        try {
            const response = await fetch('/portal/api/jefe/asignaciones', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    id_materia: Number(formData.get('id_materia')),
                    id_docente: Number(formData.get('id_docente')),
                    id_modulo: Number(formData.get('id_modulo')),
                    id_horario: Number(formData.get('id_horario')),
                })
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'No se pudo crear la asignacion.');
            }

            setFeedback(assignFeedback, payload.message || 'Asignacion creada.', true);
            await loadTeacherAvailability(formData.get('id_docente'));
        } catch (error) {
            setFeedback(assignFeedback, error.message, false);
        }
    });

    (async function init() {
        try {
            await loadCatalog();
            specificSubjects.innerHTML = '<div class="empty-state">No hay consulta cargada aun.</div>';
        } catch (error) {
            setFeedback(assignFeedback, error.message, false);
        }
    })();
</script>
@endsection
