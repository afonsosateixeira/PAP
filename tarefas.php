<?php
session_start();
include('config.php'); // Inclui a configuração do banco de dados

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Obter categorias do usuário
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$categories = $stmt->fetchAll();

// Adicionar tarefa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['tituloTarefa'];
    $descricao = $_POST['descricaoTarefa'];
    $dataConclusao = $_POST['dataHoraConclusaoTarefa'];
    $dataLembrete = $_POST['dataHoraLembreteTarefa'] ?? null;
    $recorrencia = $_POST['recorrenciaTarefa'] ?? 0;
    $category_id = $_POST['categoriaTarefa'] ?? null;  // Adicionando a categoria

    // Verificar se é uma edição ou uma nova tarefa
    if (isset($_POST['task_id']) && $_POST['task_id']) {
        $id = $_POST['task_id'];

        // Atualizar tarefa existente
        $sql = "UPDATE tbtarefas SET tituloTarefa = :titulo, descricaoTarefa = :descricao, 
                dataconclusao_date = :dataConclusao, datalembrete_date = :dataLembrete, 
                recorrenciaTarefa = :recorrencia, category_id = :category_id WHERE idTarefa = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':descricao' => $descricao,
            ':dataConclusao' => $dataConclusao,
            ':dataLembrete' => $dataLembrete,
            ':recorrencia' => $recorrencia,
            ':category_id' => $category_id,
            ':id' => $id
        ]);

        header('Location: tarefas.php');
    } else {
        // Inserir nova tarefa
        $status = 0;
        $sql = "INSERT INTO tbtarefas (tituloTarefa, descricaoTarefa, dataconclusao_date, datalembrete_date, recorrenciaTarefa, statusTarefa, category_id, user_id) 
                VALUES (:titulo, :descricao, :dataConclusao, :dataLembrete, :recorrencia, :status, :category_id, :user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':descricao' => $descricao,
            ':dataConclusao' => $dataConclusao,
            ':dataLembrete' => $dataLembrete,
            ':recorrencia' => $recorrencia,
            ':status' => $status,
            ':category_id' => $category_id,
            ':user_id' => $user_id  // Garantir que o user_id seja passado
        ]);
    }
}

// Excluir tarefa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM tbtarefas WHERE idTarefa = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
}

// Atualizar status da tarefa
if (isset($_GET['status'])) {
    $id = $_GET['status'];
    $sql = "UPDATE tbtarefas SET statusTarefa = 1 WHERE idTarefa = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
}

// Verificar tarefas vencidas e pendentes
$currentDate = new DateTime();
$stmt = $pdo->prepare("SELECT * FROM tbtarefas WHERE user_id = :user_id AND statusTarefa = 0");
$stmt->execute([':user_id' => $user_id]);
$pendingTasks = $stmt->fetchAll();

$expiredTasks = [];

foreach ($pendingTasks as $task) {
    $conclusionDate = new DateTime($task['dataconclusao_date']);
    $interval = $currentDate->diff($conclusionDate);
    
    if ($interval->days > 0 && $currentDate > $conclusionDate) {
        // Adicionar tarefas vencidas a um array para alertar o usuário
        $expiredTasks[] = $task['tituloTarefa'];
    }
}

