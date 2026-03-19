<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento - Mini Juego</title>
    <link rel="stylesheet" href="<?= base_url('css/styless.css') ?>">
    <style>
        .gear {
            animation: spin linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.4); }
            100% { transform: scale(1); }
        }
        .pop-anim { animation: pop 0.15s ease-out; }
        .target { transition: left 0.3s ease-out, top 0.3s ease-out, width 0.2s, height 0.2s, background-color 0.3s; }
        .hidden { display: none; }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        .modal-content {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            max-width: 24rem;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 font-sans">

    <!-- Engranajes decorativos -->
    <svg class="gear absolute text-gray-300 opacity-10" style="width: 80px; height: 80px; top: 8%; left: 5%; animation-duration: 12s;" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet">
        <path fill="currentColor" d="M50 30a20 20 0 100 40 20 20 0 000-40zm0-20l6 10h12l2 12 10 6-6 10 6 10-10 6-2 12H56l-6 10-6-10H32l-2-12-10-6 6-10-6-10 10-6 2-12h12z"/>
    </svg>
    <svg class="gear absolute text-gray-400 opacity-10" style="width: 120px; height: 120px; bottom: 10%; right: 10%; animation-duration: 18s;" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet">
        <path fill="currentColor" d="M50 30a20 20 0 100 40 20 20 0 000-40zm0-20l6 10h12l2 12 10 6-6 10 6 10-10 6-2 12H56l-6 10-6-10H32l-2-12-10-6 6-10-6-10 10-6 2-12h12z"/>
    </svg>
    <svg class="gear absolute text-gray-500 opacity-10" style="width: 160px; height: 160px; top: 15%; right: 5%; animation-duration: 25s;" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet">
        <path fill="currentColor" d="M50 30a20 20 0 100 40 20 20 0 000-40zm0-20l6 10h12l2 12 10 6-6 10 6 10-10 6-2 12H56l-6 10-6-10H32l-2-12-10-6 6-10-6-10 10-6 2-12h12z"/>
    </svg>

    <!-- Contenedor principal -->
    <div class="relative z-10 bg-white rounded-3xl shadow-2xl p-8 w-full max-w-lg mx-4 overflow-hidden">

        <!-- Icono de engranaje -->
        <div class="flex justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-20 h-20 text-amber-500 animate-spin" style="animation-duration: 3s;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">Sitio en mantenimiento</h1>
        <p class="text-gray-500 text-center mb-6"><?= esc($mensaje) ?></p>

        <!-- Stats -->
        <div class="flex justify-center gap-8 mb-4">
            <div class="text-center">
                <span id="timeLeft" class="text-3xl font-bold text-amber-500">20</span>
                <span class="text-gray-400 text-sm ml-1">seg</span>
            </div>
            <div class="text-center">
                <span id="score" class="text-3xl font-bold text-amber-500">0</span>
                <span class="text-gray-400 text-sm ml-1">pts</span>
            </div>
            <div class="text-center">
                <span id="highScore" class="text-xl font-bold text-gray-400">0</span>
                <span class="text-gray-400 text-sm ml-1">best</span>
            </div>
        </div>

        <!-- Barra de tiempo -->
        <div class="h-3 bg-gray-200 rounded-full mb-6 overflow-hidden">
            <div id="timeBar" class="h-full rounded-full bg-green-500 transition-all duration-300" style="width: 100%"></div>
        </div>

        <!-- Botón juego -->
        <button id="btnStart" onclick="startGame()" class="w-full py-3 px-6 rounded-xl font-bold text-white bg-amber-500 hover:bg-amber-400 transition-all hover:-translate-y-0.5 hover:shadow-lg">
            Jugar mientras esperas
        </button>

        <!-- Indicador nivel -->
        <div id="levelIndicator" class="hidden flex justify-center gap-1 mt-3 mb-2">
            <div id="dot1" class="w-2 h-2 rounded-full bg-amber-500"></div>
            <div id="dot2" class="w-2 h-2 rounded-full bg-gray-200"></div>
            <div id="dot3" class="w-2 h-2 rounded-full bg-gray-200"></div>
            <div id="dot4" class="w-2 h-2 rounded-full bg-gray-200"></div>
            <div id="dot5" class="w-2 h-2 rounded-full bg-gray-200"></div>
        </div>
        <p id="levelText" class="hidden text-center text-xs text-gray-400 mb-4">Nivel 1</p>

        <!-- Área de juego -->
        <div id="gameArea" class="hidden relative h-64 bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl border-2 border-gray-200 overflow-hidden shadow-inner mt-4">
            <div id="target" onclick="hit()" class="target absolute rounded-full flex items-center justify-center font-bold text-white cursor-pointer shadow-lg hover:scale-110" style="left: 50px; top: 50px; width: 60px; height: 60px; background-color: #f59e0b;">MB</div>
        </div>

        <!-- Instrucciones -->
        <p id="instructions" class="text-center text-sm text-gray-400 mt-4">
            Haz clic en el objetivo para ganar puntos. Se hace más rápido y pequeño con cada clic.
        </p>

        <!-- Botón mute -->
        <button id="btnMute" onclick="toggleMute()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="iconSound" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
            </svg>
        </button>
    </div>

    <!-- Modal Game Over -->
    <div id="modalGameOver" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="text-6xl mb-4">🔥</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Juego terminado!</h2>
            <p class="text-gray-500 mb-4">Tu puntuación:</p>
            <div id="finalScore" class="text-5xl font-bold text-amber-500 mb-2">0</div>
            <p id="newRecord" class="hidden text-sm text-green-500 font-medium mb-4 animate-pulse">¡Nueva mejor puntuación! 🏆</p>
            <p class="text-gray-400 text-sm mb-6">Mejor puntuación: <span id="modalHighScore" class="font-bold">0</span></p>
            <button id="btnPlayAgain" class="w-full bg-amber-500 hover:bg-amber-400 text-white font-bold py-3 px-6 rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-lg" onclick="startGame()">Jugar de nuevo</button>
            <button id="btnClose" class="mt-3 text-gray-400 hover:text-gray-600 text-sm transition-colors" onclick="closeModal()">Cerrar</button>
        </div>
    </div>

    <script src="<?= base_url('js/alpine@3.14.8.js') ?>"></script>
    <script>
        let score = 0;
        let timeLeft = 20;
        let gameDuration = 20;
        let gameActive = false;
        let gameOver = false;
        let moveSpeed = 1500;
        let baseMoveSpeed = 1500;
        let minMoveSpeed = 450;
        let targetX = 50;
        let targetY = 50;
        let targetSize = 70;
        let baseTargetSize = 70;
        let minTargetSize = 35;
        let highScore = 0;
        let isNewHighScore = false;
        let muted = false;
        let difficultyLevel = 1;
        let timerInterval = null;
        let moveTimeout = null;
        let targetColors = ['#f59e0b', '#f97316', '#ef4444', '#ec4899', '#a855f7'];

        function init() {
            const saved = localStorage.getItem('mbgame_highscore');
            if (saved) {
                highScore = parseInt(saved);
                document.getElementById('highScore').textContent = highScore;
            }
            moveTarget();
        }

        function startGame() {
            closeModal();
            
            if (gameActive) {
                gameActive = false;
                if (timerInterval) clearInterval(timerInterval);
                if (moveTimeout) clearTimeout(moveTimeout);
            }
            
            score = 0;
            timeLeft = gameDuration;
            moveSpeed = baseMoveSpeed;
            targetSize = baseTargetSize;
            difficultyLevel = 1;
            gameActive = true;
            gameOver = false;
            isNewHighScore = false;

            updateUI();
            updateTarget();
            startTimer();
            playSound(400, 0.2, 0.2);
        }

        function startTimer() {
            if (timerInterval) clearInterval(timerInterval);
            
            timerInterval = setInterval(() => {
                timeLeft--;
                updateUI();
                
                if (timeLeft <= 0) {
                    endGame();
                }
            }, 1000);
        }

        function endGame() {
            gameActive = false;
            gameOver = true;
            
            if (score > highScore) {
                highScore = score;
                isNewHighScore = true;
                localStorage.setItem('mbgame_highscore', highScore.toString());
                document.getElementById('highScore').textContent = highScore;
            }
            
            if (timerInterval) clearInterval(timerInterval);
            if (moveTimeout) clearTimeout(moveTimeout);
            
            showModal();
            playSound(200, 0.3, 0.5);
        }

        function hit() {
            if (!gameActive) return;

            // Mover inmediatamente ANTES de sumar puntos
            updateTarget();

            score++;
            triggerPop();
            playSound(600, 0.3, 0.1);

            difficultyLevel = Math.min(5, Math.floor(score / 5) + 1);
            
            if (moveSpeed > minMoveSpeed) {
                moveSpeed = Math.max(minMoveSpeed, baseMoveSpeed - (score * 25));
            }
            
            if (targetSize > minTargetSize) {
                targetSize = Math.max(minTargetSize, baseTargetSize - (score * 2));
            }
            
            updateUI();
        }

        function updateUI() {
            document.getElementById('timeLeft').textContent = timeLeft;
            document.getElementById('score').textContent = score;
            
            const timeBar = document.getElementById('timeBar');
            const percentage = (timeLeft / gameDuration) * 100;
            timeBar.style.width = percentage + '%';
            
            if (timeLeft > 12) {
                timeBar.className = 'h-full rounded-full bg-green-500 transition-all duration-300';
            } else if (timeLeft > 6) {
                timeBar.className = 'h-full rounded-full bg-yellow-500 transition-all duration-300';
            } else {
                timeBar.className = 'h-full rounded-full bg-red-500 transition-all duration-300';
            }

            const levelIndicator = document.getElementById('levelIndicator');
            const levelText = document.getElementById('levelText');
            
            if (gameActive) {
                levelIndicator.classList.remove('hidden');
                levelIndicator.classList.add('flex');
                levelText.classList.remove('hidden');
                levelText.textContent = 'Nivel ' + difficultyLevel;
                
                for (let i = 1; i <= 5; i++) {
                    const dot = document.getElementById('dot' + i);
                    if (i <= difficultyLevel) {
                        dot.classList.remove('bg-gray-200');
                        dot.classList.add('bg-amber-500');
                    } else {
                        dot.classList.remove('bg-amber-500');
                        dot.classList.add('bg-gray-200');
                    }
                }
            } else {
                levelIndicator.classList.add('hidden');
                levelIndicator.classList.remove('flex');
                levelText.classList.add('hidden');
            }

            const gameArea = document.getElementById('gameArea');
            const instructions = document.getElementById('instructions');
            const btnStart = document.getElementById('btnStart');

            if (gameActive) {
                gameArea.classList.remove('hidden');
                instructions.classList.add('hidden');
                btnStart.textContent = 'Jugando...';
                btnStart.classList.remove('bg-amber-500', 'hover:bg-amber-400');
                btnStart.classList.add('bg-gray-400', 'cursor-not-allowed');
            } else {
                gameArea.classList.add('hidden');
                instructions.classList.remove('hidden');
                btnStart.textContent = 'Jugar mientras esperas';
                btnStart.classList.add('bg-amber-500', 'hover:bg-amber-400');
                btnStart.classList.remove('bg-gray-400', 'cursor-not-allowed');
            }
        }

        function updateTarget() {
            const target = document.getElementById('target');
            const gameArea = document.getElementById('gameArea');
            
            const maxX = Math.max(10, gameArea.clientWidth - targetSize - 10);
            const maxY = Math.max(10, gameArea.clientHeight - targetSize - 10);

            // Movimiento gradual desde la posición actual
            let newX = Math.floor(Math.random() * maxX) + 5;
            let newY = Math.floor(Math.random() * maxY) + 5;
            
            // Evitar que se mueva muy cerca de la posición actual
            const minDistance = targetSize * 1.5;
            const distance = Math.sqrt(Math.pow(newX - targetX, 2) + Math.pow(newY - targetY, 2));
            
            if (distance < minDistance && score < 5) {
                newX = (newX + maxX / 2) % maxX + 5;
                newY = (newY + maxY / 2) % maxY + 5;
            }

            targetX = newX;
            targetY = newY;

            target.style.left = targetX + 'px';
            target.style.top = targetY + 'px';
            target.style.width = targetSize + 'px';
            target.style.height = targetSize + 'px';
            target.style.backgroundColor = targetColors[Math.min(difficultyLevel - 1, targetColors.length - 1)];

            if (gameActive) {
                moveTimeout = setTimeout(updateTarget, moveSpeed);
            }
        }

        function moveTarget() {
            if (!gameActive) {
                setTimeout(moveTarget, 500);
                return;
            }
            updateTarget();
        }

        function triggerPop() {
            const target = document.getElementById('target');
            target.classList.remove('pop-anim');
            void target.offsetWidth;
            target.classList.add('pop-anim');
        }

        function showModal() {
            document.getElementById('finalScore').textContent = score;
            document.getElementById('modalHighScore').textContent = highScore;
            document.getElementById('newRecord').style.display = isNewHighScore ? 'block' : 'none';
            document.getElementById('modalGameOver').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('modalGameOver').style.display = 'none';
        }

        function toggleMute() {
            muted = !muted;
            const icon = document.getElementById('iconSound');
            if (muted) {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>';
            } else {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>';
            }
        }

        function playSound(frequency, volume, duration) {
            if (muted) return;
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = frequency;
                oscillator.type = 'sine';
                gainNode.gain.setValueAtTime(volume, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + duration);
            } catch (e) {
                console.log('Audio not supported');
            }
        }

        init();

        document.getElementById('btnPlayAgain').addEventListener('click', startGame);
        document.getElementById('btnClose').addEventListener('click', closeModal);
    </script>
</body>
</html>
