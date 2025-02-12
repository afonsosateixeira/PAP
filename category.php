<?php
if (session_status() == PHP_SESSION_NONE) 
    session_start();
require 'config.php'; // Conexão com o banco de dados

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // ID do usuário autenticado

// Adicionando uma categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = $_POST['name'];
    $color = $_POST['color'];

    if ($name && $color) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $name, $color]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos!']);
    }
    exit();
}

// Editando uma categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $color = $_POST['color'];

    if ($name && $color) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $color, $id, $user_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao editar: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos!']);
    }
    exit();
}

// Deletando uma categoria
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $category_id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$category_id, $user_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
    }
    exit();
}

// Carregar todas as categorias do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Botão principal -->
    <button id="create-category">Criar Categoria</button>

    <!-- Modal para criação/edição de categoria -->
    <div id="category-modal" style="display:none;">
        <div id="category-modal-header">
            <h2>Gerenciar Categoria</h2>
            <button id="close-modal">X</button>
        </div>
        <input type="hidden" id="category-id">
        <div class="category-inputs">
            <input type="text" id="category-name" placeholder="Nome da Categoria" required>
            <input type="color" id="category-color" value="#ffffff" required>
        </div>
        <button type="button" id="save-category">Salvar</button>
        <button type="button" id="cancel-category">Cancelar</button>

        <h2>Suas categorias</h2>

        <!-- Lista de Categorias -->
        <div id="categories-list">
            <?php foreach ($categories as $category): ?>
                <div class="category-item" id="category-<?= $category['id'] ?>">
                    <span style="color: <?= $category['color'] ?>"><?= $category['name'] ?></span>
                    <button class="btn-edit-category" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', '<?= $category['color'] ?>')">Editar</button>
                    <button class="btn-delete-category" onclick="deleteCategory(<?= $category['id'] ?>)">Excluir</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Exibir modal ao clicar no botão Criar Categoria
        document.getElementById('create-category').addEventListener('click', function() {
            document.getElementById('category-modal').style.display = 'block';
        });

        // Fechar modal
        document.getElementById('close-modal').addEventListener('click', function() {
            document.getElementById('category-modal').style.display = 'none';
        });

        // Salva ou edita a categoria
        document.getElementById('save-category').addEventListener('click', function() {
            const name = document.getElementById('category-name').value;
            const color = document.getElementById('category-color').value;
            const categoryId = document.getElementById('category-id').value;

            if (name && color) {
                const data = new FormData();
                if (categoryId) {
                    data.append('action', 'edit_category');
                    data.append('id', categoryId);
                } else {
                    data.append('action', 'add_category');
                }
                data.append('name', name);
                data.append('color', color);

                fetch('category.php', {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.message || 'Erro ao salvar a categoria');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao tentar salvar a categoria.');
                });
            } else {
                alert('Preencha todos os campos!');
            }
        });

        // Função para editar categoria
        function editCategory(id, name, color) {
            document.getElementById('category-modal').style.display = 'block';
            document.getElementById('category-id').value = id;
            document.getElementById('category-name').value = name;
            document.getElementById('category-color').value = color;
        }

        // Função para excluir categoria
        function deleteCategory(id) {
            if (confirm('Tem certeza que deseja excluir esta categoria?')) {
                fetch('category.php?action=delete&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao excluir a categoria');
                    }
                })
                .catch(error => console.error('Erro:', error));
            }
        }
    </script>
</body>
</html>
