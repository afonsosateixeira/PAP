<?php
session_start();
require 'config.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Adicionar Alarme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_alarm'])) {
    $title = $_POST['title'];
    $time = $_POST['time'];
    $ringtone = $_POST['ringtone'];
    $recurrence = $_POST['recurrence'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO alarms (user_id, title, time, ringtone, recurrence) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $time, $ringtone, $recurrence]);
}

// Adicionar Temporizador
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_timer'])) {
    $duration = $_POST['duration'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO timers (user_id, duration) VALUES (?, ?)");
    $stmt->execute([$user_id, $duration]);
}

// Obter alarmes do usuário
$alarms = $pdo->prepare("SELECT * FROM alarms WHERE user_id = ?");
$alarms->execute([$_SESSION['user_id']]);
$alarms = $alarms->fetchAll();

// Obter temporizadores do usuário
$timers = $pdo->prepare("SELECT * FROM timers WHERE user_id = ?");
$timers->execute([$_SESSION['user_id']]);
$timers = $timers->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relógio e Alarmes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .section {
            display: none;
        }
        .section.active {
            display: block;
        }
        /* Mini Navbar Estilo */
        .mini-navbar {
            background-color: white;
            padding: 10px;
            display: flex;
            justify-content: flex-start; /* Alinhado à esquerda */
            gap: 20px; /* Espaço entre os itens */
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .mini-navbar a {
            text-decoration: none;
            color: black;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
        .mini-navbar a:hover {
            color: #007BFF;
        }
        
        /* Conteúdo Principal */
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
            padding-top: 60px; /* Adiciona espaço para a navbar */
        }
    </style>
    <script>
        function showSection(sectionId) {
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));

            const sectionToShow = document.getElementById(sectionId);
            sectionToShow.classList.add('active');
        }

        function updateClock() {
            const now = new Date();
            document.getElementById('local-time').innerText = now.toLocaleTimeString();
        }

        function addTimeZone() {
            const timeZone = document.getElementById('timezone-select').value;
            const now = new Date();
            const options = { timeZone: timeZone, hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const formattedTime = new Intl.DateTimeFormat('en-GB', options).format(now);
            const list = document.getElementById('timezones');
            const newItem = document.createElement('li');
            newItem.className = "list-group-item";
            newItem.innerText = `${timeZone}: ${formattedTime}`;
            list.appendChild(newItem);
        }

        function startStopwatch() {
            let startTime = Date.now();
            let interval = setInterval(() => {
                let elapsed = Date.now() - startTime;
                let seconds = Math.floor((elapsed / 1000) % 60);
                let minutes = Math.floor((elapsed / (1000 * 60)) % 60);
                let hours = Math.floor((elapsed / (1000 * 60 * 60)) % 24);
                document.getElementById('stopwatch').innerText = `${hours}:${minutes}:${seconds}`;
            }, 1000);
            document.getElementById('stop-btn').onclick = () => clearInterval(interval);
        }

        setInterval(updateClock, 1000);
    </script>
</head>
<body>

    <!-- Sidebar é incluída via PHP -->
    <?php include 'sidebar.html'; ?>

    <div id="main-content">
        <!-- Mini Navbar -->
        <div class="mini-navbar">
            <a onclick="showSection('alarm-section')">Alarmes</a>
            <a onclick="showSection('timer-section')">Temporizador</a>
            <a onclick="showSection('world-clock-section')">Relógio Mundial</a>
            <a onclick="showSection('stopwatch-section')">Cronômetro</a>
        </div>
        
        <!-- Seção de Alarmes -->
        <section id="alarm-section" class="section active">
            <h2>Gerenciador de Alarmes</h2>
            <form method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Título</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="time" class="form-label">Hora</label>
                    <input type="time" class="form-control" id="time" name="time" required>
                </div>
                <div class="mb-3">
                    <label for="ringtone" class="form-label">Toque</label>
                    <input type="text" class="form-control" id="ringtone" name="ringtone" required>
                </div>
                <div class="mb-3">
                    <label for="recurrence" class="form-label">Recorrência</label>
                    <input type="text" class="form-control" id="recurrence" name="recurrence" required>
                </div>
                <button type="submit" class="btn btn-primary" name="add_alarm">Adicionar Alarme</button>
            </form>
            <h3 class="mt-3">Meus Alarmes</h3>
            <ul class="list-group">
                <?php foreach ($alarms as $alarm): ?>
                    <li class="list-group-item"><?php echo $alarm['title']; ?> - <?php echo $alarm['time']; ?> - <?php echo $alarm['ringtone']; ?> - <?php echo $alarm['recurrence']; ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Seção de Temporizador -->
        <section id="timer-section" class="section">
            <h2>Temporizador</h2>
            <form method="POST">
                <input type="number" class="form-control mb-2" name="duration" placeholder="Duração em segundos" required>
                <button type="submit" class="btn btn-primary" name="add_timer">Iniciar Temporizador</button>
            </form>
        </section>

        <!-- Seção do Relógio Mundial -->
        <section id="world-clock-section" class="section">
            <h2>Relógio Mundial</h2>
            <div class="mb-3">
                <select id="timezone-select" class="form-select">
                    <option value="Europe/London">Londres</option>
                    <option value="America/New_York">Nova York</option>
                    <option value="Asia/Tokyo">Tóquio</option>
                    <option value="Australia/Sydney">Sydney</option>
                </select>
                <button class="btn btn-primary mt-2" onclick="addTimeZone()">Adicionar Fuso Horário</button>
            </div>
            <ul id="timezones" class="list-group"></ul>
        </section>

        <!-- Seção do Cronômetro -->
        <section id="stopwatch-section" class="section">
            <h2>Cronômetro</h2>
            <p id="stopwatch" class="fs-3">0:0:0</p>
            <button class="btn btn-success" onclick="startStopwatch()">Iniciar</button>
            <button class="btn btn-danger" id="stop-btn">Parar</button>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
