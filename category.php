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
    <style>
        /* Modal customizado (posição fixa) */
        #category-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
            width: 300px;
            border-radius: 5px;
            z-index: 9999;
        }

        /* Inputs lado a lado no modal */
        .category-inputs {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        #category-name {
            flex-grow: 1;
            padding: 5px;
        }
        #category-color {
            width: 50px;
            height: 35px;
            border: none;
        }

        /* Botão para expandir/recolher lista de categorias */
        #toggle-categories {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            margin: 20px 0;
        }

        /* Lista de Categorias */
        #categories-list {
            display: none;
            padding: 10px;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 5px;
            border-radius: 3px;
            box-shadow: 0px 0px 3px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            margin-bottom: 5px;
        }

        .category-actions {
            display: flex;
            gap: 10px;
        }

        /* Botões quadrados (edit/delete) com ícone */
        .edit-category,
        .delete-category {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid;
            cursor: pointer;
        }
        .edit-category {
            border-color: #FFD700;
            color: #FFD700;
        }
        .edit-category:hover {
            background-color: #FFD700;
            color: #000;
        }
        .delete-category {
            border-color: #FF0000;
            color: #FF0000;
        }
        .delete-category:hover {
            background-color: #FF0000;
            color: #fff;
        }
    </style>
</head>
<body>
<!-- Botão "Criar Categoria" usando classes do Bootstrap -->
<button id="create-category" class="btn btn-success mb-3">Criar Categoria</button>

<!-- Modal customizado -->
<div id="category-modal">
    <input type="hidden" id="category-id">
    <div class="category-inputs">
        <input type="text" id="category-name" class="form-control" placeholder="Nome da Categoria" required>
        <input type="color" id="category-color" required>
    </div>
    <!-- Botões Salvar/Cancelar usando Bootstrap -->
    <button type="button" id="save-category" class="btn btn-success w-100"> Salvar</button>
    <button type="button" id="cancel-category" class="btn btn-danger w-100 mt-2">Cancelar</button>
</div>

<!-- Botão para mostrar/ocultar a lista de categorias -->
<div id="toggle-categories">
    <span>&#9660;</span>
    <strong>Lista de Categorias</strong>
</div>

<!-- Lista de Categorias -->
<div id="categories-list">
    <?php foreach ($categories as $category): ?>
        <div class="category-item">
            <span style="color: <?= htmlspecialchars($category['color'], ENT_QUOTES) ?>;">
                <?= htmlspecialchars($category['name'], ENT_QUOTES) ?>
            </span>
            <div class="category-actions">
                <!-- Botão Editar -->
                <button class="edit-category"
                    onclick="editCategory(
                        <?= $category['id'] ?>, 
                        '<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>', 
                        '<?= $category['color'] ?>'
                    )">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <!-- Botão Excluir -->
                <button class="delete-category" 
                    onclick="deleteCategory(<?= $category['id'] ?>)">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Botão para abrir modal de criação
    document.getElementById('create-category').addEventListener('click', function() {
        document.getElementById('category-modal').style.display = 'block';
        document.getElementById('category-id').value = '';
        document.getElementById('category-name').value = '';
        document.getElementById('category-color').value = '#ffffff';
    });

    // Botão para cancelar modal
    document.getElementById('cancel-category').addEventListener('click', function() {
        document.getElementById('category-modal').style.display = 'none';
    });

    // Toggle exibir/ocultar lista de categorias
    document.getElementById('toggle-categories').addEventListener('click', function() {
        const list = document.getElementById('categories-list');
        list.style.display = (list.style.display === '' || list.style.display === 'none') 
            ? 'block' 
            : 'none';
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
