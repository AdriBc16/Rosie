<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoodOrder</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Outfit:wght@600;700&display=swap');
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: 'Manrope', sans-serif;
            color: #f3f4f6;
            background:
                radial-gradient(920px 540px at 5% 0%, rgba(59,130,246,.12), transparent 45%),
                radial-gradient(740px 480px at 100% 0%, rgba(16,185,129,.1), transparent 40%),
                #101114;
        }
        .card {
            width: min(640px, calc(100% - 32px));
            border: 1px solid #2e3238;
            border-radius: 16px;
            background: linear-gradient(180deg, #1b1d21, #15171a);
            padding: 24px;
        }
        h1 { margin: 0; font-family: 'Outfit', sans-serif; font-size: clamp(1.7rem, 3vw, 2.2rem); }
        p { margin: 9px 0 0; color: #a4acb7; }
        a {
            margin-top: 14px;
            display: inline-flex;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #2f6cb3;
            background: linear-gradient(180deg, #2b7be8, #1f62bf);
            color: white;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <section class="card">
        <h1>GoodOrder Workspace</h1>
        <p>Estetica tipo Notion dark aplicada al portal web.</p>
        <a href="{{ route('portal.login') }}">Ir al login</a>
    </section>
</body>
</html>
