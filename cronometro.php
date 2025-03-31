<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronômetro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        #timer {
            font-size: 6em;
            margin-top: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .btn-custom {
            background-color: #71b9f0; 
            border-radius: 50px;
            color: white;
            font-size: 1.5rem; 
            padding: 10px 20px; 
        }

        .btn-custom:hover {
            background-color: #71b9f0; 
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background-color: #fff;
            color: black;
            display: none; /* A tabela fica oculta inicialmente */
        }

        table, th, td {
            border: none;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            border-bottom: 2px solid #ccc;
            color: black;
        }

        #lap-times {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div id="main-content">
        <h2 id="timer">00:00:00,00</h2>

        <div class="button-container">
            <button class="btn btn-custom" id="startPauseBtn"><i class="fa fa-play"></i></button>
            <button class="btn btn-custom" id="lapBtn"><i class="fa fa-flag"></i></button>
            <button class="btn btn-custom" id="resetBtn"><i class="fa fa-rotate-left"></i></button>
        </div>

        <div id="lap-times">
            <table id="lapTable">
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
        let lastLapTime = 0;
        let debounceTimeout; 

        // Função para iniciar/pausar o cronômetro
        function startPauseTimer() {
            if (!running) {
                startTime = new Date().getTime() - elapsedTime;
                running = true;
                requestAnimationFrame(updateTimer);
                document.getElementById('startPauseBtn').innerHTML = '<i class="fa fa-pause"></i>';
            } else {
                running = false;
                document.getElementById('startPauseBtn').innerHTML = '<i class="fa fa-play"></i>';
            }
        }

        // Função para resetar o cronômetro
        function resetTimer() {
            running = false;
            elapsedTime = 0;
            totalTime = 0;
            lapCounter = 1;
            lastLapTime = 0;
            document.getElementById('timer').innerHTML = '00:00:00,00';
            document.getElementById('lapList').innerHTML = ''; 
            document.getElementById('startPauseBtn').innerHTML = '<i class="fa fa-play"></i>';
            document.getElementById('lapTable').style.display = 'none'; 
        }

        // Função para atualizar o cronômetro de forma mais fluida com requestAnimationFrame
        function updateTimer() {
            if (running) {
                elapsedTime = new Date().getTime() - startTime;
                let hours = Math.floor((elapsedTime / (1000 * 60 * 60)) % 24);
                let minutes = Math.floor((elapsedTime / (1000 * 60)) % 60);
                let seconds = Math.floor((elapsedTime / 1000) % 60);
                let milliseconds = Math.floor((elapsedTime % 1000) / 10);

                document.getElementById('timer').innerHTML = 
                    `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')},${String(milliseconds).padStart(2, '0')}`;

                requestAnimationFrame(updateTimer); // Chama novamente para atualização contínua
            }
        }

        // Função para formatar o tempo total
        function formatTime(timeInMillis) {
            let totalSeconds = Math.floor(timeInMillis / 1000);
            let hours = Math.floor(totalSeconds / 3600);
            let minutes = Math.floor((totalSeconds % 3600) / 60);
            let seconds = totalSeconds % 60;
            let milliseconds = Math.floor((timeInMillis % 1000) / 10);

            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')},${String(milliseconds).padStart(2, '0')}`; 
        }

        // Função para registrar a volta
        function recordLap() {
            if (debounceTimeout) {
                clearTimeout(debounceTimeout);
            }

            debounceTimeout = setTimeout(function() {
                if (running) { 
                    const lapTimeInMillis = elapsedTime - totalTime;
                    totalTime = elapsedTime;

                    let lapDuration = lapTimeInMillis - lastLapTime;
                    lastLapTime = lapTimeInMillis;

                    const lapDurationFormatted = formatTime(lapDuration);
                    const totalTimeFormatted = formatTime(totalTime);

                    const lapList = document.getElementById('lapList');
                    const row = document.createElement('tr');

                    const lapCell = document.createElement('td');
                    lapCell.innerText = lapCounter++;
                    row.appendChild(lapCell);

                    const lapDurationCell = document.createElement('td');
                    lapDurationCell.innerText = lapDurationFormatted;
                    row.appendChild(lapDurationCell);

                    const totalCell = document.createElement('td');
                    totalCell.innerText = totalTimeFormatted;
                    row.appendChild(totalCell);

                    lapList.insertBefore(row, lapList.firstChild);

                    document.getElementById('lapTable').style.display = 'table';
                }
            }, 200);
        }

        // Eventos dos botões
        document.getElementById('startPauseBtn').onclick = startPauseTimer;
        document.getElementById('resetBtn').onclick = resetTimer;
        document.getElementById('lapBtn').onclick = recordLap;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