// Exibir tarefas associadas ao usuário logado
$stmt = $pdo->prepare("SELECT * FROM tbtarefas WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$tasks = $stmt->fetchAll();

// Editar tarefa
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM tbtarefas WHERE idTarefa = :id");
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Tarefas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.min.js"></script>
    <style>
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
        .modal-header {
            border-bottom: 1px solid #ddd;
        }
        .buttons-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container" id="main-content">
        <h1 class="mb-4">Gestor de Tarefas</h1>
        <div class="buttons-container">
            <button class="btn btn-success mb-4" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="openTaskModal()"><i class="fa fa-plus"></i> Criar Tarefa
            </button>
            <?php include 'searchbar.php'; ?>
        </div>

        <!-- Modal para criar/editar tarefa -->
        <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="taskModalLabel">Adicionar Tarefa</h5>
                    </div>
                    <form method="POST" id="task-form-content">
                        <div class="modal-body">
                            <input type="text" class="form-control mb-3" name="tituloTarefa" id="titulo" placeholder="Título" required>
                            <textarea class="form-control mb-3" name="descricaoTarefa" id="descricao" placeholder="Descrição" required></textarea>
                            <input type="datetime-local" class="form-control mb-3" name="dataHoraConclusaoTarefa" id="dataHoraConclusao" required>
                            <input type="datetime-local" class="form-control mb-3" name="dataHoraLembreteTarefa" id="dataHoraLembrete" placeholder="Data e Hora de Lembrete">
                            <select class="form-select mb-3" name="recorrenciaTarefa" id="recorrencia">
                                <option value="0">Nenhuma</option>
                                <option value="1">Diária</option>
                                <option value="2">Semanal</option>
                                <option value="3">Mensal</option>
                            </select>
                            <select class="form-select mb-3" name="categoriaTarefa" id="categoria" required>
                                <option value="">Selecionar Categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                        <input type="hidden" name="task_id" id="task_id">
                    </form>
                </div>
            </div>
        </div>

        <!-- Exibição das tarefas -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Título</th>
                    <th>Descrição</th>
                    <th>Categoria</th> 
                    <th>Data e Hora de Conclusão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="task-table-body">
                <?php foreach ($tasks as $task): ?>
                    <tr class="task-row" data-category="<?= $task['category_id'] ?>">
                        <td><?= $task['statusTarefa'] ? 'Concluída' : 'Pendente' ?></td>
                        <td class="task-title"><?= $task['tituloTarefa'] ?></td>
                        <td><?= $task['descricaoTarefa'] ?></td>
                        <td class="task-category">
                            <?php
                                if ($task['category_id']) {
                                    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :id");
                                    $stmt->execute([':id' => $task['category_id']]);
                                    $categoryName = $stmt->fetchColumn();
                                    echo $categoryName ?? 'Nenhuma';
                                } else {
                                    echo 'Nenhuma';
                                }
                            ?>
                        </td>
                        <td><?= $task['dataconclusao_date'] ? date('d/m/Y H:i', strtotime($task['dataconclusao_date'])) : '-' ?></td>
                        <td>
                            <?php if (!$task['statusTarefa']): ?>
                                <a href="?status=<?= $task['idTarefa'] ?>" class="btn btn-success btn-sm">Concluir</a>
                            <?php endif; ?>
                            <button class="btn btn-warning btn-sm" onclick="editTask(<?= $task['idTarefa'] ?>, '<?= addslashes($task['tituloTarefa']) ?>', '<?= addslashes($task['descricaoTarefa']) ?>', '<?= $task['dataconclusao_date'] ?>', '<?= $task['datalembrete_date'] ?>', '<?= $task['recorrenciaTarefa'] ?>', '<?= $task['category_id'] ?>')">Editar</button>
                            <a href="javascript:void(0);" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $task['idTarefa'] ?>)">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openTaskModal() {
            // Limpar o formulário ao abrir o modal para criação
            document.getElementById('task-form-content').reset();
            document.getElementById('taskModalLabel').textContent = 'Adicionar Tarefa';
            document.getElementById('task_id').value = ''; 
        }

        function editTask(id, titulo, descricao, dataConclusao, dataLembrete, recorrencia, categoryId) {
            // Preencher os campos do formulário com os dados da tarefa
            document.getElementById('taskModalLabel').textContent = 'Editar Tarefa';
            document.getElementById('titulo').value = titulo;
            document.getElementById('descricao').value = descricao;
            document.getElementById('dataHoraConclusao').value = dataConclusao;
            document.getElementById('dataHoraLembrete').value = dataLembrete;
            document.getElementById('recorrencia').value = recorrencia;
            document.getElementById('categoria').value = categoryId;
            document.getElementById('task_id').value = id;

            // Abrir o modal
            var myModal = new bootstrap.Modal(document.getElementById('taskModal'));
            myModal.show();
        }

        function confirmDelete(taskId) {
            // Exibe o pop-up de confirmação
            if (confirm("Tem certeza que deseja excluir esta tarefa?")) {
                // Se o usuário confirmar, redireciona para a URL de exclusão
                window.location.href = "?delete=" + taskId;
            }
        }

        // Validação de tarefas vencidas
        document.addEventListener('DOMContentLoaded', function() {
    const tasks = <?= json_encode($tasks); ?>;  // Array de tarefas vindo do PHP
    const currentDate = new Date();

    tasks.forEach(task => {
        const taskConclusionDate = new Date(task.dataconclusao_date);
        
        // Ajustar as horas, minutos e segundos para comparar apenas a data
        taskConclusionDate.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);

        // Verificar se a tarefa está vencida
        if (taskConclusionDate < currentDate && task.statusTarefa == 0) {
            // Verificar se o alerta já foi desativado para esta tarefa
            if (!localStorage.getItem('alertDismissed_' + task.idTarefa)) {
                // Exibir um alerta quando a tarefa estiver vencida e ainda não for concluída
                Swal.fire({
                    icon: 'warning',
                    title: 'Tarefa não concluída!',
                    html: `
                        <p>A tarefa "${task.tituloTarefa}" não foi concluída no prazo.</p>
                        <label>
                            <input type="checkbox" id="dontShowAgain_${task.idTarefa}" />
                            Não mostrar mais esta mensagem 
                        </label>
                    `,
                    confirmButtonText: 'OK',
                    preConfirm: () => {
                        // Se o checkbox estiver marcado, salvar no localStorage
                        const dontShowAgain = document.getElementById('dontShowAgain_' + task.idTarefa).checked;
                        if (dontShowAgain) {
                            localStorage.setItem('alertDismissed_' + task.idTarefa, 'true');
                        }
                    }
                });
            }
        }

        // Verificar se a tarefa está a 1 dia do prazo (sem contar com as horas)
        const oneDayBefore = new Date(taskConclusionDate);
        oneDayBefore.setDate(oneDayBefore.getDate() - 1); // Subtrai 1 dia
        
        if (currentDate.toDateString() === oneDayBefore.toDateString() && task.statusTarefa == 0) {
            // Exibir um alerta avisando que a tarefa está quase no prazo
            Swal.fire({
                icon: 'info',
                title: 'O prazo está quase a acabar!',
                html: `
                    <p>A tarefa "${task.tituloTarefa}" está prestes a acabar amanhã!</p>
                    <label>
                        <input type="checkbox" id="dontShowAgainReminder_${task.idTarefa}" />
                        Não mostrar mais esta mensagem 
                    </label>
                `,
                confirmButtonText: 'OK',
                preConfirm: () => {
                    // Se o checkbox estiver marcado, salvar no localStorage
                    const dontShowAgainReminder = document.getElementById('dontShowAgainReminder_' + task.idTarefa).checked;
                    if (dontShowAgainReminder) {
                        localStorage.setItem('alertDismissedReminder_' + task.idTarefa, 'true');
                    }
                }
            });
        }
    });
});

    </script>
</body>
</html>
