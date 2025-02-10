<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/global/style.css">
</head>
<body>
    <!-- Incluir a sidebar -->
    <?php include 'sidebar.html'; ?>

    <!-- Conteúdo da página -->
    <main>
        <h1>Bem-vindo à Dashboard</h1>
        <p>Escolha uma opção na barra lateral para começar.</p>
    </main>

    <script src="assets/js/scripts.js"></script>
</body>
</html>
