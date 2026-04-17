<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBSP - Sistema Integral de Administración de Compras</title>
    <link rel="stylesheet" href="<?= base_url('css/styless.css') ?>">
    <link rel="shortcut icon" type="image/png" href="/favicon.ico">
    <script src="https://unpkg.com/css-doodle@0.38.4/css-doodle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap');

        :root {
            --gold: #efb810;
            --carbon: #121212;
            --ink: #1a1a1a;
        }

        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: var(--carbon);
            overflow: hidden;
            font-family: 'Montserrat', sans-serif;
        }

        css-doodle {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.6;
        }

        .content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            padding: 0 2rem;
            background: radial-gradient(circle at center, rgba(30,30,30,0) 0%, rgba(18,18,18,0.8) 100%);
        }

        .title-container {
            margin-bottom: 3rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 1.2s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }

        h1 {
            color: var(--gold);
            font-size: clamp(1.5rem, 5vw, 3.5rem);
            font-weight: 300;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }

        .signature-line {
            width: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            margin: 1.5rem auto 0;
            animation: drawLine 2s ease-out 0.5s forwards;
        }

        .subtitle {
            color: #a0a0a0;
            font-size: 1rem;
            letter-spacing: 0.5em;
            margin-top: 2rem;
            text-transform: uppercase;
            font-weight: 400;
            opacity: 0;
            animation: fadeIn 1.2s ease-out 1.2s forwards;
        }

        .enter-btn {
            position: relative;
            margin-top: 4rem;
            padding: 1rem 3.5rem;
            background: transparent;
            border: 1px solid rgba(239, 184, 16, 0.3);
            color: var(--gold);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
            overflow: hidden;
            text-decoration: none;
            opacity: 0;
            animation: fadeIn 1s ease-out 1.8s forwards;
        }

        .enter-btn:hover {
            border-color: var(--gold);
            letter-spacing: 0.4em;
            box-shadow: 0 0 30px rgba(239, 184, 16, 0.15);
            background: rgba(239, 184, 16, 0.05);
        }

        .enter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(239, 184, 16, 0.2),
                transparent
            );
            transition: all 0.6s;
        }

        .enter-btn:hover::before {
            left: 100%;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes drawLine {
            to {
                width: 80%;
                max-width: 600px;
            }
        }

        .logo-mb {
            width: 120px;
            height: auto;
            margin-bottom: 2rem;
            filter: drop-shadow(0 0 10px rgba(239, 184, 16, 0.2));
            opacity: 0;
            animation: fadeIn 1.5s ease-out 0.2s forwards;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            h1 {
                font-size: 1.8rem;
                letter-spacing: 0.1em;
            }
            .subtitle {
                font-size: 0.7rem;
                letter-spacing: 0.3em;
            }
        }
    </style>
</head>

<body>
    <css-doodle>
        <style>
            :doodle {
                @grid: 10x10 / 100vmax;
                background: #121212;
            }
            @size: 1px;
            position: absolute;
            top: @r(100%);
            left: @r(100%);
            background: #efb810;
            box-shadow: 0 0 2px #efb810, 0 0 5px #efb810;
            border-radius: 50%;
            opacity: @r(0.1, 0.8);
            animation: float @r(20s, 40s) linear infinite;
            animation-delay: -@r(40s);

            @keyframes float {
                0% { transform: translateY(0) translateX(0); }
                33% { transform: translateY(@r(-20vh, 20vh)) translateX(@r(-20vw, 20vw)); }
                66% { transform: translateY(@r(-20vh, 20vh)) translateX(@r(-20vw, 20vw)); }
                100% { transform: translateY(0) translateX(0); }
            }
        </style>
    </css-doodle>

    <div class="content">
        <img src="<?= base_url('images/logo.svg') ?>" alt="MBSP Logo" class="logo-mb">
        
        <div class="title-container">
            <h1>Sistema Integral De <br> Administración de Compras MBSP</h1>
            <div class="signature-line"></div>
            <div class="subtitle">Haz que tu tiempo rinda tanto como tu presupuesto.</div>
        </div>

        <a href="<?= base_url('auth') ?>" class="enter-btn">
            Ingresar al sistema
        </a>
    </div>

    <script>
        // Optimized transition for agility
        document.querySelector('.enter-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            // Fast fade-out (400ms) to feel more responsive
            document.body.style.transition = 'opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            document.body.style.opacity = '0';
            
            setTimeout(() => {
                window.location.href = href;
            }, 400);
        });
    </script>
</body>

</html>
