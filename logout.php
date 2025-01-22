<?php
session_start();
session_destroy(); // Destrói a sessão
header("Location: home.html"); // Redireciona para o login
exit();
