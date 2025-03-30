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

// Obter tarefas associadas ao usuário logado
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
    <title>Gestor de Tarefas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
            #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
        .task-form {
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .task-form input, .task-form textarea, .task-form select {
            width: 90%; 
            margin: 10px auto; 
            display: block; 
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: border 0.3s ease;
        }

        .task-form input:focus, .task-form textarea:focus, .task-form select:focus {
            border-color: #2ecc71;
            outline: none;
        }

        .task-form textarea {
            height: 120px;
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
        <button id="create-note" class="btn btn-success mb-4">Criar Tarefa</button>
        <?php include 'searchbar.php'; ?>
    </div>
        <!-- Formulário para adicionar ou editar tarefas -->
        <div class="task-form" id="task-form">
            <h3 id="form-title"><?php echo isset($task) ? 'Editar Tarefa' : 'Adicionar Tarefa'; ?></h3>
            <form method="post" id="task-form-content">
                <div class="mb-3">
                    <input type="text" class="form-control" id="titulo" name="tituloTarefa" placeholder="Título" required value="<?php echo isset($task) ? $task['tituloTarefa'] : ''; ?>">
                </div>
                <div class="mb-3">
                    <textarea class="form-control" id="descricao" name="descricaoTarefa" placeholder="Conteúdo" required><?php echo isset($task) ? $task['descricaoTarefa'] : ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoria</label>
                    <select class="form-control" id="categoria" name="categoriaTarefa" required>
                        <option value="">Selecione a Categoria</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (isset($task) && $task['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?= $category['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="dataHoraConclusao" class="form-label">Data e Hora de Conclusão</label>
                    <input type="datetime-local" class="form-control" id="dataHoraConclusao" name="dataHoraConclusaoTarefa" required value="<?php echo isset($task) ? date('Y-m-d\TH:i', strtotime($task['dataconclusao_date'])) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="dataHoraLembrete" class="form-label">Data e Hora do Lembrete (Opcional)</label>
                    <input type="datetime-local" class="form-control" id="dataHoraLembrete" name="dataHoraLembreteTarefa" value="<?php echo isset($task) ? date('Y-m-d\TH:i', strtotime($task['datalembrete_date'])) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="recorrencia" class="form-label">Recorrência</label>
                    <select class="form-control" id="recorrencia" name="recorrenciaTarefa">
                        <option value="0" <?php echo (isset($task) && $task['recorrenciaTarefa'] == 0) ? 'selected' : ''; ?>>Nenhuma</option>
                        <option value="1" <?php echo (isset($task) && $task['recorrenciaTarefa'] == 1) ? 'selected' : ''; ?>>Diário</option>
                        <option value="2" <?php echo (isset($task) && $task['recorrenciaTarefa'] == 2) ? 'selected' : ''; ?>>Semanal</option>
                        <option value="3" <?php echo (isset($task) && $task['recorrenciaTarefa'] == 3) ? 'selected' : ''; ?>>Mensal</option>
                        <option value="4" <?php echo (isset($task) && $task['recorrenciaTarefa'] == 4) ? 'selected' : ''; ?>>Anual</option>
                    </select>
                </div>
                <button type="submit" id="save-note" class="btn btn-success w-100">Salvar</button>
                <button type="button" id="cancel" class="btn btn-danger w-100 mt-3">Cancelar</button>
            </form>
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
            <tbody>
    <?php foreach ($tasks as $task): ?>
        <tr class="task-row">
            <td><?= $task['statusTarefa'] ? 'Concluída' : 'Pendente' ?></td>
            <td class="task-title"><?= $task['tituloTarefa'] ?></td>
            <td><?= $task['descricaoTarefa'] ?></td>
            <td class="task-category">
                <?php
                // Verifique se a categoria existe antes de tentar buscar o nome
                if ($task['category_id']) {
                    // Prepare a consulta
                    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :id");
                    $stmt->execute([':id' => $task['category_id']]);
                    $categoryName = $stmt->fetchColumn();
                    
                    // Se a consulta falhou (fetchColumn retornou false), use 'Nenhuma'
                    if (!$categoryName) {
                        $categoryName = 'Nenhuma';
                    }
                } else {
                    $categoryName = 'Nenhuma';
                }
                echo $categoryName;
                ?>
            </td>
            <td><?= $task['dataconclusao_date'] ? date('d/m/Y H:i', strtotime($task['dataconclusao_date'])) : '-' ?></td>
            <td>
                <?php if (!$task['statusTarefa']): ?>
                    <a href="?status=<?= $task['idTarefa'] ?>" class="btn btn-success btn-sm">Concluir</a>
                <?php endif; ?>
                <a href="#" onclick="editTask(<?= $task['idTarefa'] ?>, '<?= addslashes($task['tituloTarefa']) ?>', '<?= addslashes($task['descricaoTarefa']) ?>', '<?= $task['dataconclusao_date'] ?>', '<?= $task['datalembrete_date'] ?>', '<?= $task['recorrenciaTarefa'] ?>', '<?= $task['category_id'] ?>')" class="btn btn-warning btn-sm">Editar</a>
                <a href="?delete=<?= $task['idTarefa'] ?>" class="btn btn-danger btn-sm">Excluir</a>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>

    </div>

    <script>
        // Mostrar o formulário de criação de tarefa
document.getElementById('create-note').addEventListener('click', function() {
    // Resetar o formulário
    document.getElementById('task-form').style.display = 'block';
    document.getElementById('task-form-content').reset();
    document.getElementById('form-title').textContent = 'Adicionar Tarefa';
    
    // Garantir que o campo 'task_id' não exista ao criar uma nova tarefa
    const existingIdField = document.querySelector('input[name="task_id"]');
    if (existingIdField) {
        existingIdField.remove();  // Remove o campo oculto de edição, caso exista
    }
});

// Função para preencher o formulário de edição
function editTask(id, titulo, descricao, dataConclusao, dataLembrete, recorrencia, categoryId) {
    document.getElementById('task-form').style.display = 'block';
    document.getElementById('form-title').textContent = 'Editar Tarefa';

    document.getElementById('titulo').value = titulo;
    document.getElementById('descricao').value = descricao;
    document.getElementById('dataHoraConclusao').value = dataConclusao;
    document.getElementById('dataHoraLembrete').value = dataLembrete;
    document.getElementById('recorrencia').value = recorrencia;
    document.getElementById('categoria').value = categoryId;

    // Remover o campo 'hidden' existente (caso haja)
    const existingIdField = document.querySelector('input[name="task_id"]');
    if (existingIdField) {
        existingIdField.remove();
    }

    // Adicionar o id da tarefa como campo oculto
    let inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'task_id';
    inputId.value = id;
    document.getElementById('task-form-content').appendChild(inputId);
}

// Fechar o formulário
document.getElementById('cancel').addEventListener('click', function() {
    document.getElementById('task-form').style.display = 'none';
});

        document.getElementById('save-category').addEventListener('click', function() {
    // Submete o formulário manualmente
    document.getElementById('task-form-content').submit();
});

// Verifica se a categoria foi filtrada
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

// Adiciona a cláusula WHERE ao SQL se a categoria for selecionada
$sql = "SELECT * FROM tbtarefas WHERE user_id = :user_id";
if ($categoryFilter != '') {
    $sql .= " AND category_id = :category_id";
}

$stmt = $pdo->prepare($sql);

// Parâmetros para a consulta
$params = [':user_id' => $user_id];
if ($categoryFilter != '') {
    $params[':category_id'] = $categoryFilter;
}

$stmt->execute($params);
$tasks = $stmt->fetchAll();


    </script>

</body>
</html>
