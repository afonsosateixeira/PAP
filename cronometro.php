<?php
session_start();
require 'config.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cronômetro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container stopwatch">
        <h2>Cronômetro</h2>
        <p id="stopwatch">00:00:00</p>
        <button class="btn btn-success" onclick="startStopwatch()">Iniciar</button>
        <button class="btn btn-danger" onclick="stopStopwatch()">Parar</button>
    </div>

    <script>
        let stopwatchInterval;
        function startStopwatch() {
            let startTime = Date.now();
            if (stopwatchInterval) clearInterval(stopwatchInterval);
            stopwatchInterval = setInterval(() => {
                let elapsed = Date.now() - startTime;
                let seconds = Math.floor((elapsed / 1000) % 60);
                let minutes = Math.floor((elapsed / (1000 * 60)) % 60);
                let hours = Math.floor((elapsed / (1000 * 60 * 60)) % 24);
                document.getElementById('stopwatch').innerText = 
                    `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
            }, 1000);
        }
        function stopStopwatch() {
            clearInterval(stopwatchInterval);
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
