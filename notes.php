<?php
session_start();
require 'config.php'; // Conexão com o banco de dados

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Processamento do formulário de criação de nota via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    // Verifica se estamos editando uma nota existente
    if (isset($_POST['note_id']) && $_POST['note_id'] != '') {
        // Editar a nota
        $note_id = $_POST['note_id'];
        $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $note_id, $user_id]);
        echo json_encode(['success' => true, 'id' => $note_id, 'title' => $title, 'content' => $content]);
    } else {
        // Criar nova nota
        $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $content]);

        // Obter o ID da nota inserida
        $noteId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $noteId, 'title' => $title, 'content' => $content]);
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php echo file_get_contents('sidebar.html'); ?>

<main>
    <h1>Gerenciar Minhas Notas</h1>

    <button id="create-note">Criar Nota</button>

    <!-- Formulário para criar ou editar notas (oculto inicialmente) -->
    <div id="note-form" style="display: none;">
        <form id="note-form-action">
            <input type="hidden" id="note-id" name="note_id">
            <input type="text" id="note-title" name="title" placeholder="Título" required>
            <textarea id="note-content" name="content" placeholder="Conteúdo" required></textarea>
            <button type="button" id="save-note">Salvar</button>
            <button type="button" id="cancel">Cancelar</button>
        </form>
    </div>

    <!-- Contêiner das notas -->
    <div class="notes-container" id="notes-container">
        <?php foreach ($notes as $note): ?>
            <div class="note" id="note-<?= $note['id'] ?>">
                <h3><?= htmlspecialchars($note['title']) ?></h3>
                <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                <button class="edit-note" onclick="editNote(<?= $note['id'] ?>, '<?= htmlspecialchars($note['title']) ?>', '<?= htmlspecialchars($note['content']) ?>')">Editar</button>
                <button class="delete-note" onclick="deleteNote(<?= $note['id'] ?>)">Excluir</button>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
    // Exibir o formulário de criação de nota
    document.getElementById('create-note').addEventListener('click', function() {
        document.getElementById('note-form').style.display = 'block';
        document.getElementById('note-id').value = ''; // Limpar ID de nota ao criar
        document.getElementById('note-title').value = '';
        document.getElementById('note-content').value = '';
        document.getElementById('save-note').textContent = 'Salvar';
    });

    // Cancelar a criação ou edição da nota
    document.getElementById('cancel').addEventListener('click', function() {
        document.getElementById('note-form').style.display = 'none';
    });

    // Salvar ou atualizar a nota
    document.getElementById('save-note').addEventListener('click', function() {
        const title = document.getElementById('note-title').value;
        const content = document.getElementById('note-content').value;
        const noteId = document.getElementById('note-id').value;

        if (title && content) {
            const data = new FormData();
            data.append('title', title);
            data.append('content', content);
            if (noteId) {
                data.append('note_id', noteId); // Adicionar o ID da nota para edição
            }

            fetch('notes.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(note => {
                if (note.success) {
                    // Atualizar ou adicionar a nota na tela
                    const notesContainer = document.getElementById('notes-container');
                    if (noteId) {
                        // Atualizar nota existente
                        const existingNote = document.getElementById('note-' + note.id);
                        existingNote.querySelector('h3').textContent = note.title;
                        existingNote.querySelector('p').textContent = note.content;
                    } else {
                        // Criar nova nota
                        const newNote = document.createElement('div');
                        newNote.classList.add('note');
                        newNote.id = 'note-' + note.id;
                        newNote.innerHTML = `
                            <h3>${note.title}</h3>
                            <p>${note.content}</p>
                            <button class="edit-note" onclick="editNote(${note.id}, '${note.title}', '${note.content}')">Editar</button>
                            <button class="delete-note" onclick="deleteNote(${note.id})">Excluir</button>
                        `;
                        notesContainer.appendChild(newNote);
                    }

                    // Esconder o formulário
                    document.getElementById('note-form').style.display = 'none';
                } else {
                    alert('Erro ao salvar ou editar a nota');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar ou editar a nota');
            });
        } else {
            alert('Por favor, preencha todos os campos!');
        }
    });

    // Função para editar a nota
    function editNote(noteId, title, content) {
        document.getElementById('note-form').style.display = 'block';
        document.getElementById('note-id').value = noteId; // Preencher o ID da nota
        document.getElementById('note-title').value = title;
        document.getElementById('note-content').value = content;
        document.getElementById('save-note').textContent = 'Atualizar'; // Alterar texto do botão para "Atualizar"
    }

    // Função para excluir a nota
    function deleteNote(noteId) {
        if (confirm('Tem certeza que deseja excluir esta nota?')) {
            fetch('notes.php?action=delete&id=' + noteId, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const noteElement = document.getElementById('note-' + noteId);
                    noteElement.remove();
                } else {
                    alert('Erro ao excluir a nota');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir a nota');
            });
        }
    }
</script>

</body>
</html>
