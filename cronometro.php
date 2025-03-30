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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronômetro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #timer {
            font-size: 3em;
            margin-top: 20px;
        }

        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background-color: #333;
            color: white;
        }

        table, th, td {
            border: 1px solid #fff;
            padding: 10px;
            text-align: center;
        }

        th, td {
            color: white;
        }

        button {
            margin: 5px;
        }

        #lap-times {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div id="main-content">
        <h1>Cronômetro</h1>
        <h2 id="timer">00:00:00,00</h2>
        <button class="btn btn-success" id="startPauseBtn">Iniciar</button>
        <button class="btn btn-primary" id="lapBtn">Registrar Volta</button>
        <button class="btn btn-warning" id="resetBtn">Resetar</button>

        <div id="lap-times">
            <h4>Voltadas</h4>
            <table>
                <thead>
                    <tr>
                        <th>Voltas</th>
                        <th>Hora</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="lapList"></tbody>
            </table>
        </div>
    </div>

    <script>
        let startTime;
        let tInterval;
        let running = false;
        let lapCounter = 1;
        let elapsedTime = 0;
        let totalTime = 0;

        // Função para iniciar/pausar o cronômetro
        function startPauseTimer() {
            if (!running) {
                startTime = new Date().getTime() - elapsedTime;
                tInterval = setInterval(updateTimer, 10);
                document.getElementById('startPauseBtn').innerText = "Pausar";
                running = true;
            } else {
                clearInterval(tInterval);
                document.getElementById('startPauseBtn').innerText = "Iniciar";
                running = false;
            }
        }

        // Função para resetar o cronômetro
        function resetTimer() {
            clearInterval(tInterval);
            running = false;
            elapsedTime = 0;
            totalTime = 0;
            lapCounter = 1;
            document.getElementById('timer').innerHTML = '00:00:00,00';
            document.getElementById('lapList').innerHTML = ''; // Limpa as voltas
            document.getElementById('startPauseBtn').innerText = "Iniciar";
        }

        // Função para atualizar o cronômetro
        function updateTimer() {
            elapsedTime = new Date().getTime() - startTime;
            let hours = Math.floor((elapsedTime / (1000 * 60 * 60)) % 24);
            let minutes = Math.floor((elapsedTime / (1000 * 60)) % 60);
            let seconds = Math.floor((elapsedTime / 1000) % 60);
            let milliseconds = Math.floor((elapsedTime % 1000) / 10);

            document.getElementById('timer').innerHTML = 
                `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')},${String(milliseconds).padStart(2, '0')}`;
        }

// Função para formatar o tempo total
function formatTime(timeInMillis) {
    let totalSeconds = Math.floor(timeInMillis / 1000);
    let hours = Math.floor(totalSeconds / 3600);
    let minutes = Math.floor((totalSeconds % 3600) / 60);
    let seconds = totalSeconds % 60;
    let milliseconds = Math.floor((timeInMillis % 1000) / 10); // Para milissegundos

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')},${String(milliseconds).padStart(2, '0')}`;
}

let lastLapTime = 0; // Variável para armazenar o tempo da última volta

// Função para registrar a volta
function recordLap() {
    const lapTimeInMillis = elapsedTime - totalTime; // Tempo da volta em milissegundos
    totalTime = elapsedTime; // Atualiza o tempo total acumulado

    // Calcula a duração da volta (tempo desde a última volta)
    let lapDuration = lapTimeInMillis - lastLapTime;
    lastLapTime = lapTimeInMillis; // Atualiza o tempo da última volta

    // Formata a duração da volta
    const lapDurationFormatted = formatTime(lapDuration);

    // Formata o tempo total acumulado
    const totalTimeFormatted = formatTime(totalTime);

    // Cria uma nova linha para a tabela
    const lapList = document.getElementById('lapList');
    const row = document.createElement('tr');

    const lapCell = document.createElement('td');
    lapCell.innerText = lapCounter++; // Número da volta
    row.appendChild(lapCell);

    const lapDurationCell = document.createElement('td');
    lapDurationCell.innerText = lapDurationFormatted; // Duração da volta (tempo entre as voltas)
    row.appendChild(lapDurationCell);

    const totalCell = document.createElement('td');
    totalCell.innerText = totalTimeFormatted; // Tempo total acumulado até o momento
    row.appendChild(totalCell);

    lapList.insertBefore(row, lapList.firstChild); // Adiciona a linha no topo da tabela
}


        // Eventos dos botões
        document.getElementById('startPauseBtn').onclick = function() {
            startPauseTimer();
        };

        document.getElementById('resetBtn').onclick = function() {
            resetTimer();
        };

        document.getElementById('lapBtn').onclick = function() {
            recordLap();
        };

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
