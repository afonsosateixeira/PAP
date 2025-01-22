<?php
include 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o formulário de login foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['password'];

    // Consulta no banco de dados para verificar se o email e senha correspondem
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verifica se o usuário existe e se a senha está correta
    if ($user && password_verify($senha, $user['password'])) {
        session_start(); // Inicia uma sessão
        $_SESSION['user_id'] = $user['id']; // Armazena o ID do usuário na sessão
        $_SESSION['email'] = $user['email']; // Armazena o email do usuário na sessão
        header("Location: index.php"); // Redireciona para a página principal
        exit();
    } else {
        $erro = "Credenciais inválidas!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/global/layout-login.css">
    <title>Login - SYNTA</title>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Login</h2>
            <form method="POST">
                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email" required>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>

                <?php if (isset($erro)) { echo "<p class='error'>$erro</p>"; } ?>

                <a href="#" class="forgot-password">Esqueceu-se da Password?</a>
                <button type="submit">Entrar</button>
            </form>
            <div class="create-account">
                Não tem uma conta? <a href="register.php">Crie uma</a>
            </div>
        </div>
    </div>
</body>
</html>


