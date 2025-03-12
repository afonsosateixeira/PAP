<?php
session_start();
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
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <!-- Bootstrap CSS (via CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Espaço principal ao lado da sidebar */
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }

        /* Pequeno ajuste para as “caixas” do dashboard */
        .card-icon {
            font-size: 3rem; 
            opacity: 0.2; 
            position: absolute; 
            right: 15px; 
            bottom: 15px;
        }

        /* Exemplo de estilo para o “detalhe” da próxima nota/tarefa */
        .next-item {
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: #ffffff;
            border-radius: 5px;
            border-left: 5px solid #ccc; /* Por padrão, mudamos dinamicamente pela cor da categoria */
        }
    </style>
</head>
<body>
    <!-- Incluir a sidebar (menu lateral) -->
    <?php include 'sidebar.html'; ?>

    <div id="main-content">
      <div class="container-fluid">
        
        <!-- Linha com 2 cartões: Agendamentos e Tarefas -->
        <div class="row">
          <!-- Cartão de Agendamentos -->
          <div class="col-md-6 mb-4">
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
              <!-- Ícone de exemplo no fundo do cartão -->
              <i class="bi bi-calendar-check card-icon"></i>
            </div>
          </div>

          <!-- Cartão de Tarefas -->
          <div class="col-md-6 mb-4">
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
              <!-- Ícone de exemplo no fundo do cartão -->
              <i class="bi bi-check2-circle card-icon"></i>
            </div>
          </div>
        </div>

        <!-- “Box diferente” para o Gestor de Categorias -->
        <div class="card mb-4">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Gestor de Categorias</h5>
          </div>
          <div class="card-body">
            <!-- Aqui dentro você pode simplesmente incluir seu código de categorias -->
            <?php include 'category.php'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS (via CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons (opcional, para usar ícones como bi-calendar-check, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>
