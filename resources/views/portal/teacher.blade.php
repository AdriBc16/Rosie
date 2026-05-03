@extends('portal.layout')

@section('title', 'Panel Docente')
@section('breadcrumbs', 'Portal / Docente')
@section('role_chip', 'Docente')
@section('page_title', 'Panel docente')
@section('page_subtitle', 'Consulta tus asignaciones y estudiantes en formato libreta operativa.')

@section('sidebar')
    <a href="{{ route('portal.teacher') }}" class="active">Home</a>
    <a href="/api/horarios/exportar?formato=json" target="_blank">Exportar JSON</a>
    <a href="/api/horarios/exportar?formato=csv" target="_blank">Exportar CSV</a>
@endsection

@section('top_actions')
    <form method="POST" action="{{ route('portal.logout') }}">
        @csrf
        <button class="btn btn-danger" type="submit">Cerrar sesion</button>
    </form>
@endsection

@section('page_styles')
<style>
    .subject-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; }
    .subject-card {
        border:2px solid var(--line);
        border-radius: 8px 12px 7px 13px;
        background: #fff;
        padding: 12px;
        display:flex;
        flex-direction:column;
        gap:8px;
    }
    .pill {
        display:inline-flex;
        width: fit-content;
        border-radius: 8px 11px 7px 12px;
        padding:4px 9px;
        font-size:.75rem;
        font-weight:700;
        background: var(--postit);
        color: #1a1a1a;
        border: 2px solid var(--line);
    }
    .label { color: var(--muted); font-size: .86rem; }
    .status { color: var(--muted); font-weight: 700; }
    .empty {
        border: 2px dashed var(--line);
        border-radius: 8px 11px 7px 12px;
        background: #fffef8;
        padding: 14px;
        color: var(--muted);
        text-align: center;
    }
    .section-top { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; }
    .modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(26,26,26,0.35);
        padding: 14px;
        z-index: 100;
    }
    .modal.show { display: flex; }
    .modal-card {
        width: min(760px, 100%);
        border: 2px solid var(--line);
        border-radius: 9px 14px 8px 12px;
        background: #fffef8;
        padding: 14px;
    }
    .modal-header { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:10px; }
    .students { border:2px solid var(--line); border-radius: 8px 11px 7px 12px; max-height: 320px; overflow: auto; background:#fff; }
    .student-row { padding: 10px 11px; border-bottom:1px solid var(--line); }
    .student-row:last-child { border-bottom:0; }
    @media (max-width: 980px) { .subject-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
    <div class="grid-2">
        <section class="card">
            <h2>Perfil</h2>
            <p class="muted"><strong>Nombre:</strong> {{ $portalUser['name'] }}</p>
            <p class="muted"><strong>ID:</strong> {{ $portalUser['id'] }}</p>
            <p class="muted"><strong>Correo:</strong> {{ $portalUser['email'] }}</p>
            <p class="muted"><strong>Universidad:</strong> {{ $universidad?->nombre ?? 'Sin dato' }}</p>
        </section>

        <section class="card">
            <h2>Contexto</h2>
            <p class="muted">Usa el boton <strong>Info</strong> para ver estudiantes de cada asignacion sin salir del panel.</p>
        </section>
    </div>

    <section style="margin-top:12px;">
        <div class="section-top">
            <h2 style="margin:0;">Materias asignadas</h2>
            <span class="status" id="subjectsStatus">Cargando...</span>
        </div>
        <div id="subjectsContainer" class="subject-grid"></div>
    </section>

    <div class="modal" id="studentsModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle" style="margin:0;">Estudiantes asignados</h3>
                <button class="btn" type="button" id="closeModal">Cerrar</button>
            </div>
            <div id="modalMeta" class="label" style="margin-bottom:10px;"></div>
            <div id="studentsList" class="students"></div>
        </div>
    </div>
@endsection

@section('scripts')
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
@endsection
