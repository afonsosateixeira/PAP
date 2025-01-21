<?php

include 'conexao.php'; // Inclui o ficheiro de conexão com a base de dados

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Verifica o método da requisição (se é um POST)
    $acao = $_POST['acao'];

    // Verifica se a ação é "criar"
    if ($acao === 'criar') {
        $titulo = $_POST['titulo'];
        $conteudo = $_POST['conteudo'];
        $stmt = $conn->prepare("INSERT INTO notas (titulo, conteudo) VALUES (?, ?)");
        $stmt->bind_param("ss", $titulo, $conteudo);
        $stmt->execute();
    } 
    // Verifica se a ação é "editar"
    elseif ($acao === 'editar') {
        $id = $_POST['id'];
        $titulo = $_POST['titulo'];
        $conteudo = $_POST['conteudo'];

        $stmt = $conn->prepare("UPDATE notas SET titulo = ?, conteudo = ? WHERE id = ?");
        // Liga os parâmetros do comando SQL aos valores recebidos
        $stmt->bind_param("ssi", $titulo, $conteudo, $id);
        // Executa o comando SQL
        $stmt->execute();
    } 
    // Verifica se a ação é "deletar"
    elseif ($acao === 'deletar') {
        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM notas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    exit;
}

// Se não for um POST, lista todas as notas
$notas = [];
$result = $conn->query("SELECT * FROM notas"); // Executa a consulta SQL para buscar todas as notas
while ($row = $result->fetch_assoc()) { 
    // Adiciona cada linha da base de dados ao array de notas
    $notas[] = $row;
}
// Retorna as notas em formato JSON para o cliente
echo json_encode($notas);
?>
