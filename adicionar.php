<?php
// Inclui a conexão com a base de dados
include 'conexao.php';

// Verifica se os dados foram enviados pelo formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['titulo'];
    $conteudo = $_POST['conteudo'];

    // Prepara a query para inserir os dados
    $sql = "INSERT INTO notas (titulo, conteudo) VALUES (?, ?)";

    // Usa prepared statements para maior segurança
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $titulo, $conteudo);

    if ($stmt->execute()) {
        echo "Nota adicionada com sucesso!";
    } else {
        echo "Erro ao adicionar nota: " . $conn->error;
    }

    // Fecha a conexão
    $stmt->close();
    $conn->close();
}
?>