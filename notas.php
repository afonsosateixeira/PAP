<?php
include 'conexao.php';

// Verificar o método de requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'];

    if ($acao === 'criar') {
        $titulo = $_POST['titulo'];
        $conteudo = $_POST['conteudo'];

        $stmt = $conn->prepare("INSERT INTO notas (titulo, conteudo) VALUES (?, ?)");
        $stmt->bind_param("ss", $titulo, $conteudo);
        $stmt->execute();
    } elseif ($acao === 'editar') {
        $id = $_POST['id'];
        $titulo = $_POST['titulo'];
        $conteudo = $_POST['conteudo'];

        $stmt = $conn->prepare("UPDATE notas SET titulo = ?, conteudo = ? WHERE id = ?");
        $stmt->bind_param("ssi", $titulo, $conteudo, $id);
        $stmt->execute();
    } elseif ($acao === 'deletar') {
        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM notas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    exit;
}

// Listar todas as notas
$notas = [];
$result = $conn->query("SELECT * FROM notas");
while ($row = $result->fetch_assoc()) {
    $notas[] = $row;
}
echo json_encode($notas);
?>
