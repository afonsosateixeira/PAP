<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Recuperar o fuso horário do banco de dados ao fazer login
$user_id = $_SESSION['user_id'];
$query = "SELECT timezone FROM users WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user) {
    // Salvar o fuso horário na sessão
    $_SESSION['timezone'] = $user['timezone'];
} else {
    // Se o fuso horário não estiver definido, usar um padrão
    $_SESSION['timezone'] = 'Europe/Lisbon';
}

// Verificar se há uma atualização do fuso horário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timezone'])) {
    $_SESSION['timezone'] = $_POST['timezone'];  // Atualiza o fuso horário na sessão
    $timezone = $_SESSION['timezone'];

    // Atualizar o fuso horário no banco de dados
    $query = "UPDATE users SET timezone = ? WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$timezone, $user_id]);
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relógio Universal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .time-zone-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            background-color: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .location-title {
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>

<form method="POST" action="">
    <label for="timezone-select">Selecione o Fuso Horário</label>
    <select id="timezone-select" class="form-select" name="timezone" onchange="this.form.submit()">
        <option value="Europe/Lisbon" <?php echo ($_SESSION['timezone'] == 'Europe/Lisbon') ? 'selected' : ''; ?>>Portugal</option>
        <option value="Europe/London" <?php echo ($_SESSION['timezone'] == 'Europe/London') ? 'selected' : ''; ?>>Londres</option>
        <option value="America/New_York" <?php echo ($_SESSION['timezone'] == 'America/New_York') ? 'selected' : ''; ?>>Nova York</option>
        <option value="Asia/Tokyo" <?php echo ($_SESSION['timezone'] == 'Asia/Tokyo') ? 'selected' : ''; ?>>Tóquio</option>
        <option value="Australia/Sydney" <?php echo ($_SESSION['timezone'] == 'Australia/Sydney') ? 'selected' : ''; ?>>Sydney</option>
        <option value="America/Sao_Paulo" <?php echo ($_SESSION['timezone'] == 'America/Sao_Paulo') ? 'selected' : ''; ?>>São Paulo</option>
        <option value="Africa/Johannesburg" <?php echo ($_SESSION['timezone'] == 'Africa/Johannesburg') ? 'selected' : ''; ?>>Joanesburgo</option>
    </select>
</form>

<div class="time-zone-item">
    <div class="location-title" id="location-name"><?php echo $_SESSION['timezone']; ?></div>
    <div id="selected-time"></div>
</div>

<script>
    // Função para atualizar a hora com base no fuso horário selecionado
    function updateTime() {
        const selectedTimeZone = '<?php echo $_SESSION['timezone']; ?>';
        const now = new Date();

        // Atualizar o nome da localização
        const locationName = document.getElementById('timezone-select').options[document.getElementById('timezone-select').selectedIndex].text;
        document.getElementById('location-name').innerText = locationName;

        // Hora no fuso horário selecionado
        const selectedTime = new Date().toLocaleString('pt-PT', { timeZone: selectedTimeZone });
        document.getElementById('selected-time').innerText = selectedTime;
    }

    // Carregar o fuso horário selecionado
    window.onload = function() {
        updateTime();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
