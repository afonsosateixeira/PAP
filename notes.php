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

// Processamento do formulário de criação ou edição de nota via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $schedule_date = $_POST['schedule_date']; // Data de agendamento
    $category_id = $_POST['category_id']; // ID da categoria
    $user_id = $_SESSION['user_id'];

    // Verifica se é uma edição de nota existente
    if (!empty($_POST['note_id'])) {
        $note_id = $_POST['note_id'];
        $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, schedule_date = ?, category_id = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $schedule_date, $category_id, $note_id, $user_id]);
        echo json_encode(['success' => true, 'id' => $note_id, 'title' => $title, 'content' => $content, 'schedule_date' => $schedule_date, 'category_id' => $category_id]);
    } else {
        // Inserção de nova nota
        $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, schedule_date, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $content, $schedule_date, $category_id]);

        $noteId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $noteId, 'title' => $title, 'content' => $content, 'schedule_date' => $schedule_date, 'category_id' => $category_id]);
    }
    exit();
}

// Exclusão de nota via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $note_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);

    echo json_encode(['success' => true]);
    exit();
}

// Carregar as notas do banco de dados
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();


?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Notas</title>
    <link rel="stylesheet" href="assets/css/global/notes.css">
</head>
<body>
    <?php include 'sidebar.html'; ?>

    <main>
        <h1>Agendamento de Notas</h1>
        <?php include 'category.php'; ?>
        <?php include 'searchbar.php'; ?>
        <!-- Botão Criar Nota -->
        <button id="create-note">+</button>

        <!-- Formulário de Criação e Edição -->
        <div id="note-form" class="note-form">
            <form id="note-form-action">
                <input type="hidden" id="note-id" name="note_id">
                <input type="text" id="note-title" name="title" placeholder="Título" required>
                <textarea id="note-content" name="content" placeholder="Conteúdo" required></textarea>
                <select id="note-category" name="category_id">
                    <option value="">Selecione uma Categoria</option>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC");
                    $stmt->execute([$_SESSION['user_id']]);
                    $categories = $stmt->fetchAll();
                    foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="datetime-local" id="note-date" name="schedule_date" required>
                <button type="button" id="save-note">Salvar</button>
                <button type="button" id="cancel">Cancelar</button>
            </form>
        </div>

        <!-- Container de Notas -->
        <div class="notes-container" id="notes-container">
            <?php foreach ($notes as $note): ?>
                <div class="note-card" id="note-<?= $note['id'] ?>">
                    <h3><?= htmlspecialchars($note['title']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                    <p><b>Data:</b> <?= htmlspecialchars($note['schedule_date']) ?></p>
                    <!-- Exibir Categoria -->
                    <p><b>Categoria:</b>
                        <?php
                        // Obter a categoria associada à nota
                        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ? AND user_id = ?");
                        $stmt->execute([$note['category_id'], $_SESSION['user_id']]);
                        $category = $stmt->fetch();
                        echo htmlspecialchars($category['name'] ?? 'Sem Categoria');
                        ?>
                    </p>

                    <div class="note-card-buttons">
                        <button class="btn-edit" onclick="editNote(<?= $note['id'] ?>, '<?= htmlspecialchars($note['title']) ?>', '<?= htmlspecialchars($note['content']) ?>', '<?= htmlspecialchars($note['schedule_date']) ?>', <?= $note['category_id'] ?>)">Editar</button>
                        <button class="btn-delete" onclick="deleteNote(<?= $note['id'] ?>)">Excluir</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <script>
    document.getElementById('create-note').addEventListener('click', function() {
        document.getElementById('note-form').style.display = 'block'; // Exibe o formulário
        document.getElementById('note-id').value = ''; // Limpa o id da nota
        document.getElementById('note-title').value = ''; // Limpa o título
        document.getElementById('note-content').value = ''; // Limpa o conteúdo
        document.getElementById('note-date').value = ''; // Limpa a data
        document.getElementById('save-note').textContent = 'Salvar'; // Muda o texto do botão
    });

    document.getElementById('cancel').addEventListener('click', function() {
        document.getElementById('note-form').style.display = 'none'; // Fecha o formulário
    });

    document.getElementById('save-note').addEventListener('click', function() {
        const title = document.getElementById('note-title').value;
        const content = document.getElementById('note-content').value;
        const scheduleDate = document.getElementById('note-date').value;
        const noteId = document.getElementById('note-id').value;
        const categoryId = document.getElementById('note-category').value; // Obtém o ID da categoria selecionada

        if (title && content && scheduleDate) {
            const data = new FormData();
            data.append('title', title);
            data.append('content', content);
            data.append('schedule_date', scheduleDate);
            data.append('category_id', categoryId); // Adiciona o ID da categoria ao FormData
            if (noteId) {
                data.append('note_id', noteId); // Para editar uma nota existente
            }

            fetch('notes.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(note => {
                if (note.success) {
                    location.reload(); // Recarregar as notas
                } else {
                    alert('Erro ao salvar ou editar a nota');
                }
            })
            .catch(error => console.error('Erro:', error));
        } else {
            alert('Preencha todos os campos!');
        }
    });

    function editNote(noteId, title, content, scheduleDate, categoryId) {
        document.getElementById('note-form').style.display = 'block'; // Exibe o formulário de edição
        document.getElementById('note-id').value = noteId;
        document.getElementById('note-title').value = title;
        document.getElementById('note-content').value = content;
        document.getElementById('note-date').value = scheduleDate;
        document.getElementById('note-category').value = categoryId; // Preenche o campo de categoria
        document.getElementById('save-note').textContent = 'Atualizar'; // Muda o botão para Atualizar
    }

    function deleteNote(noteId) {
        if (confirm('Tem certeza que deseja excluir esta nota?')) {
            fetch('notes.php?action=delete&id=' + noteId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recarregar as notas
                } else {
                    alert('Erro ao excluir a nota');
                }
            })
            .catch(error => console.error('Erro:', error));
        }
    }
    </script>

</body>
</html>
