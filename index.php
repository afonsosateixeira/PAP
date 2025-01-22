<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redireciona para o login se não estiver autenticado
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina Inicial - SYNTA</title>
    <link rel="stylesheet" href="assets/css/pages/index/partials/index.css">
    <script src="assets/js/pages/index/index.js"></script>
</head>
<body>
    <!-- Cabeçalho -->
    <header>
        <h1>Bem-vindo</h1>
    </header>

    <!-- Botão para adicionar nota e container de notas -->
    <button class="add-button" onclick="criarNota()">+ Nova Nota</button>
    <div class="notes-container" id="notesContainer"></div>

    <!-- Modal para criar/editar notas -->
    <div id="modal-overlay" onclick="fecharModal()"></div>
    <div id="modal">
        <h3 id="modalTitle">Nova Nota</h3>
        <input type="text" id="titulo" placeholder="Título" style="width: 100%;"><br><br>
        <textarea id="conteudo" placeholder="Conteúdo" style="width: 100%; height: 100px;"></textarea><br><br>
        <button onclick="salvarNota()">Salvar</button>
        <button onclick="fecharModal()">Cancelar</button>
    </div>
    <a href="logout.php">Sair</a>
</body>
</html>
