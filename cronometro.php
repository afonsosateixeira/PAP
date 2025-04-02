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
            font-size: 40px; /* Tamanho do cronômetro reduzido */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
          
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

    </style>
</head>
<body>

        <h2 id="timer">00:00:00,00</h2>

        <div class="button-container">
            <button class="btn btn-custom" id="startPauseBtn"><i class="fa fa-play"></i></button>
            <button class="btn btn-custom" id="resetBtn"><i class="fa fa-rotate-left"></i></button>
        </div>

    <script>
        let startTime;
        let tInterval;
        let running = false;
        let elapsedTime = 0;

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
            document.getElementById('timer').innerHTML = '00:00:00,00';
            document.getElementById('startPauseBtn').innerHTML = '<i class="fa fa-play"></i>';
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

        // Eventos dos botões
        document.getElementById('startPauseBtn').onclick = startPauseTimer;
        document.getElementById('resetBtn').onclick = resetTimer;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
