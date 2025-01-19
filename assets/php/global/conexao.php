<?php
$host = "localhost";
$user = "root"; // Utilizador padrão do XAMPP
$password = ""; // Sem palavra-passe por padrão
$dbname = "pap_db";

// Criação da conexão
$conn = new mysqli($host, $user, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>