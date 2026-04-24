<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal GoodOrder')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --bg: #191a1b;
            --sidebar: #1f2022;
            --surface: #202224;
            --surface-soft: #242629;
            --line: #2f3337;
            --line-soft: #3a3e44;
            --text: #eceef1;
            --muted: #a7adb7;
            --accent: #2f81f7;
            --accent-soft: rgba(47, 129, 247, 0.14);
            --danger: #fca5a5;
            --ok: #86efac;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        .workspace {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
        }

        .sidebar {
            background: var(--sidebar);
            border-right: 1px solid var(--line);
            padding: 12px 10px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .brand:hover { border-color: #3a3e44; background: rgba(255,255,255,.02); }

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

        .brand span {
            font-size: 14px;
            font-weight: 700;
            color: #f5f5f5;
        }

        .menu-title {
            color: #8e8e8e;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 2px 10px;
            margin-top: 4px;
        }

        .menu {
            display: grid;
            gap: 2px;
        }

        .menu a {
            color: #b2b2b2;
            text-decoration: none;
            border-radius: 7px;
            padding: 7px 10px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid transparent;
            transition: background .15s ease, color .15s ease, border-color .15s ease;
        }

        .menu a:hover {
            background: #2a2a2a;
            color: #efefef;
            border-color: #343434;
        }

        .menu a.active {
            background: #2f2f2f;
            color: #ffffff;
            border-color: #3a3a3a;
        }

        .content {
            min-width: 0;
            display: grid;
            grid-template-rows: 50px 1fr;
        }

        .topbar {
            background: #1e1f21;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 0 16px;
        }

        .breadcrumbs {
            color: #9d9d9d;
            font-size: 12px;
            font-weight: 600;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chip {
            border: 1px solid #3a3a3a;
            background: #272727;
            color: #d9d9d9;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 10px;
            letter-spacing: .1em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .page-wrap {
            padding: 24px;
            overflow: auto;
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .page-header h1 {
            margin: 0;
            font-size: clamp(1.5rem, 2.2vw, 2rem);
            font-weight: 800;
            color: #f8f9fb;
            letter-spacing: -0.01em;
        }

        .page-header p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .card {
            border: 1px solid var(--line-soft);
            border-radius: 12px;
            background: var(--surface);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
            padding: 15px;
        }

        .card h2,
        .card h3 {
            margin: 0 0 8px;
            font-size: 15px;
            font-weight: 800;
            color: #f1f1f1;
        }

        .muted { color: var(--muted); font-size: 14px; }

        .btn {
            border-radius: 8px;
            border: 1px solid #3a3a3a;
            background: #2a2d31;
            color: #eceef1;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 12px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all .15s ease;
        }

        .btn:hover { background: #32363b; border-color: #4a4f57; }

        .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .btn-danger {
            border-color: #5b2a2a;
            background: #3a1f1f;
            color: #fecaca;
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 10px;
        }

        .field label {
            font-size: 12px;
            color: #9f9f9f;
            font-weight: 700;
        }

        .field input,
        .field select {
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            background: #17191c;
            color: #f1f3f5;
            padding: 10px 11px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: #2f81f7;
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }

        .feedback {
            min-height: 20px;
            margin-top: 7px;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
        }

        .feedback.ok { color: var(--ok); }
        .feedback.error { color: var(--danger); }

        @media (max-width: 1080px) {
            .workspace { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid var(--line); }
            .menu { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
            .content { grid-template-rows: auto 1fr; }
            .topbar { padding: 10px 12px; }
            .page-wrap { padding: 14px; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
    </style>
    @yield('page_styles')
</head>
<body>
<div class="workspace">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-badge">N</div>
            <span>jocagu's Notion</span>
        </div>

        <div>
            <div class="menu-title">Recents</div>
            <nav class="menu">
                @yield('sidebar')
            </nav>
        </div>
    </aside>

    <main class="content">
        <header class="topbar">
            <div class="breadcrumbs">@yield('breadcrumbs', 'Workspace / Dashboard')</div>
            <div class="top-actions">
                @hasSection('role_chip')
                    <span class="chip">@yield('role_chip')</span>
                @endif
                @yield('top_actions')
            </div>
        </header>

        <div class="page-wrap">
            <section class="page">
                <div class="page-header">
                    <h1>@yield('page_title', 'Portal')</h1>
                    <p>@yield('page_subtitle')</p>
                </div>

                <div style="margin-top:14px;">
                    @yield('content')
                </div>
            </section>
        </div>
    </main>
</div>

@yield('scripts')
</body>
</html>
