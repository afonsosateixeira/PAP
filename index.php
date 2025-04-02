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

// Obter o agendamento mais próximo (a partir de agora)
$stmt = $pdo->prepare("
    SELECT n.*, c.color 
    FROM notes n 
    LEFT JOIN categories c ON n.category_id = c.id 
    WHERE n.user_id = ? AND n.schedule_date >= NOW() 
    ORDER BY n.schedule_date ASC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$upcomingNote = $stmt->fetch();

// Contar o total de agendamentos do usuário
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
$stmt2->execute([$user_id]);
$notesCount = $stmt2->fetchColumn();

// Contar tarefas pendentes (statusTarefa = 0)
$stmt3 = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tbtarefas t
    JOIN categories c ON t.category_id = c.id
    WHERE c.user_id = ? AND t.statusTarefa = 0
");
$stmt3->execute([$user_id]);
$pendingTasks = $stmt3->fetchColumn();

// Contar tarefas concluídas (statusTarefa = 1)
$stmt4 = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tbtarefas t
    JOIN categories c ON t.category_id = c.id
    WHERE c.user_id = ? AND t.statusTarefa = 1
");
$stmt4->execute([$user_id]);
$completedTasks = $stmt4->fetchColumn();

// Obter a tarefa pendente com a data de conclusão mais próxima
$stmt5 = $pdo->prepare("
    SELECT t.*, c.color AS category_color
    FROM tbtarefas t
    JOIN categories c ON t.category_id = c.id
    WHERE c.user_id = ? AND t.statusTarefa = 0
    ORDER BY t.dataconclusao_date ASC
    LIMIT 1
");
$stmt5->execute([$user_id]);
$nextTask = $stmt5->fetch();

// Obter informações sobre reposição de horas (usando a tabela reposicao_horas)
$stmt6 = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN tipo = 'modulos' THEN 1 END) AS total_modules,
        COUNT(CASE WHEN tipo = 'horas' AND status = 'pendente' AND datahora_reposicao > NOW() THEN 1 END) AS total_pending_hours,  -- Contar reposições de horas pendentes e futuras
        (SELECT disciplina FROM reposicao_horas WHERE user_id = ? AND datahora_reposicao > NOW() ORDER BY datahora_reposicao ASC LIMIT 1) AS last_module,
        (SELECT datahora_reposicao FROM reposicao_horas WHERE user_id = ? AND datahora_reposicao > NOW() ORDER BY datahora_reposicao ASC LIMIT 1) AS last_replacement_date
    FROM reposicao_horas
    WHERE user_id = ? AND datahora_reposicao > NOW()  -- Filtra apenas reposições futuras
");
$stmt6->execute([$user_id, $user_id, $user_id]);
$moduleInfo = $stmt6->fetch();

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="manifest" href="/manifest.json">
    <!-- Bootstrap CSS (via CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* Espaço principal ao lado da sidebar */
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
        /* Estilos para os cartões */
        .card {
            margin-bottom: 20px;
        }
        .next-item {
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: #ffffff;
            border-radius: 5px;
            border-left: 5px solid;
            padding-left: 10px;
        }
    </style>
</head>
<body>
    <!-- Incluindo a sidebar -->
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <div class="container-fluid">
            <!-- Linha para os cartões do Cronômetro e Relógio Universal -->
            <div class="row mb-4">
                <!-- Relógio Universal (agora à esquerda) -->
                <div class="col-md-6">
                    <div class="card shadow position-relative">
                        <div class="card-body">
                            <h5 class="card-title">Relógio Universal</h5>
                            <div class="card-text">
                                <?php include 'relogio-universal.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Cronômetro (agora à direita) -->
                <div class="col-md-6">
                    <div class="card shadow position-relative">
                        <div class="card-body">
                            <h5 class="card-title">Cronômetro</h5>
                            <div class="card-text">
                                <?php include 'cronometro.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linha com 3 cartões: Reposição de Horas, Agendamentos e Tarefas -->
            <div class="row">
<!-- Caixa de Reposição de Horas -->
<div class="col-md-4 mb-4">
    <div class="card text-dark bg-light shadow position-relative">
        <div class="card-body">
            <h5 class="card-title">Reposições</h5>
            <p class="card-text">
                <strong>Módulos Pendentes:</strong> <?php echo $moduleInfo['total_modules']; ?><br>
                <strong>Horas Pendentes:</strong> <?php echo $moduleInfo['total_pending_hours']; ?>
            </p>
            <?php if ($moduleInfo['last_module']): ?>
                <div class="next-item">
                    <strong><?php echo htmlspecialchars($moduleInfo['last_module']); ?></strong>
                    <br>
                    <?php echo date('d/m/Y H:i', strtotime($moduleInfo['last_replacement_date'])); ?>
                </div>
            <?php else: ?>
                <p class="mt-2">Nenhuma reposição futura.</p>
            <?php endif; ?>
        </div>
    </div>
</div>


                <!-- Cartão de Agendamentos -->
                <div class="col-md-4 mb-4">
                    <div class="card text-dark bg-light shadow position-relative">
                        <div class="card-body">
                            <h5 class="card-title">Agendamentos</h5>
                            <p class="card-text">
                                <strong>Total:</strong> <?php echo $notesCount; ?>
                            </p>
                            <?php if ($upcomingNote): ?>
                                <div class="next-item" style="border-left-color: <?php echo htmlspecialchars($upcomingNote['color']); ?>;">
                                    <strong><?php echo htmlspecialchars($upcomingNote['title']); ?></strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($upcomingNote['schedule_date'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="mt-2">Nenhum agendamento próximo.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cartão de Tarefas -->
                <div class="col-md-4 mb-4">
                    <div class="card text-dark bg-light shadow position-relative">
                        <div class="card-body">
                            <h5 class="card-title">Tarefas</h5>
                            <p class="card-text">
                                <strong>Pendentes:</strong> <?php echo $pendingTasks; ?><br>
                                <strong>Concluídas:</strong> <?php echo $completedTasks; ?>
                            </p>
                            <?php if ($nextTask): ?>
                                <div class="next-item" style="border-left-color: <?php echo htmlspecialchars($nextTask['category_color']); ?>;">
                                    <strong><?php echo htmlspecialchars($nextTask['tituloTarefa']); ?></strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($nextTask['dataconclusao_date'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="mt-2">Nenhuma tarefa pendente.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gestor de Categorias -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Gestor de Categorias</h5>
                </div>
                <div class="card-body">
                    <?php include __DIR__ . '/category.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (via CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
