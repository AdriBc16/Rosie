<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoodOrder | Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --bg: #191919;
            --sidebar: #202020;
            --surface: #1f1f1f;
            --surface-2: #232323;
            --line: #2d2d2d;
            --line-soft: #323232;
            --text: #ebebeb;
            --muted: #a3a3a3;
            --accent: #2563eb;
            --accent-soft: rgba(47,129,247,0.14);
            --danger: #fca5a5;
            --danger-bg: #3a1f1f;
            --ok: #86efac;
            --ok-bg: #163321;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
        }

        .sidebar {
            background: var(--sidebar);
            border-right: 1px solid var(--line);
            padding: 10px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
        }

        .brand-badge {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            border: 1px solid #3a3a3a;
            background: #171717;
            display: grid;
            place-items: center;
            font-size: 11px;
            font-weight: 700;
            color: #d4d4d4;
        }

        .brand span { font-size: 14px; font-weight: 700; color: #f5f5f5; }

        .menu-label {
            color: #8e8e8e;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 2px 10px;
            margin-top: 8px;
        }

        .menu-item {
            display: block;
            color: #b2b2b2;
            text-decoration: none;
            border-radius: 7px;
            padding: 7px 10px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid transparent;
            margin-top: 2px;
        }

        .menu-item.active {
            background: #2f2f2f;
            color: #fff;
            border-color: #3a3a3a;
        }

        .main {
            display: grid;
            place-items: center;
            padding: 18px;
        }

        .card {
            width: min(460px, 100%);
            border: 1px solid var(--line-soft);
            border-radius: 12px;
            background: linear-gradient(180deg, var(--surface-2), var(--surface));
            padding: 16px;
        }

        h1 {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        form { margin-top: 14px; display: grid; gap: 10px; }

        .field { display: grid; gap: 6px; }
        .field label { font-size: 12px; color: #9f9f9f; font-weight: 700; }

        .field input,
        .field select {
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            background: #171717;
            color: #ececec;
            padding: 9px 10px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: #2f81f7;
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .btn {
            border-radius: 8px;
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #fff;
            padding: 10px 12px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
        }

        .btn:hover { background: #1d4ed8; border-color: #1d4ed8; }

        .hint { margin: 0; color: var(--muted); font-size: 12px; }
        .alert { border-radius: 8px; padding: 9px 10px; font-size: 13px; margin-top: 10px; border: 1px solid; }
        .alert.error { background: var(--danger-bg); color: var(--danger); border-color: #5b2a2a; }
        .alert.ok { background: var(--ok-bg); color: var(--ok); border-color: #1f4f30; }

        @media (max-width: 940px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid var(--line); }
            .main { padding: 14px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-badge">N</div>
            <span>jocagu's Notion</span>
        </div>

        <div class="menu-label">Recents</div>
        <a class="menu-item active" href="#">LOGIN</a>
        <a class="menu-item" href="#">EXAMEN</a>
        <a class="menu-item" href="#">DOCENTE</a>
        <a class="menu-item" href="#">ESTUDIANTE</a>
    </aside>

    <main class="main">
        <section class="card">
            <h1>Acceso al portal</h1>
            <p class="subtitle">Ingresa con tu perfil para abrir el workspace.</p>

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
                <p class="hint">Password demo seed: <strong>UPB123</strong></p>
            </form>
        </section>
    </main>
</div>
</body>
</html>
