<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtenção do modo de visualização (semanal, mensal ou anual)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'monthly'; // 'weekly', 'monthly', 'yearly'

// Determinando o mês e o ano a partir da URL ou utilizando a data atual
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Calcular a primeira data do mês para gerar o calendário
$firstDayOfMonth = strtotime("$year-$month-01");
$daysInMonth = date('t', $firstDayOfMonth);
$startDay = date('N', $firstDayOfMonth);

// Obter as notas agendadas
$stmt = $pdo->prepare("SELECT id, title, content, DATE(schedule_date) as date, schedule_date FROM notes WHERE user_id = ? AND MONTH(schedule_date) = ? AND YEAR(schedule_date) = ?");
$stmt->execute([$user_id, $month, $year]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$notesByDate = [];
foreach ($notes as $note) {
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
    
</head>
<body>
<?php echo file_get_contents('sidebar.html'); ?>

<main>
    <h1>Calendário de <?= date('F Y', $firstDayOfMonth) ?></h1>

    <div id="view-buttons">
        <a href="?view=monthly&month=<?= $prevMonth ?>&year=<?= $prevYear ?>">&#9664; Mês Anterior</a>
        <a href="?view=monthly&month=<?= $nextMonth ?>&year=<?= $nextYear ?>">Próximo Mês &#9654;</a>
        <br><br>
        <a href="?view=weekly&month=<?= $month ?>&year=<?= $year ?>">Modo Semanal</a> |
        <a href="?view=monthly&month=<?= $month ?>&year=<?= $year ?>">Modo Mensal</a> |
        <a href="?view=yearly&month=<?= $month ?>&year=<?= $year ?>">Modo Anual</a>
    </div>

    <?php if ($view_mode == 'monthly'): ?>
        <div class="calendar">
            <div class="header">Dom</div>
            <div class="header">Seg</div>
            <div class="header">Ter</div>
            <div class="header">Qua</div>
            <div class="header">Qui</div>
            <div class="header">Sex</div>
            <div class="header">Sáb</div>

            <?php
            // Preencher os dias do mês
            for ($i = 1; $i < $startDay; $i++) {
                echo '<div class="day"></div>';
            }

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                echo "<div class='day'>";
                echo "<strong>$day</strong>";
                if (isset($notesByDate[$date])) {
                    foreach ($notesByDate[$date] as $note) {
                        echo "<div class='note' onclick='showNoteDetails(\"" . htmlspecialchars($note['title']) . "\", \"" . htmlspecialchars($note['content']) . "\", \"" . htmlspecialchars($note['schedule_date']) . "\")'>" . htmlspecialchars($note['title']) . "</div>";
                    }
                }
                echo "</div>";
            }
            ?>
        </div>
    <?php elseif ($view_mode == 'weekly'): ?>
        <!-- Lógica para o modo semanal -->
        <p>Modo Semanal em desenvolvimento...</p>
    <?php elseif ($view_mode == 'yearly'): ?>
        <!-- Lógica para o modo anual -->
        <p>Modo Anual em desenvolvimento...</p>
    <?php endif; ?>

</main>

<!-- Modal de visualização da nota -->
<div id="noteModal">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2 id="note-title"></h2>
    <p id="note-content"></p>
    <p id="note-schedule-date"></p>
</div>

<script>
// Função para exibir o modal com os detalhes da nota
function showNoteDetails(title, content, scheduleDate) {
    document.getElementById('note-title').innerText = title;
    document.getElementById('note-content').innerText = content;
    document.getElementById('note-schedule-date').innerText = "Agendado para: " + scheduleDate;
    document.getElementById('noteModal').style.display = 'block';
}

// Função para fechar o modal
function closeModal() {
    document.getElementById('noteModal').style.display = 'none';
}
</script>

</body>
</html>
