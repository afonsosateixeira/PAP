<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Determinando o mês e o ano a partir da URL ou utilizando a data atual
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

// Navegação entre os meses
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth == 0) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth == 13) {
    $nextMonth = 1;
    $nextYear++;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário</title>
    <style>
        /* Estilos gerais */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f9fafb;
    color: #333;
    margin: 0;
    padding: 0;
}

/* Título do Calendário */
h1 {
    font-size: 24px;
    margin: 20px 0;
    text-align: center;
    color: #333;
}

/* Estilos para o calendário */
.calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin: 0 20px;
    max-width: 800px; /* Limita o tamanho do calendário */
    margin-left: auto;
    margin-right: auto;
}

.day-header {
    font-weight: bold;
    padding: 12px;
    background-color: #71b9f0;
    color: white;
    border-radius: 8px;
    text-transform: uppercase;
    text-align: center;
}

.day {
    display: flex;
    flex-direction: column;
    padding: 12px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    height: 100px;
    position: relative;
    transition: background-color 0.3s ease;
}

.day:hover {
    background-color: #e3e9f7;
}

.day strong {
    font-size: 18px;
    color: #333;
}

/* Notas */
.note {
    background-color: #f39c12;
    color: white;
    padding: 6px;
    margin-top: 6px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.note:hover {
    background-color: #e67e22;
}

/* Estilo para os botões de navegação */
#view-buttons {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

#view-buttons a {
    background-color: #2ecc71;
    color: white;
    padding: 8px 16px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 16px;
    margin: 0 10px;
    transition: background-color 0.3s ease;
}

#view-buttons a:hover {
    background-color: #27ae60;
}

/* Estilos do modal */
#noteModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

#noteModal .modal-content {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    width: 350px;
    max-width: 80%;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

#noteModal .close {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 20px;
    color: #333;
    cursor: pointer;
}

#note-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

#note-content {
    font-size: 16px;
    margin-top: 10px;
    line-height: 1.6;
}

#note-schedule-date {
    font-size: 14px;
    color: #71b9f0;
    margin-top: 10px;
}

/* Responsividade */
@media (max-width: 768px) {
    .calendar {
        width: 100%;
        margin: 0 10px;
    }

    #noteModal .modal-content {
        width: 90%;
    }

    #view-buttons a {
        font-size: 14px;
        padding: 6px 12px;
    }
}

    </style>
</head>
<body>
    <?php echo file_get_contents('sidebar.html'); ?> <!-- Inclusão da barra lateral -->

    <main>
        <h1>Calendário de <?= date('F Y', $firstDayOfMonth) ?></h1>

        <div id="view-buttons">
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">&#9664; Mês Anterior</a>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">Próximo Mês &#9654;</a>
        </div>

        <div class="calendar">
            <div class="day-header">Dom</div>
            <div class="day-header">Seg</div>
            <div class="day-header">Ter</div>
            <div class="day-header">Qua</div>
            <div class="day-header">Qui</div>
            <div class="day-header">Sex</div>
            <div class="day-header">Sáb</div>

            <?php
            // Preencher os dias do mês
            for ($i = 1; $i < $startDay; $i++) {
                echo '<div class="day"></div>';
            }

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                echo "<div class='day'>";
                echo "<strong>$day</strong>";
                // Verificar se há notas para esse dia específico
                if (isset($notesByDate[$date])) {
                    foreach ($notesByDate[$date] as $note) {
                        echo "<div class='note' onclick='showNoteDetails(\"" . htmlspecialchars($note['title']) . "\", \"" . htmlspecialchars($note['content']) . "\", \"" . htmlspecialchars($note['schedule_date']) . "\")'>" . htmlspecialchars($note['title']) . "</div>";
                    }
                }
                echo "</div>";
            }
            ?>
        </div>

    </main>

    <!-- Modal de Visualização da Nota -->
    <div id="noteModal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="note-title"></h2>
            <p id="note-content"></p>
            <p id="note-schedule-date"></p>
        </div>
    </div>

    <script>
        // Função para exibir o modal com os detalhes da nota
        function showNoteDetails(title, content, scheduleDate) {
            document.getElementById('note-title').innerText = title;
            document.getElementById('note-content').innerText = content;
            document.getElementById('note-schedule-date').innerText = "Agendado para: " + scheduleDate;
            document.getElementById('noteModal').style.display = 'flex'; // Exibe o modal centralizado
        }

        // Função para fechar o modal
        function closeModal() {
            document.getElementById('noteModal').style.display = 'none'; // Fecha o modal
        }

        // Fechar o modal quando clicar fora da área do modal
        window.onclick = function(event) {
            let modal = document.getElementById('noteModal');
            if (event.target == modal) {
                closeModal(); // Fecha o modal se o clique for fora da área do modal
            }
        }
    </script>

</body>
</html>
