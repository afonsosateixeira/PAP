<?php
session_start();

// Destrói a sessão e redireciona para a página de login
session_destroy();
header("Location: home.html");
exit();
?>


