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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Adicionando margens e espaços para acomodar o layout do Bootstrap */
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
        .buttons-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        /* Formulário de Criação e Edição */
        .note-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px; 
            border-radius: 8px;
            width: 400px;
            max-width: 100%;
            font-family: Arial, sans-serif;
            text-align: left; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Estilizando os campos do formulário */
        .note-form input, textarea, select {
            width: 100%;
            margin: 10px 0;
            display: block;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: border 0.3s ease;
            text-align: left;
        }

        .note-form input:focus, textarea:focus, select:focus {
            border-color: #2ecc71;
            outline: none;
        }

        textarea#note-content {
            height: 120px;
        }

        /* Container de Notas */
        .notes-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        /* Card das Notas */
        .note-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }

        .note-card:hover {
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .note-card h3 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .note-card p {
            font-size: 14px;
            color: #666;
        }

        .note-form label {
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
    <main>
    <div class="container" id="main-content">
        <h1 class="mb-4">Agendamento</h1>
        <div class="buttons-container">
            <!-- Botão Criar Nota com Bootstrap -->
            <button id="create-note" class="btn btn-success mb-4">Criar Nota</button>
        <?php include 'searchbar.php'; ?>
        </div>

        <!-- Formulário de Criação e Edição de Notas -->
        <div id="note-form" class="note-form" style="display: none;">
            <h3 id="form-title">Criar Nota</h3>
            <form id="note-form-action">
                <input type="hidden" id="note-id" name="note_id">

                <div class="mb-3">
                    <input type="text" id="note-title" name="title" placeholder="Título" class="form-control" required>
                </div>

                <div class="mb-3">
                    <textarea id="note-content" name="content" placeholder="Conteúdo" class="form-control" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="note-category" class="form-label">Categoria</label>
                    <select id="note-category" name="category_id" class="form-select">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY CASE WHEN name = 'Sem Categoria' THEN 0 ELSE 1 END, name ASC");
                        $stmt->execute([$_SESSION['user_id']]);
                        $categories = $stmt->fetchAll();
                        foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="note-date" class="form-label">Data e Hora</label>
                    <input type="datetime-local" id="note-date" name="schedule_date" class="form-control" required>
                </div>

                <button type="button" id="save-note" class="btn btn-success w-100"> Salvar</button>
                <button type="button" id="cancel-note" class="btn btn-danger w-100 mt-3">Cancelar</button>
            </form>
        </div>
        <!-- Container de Notas -->
        <div class="notes-container" id="notes-container">
            <?php foreach ($notes as $note): ?>
                <div class="note-card" id="note-<?= $note['id'] ?>" data-category="<?= $note['category_id'] ?>">
                    <h3><?= htmlspecialchars($note['title']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                    <p><b>Data:</b> <?= htmlspecialchars($note['schedule_date']) ?></p>
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
                        <!-- Botões Editar e Excluir com Bootstrap -->
                        <button class="btn btn-warning btn-sm" onclick="editNote(<?= $note['id'] ?>, '<?= htmlspecialchars($note['title']) ?>', '<?= htmlspecialchars($note['content']) ?>', '<?= htmlspecialchars($note['schedule_date']) ?>', <?= $note['category_id'] ?>)">Editar</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteNote(<?= $note['id'] ?>)">Excluir</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
    document.getElementById('create-note').addEventListener('click', function() {
        document.getElementById('note-form').style.display = 'block';
        document.getElementById('note-id').value = '';
        document.getElementById('note-title').value = '';
        document.getElementById('note-content').value = '';
        document.getElementById('note-date').value = '';

        const categorySelect = document.getElementById('note-category');
        if (categorySelect) {
            for (let option of categorySelect.options) {
                if (option.textContent.trim() === 'Sem Categoria') {
                    categorySelect.value = option.value;
                    break;
                }
            }
        }

        document.getElementById('save-note').textContent = 'Salvar';
        document.getElementById('form-title').textContent = 'Criar Nota'; // Define o título como 'Criar Nota'
    });

    document.getElementById('cancel-note').addEventListener('click', function() {
        document.getElementById('note-form').style.display = 'none';
    });

    document.getElementById('save-note').addEventListener('click', function() {
        const title = document.getElementById('note-title').value;
        const content = document.getElementById('note-content').value;
        const scheduleDate = document.getElementById('note-date').value;
        const noteId = document.getElementById('note-id').value;
        const categoryId = document.getElementById('note-category').value;

        if (title && content && scheduleDate) {
            const data = new FormData();
            data.append('title', title);
            data.append('content', content);
            data.append('schedule_date', scheduleDate);
            data.append('category_id', categoryId);
            if (noteId) {
                data.append('note_id', noteId);
            }

            fetch('notes.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(note => {
                if (note.success) {
                    location.reload();
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
        document.getElementById('note-form').style.display = 'block';
        document.getElementById('note-id').value = noteId;
        document.getElementById('note-title').value = title;
        document.getElementById('note-content').value = content;
        document.getElementById('note-date').value = scheduleDate;
        document.getElementById('note-category').value = categoryId;
        document.getElementById('form-title').textContent = 'Editar Nota'; // Define o título como 'Editar Nota'
    }

    function deleteNote(noteId) {
        if (confirm('Tem certeza que deseja excluir esta nota?')) {
            fetch('notes.php?action=delete&id=' + noteId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao excluir a nota');
                }
            })
            .catch(error => console.error('Erro:', error));
        }
    }
</script>


    <!-- Inclusão do Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0p3p5hK/ihpJ2duLRf72jjrCXY5z2v+b+gYbbhAiYZwpx+Yg" crossorigin="anonymous"></script>
</body>
</html>
