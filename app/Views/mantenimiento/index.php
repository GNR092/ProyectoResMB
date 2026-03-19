<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mantenimiento - Mini Juego</title>

<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: #f0f2f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        overflow: hidden;
    }

    .gear {
        position: absolute;
        opacity: 0.08;
        animation: spin 10s linear infinite;
    }

    .gear.small {
        width: 60px;
        top: 10%;
        left: 10%;
    }

    .gear.medium {
        width: 100px;
        bottom: 15%;
        right: 15%;
        animation-duration: 15s;
    }

    .gear.large {
        width: 140px;
        top: 20%;
        right: 5%;
        animation-duration: 20s;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .container {
        position: relative;
        background: white;
        border-radius: 20px;
        padding: 30px;
        width: 90%;
        max-width: 600px;
        text-align: center;
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        z-index: 2;
    }

    .illustration {
        width: 100%;
        max-height: 220px;
        object-fit: contain;
        margin-bottom: 15px;
    }

    h1 {
        margin: 10px 0;
        color: #333;
    }

    p {
        color: #666;
    }

    button {
        background: #d4af37;
        border: none;
        padding: 10px 25px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.2s;
    }

    button:hover {
        background: #f0d46a;
        transform: translateY(-2px);
    }

    .info {
        margin-top: 10px;
        color: #555;
        font-size: 14px;
    }

    #gameArea {
        position: relative;
        width: 100%;
        height: 250px;
        background: #f9f9f9;
        border-radius: 10px;
        margin-top: 15px;
        border: 1px solid #ddd;
        overflow: hidden;
    }

    #target {
        width: 60px;
        height: 60px;
        background: #d4af37;
        border-radius: 50%;
        position: absolute;
        display: none;
        justify-content: center;
        align-items: center;
        font-style: italic;
        font-weight: bold;
        color: black;
        cursor: pointer;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    #target:hover {
        transform: scale(1.2);
    }
</style>
</head>

<body>

<svg class="gear small" viewBox="0 0 100 100">
    <path fill="#000" d="M50 30a20 20 0 100 40 20 20 0 000-40zm0-20l6 10h12l2 12 10 6-6 10 6 10-10 6-2 12H56l-6 10-6-10H32l-2-12-10-6 6-10-6-10 10-6 2-12h12z"/>
</svg>

<svg class="gear medium" viewBox="0 0 100 100">
    <path fill="#000" d="M50 30a20 20 0 100 40 20 20 0 000-40zm0-20l6 10h12l2 12 10 6-6 10 6 10-10 6-2 12H56l-6 10-6-10H32l-2-12-10-6 6-10-6-10 10-6 2-12h12z"/>
</svg>

<svg class="gear large" viewBox="0 0 100 100">
    <path fill="#000" d="M50 30a20 20 0 100 40 20 20 0 000-40zm0-20l6 10h12l2 12 10 6-6 10 6 10-10 6-2 12H56l-6 10-6-10H32l-2-12-10-6 6-10-6-10 10-6 2-12h12z"/>
</svg>

<div class="container">

    <img src="<?= base_url('images/mantenimiento.png') ?>" class="illustration" alt="Mantenimiento">

    <h1>Sitio en mantenimiento</h1>
    <p>Estamos trabajando para mejorar tu experiencia ⚙️</p>

    <button onclick="startGame()">Jugar mientras esperas</button>

    <div class="info">
        ⏱ <span id="time">20</span>s | 🎯 <span id="score">0</span>
    </div>

    <div id="gameArea">
        <div id="target">mb</div>
    </div>

</div>

<script>
let score = 0;
let timeLeft = 20;
let gameInterval;
let moveSpeed = 1000;
let gameActive = false;

const target = document.getElementById("target");
const gameArea = document.getElementById("gameArea");

function startGame() {
    score = 0;
    timeLeft = 20;
    moveSpeed = 1000;
    gameActive = true;

    document.getElementById("score").textContent = score;
    document.getElementById("time").textContent = timeLeft;

    target.style.display = "flex";

    moveTarget();

    gameInterval = setInterval(() => {
        timeLeft--;
        document.getElementById("time").textContent = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(gameInterval);
            target.style.display = "none";
            gameActive = false;
            alert("🔥 Fin del juego! Puntuación: " + score);
        }
    }, 1000);
}

function moveTarget() {
    if (!gameActive) return;

    const maxX = gameArea.clientWidth - 60;
    const maxY = gameArea.clientHeight - 60;

    const x = Math.random() * maxX;
    const y = Math.random() * maxY;

    target.style.left = x + "px";
    target.style.top = y + "px";

    setTimeout(moveTarget, moveSpeed);
}

target.addEventListener("click", () => {
    if (!gameActive) return;

    score++;
    document.getElementById("score").textContent = score;

    if (moveSpeed > 300) moveSpeed -= 50;

    moveTarget();
});
</script>

</body>
</html>
