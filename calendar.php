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
    <link rel="stylesheet" href="assets/css/global/calendar.css"> <!-- O seu CSS que será utilizado em todo o site -->
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
