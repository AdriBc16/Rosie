<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoodOrder | Portal de Acceso</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Outfit:wght@400;500;700;800&display=swap');

        :root {
            --ink: #f8f4e8;
            --ink-soft: #e6dcc8;
            --bg-a: #0f2226;
            --bg-b: #b07b3e;
            --glass: rgba(255, 248, 230, 0.12);
            --line: rgba(255, 255, 255, 0.22);
            --btn: #f1a03d;
            --btn-ink: #16242f;
            --danger: #ffd6ca;
            --danger-ink: #6f1e12;
            --ok: #d9ffe7;
            --ok-ink: #1a5d39;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at 20% 20%, rgba(31, 119, 105, 0.45), transparent),
                radial-gradient(900px 500px at 80% 0%, rgba(241, 160, 61, 0.45), transparent),
                linear-gradient(140deg, var(--bg-a), #0a1217 40%, #332312 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            border-radius: 30px;
            border: 1px solid var(--line);
            background: linear-gradient(130deg, rgba(255,255,255,0.12), rgba(255,255,255,0.05));
            backdrop-filter: blur(24px) saturate(120%);
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.38);
            animation: lift 620ms ease;
        }

        .hero {
            padding: 38px;
            position: relative;
            border-right: 1px solid var(--line);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
        }

        .orb.one { width: 170px; height: 170px; background: rgba(31, 164, 137, 0.26); right: -50px; top: -50px; }
        .orb.two { width: 210px; height: 210px; background: rgba(241, 160, 61, 0.22); left: -70px; bottom: -90px; }

        h1 {
            font-family: 'Outfit', sans-serif;
            margin: 0 0 10px;
            font-size: 2.2rem;
            letter-spacing: 0.3px;
        }

        .hero p { margin: 0; color: var(--ink-soft); line-height: 1.45; max-width: 44ch; }

        .chips { margin-top: 22px; display: flex; gap: 8px; flex-wrap: wrap; }

        .chip {
            border: 1px solid var(--line);
            background: var(--glass);
            border-radius: 999px;
            padding: 8px 11px;
            font-size: 0.84rem;
        }

        .form-wrap { padding: 34px; }

        .title {
            margin: 0 0 15px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.55rem;
        }

        form { display: grid; gap: 13px; }

        label { display: grid; gap: 6px; font-weight: 600; font-size: 0.92rem; color: var(--ink-soft); }

        input, select {
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.1);
            color: var(--ink);
            border-radius: 12px;
            padding: 11px 12px;
            font: inherit;
            outline: none;
        }

        input::placeholder { color: #dacfb8; }

        input:focus, select:focus { box-shadow: 0 0 0 3px rgba(241, 160, 61, 0.26); }

        option { color: #1b2830; }

        .btn {
            margin-top: 4px;
            border: 0;
            border-radius: 12px;
            background: var(--btn);
            color: var(--btn-ink);
            padding: 12px;
            font: 700 1rem/1 'Outfit', sans-serif;
            cursor: pointer;
        }

        .hint { color: var(--ink-soft); margin: 0; font-size: 0.88rem; }

        .alert { border-radius: 12px; padding: 10px 12px; font-size: 0.9rem; margin-bottom: 10px; }
        .alert.error { background: var(--danger); color: var(--danger-ink); }
        .alert.ok { background: var(--ok); color: var(--ok-ink); }

        @keyframes lift {
            from { transform: translateY(16px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 860px) {
            .shell { grid-template-columns: 1fr; }
            .hero { border-right: 0; border-bottom: 1px solid var(--line); }
            .form-wrap { padding: 24px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="hero">
        <div class="orb one"></div>
        <div class="orb two"></div>
        <h1>GoodOrder Portal</h1>
        <p>Acceso por rol con look glass y ambiente chill para gestionar modulos, horarios y datos academicos.</p>
        <div class="chips">
            <span class="chip">Glass UI</span>
            <span class="chip">Estudiante / Docente / Jefe</span>
            <span class="chip">Mood Frank Ocean</span>
        </div>
    </section>

    <section class="form-wrap">
        <h2 class="title">Acceso al sistema</h2>

        @if (session('auth_error'))
            <div class="alert error">{{ session('auth_error') }}</div>
        @endif

        @if (session('auth_ok'))
            <div class="alert ok">{{ session('auth_ok') }}</div>
        @endif

        <form method="POST" action="{{ route('portal.login.submit') }}">
            @csrf

            <label>
                Perfil
                <select name="role" required>
                    <option value="estudiante" @selected(old('role') === 'estudiante')>Estudiante</option>
                    <option value="docente" @selected(old('role') === 'docente')>Docente</option>
                    <option value="jefe" @selected(old('role') === 'jefe')>Jefe de carrera</option>
                </select>
            </label>

            <label>
                Correo
                <input type="email" name="correo" value="{{ old('correo') }}" placeholder="nombre@correo.com" required>
            </label>

            <label>
                Contrasena
                <input type="password" name="password" placeholder="Tu contrasena" required>
            </label>

            <button class="btn" type="submit">Entrar</button>
            <p class="hint">Contrasena demo por defecto en datos seed: <strong>UPB123</strong></p>
        </form>
    </section>
</div>
</body>
</html>
