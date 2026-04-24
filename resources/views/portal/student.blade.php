@extends('portal.layout')

@section('title', 'Panel Estudiante')
@section('breadcrumbs', 'Portal / Estudiante')
@section('role_chip', 'Estudiante')
@section('page_title', 'Panel de estudiante')
@section('page_subtitle', 'Consulta tu estado academico con una interfaz unificada tipo Notion dark.')

@section('sidebar')
    <a class="active" href="{{ route('portal.student') }}">Home</a>
    <a href="/api/materias" target="_blank">Materias API</a>
    <a href="/api/horarios" target="_blank">Horarios API</a>
    <a href="/api/inscripciones" target="_blank">Inscripciones API</a>
@endsection

@section('top_actions')
    <form method="POST" action="{{ route('portal.logout') }}">
        @csrf
        <button class="btn btn-danger" type="submit">Cerrar sesion</button>
    </form>
@endsection

@section('content')
    <div class="grid-2">
        <section class="card">
            <h2>Perfil</h2>
            <p class="muted"><strong>Nombre:</strong> {{ $portalUser['name'] }}</p>
            <p class="muted"><strong>Correo:</strong> {{ $portalUser['email'] }}</p>
            <p class="muted"><strong>ID:</strong> {{ $portalUser['id'] }}</p>
            <p class="muted"><strong>Universidad:</strong> {{ $universidad?->nombre ?? 'Sin dato' }}</p>
            <p class="muted"><strong>Modulo actual:</strong> {{ $modulo?->nombre ?? 'Sin dato' }}</p>
        </section>

        <section class="card">
            <h2>Atajos rapidos</h2>
            <p class="muted">Desde aqui puedes abrir recursos de API para trabajar tus consultas del portal.</p>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                <a class="btn" href="/api/horarios/configuracion-ideal" target="_blank">Configuracion ideal</a>
                <a class="btn" href="/api/horarios/exportar?formato=json" target="_blank">Exportar JSON</a>
                <a class="btn" href="/api/horarios/exportar?formato=csv" target="_blank">Exportar CSV</a>
            </div>
        </section>
    </div>
@endsection
