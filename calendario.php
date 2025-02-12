<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Calcular a primeira data do mês para gerar o calendário
$firstDayOfMonth = strtotime("$year-$month-01");
$daysInMonth = date('t', $firstDayOfMonth);
$startDay = date('N', $firstDayOfMonth);

// Obter as notas agendadas para o mês e ano selecionados
$stmt = $pdo->prepare("SELECT id, title, content, DATE(schedule_date) as date, schedule_date FROM notes WHERE user_id = ? AND MONTH(schedule_date) = ? AND YEAR(schedule_date) = ?");
$stmt->execute([$user_id, $month, $year]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$notesByDate = [];
foreach ($notes as $note) {
    // Associar cada nota à data correspondente
    $notesByDate[$note['date']][] = $note;
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário Simples</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            display: flex;
            min-height: 100vh;
            background-color: #e3e9f7;
        }
        #sidebar {
            min-width: 82px;
            background: #ffffff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 10;
            transition: all .3s;
        }
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
        #navbar {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        #nav-left, #nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #button-calendar button, #view-mode {
            padding: 8px 12px;
            border: none;
            background: #007BFF;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }
        #view-mode {
            background: white;
            color: black;
            border: 1px solid #ccc;
        }
        #calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            width: 100%;
        }
        .day {
            border: 1px solid #ddd;
            min-height: 120px;
            padding: 10px;
            text-align: center;
            font-size: 18px;
            position: relative;
        }
        .day-header {
            font-weight: bold;
            background: #007BFF;
            color: white;
            padding: 10px;
        }
        .note {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background-color: #f39c12;
            color: white;
            padding: 6px;
            font-size: 12px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.html'; ?>

    <div id="main-content">
        <div id="navbar">
            <div id="nav-left">
                <div id="button-calendar">
                    <button id="prev">◀</button>
                    <button id="today">Hoje</button>
                    <button id="next">▶</button>
                </div>
            </div>
            <div id="nav-right">
                <select id="view-mode">
                    <option value="month">Mês</option>
                    <option value="week">Semana</option>
                    <option value="day">Dia</option>
                </select>
            </div>
        </div>

        <div id="calendar">
            <div class="day-header">Dom</div>
            <div class="day-header">Seg</div>
            <div class="day-header">Ter</div>
            <div class="day-header">Qua</div>
            <div class="day-header">Qui</div>
            <div class="day-header">Sex</div>
            <div class="day-header">Sáb</div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const calendar = document.querySelector("#calendar");
            const todayBtn = document.getElementById("today");
            const prevBtn = document.getElementById("prev");
            const nextBtn = document.getElementById("next");
            let currentDate = new Date();

            // Passar as notas para o JavaScript
            const notesByDate = <?php echo json_encode($notesByDate); ?>;

            function renderCalendar(date) {
                calendar.innerHTML = 
                    `<div class='day-header'>Dom</div>
                    <div class='day-header'>Seg</div>
                    <div class='day-header'>Ter</div>
                    <div class='day-header'>Qua</div>
                    <div class='day-header'>Qui</div>
                    <div class='day-header'>Sex</div>
                    <div class='day-header'>Sáb</div>`;

                let firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
                let daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

                // Preencher os espaços vazios antes do primeiro dia
                for (let i = 0; i < firstDay; i++) {
                    calendar.innerHTML += `<div class='day'></div>`;
                }

                // Preencher os dias com notas
                for (let day = 1; day <= daysInMonth; day++) {
                    const formattedDate = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    let notesHTML = '';

                    if (notesByDate[formattedDate]) {
                        notesByDate[formattedDate].forEach(note => {
                            notesHTML += `<div class="note">${note.title}</div>`;
                        });
                    }

                    calendar.innerHTML += `<div class='day'>
                        <strong>${day}</strong>
                        ${notesHTML}
                    </div>`;
                }
            }

            todayBtn.addEventListener("click", function () {
                currentDate = new Date();
                renderCalendar(currentDate);
            });

            prevBtn.addEventListener("click", function () {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar(currentDate);
            });

            nextBtn.addEventListener("click", function () {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar(currentDate);
            });

            renderCalendar(currentDate);
        });
    </script>
</body>
</html>
