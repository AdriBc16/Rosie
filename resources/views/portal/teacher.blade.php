<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500&display=swap');

        :root {
            --bg: #eef1f7;
            --bg-strong: #e4e9f4;
            --surface: rgba(255, 255, 255, 0.7);
            --surface-strong: #ffffff;
            --ink: #111827;
            --muted: #5f6472;
            --line: rgba(17, 24, 39, 0.12);
            --accent: #1463ff;
            --accent-soft: rgba(20, 99, 255, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 6% -5%, #dce6ff 0, rgba(220, 230, 255, 0) 40%),
                radial-gradient(circle at 92% 0%, #ffe8dc 0, rgba(255, 232, 220, 0) 30%),
                linear-gradient(160deg, var(--bg), var(--bg-strong));
            min-height: 100vh;
            padding: 24px;
        }

        .shell {
            max-width: 1120px;
            margin: 0 auto;
            animation: rise .6s ease;
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0;
            font-family: 'Fraunces', serif;
            font-size: clamp(1.75rem, 3vw, 2.4rem);
            line-height: 1.1;
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
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr 1fr;
        }

        .card {
            border: 1px solid var(--line);
            background: var(--surface);
            border-radius: 20px;
            padding: 16px;
            backdrop-filter: blur(12px);
            box-shadow: 0 12px 32px rgba(17, 24, 39, 0.08);
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 1.05rem;
            font-weight: 800;
        }

        .meta {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .section {
            margin-top: 16px;
        }

        .section-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .subject-card {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--surface-strong);
            padding: 14px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, 0.06);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .subject-card strong {
            font-size: 1rem;
        }

        .label {
            color: var(--muted);
            font-size: .88rem;
        }

        .pill {
            display: inline-flex;
            width: fit-content;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: .78rem;
            font-weight: 700;
            padding: 4px 10px;
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 8px 16px rgba(20, 99, 255, 0.25);
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

        .actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .empty {
            border: 1px dashed var(--line);
            border-radius: 16px;
            padding: 18px;
            color: var(--muted);
            text-align: center;
            background: rgba(255, 255, 255, 0.4);
        }

        .modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(9, 15, 30, 0.28);
        }

        .modal.show {
            display: flex;
        }

        .modal-card {
            width: min(720px, 100%);
            background: #fff;
            border-radius: 20px;
            border: 1px solid var(--line);
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.16);
            padding: 16px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 12px;
        }

        .modal-header h3 { margin: 0; }

        .students {
            max-height: 320px;
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .student-row {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
        }

        .student-row:last-child {
            border-bottom: 0;
        }

        .status {
            color: var(--muted);
            font-weight: 600;
        }

        @media (max-width: 980px) {
            .grid { grid-template-columns: 1fr; }
            .subject-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="top">
        <div>
            <h1>Panel Docente</h1>
            <p class="subtitle">Bienvenido, {{ $portalUser['name'] }}. Tus materias se cargan en vivo desde APIs.</p>
        </div>
        <span class="chip">DOCENTE</span>
    </div>

    <div class="grid">
        <section class="card">
            <h2>Tu perfil</h2>
            <p class="meta"><strong>ID:</strong> {{ $portalUser['id'] }}</p>
            <p class="meta"><strong>Correo:</strong> {{ $portalUser['email'] }}</p>
            <p class="meta"><strong>Universidad:</strong> {{ $universidad?->nombre ?? 'Sin dato' }}</p>
        </section>

        <section class="card">
            <h2>Vista de carga academica</h2>
            <p class="meta">Abre cada materia con el boton <strong>Info</strong> para ver los estudiantes inscritos en esa asignacion.</p>
        </section>
    </div>

    <section class="section">
        <div class="section-top">
            <h2 style="margin:0;">Materias asignadas</h2>
            <span class="status" id="subjectsStatus">Cargando...</span>
        </div>
        <div id="subjectsContainer" class="subject-grid"></div>
    </section>

    <div class="actions">
        <a class="btn btn-ghost" href="/api/horarios/exportar?formato=json" target="_blank">Exportar JSON</a>
        <a class="btn btn-ghost" href="/api/horarios/exportar?formato=csv" target="_blank">Exportar CSV</a>
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Cerrar sesion</button>
        </form>
    </div>
</div>

<div class="modal" id="studentsModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Estudiantes asignados</h3>
            <button class="btn btn-ghost" type="button" id="closeModal">Cerrar</button>
        </div>
        <div id="modalMeta" class="label" style="margin-bottom:10px;"></div>
        <div id="studentsList" class="students"></div>
    </div>
</div>

<script>
    const subjectsContainer = document.getElementById('subjectsContainer');
    const subjectsStatus = document.getElementById('subjectsStatus');
    const studentsModal = document.getElementById('studentsModal');
    const studentsList = document.getElementById('studentsList');
    const modalTitle = document.getElementById('modalTitle');
    const modalMeta = document.getElementById('modalMeta');

    async function loadAssignments() {
        subjectsStatus.textContent = 'Cargando...';
        subjectsContainer.innerHTML = '';

        try {
            const response = await fetch('/portal/api/docente/materias', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar la lista de materias.');
            }

            const payload = await response.json();
            const assignments = payload.data || [];

            if (assignments.length === 0) {
                subjectsContainer.innerHTML = '<div class="empty">No tienes materias asignadas todavia.</div>';
                subjectsStatus.textContent = 'Sin asignaciones';
                return;
            }

            subjectsStatus.textContent = `${assignments.length} asignaciones`;
            subjectsContainer.innerHTML = assignments.map((item) => `
                <article class="subject-card">
                    <span class="pill">${item.modulo?.nombre || 'Sin modulo'}</span>
                    <strong>${item.materia?.nombre || 'Materia sin nombre'}</strong>
                    <span class="label">Horario: ${item.horario?.nombre || 'Sin horario'} (${item.horario?.hora_inicio || '--'} - ${item.horario?.hora_fin || '--'})</span>
                    <span class="label">Estudiantes inscritos: ${item.estudiantes_count}</span>
                    <button class="btn btn-primary" type="button" onclick="showStudents(${item.id_dm})">Info</button>
                </article>
            `).join('');
        } catch (error) {
            subjectsContainer.innerHTML = `<div class="empty">${error.message}</div>`;
            subjectsStatus.textContent = 'Error';
        }
    }

    async function showStudents(idDm) {
        studentsList.innerHTML = '<div class="student-row">Cargando estudiantes...</div>';
        studentsModal.classList.add('show');

        try {
            const response = await fetch(`/portal/api/docente/materias/${idDm}/estudiantes`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar la informacion de estudiantes.');
            }

            const payload = await response.json();
            const data = payload.data;
            const students = data.estudiantes || [];
            const assignment = data.asignacion || {};

            modalTitle.textContent = assignment.materia || 'Materia';
            modalMeta.textContent = `${assignment.modulo || 'Sin modulo'} | ${assignment.horario || 'Sin horario'} (${assignment.hora_inicio || '--'} - ${assignment.hora_fin || '--'})`;

            if (students.length === 0) {
                studentsList.innerHTML = '<div class="student-row">No hay estudiantes inscritos en esta asignacion.</div>';
                return;
            }

            studentsList.innerHTML = students.map((student) => `
                <div class="student-row">
                    <strong>${student.nombre || 'Sin nombre'}</strong><br>
                    <span class="label">ID: ${student.id_estudiante} | ${student.correo || 'Sin correo'}</span>
                </div>
            `).join('');
        } catch (error) {
            studentsList.innerHTML = `<div class="student-row">${error.message}</div>`;
        }
    }

    document.getElementById('closeModal').addEventListener('click', () => {
        studentsModal.classList.remove('show');
    });

    studentsModal.addEventListener('click', (event) => {
        if (event.target === studentsModal) {
            studentsModal.classList.remove('show');
        }
    });

    loadAssignments();
</script>
</body>
</html>
