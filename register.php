<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO login (name, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $email, $password])) {
        echo "<script>alert('Conta criada com sucesso!'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Erro ao criar conta!'); window.location.href='register.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="assets/css/global/login.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Criar Conta</h2>
            <form action="register.php" method="POST">
                <label for="name">Nome</label>
                <input type="text" name="name" placeholder="Nome" required>

                <label for="email">Email</label>
                <input type="email" name="email" placeholder="Email" required>

                <label for="password">Senha</label>
                <input type="password" name="password" placeholder="Senha" required>

                <button type="submit">Registrar</button>
            </form>
            <p class="create-account">Já tem uma conta? <a href="login.php">Entrar</a></p>
        </div>
    </div>
</body>
</html>
