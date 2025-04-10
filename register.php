<?php
session_start();
require 'config.php';

$alert = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $alert = "email_exists";
    } else {
        // Criar hash da senha
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Inserir usuário no banco
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $hash])) {
            $alert = "success";
        } else {
            $alert = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="assets/css/global/.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #e4e4e4;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            width: 100%;
            text-align: center;
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            text-align: left;
            font-weight: 500;
            margin-top: 10px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
            transition: border 0.3s;
        }

        input:focus {
            border-color: #71b9f0;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            border: none;
            background-color: #71b9f0;
            color: white;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #4094d4;
        }

        .create-account {
            margin-top: 15px;
            font-size: 14px;
            color: #555;
        }

        .create-account a {
            color: #71b9f0;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .create-account a:hover {
            color: #4094d4;
        }

    </style>
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
                
                <button type="submit">Continuar</button>
            </form>
            <p class="create-account">Já tem uma conta? <a href="login.php">Entrar</a></p>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        <?php if ($alert === "email_exists"): ?>
            Swal.fire({
                icon: 'error',
                title: 'Email já cadastrado!',
                text: 'Por favor, utilize outro email.',
            }).then(() => {
                window.location.href = 'register.php';
            });
        <?php elseif ($alert === "success"): ?>
            Swal.fire({
                icon: 'success',
                title: 'Conta criada com sucesso!',
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                window.location.href = 'login.php';
            });
        <?php elseif ($alert === "error"): ?>
            Swal.fire({
                icon: 'error',
                title: 'Erro ao criar conta!',
                text: 'Tente novamente mais tarde.',
            }).then(() => {
                window.location.href = 'register.php';
            });
        <?php endif; ?>
    </script>
</body>
</html>
