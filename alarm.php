<?php
session_start();
require 'config.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Processar criação/edição de alarme
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_alarm'])) {
    $alarm_id   = isset($_POST['alarm_id']) ? intval($_POST['alarm_id']) : 0;
    $title      = trim($_POST['alarm_title']);
    $time       = $_POST['alarm_time']; // "HH:MM"
    $ringtone   = trim($_POST['alarm_ringtone']);
    $active     = isset($_POST['alarm_active']) ? 1 : 0;
    $daysOfWeek = isset($_POST['days_of_week']) ? implode(',', $_POST['days_of_week']) : null;

    if ($alarm_id > 0) {
        // Edição do alarme
        $stmt = $pdo->prepare("UPDATE alarms SET title = ?, time = ?, ringtone = ?, active = ?, days_of_week = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $time, $ringtone, $active, $daysOfWeek, $alarm_id, $user_id]);
    } else {
        // Criação do alarme
        $stmt = $pdo->prepare("INSERT INTO alarms (user_id, title, time, ringtone, active, days_of_week) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $time, $ringtone, $active, $daysOfWeek]);
    }
}

// Ativar/Desativar alarme via GET
if (isset($_GET['toggle_alarm'])) {
    $alarm_id = intval($_GET['toggle_alarm']);
    $stmt = $pdo->prepare("SELECT active FROM alarms WHERE id = ? AND user_id = ?");
    $stmt->execute([$alarm_id, $user_id]);
    $alarm = $stmt->fetch();

    if ($alarm) {
        $newActive = $alarm['active'] ? 0 : 1;
        $stmt2 = $pdo->prepare("UPDATE alarms SET active = ? WHERE id = ? AND user_id = ?");
        $stmt2->execute([$newActive, $alarm_id, $user_id]);
    }
    header("Location: alarm.php");
    exit();
}

// Excluir alarme (opcional)
if (isset($_GET['delete_alarm'])) {
    $alarm_id = intval($_GET['delete_alarm']);
    $stmt = $pdo->prepare("DELETE FROM alarms WHERE id = ? AND user_id = ?");
    $stmt->execute([$alarm_id, $user_id]);
    header("Location: alarm.php");
    exit();
}

// Carregar alarmes do usuário
$alarmsQuery = $pdo->prepare("SELECT * FROM alarms WHERE user_id = ?");
$alarmsQuery->execute([$user_id]);
$alarms = $alarmsQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Alarmes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
/* Ajustando o espaçamento e o layout */
#main-content {
    flex-grow: 1;
    margin-left: 82px;
    padding: 20px;
    width: calc(100% - 82px);
}

/* Estilos para os alarmes */
.alarm-card {
    background-color: #1e1e1e;
    color: #fff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #333;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
}

.alarm-card:hover {
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.card-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
}

.card-title { 
    font-weight: bold; 
    font-size: 1rem; 
}

.edit-btn, .toggle-btn { 
    background: none; 
    border: none; 
    color: #fff; 
    cursor: pointer; 
}

.card-time { 
    font-size: 2rem; 
    text-align: center; 
    margin: 10px 0; 
}

.card-subtitle { 
    text-align: center; 
    font-size: 0.9rem; 
    color: #ccc; 
}

