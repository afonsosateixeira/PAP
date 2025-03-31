<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Endpoint para atualização via AJAX do status
if (isset($_GET['action']) && $_GET['action'] === 'toggleStatus' && isset($_GET['id']) && isset($_GET['status'])) {
    $newStatus = $_GET['status'];
    if ($newStatus !== 'concluido' && $newStatus !== 'pendente') {
        $newStatus = 'pendente';
    }
    $stmt = $pdo->prepare("UPDATE reposicao_horas SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newStatus, $_GET['id'], $_SESSION['user_id']]);
    echo 'success';
    exit();
}

$disciplinas = ['Português', 'Matemática', 'Inglês', 'Integração', 'Fisíca e Química', 'Programação','Educação Física', 'TIC', 'Redes Operacionais'];
$professores = ['Marco Silva', 'Jorge Honorato', 'Helena Ferreira'];
$justificativas = ['Justificada', 'Injustificada'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = $_POST['tipo'];
    $disciplina = $_POST['disciplina'];
    $professor = $_POST['professor'];
    $modulo = $_POST['modulo'];
    $horas = ($tipo == 'horas' && isset($_POST['horas']) && $_POST['horas'] != '') ? $_POST['horas'] : null;
    $justificativa = $_POST['justificativa'];
    $datahora_reposicao = $_POST['datahora_reposicao'];
    $status = 'pendente';

    if (isset($_POST['reposicao-id']) && $_POST['reposicao-id']) {
        $stmt = $pdo->prepare("UPDATE reposicao_horas SET tipo = ?, disciplina = ?, professor = ?, modulo = ?, horas = ?, justificativa = ?, datahora_reposicao = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$tipo, $disciplina, $professor, $modulo, $horas, $justificativa, $datahora_reposicao, $status, $_POST['reposicao-id'], $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO reposicao_horas (user_id, tipo, disciplina, professor, modulo, horas, justificativa, datahora_reposicao, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $tipo, $disciplina, $professor, $modulo, $horas, $justificativa, $datahora_reposicao, $status]);
    }
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $reposicao_id = $_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("SELECT id FROM reposicao_horas WHERE id = ? AND user_id = ?");
        $stmt->execute([$reposicao_id, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM reposicao_horas WHERE id = ?");
            $stmt->execute([$reposicao_id]);
        }
        header("Location: modulos.php");
        exit();
    }
}

$stmtHoras = $pdo->prepare("SELECT * FROM reposicao_horas WHERE user_id = ? AND tipo = 'horas'");
$stmtHoras->execute([$_SESSION['user_id']]);
$reposicoes_horas = $stmtHoras->fetchAll();

$stmtModulos = $pdo->prepare("SELECT * FROM reposicao_horas WHERE user_id = ? AND tipo = 'modulos'");
$stmtModulos->execute([$_SESSION['user_id']]);
$reposicoes_modulos = $stmtModulos->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Reposição</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilo para a página */
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
        .modal-custom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
        }
        .modal-content-custom {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 500px;
            max-width: 100%;
        }
        .modal-custom .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        .form-control {
            margin-bottom: 10px;
        }
        .status-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .btn-info {
            background-color: white;
            color: #007bff;
            border: 1px solid #007bff;
            box-shadow: 0px 4px 6px rgba(0, 123, 255, 0.1);
        }
        .btn-info:hover {
            background-color: #007bff;
            color: white;
        }
        .modal-body button {
            margin-bottom: 10px;
        }
        .form-label {
            font-weight: bold;
        }
    </style>
    <script>
        function selecionarTipo(tipo) {
            document.getElementById('form-horas').style.display = (tipo === 'horas') ? 'block' : 'none';
            document.getElementById('form-modulos').style.display = (tipo === 'modulos') ? 'block' : 'none';
        }

        function preencherFormulario(reposicao) {
            if (reposicao.tipo === 'horas') {
                document.querySelector('#form-horas input[name=modulo]').value = reposicao.modulo;
                document.querySelector('#form-horas input[name=horas]').value = reposicao.horas;
                document.querySelector('#form-horas select[name=disciplina]').value = reposicao.disciplina;
                document.querySelector('#form-horas select[name=professor]').value = reposicao.professor;
                document.querySelector('#form-horas select[name=justificativa]').value = reposicao.justificativa;
                document.querySelector('#form-horas input[name=datahora_reposicao]').value = reposicao.datahora_reposicao;
                document.querySelector('#form-horas input[name="reposicao-id"]').value = reposicao.id;
            } else if (reposicao.tipo === 'modulos') {
                document.querySelector('#form-modulos input[name=modulo]').value = reposicao.modulo;
                document.querySelector('#form-modulos select[name=disciplina]').value = reposicao.disciplina;
                document.querySelector('#form-modulos select[name=professor]').value = reposicao.professor;
                document.querySelector('#form-modulos select[name=justificativa]').value = reposicao.justificativa;
                document.querySelector('#form-modulos input[name=datahora_reposicao]').value = reposicao.datahora_reposicao;
                document.querySelector('#form-modulos input[name="reposicao-id"]').value = reposicao.id;
            }
            selecionarTipo(reposicao.tipo);
            toggleModal();
        }

        function toggleModal() {
            var modal = document.getElementById('modal-reposicao');
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }

        function updateStatus(id, checkbox) {
            var newStatus = checkbox.checked ? 'concluido' : 'pendente';
            fetch('?action=toggleStatus&id=' + id + '&status=' + newStatus)
            .then(response => response.text())
            .then(result => {
                if(result.trim() === 'success') {
                    document.getElementById('status-text-' + id).innerText = newStatus;
                } else {
                    alert('Erro ao atualizar o status.');
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(err => {
                alert('Erro na requisição.');
                checkbox.checked = !checkbox.checked;
            });
        }
    </script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div id="main-content">
        <h1 class="mb-4">Gestor de Reposição</h1>
        <button class="btn btn-success mb-4" onclick="toggleModal()"> Criar Reposição</button>

        <!-- Modal -->
        <div class="modal-custom" id="modal-reposicao">
            <div class="modal-content-custom">
                <div class="modal-header">
                    <h5 class="modal-title">Escolher Tipo de Reposição</h5>
                    <span class="close" onclick="toggleModal()">×</span>
                </div>
                <div class="modal-body">
                    <button class="btn btn-info" onclick="selecionarTipo('horas')">Reposição de Horas</button>
                    <button class="btn btn-info" onclick="selecionarTipo('modulos')">Reposição de Módulos</button>

                    <!-- Formulário para horas -->
                    <form method="POST" id="form-horas" style="display:none;">
                        <input type="hidden" name="tipo" value="horas">
                        <label for="disciplina-horas" class="form-label">Disciplina</label>
                        <select name="disciplina" id="disciplina-horas" class="form-control" required>
                            <option value="">Selecione a disciplina</option>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?= $disciplina ?>"><?= $disciplina ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="professor-horas" class="form-label">Professor</label>
                        <select name="professor" id="professor-horas" class="form-control" required>
                            <option value="">Selecione o professor</option>
                            <?php foreach ($professores as $professor): ?>
                                <option value="<?= $professor ?>"><?= $professor ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="modulo-horas" class="form-label">Módulo</label>
                        <input type="number" name="modulo" id="modulo-horas" class="form-control" placeholder="Módulo" required>
                        
                        <label for="horas-horas" class="form-label">Horas</label>
                        <input type="number" name="horas" id="horas-horas" class="form-control" placeholder="Horas" required>
                        
                        <label for="justificativa-horas" class="form-label">Justificativa</label>
                        <select name="justificativa" id="justificativa-horas" class="form-control" required>
                            <option value="">Selecione a justificativa</option>
                            <?php foreach ($justificativas as $justificativa): ?>
                                <option value="<?= $justificativa ?>"><?= $justificativa ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="datahora_reposicao-horas" class="form-label">Data/Hora da Reposição</label>
                        <input type="datetime-local" name="datahora_reposicao" id="datahora_reposicao-horas" class="form-control" required>
                        
                        <button type="submit" class="btn btn-success w-100">Salvar</button>
                        <button type="button" class="btn btn-danger w-100 mt-2" onclick="toggleModal()">Cancelar</button>
                        <input type="hidden" name="reposicao-id" value="">
                    </form>

                    <!-- Formulário para módulos -->
                    <form method="POST" id="form-modulos" style="display:none;">
                        <input type="hidden" name="tipo" value="modulos">
                        
                        <label for="disciplina-modulos" class="form-label">Disciplina</label>
                        <select name="disciplina" id="disciplina-modulos" class="form-control" required>
                            <option value="">Selecione a disciplina</option>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?= $disciplina ?>"><?= $disciplina ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="professor-modulos" class="form-label">Professor</label>
                        <select name="professor" id="professor-modulos" class="form-control" required>
                            <option value="">Selecione o professor</option>
                            <?php foreach ($professores as $professor): ?>
                                <option value="<?= $professor ?>"><?= $professor ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="modulo-modulos" class="form-label">Módulo</label>
                        <input type="number" name="modulo" id="modulo-modulos" class="form-control" placeholder="Módulo" required>
                        
                        <label for="justificativa-modulos" class="form-label">Justificativa</label>
                        <select name="justificativa" id="justificativa-modulos" class="form-control" required>
                            <option value="">Selecione a justificativa</option>
                            <?php foreach ($justificativas as $justificativa): ?>
                                <option value="<?= $justificativa ?>"><?= $justificativa ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="datahora_reposicao-modulos" class="form-label">Data/Hora da Reposição</label>
                        <input type="datetime-local" name="datahora_reposicao" id="datahora_reposicao-modulos" class="form-control" required>
                        
                        <button type="submit" class="btn btn-success w-100">Salvar</button>
                        <button type="button" class="btn btn-danger w-100 mt-2" onclick="toggleModal()">Cancelar</button>
                        <input type="hidden" name="reposicao-id" value="">
                    </form>
                </div>
            </div>
        </div>

        <!-- Listagem de reposições -->
        <h4 class="text-dark font-weight-bold">Reposições de horas</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Módulo</th>
                    <th>Horas</th>
                    <th>Justificativa</th>
                    <th>Data e hora da reposição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reposicoes_horas as $reposicao): ?>
                <tr>
                    <td>
                    <input type="checkbox" class="status-checkbox" id="status-checkbox-<?= $reposicao['id'] ?>" <?= ($reposicao['status'] == 'concluido' ? 'checked' : '') ?> onchange="updateStatus(<?= $reposicao['id'] ?>, this)">
                    <span id="status-text-<?= $reposicao['id'] ?>"><?= $reposicao['status'] ?></span>
                    </td>
                    <td><?= $reposicao['disciplina'] ?></td>
                    <td><?= $reposicao['professor'] ?></td>
                    <td><?= $reposicao['modulo'] ?></td>
                    <td><?= $reposicao['horas'] ?></td>
                    <td><?= $reposicao['justificativa'] ?></td>
                    <td><?= $reposicao['datahora_reposicao'] ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="preencherFormulario(<?= json_encode($reposicao) ?>)">Editar</button>
                        <a href="?action=delete&id=<?= $reposicao['id'] ?>" class="btn btn-danger btn-sm">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 class="text-dark font-weight-bold">Reposições de módulos</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Módulo</th>
                    <th>Justificativa</th>
                    <th>Data e hora da reposição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reposicoes_modulos as $reposicao): ?>
                <tr>
                    <td>
                    <input type="checkbox" class="status-checkbox" id="status-checkbox-<?= $reposicao['id'] ?>" <?= ($reposicao['status'] == 'concluido' ? 'checked' : '') ?> onchange="updateStatus(<?= $reposicao['id'] ?>, this)">
                    <span id="status-text-<?= $reposicao['id'] ?>"><?= $reposicao['status'] ?></span>
                    </td>
                    <td><?= $reposicao['disciplina'] ?></td>
                    <td><?= $reposicao['professor'] ?></td>
                    <td><?= $reposicao['modulo'] ?></td>
                    <td><?= $reposicao['justificativa'] ?></td>
                    <td><?= $reposicao['datahora_reposicao'] ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="preencherFormulario(<?= json_encode($reposicao) ?>)">Editar</button>
                        <a href="?action=delete&id=<?= $reposicao['id'] ?>" class="btn btn-danger btn-sm">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
