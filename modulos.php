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
        .status-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .form-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div id="main-content">
        <h1 class="mb-4">Gestor de Reposição</h1>

        <!-- Modal -->
        <div class="modal fade" id="modal-reposicao" tabindex="-1" aria-labelledby="modal-reposicaoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-reposicaoLabel">Criar/ Editar Reposição</h5>
                    </div>
                    <div class="modal-body">
                        <!-- Formulário para horas -->
                        <form method="POST" id="form-horas" style="display:none;">
                            <input type="hidden" name="tipo" value="horas">
                            <input type="hidden" name="reposicao-id" id="reposicao-id-horas">
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
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                    </form>

                        <!-- Formulário para módulos -->
                        <form method="POST" id="form-modulos" style="display:none;">
                            <input type="hidden" name="tipo" value="modulos">
                            <input type="hidden" name="reposicao-id" id="reposicao-id-modulos">
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
                            <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listagem de reposições -->
        <button class="btn btn-success mb-4" data-bs-toggle="modal" data-bs-target="#modal-reposicao" onclick="selecionarTipo('horas')"><i class="fa fa-plus"></i> Criar Reposição de horas
        </button>
        <h4 class="text-dark font-weight-bold">Reposições de Horas</h4>
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
                        <button class="btn btn-warning btn-sm" onclick='preencherFormulario(<?= json_encode($reposicao) ?>)'>Editar</button>
                        <a href="?action=delete&id=<?= $reposicao['id'] ?>" class="btn btn-danger btn-sm">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Reposições de Módulos -->
        <button class="btn btn-success mb-4" data-bs-toggle="modal" data-bs-target="#modal-reposicao" onclick="selecionarTipo('modulos')"><i class="fa fa-plus"></i> Criar Reposição de Módulos
        </button>
        <h4 class="text-dark font-weight-bold">Reposições de Módulos</h4>
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
                        <button class="btn btn-warning btn-sm" onclick='preencherFormulario(<?= json_encode($reposicao) ?>)'>Editar</button>
                        <a href="?action=delete&id=<?= $reposicao['id'] ?>" class="btn btn-danger btn-sm">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Ao clicar no botão "Criar", limpar os campos do formulário
    function selecionarTipo(tipo) {
        document.getElementById('form-horas').style.display = (tipo === 'horas') ? 'block' : 'none';
        document.getElementById('form-modulos').style.display = (tipo === 'modulos') ? 'block' : 'none';
        document.getElementById('form-horas').reset();
        document.getElementById('form-modulos').reset();
    }

    // Preencher o formulário ao editar e abrir o modal
    function preencherFormulario(dados) {
        selecionarTipo(dados.tipo);
        
        if (dados.tipo === 'horas') {
            document.getElementById('reposicao-id-horas').value = dados.id;
            document.getElementById('disciplina-horas').value = dados.disciplina;
            document.getElementById('professor-horas').value = dados.professor;
            document.getElementById('modulo-horas').value = dados.modulo;
            document.getElementById('horas-horas').value = dados.horas;
            document.getElementById('justificativa-horas').value = dados.justificativa;
            document.getElementById('datahora_reposicao-horas').value = dados.datahora_reposicao;
        } else {
            document.getElementById('reposicao-id-modulos').value = dados.id;
            document.getElementById('disciplina-modulos').value = dados.disciplina;
            document.getElementById('professor-modulos').value = dados.professor;
            document.getElementById('modulo-modulos').value = dados.modulo;
            document.getElementById('justificativa-modulos').value = dados.justificativa;
            document.getElementById('datahora_reposicao-modulos').value = dados.datahora_reposicao;
        }

        var modal = new bootstrap.Modal(document.getElementById('modal-reposicao'));
        modal.show();
    }

    // Atualizar o status
    function updateStatus(id, checkbox) {
        var status = checkbox.checked ? 'concluido' : 'pendente';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '?action=toggleStatus&id=' + id + '&status=' + status, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                document.getElementById('status-text-' + id).textContent = status;
            }
        };
        xhr.send();
    }
</script>

</body>
</html>
