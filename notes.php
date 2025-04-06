<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d H:i:s'); // Obtém a data e hora atual

// Deletar notas com data de agendamento passada e mais de 1 dia de atraso
$stmt = $pdo->prepare("DELETE FROM notes WHERE user_id = ? AND schedule_date < ? AND schedule_date < DATE_SUB(NOW(), INTERVAL 2 DAY)");
$stmt->execute([$user_id, $current_date]);

// Verificar se existem eventos para o próximo dia
$next_day_start = date('Y-m-d 00:00:00', strtotime('+1 day', strtotime($current_date))); // próximo dia (meia-noite)
$next_day_end = date('Y-m-d 23:59:59', strtotime('+1 day', strtotime($current_date))); // próximo dia (final do dia)

$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? AND schedule_date BETWEEN ? AND ?");
$stmt->execute([$user_id, $next_day_start, $next_day_end]);
$upcoming_notes = $stmt->fetchAll();

// Inserir ou atualizar nota
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $schedule_date = $_POST['schedule_date'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];

    if (!empty($_POST['note_id'])) {
        $note_id = $_POST['note_id'];
        $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, schedule_date = ?, category_id = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $schedule_date, $category_id, $note_id, $user_id]);
        echo json_encode(['success' => true]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, schedule_date, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $content, $schedule_date, $category_id]);
        echo json_encode(['success' => true]);
    }
    exit();
}

// Deletar uma nota (via GET)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $note_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);

    echo json_encode(['success' => true]);
    exit();
}

// Carregar as notas
$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();

