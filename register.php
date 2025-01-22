<?php
include 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o formulário de registro foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Criptografa a senha

    // Prepara e executa a consulta de inserção do novo usuário
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    $stmt->execute();

    header("Location: login.php"); // Redireciona para a página de login após o registro
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/global/layout-login.css">
    <title>Registrar - SYNTA</title>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Signup</h2>
            <form method="POST">
                <label for="username">Nome de utilizador:</label>
                <input type="text" name="username" id="username" required>

                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email" required>

                <label for="password">Senha:</label>
                <input type="password" name="password" id="password" required>

                <button type="submit">Registrar</button>
            </form>
        </div>
    </div>
</body>
</html>


