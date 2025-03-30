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
    <title>Relógio Mundial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
                /* Ajustando o espaçamento e o layout */
#main-content {
    flex-grow: 1;
    margin-left: 82px;
    padding: 20px;
    width: calc(100% - 82px);
}
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
    <h1 class="mb-4"> Relógio Mundial</h1>
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
    </div>

    <script>
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
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