// Carregar as categorias
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY CASE WHEN name = 'Sem Categoria' THEN 0 ELSE 1 END, name ASC");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
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

        .note-content {
            font-size: 14px;
            color: #666;
            overflow-y: auto;
            max-height: 100px;
            margin: 10px 0;
            padding-right: 5px;
        }
        .card-text {
            max-height: 80px; 
            overflow-y: auto;  
            white-space: pre-wrap; 
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main>
    <div class="container" id="main-content">
        <h1 class="mb-4">Agendamento</h1>
        <div class="buttons-container">
            <button id="create-note" class="btn btn-success mb-4"><i class="fa fa-plus"></i> Criar Nota</button>
            <?php include 'searchbar.php'; ?>
        </div>

        <!-- Modal Bootstrap para Criar/Editar Nota -->
        <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="note-form-action">
                        <div class="modal-header">
                            <h5 class="modal-title" id="noteModalLabel">Adicionar Nota</h5>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="note-id" name="note_id">

                            <div class="mb-3">
                                <input type="text" id="note-title" name="title" class="form-control" placeholder="Título" required>
                            </div>

                            <div class="mb-3">
                                <textarea id="note-content" name="content" class="form-control" placeholder="Conteúdo" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="note-category" class="form-label">Categoria</label>
                                <select id="note-category" name="category_id" class="form-select">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="note-date" class="form-label">Data e Hora</label>
                                <input type="datetime-local" id="note-date" name="schedule_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" id="save-note" class="btn btn-success">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Container de Notas -->
        <div class="row" id="notes-container">
            <?php foreach ($notes as $note): ?>
                <div class="col-md-4 mb-4" id="note-<?= $note['id'] ?>" data-category="<?= $note['category_id'] ?>">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($note['title']) ?></h5>
                            <p class="card-text flex-grow-1" style="white-space: pre-wrap;"><?= htmlspecialchars($note['content']) ?></p>
                            <p class="mb-1"><strong>Data:</strong> <?= htmlspecialchars($note['schedule_date']) ?></p>
                            <p class="mb-3"><strong>Categoria:</strong>
                                <?php
                                $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ? AND user_id = ?");
                                $stmt->execute([$note['category_id'], $_SESSION['user_id']]);
                                $category = $stmt->fetch();
                                echo htmlspecialchars($category['name'] ?? 'Sem Categoria');
                                ?>
                            </p>
                            <div class="d-flex justify-content-between">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-warning btn-sm" onclick="editNote(
                                        <?= $note['id'] ?>,
                                        `<?= htmlspecialchars($note['title'], ENT_QUOTES) ?>`,
                                        `<?= htmlspecialchars($note['content'], ENT_QUOTES) ?>`,
                                        `<?= htmlspecialchars($note['schedule_date']) ?>`,
                                        <?= $note['category_id'] ?>
                                    )">Editar</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteNote(<?= $note['id'] ?>)">Excluir</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        <?php if (!empty($upcoming_notes)): ?>
            if (!localStorage.getItem('dontShowReminder')) {
                Swal.fire({
                    icon: 'info',
                    title: 'O prazo está quase a acabar!',
                    html: `
                        <p>A nota está prestes a acabar amanhã!</p>
                        <label>
                            <input type="checkbox" id="dontShowAgainReminder" />
                            Não mostrar mais esta mensagem
                        </label>
                    `,
                    showCancelButton: true,
                    cancelButtonText: 'Fechar',
                    confirmButtonText: 'Ok',
                    preConfirm: () => {
                        if (document.getElementById('dontShowAgainReminder').checked) {
                            localStorage.setItem('dontShowReminder', 'true');
                        }
                    }
                });
            }
        <?php endif; ?>

        const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));

        // Função para validar a data
        function validateDate(date) {
            const currentDate = new Date();
            const selectedDate = new Date(date);

            if (selectedDate < currentDate) {
                return false;  // Data inválida
            }
            return true;  // Data válida
        }

        document.getElementById('create-note').addEventListener('click', function () {
            document.getElementById('note-id').value = '';
            document.getElementById('note-title').value = '';
            document.getElementById('note-content').value = '';
            document.getElementById('note-date').value = '';

            const categorySelect = document.getElementById('note-category');
            for (let option of categorySelect.options) {
                if (option.textContent.trim() === 'Sem Categoria') {
                    categorySelect.value = option.value;
                    break;
                }
            }

            document.getElementById('noteModalLabel').textContent = 'Criar Nota';
            noteModal.show();
        });

        document.getElementById('save-note').addEventListener('click', function () {
            const title = document.getElementById('note-title').value;
            const content = document.getElementById('note-content').value;
            const scheduleDate = document.getElementById('note-date').value;
            const noteId = document.getElementById('note-id').value;
            const categoryId = document.getElementById('note-category').value;

            if (!title || !content || !scheduleDate || !categoryId) {
                Swal.fire('Erro', 'Todos os campos são obrigatórios.', 'error');
                return;
            }

            if (!validateDate(scheduleDate)) {
                Swal.fire('Erro', 'A data selecionada não pode ser no passado.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('title', title);
            formData.append('content', content);
            formData.append('schedule_date', scheduleDate);
            formData.append('category_id', categoryId);

            if (noteId) {
                formData.append('note_id', noteId);
            }

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Erro', 'Ocorreu um erro ao salvar a nota.', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Erro', 'Ocorreu um erro. Tente novamente.', 'error');
            });
        });
    });

    function editNote(noteId, title, content, scheduleDate, categoryId) {
        document.getElementById('note-id').value = noteId;
        document.getElementById('note-title').value = title;
        document.getElementById('note-content').value = content;
        document.getElementById('note-date').value = scheduleDate;

        const categorySelect = document.getElementById('note-category');
        categorySelect.value = categoryId;

        document.getElementById('noteModalLabel').textContent = 'Editar Nota';
        const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
        noteModal.show();
    }

    function deleteNote(noteId) {
        Swal.fire({
            title: 'Você tem certeza?',
            text: "Essa ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`?action=delete&id=${noteId}`, {
                    method: 'GET',
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const noteCard = document.getElementById(`note-${noteId}`);
                        noteCard.remove();
                        Swal.fire('Excluído!', 'A nota foi excluída com sucesso.', 'success');
                    } else {
                        Swal.fire('Erro', 'Ocorreu um erro ao excluir a nota.', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Erro', 'Ocorreu um erro. Tente novamente.', 'error');
                });
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
