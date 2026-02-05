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
    html,
    body {
        background-color: transparent;
    }

    css-doodle {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 bg-carbon-500">
    <css-doodle>
        <style>
         --color: #efb810, #efb810, #efb810, #efb810;

        :doodle {
    @grid: 12x1 / 100vw 100vh;
    background-position: 50%;
    background: #3c3c3c;
    background-image: @svg(
      viewBox: 0 0 1000 1000;
      circle*240 {
        cx: @r(20, 980);
        cy: @r(20, 980);
        r: @r(.01, 10);
        fill: @p(--color);
        fill-opacity: @r.5;
      }
      path*100 {
        d: M @r(1000) @r(1000) l @r(-30, 30) @r(-200, 500);
        stroke: @p(--color);
        stroke-width: @r(.01, .6);
        stroke-dasharray: 5 @round.r(5, 20)
      }
      path*20 {
        d: M @r(1000) @r(1000) l @r(-30, 30) @r(-20, 50);
        stroke: @p(--color);
        stroke-width: @r(8, 15);
        stroke-dasharray: 5 @round.r(5, 20);
      }
    );
  }

        @size: 100% 50%;
        position: absolute;
        top: 25%;
        rotate: @iI(*360deg);

        --s: @r5;
        --c: @p(--color);

        :after {
            content: '';
            position: absolute;

            @size: @r(40vmin, 61vmin) @r(12vmin, 17vmin);
            border-left: @r(3px) solid var(--c);
            border-radius: 50vmin;
            background:
                radial-gradient(var(--c) 50%, transparent 0%) 1vmin 42% / 3px 6px no-repeat,
                radial-gradient(var(--c) 50%, transparent 0%) 1vmin 58% / 3px 6px no-repeat,
                @m20(linear-gradient(to right, var(--c), transparent @r(50%, 80%)) 0 @r(100%) / @r(20%) 1px no-repeat),
                linear-gradient(to right, var(--c), transparent @r(50%, 80%)) 0 50% / @r(40%, 60%) 1px no-repeat,
                linear-gradient(to right, rgba(255, 255, 255, .015), transparent);
            transform: rotateY(0) scaleX(@p(--s)) translateZ(50vmin);
            transform-origin: 0 50%;
            will-change: transform;
            animation: r @r(10s, 20s) linear infinite;
            animation-delay: -@r(50s);
        }

        @keyframes r {
            to {
                transform: rotateY(-1turn) scaleX(@p(--s)) translateZ(50vmin)
            }
        }
        </style>
    </css-doodle>

    <div class="bg-gray-200 opacity-95 rounded-lg shadow-lg p-8 w-full max-w-md font-montserrat">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="<?= base_url(
                'images/logo.svg',
            ) ?>" alt="MB Signature Properties" class="mx-auto h-20 w-auto">
        </div>
        <?php if (!function_exists('form_open')) {
            helper('form');
        } ?>
        <?= form_open('auth/login', ['class' => 'space-y-6']) ?>
        <?= csrf_field() ?>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2 ">
                Correo *
            </label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>" required
                class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focusring-orange-400 focus:border-transparent">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña *</label>
            <input type="password" id="password" name="password" required
                class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
            <p class="text-red-500 text-sm mt-1"></p>
        </div>
        <div class="space-y-4 mb-6">
            <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-md border border-red-400">
                <?= esc($error) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center">
            <input id="login_as_employee" name="login_as_employee" type="checkbox" value="1" <?= set_checkbox(
                'login_as_employee',
                '1',
            ) ?> class="h-4 w-4 text-gray-800 focus:ring-gray-900 border-gray-300 rounded">
            <label for="login_as_employee" class="ml-2 block text-sm text-gray-900">
                Auxiliar
            </label>
        </div>

        <button type="submit"
            class="w-full bg-gray-800 text-white py-3 px-4 rounded-md hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 transition duration-200">
            Iniciar sesión
        </button>
        <?= form_close() ?>
    </div>
</body>

</html>