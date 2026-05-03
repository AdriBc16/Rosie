<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal GoodOrder')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Caveat:wght@500;700&display=swap');

        :root {
            --paper: #fafaf5;
            --ink: #1a1a1a;
            --postit: #FFEF9F;
            --line: #1a1a1a;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            background:
                linear-gradient(transparent 31px, rgba(26,26,26,0.045) 32px),
                linear-gradient(90deg, transparent 31px, rgba(26,26,26,0.03) 32px),
                var(--paper);
            background-size: 32px 32px, 32px 32px, auto;
        }

        .workspace {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px 1fr;
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(250, 250, 245, 0.94);
        }

        .sidebar {
            border-right: 2px solid var(--line);
            padding: 14px 12px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .brand {
            border: 2px solid var(--line);
            border-radius: 10px 6px 12px 8px;
            padding: 10px 10px;
            background: #fffef8;
        }

        .brand-badge { display: none; }

        .brand span {
            display: block;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .brand small {
            display: block;
            margin-top: 2px;
            color: #555;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .menu-title {
            color: #444;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 0 10px;
        }

        .menu { display: grid; gap: 6px; }

        .menu a {
            color: var(--ink);
            text-decoration: none;
            border: 2px solid transparent;
            border-radius: 8px 12px 7px 10px;
            padding: 9px 10px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            background: transparent;
        }

        .menu a::before {
            content: '◻';
            margin-right: 8px;
            font-size: 10px;
            vertical-align: middle;
        }

        .menu a:hover {
            border-color: var(--line);
            background: #fffef8;
        }

        .menu a.active {
            border-color: var(--line);
            background: var(--postit);
        }

        .content {
            min-width: 0;
            display: grid;
            grid-template-rows: 54px 1fr;
        }

        .topbar {
            border-bottom: 2px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 0 16px;
            background: rgba(255,255,255,0.45);
        }

        .breadcrumbs {
            color: #333;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chip {
            border: 2px solid var(--line);
            background: #fff;
            border-radius: 7px 10px 8px 11px;
            padding: 4px 9px;
            font-size: 10px;
            letter-spacing: .1em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .page-wrap {
            padding: 26px 28px;
            overflow: auto;
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
        }

        .page-header h1 {
            margin: 0;
            font-size: clamp(1.6rem, 2.2vw, 2.15rem);
            font-weight: 900;
            color: var(--ink);
            text-transform: uppercase;
            letter-spacing: .01em;
        }

        .page-header p {
            margin: 6px 0 0;
            color: #555;
            font-size: 14px;
            font-weight: 600;
        }

        .card {
            position: relative;
            border: 2px solid var(--line);
            border-radius: 10px 14px 9px 15px;
            background: #fffefb;
            padding: 16px;
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

        .card h2,
        .card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--ink);
            letter-spacing: .01em;
        }

        .muted { color: #4d4d4d; font-size: 14px; }
        .note { font-family: 'Caveat', cursive; font-size: 18px; color: #515151; }

        .btn {
            border-radius: 8px 12px 8px 11px;
            border: 2px solid var(--line);
            background: #fff;
            color: var(--ink);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
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

        .btn-primary,
        .btn-danger {
            background: #fff;
            color: var(--ink);
            border-color: var(--ink);
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 11px;
        }

        .field label {
            font-size: 12px;
            color: #3f3f3f;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .field input,
        .field select,
        .field textarea {
            border: 2px solid var(--line);
            border-radius: 8px 12px 8px 11px;
            background: #fff;
            color: var(--ink);
            padding: 9px 10px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            background: #fffdf1;
        }

        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }

        .feedback {
            min-height: 20px;
            margin-top: 8px;
            font-size: 12px;
            font-weight: 800;
            color: #555;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .feedback.ok { color: var(--ok); }
        .feedback.error { color: var(--danger); }

        @media (max-width: 1080px) {
            .workspace { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 2px solid var(--line); }
            .menu { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
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
            <span>CAMPUS_CORE</span>
            <small>Admin Portal</small>
        </div>

        <div>
            <div class="menu-title">Navegacion</div>
            <nav class="menu">
                @yield('sidebar')
            </nav>
        </div>
    </aside>

    <main class="content">
        <header class="topbar">
            <div class="breadcrumbs">@yield('breadcrumbs', 'Operational / Dashboard')</div>
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

                <div style="margin-top:18px;">
                    @yield('content')
                </div>
            </section>
        </div>
    </main>
</div>

@yield('scripts')
</body>
</html>
