<?php
session_start();
require 'config.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Processar criação/edição de temporizador
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_timer'])) {
    $timer_id = isset($_POST['timer_id']) ? intval($_POST['timer_id']) : 0;
    $name = trim($_POST['timer_name']);
    $duration_minutes = intval($_POST['timer_duration']);
    $duration_seconds = $duration_minutes * 60;

    if ($timer_id > 0) {
        $stmt = $pdo->prepare("UPDATE timers SET name = ?, duration = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$name, $duration_seconds, $timer_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO timers (user_id, name, duration) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $name, $duration_seconds]);
    }
}

// Excluir temporizador (opcional)
if (isset($_GET['delete_timer'])) {
    $timer_id = intval($_GET['delete_timer']);
    $stmt = $pdo->prepare("DELETE FROM timers WHERE id = ? AND user_id = ?");
    $stmt->execute([$timer_id, $user_id]);
    header("Location: temporizador.php");
    exit();
}

// Carregar temporizadores do usuário
$timersQuery = $pdo->prepare("SELECT * FROM timers WHERE user_id = ?");
$timersQuery->execute([$user_id]);
$timers = $timersQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Temporizador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .timer-card {
            background-color: #1e1e1e;
            color: #fff;
            width: 250px;
            border-radius: 8px;
            padding: 10px;
            margin: 10px;
        }
        .card-header { display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-weight: bold; font-size: 1rem; }
        .edit-btn, .toggle-btn { background: none; border: none; color: #fff; cursor: pointer; }
        .timer-display {
            margin: 0 auto;
            width: 130px;
            height: 130px;
            border: 6px solid #555;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .timer-controls { display: flex; justify-content: center; gap: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container mt-5">
        <h2>Temporizador</h2>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#timerModal"
                onclick="
                    document.getElementById('timer_id').value='';
                    document.getElementById('timer_name').value='';
                    document.getElementById('timer_duration').value='';
                ">
            <i class="fa fa-plus"></i> Criar Temporizador
        </button>

        <div class="d-flex flex-wrap">
            <?php foreach ($timers as $timer): 
                $timerId = $timer['id'];
                $timerName = htmlspecialchars($timer['name']);
                $durationSeconds = intval($timer['duration']);
            ?>
            <div class="timer-card" data-timer-id="<?php echo $timerId; ?>" data-duration="<?php echo $durationSeconds; ?>">
                <div class="card-header">
                    <span class="card-title"><?php echo $timerName; ?></span>
                    <div>
                        <button class="edit-btn me-2" data-bs-toggle="modal" data-bs-target="#timerModal"
                                onclick="
                                    document.getElementById('timer_id').value='<?php echo $timerId; ?>';
                                    document.getElementById('timer_name').value='<?php echo $timerName; ?>';
                                    document.getElementById('timer_duration').value='<?php echo floor($durationSeconds/60); ?>';
                                ">
                            <i class="fa fa-pen"></i>
                        </button>
                        <a href="?delete_timer=<?php echo $timerId; ?>" class="toggle-btn">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="timer-display">00:00:00</div>
                <div class="timer-controls">
                    <button onclick="playTimer('<?php echo $timerId; ?>')"><i class="fa fa-play"></i></button>
                    <button onclick="stopTimer('<?php echo $timerId; ?>')"><i class="fa fa-stop"></i></button>
                    <button onclick="resetTimer('<?php echo $timerId; ?>')"><i class="fa fa-rotate-left"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para criar/editar temporizador -->
    <div class="modal fade" id="timerModal" tabindex="-1" aria-labelledby="timerModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="timerModalLabel">Criar/Editar Temporizador</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="timer_id" id="timer_id" value="">
              <div class="mb-3">
                <label for="timer_name" class="form-label">Nome do Temporizador</label>
                <input type="text" class="form-control" id="timer_name" name="timer_name" required>
              </div>
              <div class="mb-3">
                <label for="timer_duration" class="form-label">Duração (em minutos)</label>
                <input type="number" class="form-control" id="timer_duration" name="timer_duration" min="1" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary" name="save_timer">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Scripts para os temporizadores -->
    <script>
        // Armazena dados de cada temporizador
        let timerData = {};
        function initTimers() {
            const timerCards = document.querySelectorAll('.timer-card');
            timerCards.forEach(card => {
                const timerId = card.getAttribute('data-timer-id');
                const duration = parseInt(card.getAttribute('data-duration'), 10);
                timerData[timerId] = { remainingSeconds: duration, initialSeconds: duration, intervalRef: null };
                updateTimerDisplay(timerId);
            });
        }
        function updateTimerDisplay(timerId) {
            const card = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
            if (!card) return;
            const display = card.querySelector('.timer-display');
            const remaining = timerData[timerId].remainingSeconds;
            const hh = String(Math.floor(remaining / 3600)).padStart(2,'0');
            const mm = String(Math.floor((remaining % 3600) / 60)).padStart(2,'0');
            const ss = String(remaining % 60).padStart(2,'0');
            display.textContent = `${hh}:${mm}:${ss}`;
        }
        function playTimer(timerId) {
            if (timerData[timerId].intervalRef) return;
            timerData[timerId].intervalRef = setInterval(() => {
                if (timerData[timerId].remainingSeconds > 0) {
                    timerData[timerId].remainingSeconds--;
                    updateTimerDisplay(timerId);
                } else {
                    stopTimer(timerId);
                    alert('Timer finalizado!');
                }
            }, 1000);
        }
        function stopTimer(timerId) {
            if (timerData[timerId].intervalRef) {
                clearInterval(timerData[timerId].intervalRef);
                timerData[timerId].intervalRef = null;
            }
        }
        function resetTimer(timerId) {
            stopTimer(timerId);
            timerData[timerId].remainingSeconds = timerData[timerId].initialSeconds;
            updateTimerDisplay(timerId);
        }
        window.addEventListener('DOMContentLoaded', initTimers);
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
