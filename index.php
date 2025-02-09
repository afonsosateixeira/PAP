<?php
session_start();
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Incluir a sidebar -->
    <?php echo file_get_contents('sidebar.html'); ?>
    
    <!-- Conteúdo da página -->
    <main>
        <h1>Bem-vindo à Dashboard</h1>
        <p>Escolha uma opção na barra lateral para começar.</p>
    </main>

    <script src="assets/js/scripts.js"></script>
</body>
</html>
