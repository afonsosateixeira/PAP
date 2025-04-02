<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

function limitarTentativas($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $time = time();

    // Limpar tentativas antigas
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function ($attempt) use ($time) {
        return ($time - $attempt['time']) < 900; // Apenas tentativas dos últimos 15 minutos
    });

    // Contar tentativas falhas
    $tentativas = array_filter($_SESSION['login_attempts'], function ($attempt) use ($ip, $email) {
        return $attempt['ip'] === $ip && $attempt['email'] === $email;
    });

    return count($tentativas) < 5; // Permitir no máximo 5 tentativas em 15 minutos
}

function registrarTentativaFalha($email) {
    $_SESSION['login_attempts'][] = [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'email' => $email,
        'time' => time()
    ];
}
?>
