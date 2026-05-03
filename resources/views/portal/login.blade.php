<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Core | Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Caveat:wght@500;700&display=swap');

        :root {
            --paper: #fafaf5;
            --ink: #1a1a1a;
            --postit: #FFEF9F;
            --line: #1a1a1a;
            --danger: #8a2a2a;
            --ok: #2f7d3b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            background:
                linear-gradient(transparent 31px, rgba(26,26,26,0.045) 32px),
                linear-gradient(90deg, transparent 31px, rgba(26,26,26,0.03) 32px),
                var(--paper);
            background-size: 32px 32px, 32px 32px, auto;
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px 1fr;
            max-width: 1300px;
            margin: 0 auto;
            border-left: 2px solid var(--line);
            border-right: 2px solid var(--line);
        }

        .sidebar {
            border-right: 2px solid var(--line);
            padding: 14px 12px;
        }

        .brand {
            border: 2px solid var(--line);
            border-radius: 10px 6px 12px 8px;
            padding: 10px;
            background: #fffef8;
        }

        .brand h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .brand p {
            margin: 2px 0 0;
            color: #555;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .menu-label {
            color: #444;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 0 10px;
            margin: 12px 0 6px;
        }

        .menu-item {
            display: block;
            border: 2px solid transparent;
            border-radius: 8px 12px 7px 10px;
            padding: 9px 10px;
            text-transform: uppercase;
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            text-decoration: none;
            margin-bottom: 6px;
        }

        .menu-item.active {
            border-color: var(--line);
            background: var(--postit);
        }

        .main {
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            position: relative;
            width: min(500px, 100%);
            border: 2px solid var(--line);
            border-radius: 10px 14px 9px 15px;
            background: #fffefb;
            padding: 18px;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%) rotate(-2deg);
            width: 52px;
            height: 16px;
            background: rgba(255, 239, 159, 0.55);
            border: 1px solid rgba(26,26,26,.28);
        }

        h2 {
            margin: 0;
            font-size: 1.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .01em;
        }

        .subtitle {
            margin: 6px 0 0;
            color: #555;
            font-size: 14px;
            font-weight: 600;
        }

        form { margin-top: 15px; display: grid; gap: 11px; }

        .field { display: grid; gap: 6px; }

        .field label {
            font-size: 12px;
            color: #3f3f3f;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .field input,
        .field select {
            border: 2px solid var(--line);
            border-radius: 8px 12px 8px 11px;
            background: #fff;
            color: var(--ink);
            padding: 9px 10px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .btn {
            border-radius: 8px 12px 8px 11px;
            border: 2px solid var(--line);
            background: #fff;
            color: var(--ink);
            padding: 9px 12px;
            font-weight: 800;
            font-size: 12px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: .08em;
            transition: none;
        }

        .btn:hover {
            background: var(--ink);
            color: #fff;
        }

        .hint {
            margin: 0;
            color: #555;
            font-size: 18px;
            font-family: 'Caveat', cursive;
        }

        .alert {
            border: 2px solid var(--line);
            border-radius: 8px 12px 7px 11px;
            padding: 9px 10px;
            font-size: 13px;
            margin-top: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .alert.error { color: var(--danger); background: #fff; }
        .alert.ok { color: var(--ok); background: #fff; }

        @media (max-width: 940px) {
            .shell { grid-template-columns: 1fr; border-left: 0; border-right: 0; }
            .sidebar { border-right: 0; border-bottom: 2px solid var(--line); }
            .main { padding: 14px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <h1>CAMPUS_CORE</h1>
            <p>Admin Portal</p>
        </div>

        <div class="menu-label">Recents</div>
        <a class="menu-item active" href="#">Dashboard</a>
        <a class="menu-item" href="#">Schedule</a>
        <a class="menu-item" href="#">Resources</a>
    </aside>

    <main class="main">
        <section class="card">
            <h2>Acceso al portal</h2>
            <p class="subtitle">Operational login / academic management.</p>

            @if (session('auth_error'))
                <div class="alert error">{{ session('auth_error') }}</div>
            @endif

            @if (session('auth_ok'))
                <div class="alert ok">{{ session('auth_ok') }}</div>
            @endif

            <form method="POST" action="{{ route('portal.login.submit') }}">
                @csrf

                <div class="field">
                    <label for="role">Perfil</label>
                    <select id="role" name="role" required>
                        <option value="estudiante" @selected(old('role') === 'estudiante')>Estudiante</option>
                        <option value="docente" @selected(old('role') === 'docente')>Docente</option>
                        <option value="jefe" @selected(old('role') === 'jefe')>Jefe de carrera</option>
                    </select>
                </div>

                <div class="field">
                    <label for="correo">Correo</label>
                    <input id="correo" type="email" name="correo" value="{{ old('correo') }}" placeholder="nombre@correo.com" required>
                </div>

                <div class="field">
                    <label for="password">Contrasena</label>
                    <input id="password" type="password" name="password" placeholder="Tu contrasena" required>
                </div>

                <button class="btn" type="submit">Entrar</button>
                <p class="hint">Nota: password demo seed -> UPB123</p>
            </form>
        </section>
    </main>
</div>
</body>
</html>
