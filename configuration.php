<?php
session_start();
require 'config.php';  // Certifique-se de que 'config.php' está no mesmo diretório ou forneça o caminho correto

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Buscar dados atuais do usuário
$stmt = $pdo->prepare("SELECT profile_picture, description FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profilePicture = $user['profile_picture'] ?? 'assets/images/default.png';
$description = $user['description'] ?? 'Bem-vindo!';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $imgDir = "uploads/";

    // Criar a pasta caso não exista
    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0777, true);
    }

    // Atualizar foto de perfil
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileExt = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid("profile_", true) . "." . $fileExt;
        $imgPath = $imgDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $imgPath)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$imgPath, $userId]);
            $profilePicture = $imgPath;
        }
    }

    // Atualizar descrição
    if (!empty($_POST['description'])) {
        $description = htmlspecialchars($_POST['description']);
        $stmt = $pdo->prepare("UPDATE users SET description = ? WHERE id = ?");
        $stmt->execute([$description, $userId]);
    }

    header("Location: configuration.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações</title>
    <style>
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
    </style>
</head>
<body>
    <!-- Incluir a sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo da página -->
    <main>
        <div id="main-content">
        <h2>Configurações do Perfil</h2>
        <form action="configuration.php" method="POST" enctype="multipart/form-data">
            <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Foto de Perfil" width="100"><br><br>

            <label for="profile_picture">Alterar Foto de Perfil:</label>
            <input type="file" name="profile_picture" accept="image/*"><br><br>
            
            <label for="description">Alterar Descrição:</label>
            <input type="text" name="description" value="<?= htmlspecialchars($description) ?>"><br><br>
            
            <button type="submit">Salvar Alterações</button>
        </form>
        </div>
    </main>
</body>
</html>
