<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Jefe de Carrera</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500&display=swap');

        :root {
            --bg: #edf0f7;
            --bg-2: #e4eaf7;
            --surface: rgba(255, 255, 255, 0.75);
            --surface-strong: #ffffff;
            --ink: #101828;
            --muted: #596273;
            --line: rgba(16, 24, 40, 0.14);
            --accent: #145df4;
            --accent-soft: rgba(20, 93, 244, 0.12);
            --ok: #0f9f6e;
            --warn: #ce6f1f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% -4%, #dce5ff 0, rgba(220, 229, 255, 0) 38%),
                radial-gradient(circle at 90% 0%, #ffe8dd 0, rgba(255, 232, 221, 0) 30%),
                linear-gradient(155deg, var(--bg), var(--bg-2));
            min-height: 100vh;
            padding: 24px;
        }

        .shell {
            max-width: 1200px;
            margin: 0 auto;
            animation: rise .65s ease;
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        h1 {
            margin: 0;
            font-family: 'Fraunces', serif;
            font-size: clamp(1.8rem, 3vw, 2.45rem);
            line-height: 1.08;
        }

        .subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-weight: 500;
        }

        .chip {
            border: 1px solid var(--line);
            background: var(--surface);
            padding: 8px 14px;
            border-radius: 999px;
            font-size: .78rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .grid {
            margin-top: 14px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .card {
            border: 1px solid var(--line);
            background: var(--surface);
            border-radius: 20px;
            padding: 16px;
            backdrop-filter: blur(12px);
            box-shadow: 0 12px 28px rgba(16, 24, 40, 0.08);
        }

        h2 {
            margin: 0 0 10px;
            font-size: 1.02rem;
            font-weight: 800;
        }

        .big {
            margin: 0;
            font-size: 2.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .muted {
            margin: 0;
            color: var(--muted);
        }

        .work-grid {
            margin-top: 12px;
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr 1fr;
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 10px;
        }

        .field label {
            font-size: .86rem;
            font-weight: 700;
            color: var(--muted);
        }

        .field input,
        .field select {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            color: var(--ink);
            padding: 10px 12px;
            font-family: inherit;
            font-size: .95rem;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: rgba(20, 93, 244, 0.5);
            box-shadow: 0 0 0 3px rgba(20, 93, 244, 0.12);
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 10px 13px;
            font-size: .92rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 8px 16px rgba(20, 93, 244, 0.28);
        }

        .btn-ghost {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--line);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .feedback {
            min-height: 22px;
            margin-top: 6px;
            font-size: .88rem;
            font-weight: 700;
        }

        .feedback.ok { color: var(--ok); }
        .feedback.error { color: #c53030; }

        .availability {
            margin-top: 8px;
            display: grid;
            gap: 8px;
            max-height: 290px;
            overflow: auto;
            padding-right: 2px;
        }

        .specific-layout {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 12px;
        }

        .specific-sidebar {
            display: grid;
            gap: 10px;
            align-content: start;
        }

        .specific-board {
            min-height: 300px;
        }

        .subject-list {
            margin-top: 10px;
            display: grid;
            gap: 8px;
            max-height: 360px;
            overflow: auto;
            padding-right: 2px;
        }

        .empty-state {
            border: 1px dashed var(--line);
            border-radius: 14px;
            padding: 14px;
            background: rgba(255, 255, 255, .5);
            color: var(--muted);
            text-align: center;
            font-weight: 600;
        }

        .slot {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px;
            background: var(--surface-strong);
        }

        .slot .tag {
            display: inline-flex;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: .74rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .tag.busy {
            background: rgba(206, 111, 31, 0.12);
            color: var(--warn);
        }

        .tag.pref {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .footer {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 1040px) {
            .grid { grid-template-columns: 1fr; }
            .work-grid { grid-template-columns: 1fr; }
            .specific-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="top">
        <div>
            <h1>Panel de Coordinacion</h1>
            <p class="subtitle">Bienvenido, {{ $portalUser['name'] }}. Gestiona materias y asignaciones con APIs del portal.</p>
        </div>
        <span class="chip">JEFE DE CARRERA</span>
    </div>

    <div class="grid">
        <section class="card">
            <h2>Docentes</h2>
            <p class="big">{{ $teachersCount }}</p>
            <p class="muted">Registrados en tu universidad.</p>
        </section>

        <section class="card">
            <h2>Estudiantes</h2>
            <p class="big">{{ $studentsCount }}</p>
            <p class="muted">Activos en tu universidad.</p>
        </section>

        <section class="card">
            <h2>Modulos</h2>
            <p class="big">{{ $modulosCount }}</p>
            <p class="muted">Total de modulos cargados.</p>
        </section>
    </div>

    <div class="work-grid">
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

    <div class="work-grid">
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
            <p class="muted" style="margin-bottom:8px;">Cuando eliges docente, aqui ves sus horarios ocupados y sus horarios de preferencia por modulo.</p>
            <div id="availabilityStatus" class="feedback">Selecciona un docente para cargar disponibilidad.</div>
            <div id="availabilityList" class="availability"></div>
        </section>
    </div>

    <div class="specific-layout">
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

        <section class="card specific-board">
            <h2>Materias por persona</h2>
            <p class="muted" id="specificMeta">Selecciona docente o estudiante para ver su carga academica exacta.</p>
            <div id="specificFeedback" class="feedback"></div>
            <div id="specificSubjects" class="subject-list"></div>
        </section>
    </div>

    <div class="footer">
        <a class="btn btn-ghost" href="/api/horarios/configuracion-ideal" target="_blank">Ver recomendacion de horarios</a>
        <a class="btn btn-ghost" href="/api/horarios/exportar?formato=csv" target="_blank">Exportar horarios CSV</a>
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Cerrar sesion</button>
        </form>
    </div>
</div>

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
</body>
</html>