.alarm-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 300px;
    background-color: #2d2d2d;
    color: #fff;
    padding: 20px;
    border-radius: 8px;
    display: none;
    z-index: 9999;
}


    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4"> Gestor de alarmes</h1>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#alarmModal"
                onclick="
                    document.getElementById('alarm_id').value='';
                    document.getElementById('alarm_title').value='';
                    document.getElementById('alarm_time').value='07:00';
                    document.getElementById('alarm_ringtone').value='Alarme 1';
                    document.querySelectorAll('#days_of_week_checkboxes input[type=checkbox]').forEach(ch => ch.checked=false);
                    document.getElementById('alarm_active').checked = true;
                ">
            <i class="fa fa-plus"></i> Criar Alarme
        </button>

        <div class="d-flex flex-wrap">
            <?php foreach ($alarms as $alarm): 
                $alarmId    = $alarm['id'];
                $alarmTitle = htmlspecialchars($alarm['title']);
                $alarmTime  = $alarm['time'];
                $ringtone   = htmlspecialchars($alarm['ringtone']);
                $active     = $alarm['active'];
                $daysOfWeek = $alarm['days_of_week'];

                // Cálculo aproximado do tempo restante
                date_default_timezone_set('UTC');
                $nowSec = time();
                list($hh, $mm) = explode(':', $alarmTime);
                $alarmSec = $hh * 3600 + $mm * 60; 
                $diffSec  = $alarmSec - ((int)date('H') * 3600 + (int)date('i') * 60);
                if ($diffSec < 0) { $diffSec += 86400; }
                $hoursLeft   = floor($diffSec / 3600);
                $minutesLeft = floor(($diffSec % 3600) / 60);
                $timeLeftStr = sprintf("%02dh %02dm", $hoursLeft, $minutesLeft);
            ?>
            <div class="alarm-card" data-alarm-id="<?php echo $alarmId; ?>" data-alarm-time="<?php echo substr($alarmTime, 0, 5); ?>" data-alarm-active="<?php echo $active; ?>" data-alarm-days="<?php echo $daysOfWeek; ?>">
                <div class="card-header">
                    <span class="card-title"><?php echo $alarmTitle; ?></span>
                    <div>
                        <!-- Botão Editar -->
                        <button class="edit-btn me-2" data-bs-toggle="modal" data-bs-target="#alarmModal"
                                onclick="
                                    document.getElementById('alarm_id').value='<?php echo $alarmId; ?>';
                                    document.getElementById('alarm_title').value='<?php echo $alarmTitle; ?>';
                                    document.getElementById('alarm_time').value='<?php echo substr($alarmTime, 0, 5); ?>';
                                    document.getElementById('alarm_ringtone').value='<?php echo $ringtone; ?>';
                                    let daysStr = '<?php echo $daysOfWeek; ?>';
                                    document.querySelectorAll('#days_of_week_checkboxes input[type=checkbox]').forEach(ch => ch.checked = false);
                                    if(daysStr) {
                                        let arr = daysStr.split(',');
                                        document.querySelectorAll('#days_of_week_checkboxes input[type=checkbox]').forEach(ch => {
                                            if(arr.includes(ch.value)) { ch.checked = true; }
                                        });
                                    }
                                    document.getElementById('alarm_active').checked = <?php echo $active ? 'true' : 'false'; ?>;
                                ">
                            <i class="fa fa-pen"></i>
                        </button>
                        <!-- Botão On/Off -->
                        <a href="?toggle_alarm=<?php echo $alarmId; ?>" class="toggle-btn">
                            <?php if ($active): ?>
                                <i class="fa fa-toggle-on"></i>
                            <?php else: ?>
                                <i class="fa fa-toggle-off"></i>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
                <div class="card-time"><?php echo substr($alarmTime, 0, 5); ?></div>
                <div class="card-subtitle">Faltam <?php echo $timeLeftStr; ?> para tocar</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para criar/editar alarme -->
    <div class="modal fade" id="alarmModal" tabindex="-1" aria-labelledby="alarmModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="alarmModalLabel">Adicionar/Editar Alarme</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="alarm_id" id="alarm_id" value="">
              <div class="mb-3">
                <label for="alarm_time" class="form-label">Horário</label>
                <input type="time" class="form-control" id="alarm_time" name="alarm_time" required>
              </div>
              <div class="mb-3">
                <label for="alarm_title" class="form-label">Título</label>
                <input type="text" class="form-control" id="alarm_title" name="alarm_title" required>
              </div>
              <div class="mb-3">
                <label for="alarm_ringtone" class="form-label">Toque</label>
                <select class="form-select" id="alarm_ringtone" name="alarm_ringtone">
                  <option value="Alarme 1">Alarme 1</option>
                  <option value="Alarme 2">Alarme 2</option>
                  <option value="Tubos">Tubos</option>
                  <option value="Digital">Digital</option>
                </select>
              </div>
              <div class="mb-3" id="days_of_week_checkboxes">
                <label class="form-label">Repetir alarme (dias):</label><br/>
                <input type="checkbox" id="dom" value="Dom" name="days_of_week[]"> <label for="dom">Dom</label>
                <input type="checkbox" id="seg" value="Seg" name="days_of_week[]"> <label for="seg">Seg</label>
                <input type="checkbox" id="ter" value="Ter" name="days_of_week[]"> <label for="ter">Ter</label>
                <input type="checkbox" id="qua" value="Qua" name="days_of_week[]"> <label for="qua">Qua</label>
                <input type="checkbox" id="qui" value="Qui" name="days_of_week[]"> <label for="qui">Qui</label>
                <input type="checkbox" id="sex" value="Sex" name="days_of_week[]"> <label for="sex">Sex</label>
                <input type="checkbox" id="sab" value="Sab" name="days_of_week[]"> <label for="sab">Sab</label>
              </div>
              <div class="mb-3 form-check">
                <input class="form-check-input" type="checkbox" id="alarm_active" name="alarm_active" checked>
                <label class="form-check-label" for="alarm_active">Alarme Ativo</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary" name="save_alarm">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Script para gerenciamento dos alarmes -->
    <script>
        // Funções para checar e notificar alarmes
        let alarmsData = {};
        function initAlarms() {
            const alarmCards = document.querySelectorAll('.alarm-card');
            alarmCards.forEach(card => {
                const alarmId   = card.getAttribute('data-alarm-id');
                const alarmTime = card.getAttribute('data-alarm-time');
                const active    = card.getAttribute('data-alarm-active') === "1";
                const days      = card.getAttribute('data-alarm-days');
                alarmsData[alarmId] = { alarmTime, active, days };
            });
            setInterval(checkAlarms, 30000);
        }
        function checkAlarms() {
            const now = new Date();
            const currentHM = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const dayMap = ["Dom","Seg","Ter","Qua","Qui","Sex","Sab"];
            const currentDayStr = dayMap[now.getDay()];
            for (let alarmId in alarmsData) {
                const a = alarmsData[alarmId];
                if (a.active) {
                    if (!a.days || a.days.includes(currentDayStr)) {
                        if (a.alarmTime === currentHM) {
                            showAlarmNotification(alarmId);
                        }
                    }
                }
            }
        }
        function showAlarmNotification(alarmId) {
            const notification = document.getElementById('alarm-notification');
            if (!notification) return;
            const card = document.querySelector(`.alarm-card[data-alarm-id="${alarmId}"]`);
            if (!card) return;
            const title = card.querySelector('.card-title').innerText;
            const time  = card.getAttribute('data-alarm-time');
            document.getElementById('notif-alarm-title').innerText = title;
            document.getElementById('notif-alarm-time').innerText  = time;
            notification.style.display = 'block';
        }
        function dismissAlarm() {
            document.getElementById('alarm-notification').style.display = 'none';
        }
        function snoozeAlarm() {
            const snoozeSelect = document.getElementById('snooze-select');
            const snoozeValue = parseInt(snoozeSelect.value, 10);
            if (snoozeValue > 0) {
                const notification = document.getElementById('alarm-notification');
                const title = document.getElementById('notif-alarm-title').innerText;
                const time  = document.getElementById('notif-alarm-time').innerText;
                const now = new Date();
                const [hh, mm] = time.split(':');
                let alarmDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(hh), parseInt(mm));
                alarmDate.setMinutes(alarmDate.getMinutes() + snoozeValue);
                const alarmCard = [...document.querySelectorAll('.alarm-card')].find(c => c.querySelector('.card-title').innerText === title);
                if (alarmCard) {
                    const alarmId = alarmCard.getAttribute('data-alarm-id');
                    let newHM = String(alarmDate.getHours()).padStart(2, '0') + ":" + String(alarmDate.getMinutes()).padStart(2, '0');
                    alarmsData[alarmId].alarmTime = newHM;
                    alarmCard.setAttribute('data-alarm-time', newHM);
                    const timeEl = alarmCard.querySelector('.card-time');
                    if (timeEl) { timeEl.innerText = newHM; }
                }
            }
            dismissAlarm();
        }
        window.addEventListener('DOMContentLoaded', initAlarms);
    </script>

    <!-- Notificação de Alarme -->
    <div class="alarm-notification" id="alarm-notification">
        <h5 id="notif-alarm-title">Alarme Título</h5>
        <p>Horário: <span id="notif-alarm-time">00:00</span></p>
        <div class="mb-2">
            <label for="snooze-select" class="form-label">Soneca:</label>
            <select id="snooze-select" class="form-select form-select-sm">
                <option value="0">Desativado</option>
                <option value="5">5 min</option>
                <option value="10">10 min</option>
                <option value="30">30 min</option>
                <option value="60">1 hora</option>
            </select>
        </div>
        <button class="btn btn-warning btn-sm" onclick="snoozeAlarm()">Suspender</button>
        <button class="btn btn-secondary btn-sm" onclick="dismissAlarm()">Dispensar</button>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
