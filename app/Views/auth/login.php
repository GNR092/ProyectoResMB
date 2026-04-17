<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MB Signature Properties</title>
    <link rel="stylesheet" href="<?= base_url('css/styless.css') ?>">
    <link rel="shortcut icon" type="image/png" href="/favicon.ico">
    <script src="https://unpkg.com/css-doodle@0.38.4/css-doodle.min.js"></script>
    <style>
    body {
        opacity: 0;
        animation: fadeIn 0.8s ease-out forwards;
        background-color: #0a0a0a !important;
        overflow: hidden;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    html,
    body {
        background-color: #0a0a0a;
    }

    css-doodle {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    /* Glassmorphism accent */
    .glass-card {
        background: rgba(18, 18, 18, 0.75);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.8);
    }

    .input-field {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        transition: all 0.3s ease;
    }

    .input-field:focus {
        background: rgba(255, 255, 255, 0.07);
        border-color: #efb810;
        box-shadow: 0 0 0 2px rgba(239, 184, 16, 0.2);
        outline: none;
    }

    .btn-primary {
        background: #efb810;
        color: #000;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: #fccb35;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 184, 16, 0.3);
    }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <css-doodle>
        <style>
        --color: #efb810, #d4af37, #b8860b, #efb810;

        :doodle {
            @grid: 14x1 / 100vw 100vh;
            background-position: 50%;
            background: #0a0a0a;
            perspective: 1000px;
            background-image: @svg(
                viewBox: 0 0 1000 1000;
                circle*240 {
                    cx: @r(20, 980);
                    cy: @r(20, 980);
                    r: @r(.01, 8);
                    fill: @p(--color);
                    fill-opacity: @r.4;
                }
                path*80 {
                    d: M @r(1000) @r(1000) l @r(-50, 50) @r(-300, 600);
                    stroke: @p(--color);
                    stroke-width: @r(.01, .4);
                    stroke-dasharray: 5 @round.r(5, 15)
                }
            );
        }

        @size: 100% 60%;
        position: absolute;
        top: 20%;
        left: 0;
        rotate: @r(360deg);

        --s: @r(1, 4);
        --c: @p(--color);

        :after {
            content: '';
            position: absolute;
            transform-style: preserve-3d;

            @size: @r(30vmin, 70vmin) @r(10vmin, 20vmin);
            border-left: @r(2px) solid var(--c);
            border-radius: 50vmin;
            background:
                radial-gradient(var(--c) 50%, transparent 0%) 1vmin 42% / 2px 4px no-repeat,
                radial-gradient(var(--c) 50%, transparent 0%) 1vmin 58% / 2px 4px no-repeat,
                @m15(linear-gradient(to right, var(--c), transparent @r(40%, 70%)) 0 @r(100%) / @r(15%) 1px no-repeat),
                linear-gradient(to right, var(--c), transparent @r(40%, 70%)) 0 50% / @r(30%, 50%) 1px no-repeat,
                linear-gradient(to right, rgba(255, 255, 255, 0.01), transparent);
            
            will-change: transform;
            animation: move @r(15s, 30s) linear infinite;
            animation-delay: -@r(50s);
            opacity: @r(0.2, 0.6);
        }

        @keyframes move {
            0% {
                transform: rotateX(@r(360deg)) rotateY(0deg) rotateZ(@r(360deg)) scaleX(var(--s)) translateZ(-200px);
                opacity: 0;
            }
            10% {
                opacity: @r(0.2, 0.6);
            }
            90% {
                opacity: @r(0.2, 0.6);
            }
            100% {
                transform: rotateX(@r(360deg)) rotateY(360deg) rotateZ(@r(360deg)) scaleX(var(--s)) translateZ(500px);
                opacity: 0;
            }
        }
        </style>
    </css-doodle>

    <div class="glass-card rounded-3xl px-12 py-16 w-full max-w-md font-montserrat transition-all duration-500">
        <!-- Contenedor interno para centrar y estrechar el contenido -->
        <div class="max-w-[340px] mx-auto">
            <!-- Logo -->
            <div class="text-center mb-12">
                <img src="<?= base_url('images/logo.svg') ?>" alt="MB Signature Properties" class="mx-auto h-24 w-auto brightness-110">
                <h2 class="text-white text-[10px] font-medium uppercase tracking-[0.4em] mt-6 opacity-50">Portal Corporativo</h2>
            </div>

            <?php if (!function_exists('form_open')) { helper('form'); } ?>
            <?= form_open('auth/login', ['class' => 'space-y-8']) ?>
            <?= csrf_field() ?>

            <div class="space-y-3">
                <label for="email" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">
                    Correo Electrónico
                </label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required
                    class="w-full px-5 py-4 rounded-2xl input-field text-sm" placeholder="ejemplo@mbsignature.com">
            </div>

            <div class="space-y-3">
                <label for="password" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">
                    Contraseña
                </label>
                <input type="password" id="password" name="password" required
                    class="w-full px-5 py-4 rounded-2xl input-field text-sm" placeholder="••••••••">
            </div>

            <?php if (isset($error)): ?>
            <div class="bg-red-500/10 text-red-400 p-4 rounded-2xl border border-red-500/20 text-xs text-center">
                <?= esc($error) ?>
            </div>
            <?php endif; ?>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center group cursor-pointer">
                    <input id="login_as_employee" name="login_as_employee" type="checkbox" value="1" <?= set_checkbox('login_as_employee', '1') ?> 
                        class="h-4 w-4 bg-transparent border-white/20 rounded-md text-[#efb810] focus:ring-[#efb810] transition-colors">
                    <label for="login_as_employee" class="ml-3 block text-[11px] text-gray-400 group-hover:text-gray-300 transition-colors cursor-pointer">
                        Acceso Auxiliar
                    </label>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full btn-primary py-4 rounded-2xl text-[11px] font-bold uppercase tracking-[0.2em]">
                    Iniciar Sesión
                </button>
            </div>
            
            <div class="mt-14 text-center">
                <p class="text-[9px] text-gray-600 uppercase tracking-[0.3em] font-medium">
                    &copy; <?= date('Y') ?> MB Signature Properties
                </p>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</body>

</html>