<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php'; // Conexão com o banco de dados

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // ID do usuário autenticado

// Garante a existência de "Sem Categoria"
$stmt = $pdo->prepare("
    INSERT INTO categories (user_id, name, color) 
    SELECT ?, 'Sem Categoria', '#D3D3D3' 
    WHERE NOT EXISTS (
        SELECT 1 FROM categories 
        WHERE user_id = ? AND name = 'Sem Categoria'
    )
");
$stmt->execute([$user_id, $user_id]);

// Rota para carregar as categorias (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC");
        $stmt->execute([$user_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['categories' => $categories]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao carregar as categorias: ' . $e->getMessage()]);
    }
    exit();
}

// Adicionando uma categoria (AJAX)
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

// Editando uma categoria (AJAX)
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

// Deletando uma categoria (AJAX)
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

// Carregar todas as categorias do usuário autenticado, excluindo "Sem Categoria"
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? AND name != 'Sem Categoria' ORDER BY name ASC");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Categorias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .category-item {
    background-color: #ffffff;
    color: #000000;
    width: 250px;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    padding: 10px;
    margin: 10px;
}

.category-item .d-flex {
    justify-content: space-between;
    align-items: center;
}

.category-item .edit-category, 
.category-item .delete-category {
    background: none;
    border: none;
    color: #000000;
    cursor: pointer;
    font-size: 1rem;
}

    </style>
</head>
<body>

<button id="create-category" class="btn btn-success mb-3"><i class="fa fa-plus"></i> Criar categoria </button>

<!-- Modal do Bootstrap -->
<div class="modal fade" id="category-modal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Criar/Editar Categoria</h5>
            </div>
            <div class="modal-body">
                <input type="hidden" id="category-id">
                <div class="mb-3">
                    <label for="category-name" class="form-label">Nome da Categoria</label>
                    <input type="text" id="category-name" class="form-control" placeholder="Nome da Categoria" required>
                </div>
                <div class="mb-3">
                    <label for="category-color" class="form-label">Cor</label>
                    <input type="color" id="category-color" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="save-category" class="btn btn-success">Salvar</button>
                
            </div>
        </div>
    </div>
</div>

<!-- Botão para mostrar/ocultar a lista de categorias -->
<div id="toggle-categories" class="cursor-pointer">
    <span id="toggle-icon">&#9660;</span>
    <strong>Lista de Categorias</strong>
</div>

<!-- Lista de Categorias -->
<div id="categories-list" style="display: none;">
    <?php foreach ($categories as $category): ?>
        <div class="category-item" style="background-color: #ffffff; color: #000000; width: 250px; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); padding: 10px; margin: 10px;">
            <div class="d-flex justify-content-between align-items-center">
                <span style="color: <?= htmlspecialchars($category['color'], ENT_QUOTES) ?>;">
                    <?= htmlspecialchars($category['name'], ENT_QUOTES) ?>
                </span>
                <div>
                    <!-- Botão Editar -->
                    <button class="edit-category btn btn-link" onclick="editCategory(
                        <?= $category['id'] ?>, 
                        '<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>', 
                        '<?= $category['color'] ?>'
                    )">
                        <i class="fa fa-pen"></i>
                    </button>
                    <!-- Botão Excluir -->
                    <button class="delete-category btn btn-link" onclick="deleteCategory(<?= $category['id'] ?>)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Botão para abrir modal de criação
    document.getElementById('create-category').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('category-modal'));
        modal.show();
        document.getElementById('category-id').value = '';
        document.getElementById('category-name').value = '';
        document.getElementById('category-color').value = '#ffffff';
    });

    // Salvar ou editar categoria
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
        const modal = new bootstrap.Modal(document.getElementById('category-modal'));
        modal.show();
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
    // Função para alternar a visibilidade da lista de categorias
document.getElementById('toggle-categories').addEventListener('click', function() {
    const categoriesList = document.getElementById('categories-list');
    const toggleIcon = document.getElementById('toggle-icon');
    
    if (categoriesList.style.display === 'none' || categoriesList.style.display === '') {
        categoriesList.style.display = 'block';
        toggleIcon.innerHTML = '&#9650;';  // Ícone de seta para cima
    } else {
        categoriesList.style.display = 'none';
        toggleIcon.innerHTML = '&#9660;';  // Ícone de seta para baixo
    }
});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
