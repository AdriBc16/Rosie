<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Estudiante</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap');
        :root { --ink:#f8f4e8; --muted:#d7ccb6; --line:rgba(255,255,255,.25); --glass:rgba(255,255,255,.1); --btn:#f1a03d; --btnInk:#16242f; }
        *{box-sizing:border-box}
        body{margin:0;font-family:'Space Grotesk',sans-serif;background:radial-gradient(circle at top right,rgba(241,160,61,.24),transparent 38%),radial-gradient(circle at top left,rgba(44,166,145,.3),transparent 34%),linear-gradient(145deg,#0f2226,#10161d 45%,#2b1f14);color:var(--ink);padding:22px}
        .top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:6px 2px}
        .badge{border:1px solid var(--line);background:var(--glass);padding:8px 12px;border-radius:999px;font-weight:700}
        .layout{margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .card{background:var(--glass);border:1px solid var(--line);border-radius:20px;padding:18px;box-shadow:0 14px 32px rgba(0,0,0,.24);backdrop-filter:blur(18px)}
        h1,h2{margin:0 0 8px;font-family:'Outfit',sans-serif}
        p{margin:0;color:var(--muted)}
        .list{margin:10px 0 0;padding-left:18px}
        .list li{margin-bottom:6px}
        .actions{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap}
        a.btn,button.btn{border:0;border-radius:12px;padding:10px 12px;font-weight:700;text-decoration:none;cursor:pointer;font-family:'Outfit',sans-serif}
        a.btn{background:var(--btn);color:var(--btnInk)}
        button.btn{background:rgba(255,255,255,.18);color:var(--ink);border:1px solid var(--line)}
        @media (max-width:860px){.layout{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="top">
    <div>
        <h1>Hola, {{ $portalUser['name'] }}</h1>
        <p>Tu panel de estudiante para revisar tu contexto academico.</p>
    </div>
    <span class="badge">ESTUDIANTE</span>
</div>

<div class="layout">
    <section class="card">
        <h2>Perfil</h2>
        <p><strong>Correo:</strong> {{ $portalUser['email'] }}</p>
        <p><strong>ID:</strong> {{ $portalUser['id'] }}</p>
        <p><strong>Universidad:</strong> {{ $universidad?->nombre ?? 'Sin dato' }}</p>
        <p><strong>Modulo actual:</strong> {{ $modulo?->nombre ?? 'Sin dato' }}</p>
    </section>

    <section class="card">
        <h2>Atajos utiles</h2>
        <ul class="list">
            <li>Ver materias: /api/materias</li>
            <li>Ver horarios: /api/horarios</li>
            <li>Ver inscripciones: /api/inscripciones</li>
        </ul>
        <p>Desde aqui puedes consumir el API para tu portal academico.</p>
    </section>
</div>

<div class="actions">
    <a class="btn" href="/api/horarios/configuracion-ideal" target="_blank">Configuracion ideal</a>
    <form method="POST" action="{{ route('portal.logout') }}">
        @csrf
        <button class="btn" type="submit">Cerrar sesion</button>
    </form>
</div>
</body>
</html>
