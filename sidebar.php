<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Buscar os dados do usuário
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT profile_picture, description, name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profilePicture = $user['profile_picture'] ?? 'assets/images/default.png';
$description = htmlspecialchars($user['description'] ?? 'Bem-vindo!');
$userName = htmlspecialchars($user['name'] ?? 'Usuário');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/global.css">
    <title>Sidebar</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        #sidebar {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: #b5dfff;
            height: 100vh;
            position: fixed; /* Fixar a sidebar */
            top: 0; /* Garantir que a sidebar esteja no topo da página */
            left: 0;
            border-radius: 0px 18px 18px 0px;
            transition: all .5s;
            min-width: 82px;
            z-index: 2;
        }

        #open_btn {
            position: absolute;
            top: 30px;
            right: -10px;
            background-color: #71b9f0;
            color: #e3e9f7;
            border-radius: 100%;
            width: 20px;
            height: 20px;
            border: none;
            cursor: pointer;
        }

        #sidebar_content {
            padding: 12px;
        }

        #user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        #user_avatar {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 20px;
        }

        #user_infos {
            display: flex;
            flex-direction: column;
        }

        #user_infos span:last-child {
            color: #6b6b6b;
            font-size: 12px;
        }

        #side_items {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0;
            margin: 0;
        }

        .side-item {
            border-radius: 8px;
            padding: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .side-item.active {
            background-color: #71b9f0;
            padding: 14px;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .side-item:hover:not(.active),
        #logout_btn:hover {
            background-color: #e3e9f7;
        }

        .side-item a {
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a0a0a;
            font-weight: 500;
        }

        .side-item.active a {
            color: #e3e9f7;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .side-item a i {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
        }

        #logout {
            border-top: 1px solid #e3e9f7;
            padding: 12px;
        }

        #logout a {
            text-decoration: none;
        }

        #logout_btn {
            border: none;
            padding: 12px;
            font-size: 14px;
            display: flex;
            gap: 20px;
            align-items: center;
            border-radius: 8px;
            text-align: start;
            cursor: pointer;
            background-color: transparent;
        }

        #open_btn {
            position: absolute;
            top: 30px;
            right: -10px;
            background-color: #71b9f0;
            color: #e3e9f7;
            border-radius: 100%;
            width: 20px;
            height: 20px;
            border: none;
            cursor: pointer;
        }

        #open_btn_icon {
            transition: transform .3s ease;
        }

        .open-sidebar #open_btn_icon {
            transform: rotate(180deg);
        }

        .item-description {
            width: 0px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-size: 14px;
            transition: width .6s;
            height: 0px;
        }

        #sidebar.open-sidebar {
            min-width: 15%;
        }

        #sidebar.open-sidebar .item-description {
            width: 150px;
            height: auto;
        }

        #sidebar.open-sidebar .side-item a {
            justify-content: flex-start;
            gap: 14px;
        }

        ul, li {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .side-section {
            font-size: 12px;
            font-weight: bold;
            color: #6b6b6b;
            padding: 10px 14px;
            text-transform: uppercase;
        }

        .small-item {
            font-size: 14px;
            padding: 10px;
        }

        .separator {
            border-top: 1px solid #e3e9f7;
            margin: 8px 0;
        }

    </style>
</head>
<body>
<nav id="sidebar">
    <div id="sidebar_content">
        <div id="user">
            <img src="<?= htmlspecialchars($profilePicture) ?>" id="user_avatar" alt="Foto de Perfil">
            <p id="user_infos">
                <span class="item-description"><?= htmlspecialchars($userName) ?></span>
                <span class="item-description"><?= htmlspecialchars($description) ?></span>
            </p>
        </div>

        <ul id="side_items">
            <li class="side-item">
                <a href="index.php">
                    <i class="fa fa-home"></i>
                    <span class="item-description">Dashboard</span>
                </a>
            </li>

            <li class="side-item small-item">
                <a href="modulos.php">
                    <i class="fas fa-school"></i>
                    <span class="item-description">Escola</span>
                </a>
            </li>

            <li class="separator"></li>

            <li class="side-section">Notas</li>
            <li class="side-item small-item">
                <a href="tarefas.php">
                    <i class="fa fa-tasks"></i>
                    <span class="item-description">Tarefas</span>
                </a>
            </li>
            <li class="side-item small-item">
                <a href="notes.php">
                    <i class="fa fa-file-alt"></i>
                    <span class="item-description">Agendamento</span>
                </a>
            </li>
            <li class="side-item small-item">
                <a href="calendario.php">
                    <i class="fa fa-calendar"></i>
                    <span class="item-description">Calendário</span>
                </a>
            </li>

            <li class="separator"></li>

            <li class="side-section">Relógio</li>
            <li class="side-item small-item">
                <a href="alarm.php">
                    <i class="fa fa-bell"></i>
                    <span class="item-description">Alarme</span>
                </a>
            </li>
            <li class="side-item small-item">
                <a href="temporizador.php">
                    <i class="fa fa-hourglass-half"></i>
                    <span class="item-description">Temporizador</span>
                </a>
            </li>

            <li class="separator"></li>

            <li class="side-item">
                <a href="configuration.php">
                    <i class="fa-solid fa-gear"></i>
                    <span class="item-description">Configurações</span>
                </a>
            </li>
        </ul>

        <button id="open_btn">
            <i id="open_btn_icon" class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <div id="logout">
        <a href="logout.php">
            <button id="logout_btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span class="item-description">
                    Logout
                </span>
            </button>
        </a>
    </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Verificar o estado no localStorage e aplicar ao carregar a página
    const sidebar = document.getElementById('sidebar');
    const openBtnIcon = document.getElementById('open_btn_icon');
    const sidebarState = localStorage.getItem('sidebarState');
    const body = document.body;  // Referência ao body

    // Se o estado estiver no localStorage, aplicamos
    if (sidebarState === 'minimized') {
        sidebar.classList.remove('open-sidebar');  // Sidebar minimizada
        openBtnIcon.classList.remove('fa-chevron-left');
        openBtnIcon.classList.add('fa-chevron-right');
        body.style.paddingLeft = "82px";  // Ajuste quando a sidebar estiver minimizada
    } else {
        sidebar.classList.add('open-sidebar');  // Sidebar maximizada
        openBtnIcon.classList.remove('fa-chevron-right');
        openBtnIcon.classList.add('fa-chevron-left');
        body.style.paddingLeft = "250px";  // Aumente o valor aqui para mais espaçamento
    }

    // Adicionar funcionalidade de minimizar/maximizar ao botão
    document.getElementById('open_btn').addEventListener('click', function () {
        sidebar.classList.toggle('open-sidebar');
        const isOpen = sidebar.classList.contains('open-sidebar');

        // Salvar o estado no localStorage
        if (isOpen) {
            localStorage.setItem('sidebarState', 'maximized'); // Sidebar maximizada
            body.style.paddingLeft = "250px";  // Aumente o valor aqui para mais espaçamento
        } else {
            localStorage.setItem('sidebarState', 'minimized'); // Sidebar minimizada
            body.style.paddingLeft = "82px";  // Ajuste quando a sidebar estiver minimizada
        }

        // Alternar o ícone do botão para mostrar a ação de expandir ou retrair
        openBtnIcon.classList.toggle('fa-chevron-right');
        openBtnIcon.classList.toggle('fa-chevron-left');
    });

    // Obtém a URL atual
    const currentPage = window.location.pathname.split("/").pop();

    // Define os itens do menu
    const menuItems = document.querySelectorAll('.side-item a');

    // Loop sobre cada item de menu e verifica se o link corresponde à página atual
    menuItems.forEach(item => {
        const link = item.getAttribute('href').split("/").pop();
        if (link === currentPage) {
            item.closest('.side-item').classList.add('active');
        }
    });
});
</script>

</body>
</html>
