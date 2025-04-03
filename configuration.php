<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Obter dados do usuário
$stmt = $pdo->prepare("SELECT profile_picture, description FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$defaultImage = "assets/images/default.png";
// Verifica se a imagem existe fisicamente para evitar links quebrados
$profilePicture = (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) ? $user['profile_picture'] : $defaultImage;
$description = $user['description'] ?? 'Bem-vindo!';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $imgDir = "uploads/";

    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0777, true);
    }

    // Caso o usuário tenha enviado uma imagem recortada
    if (isset($_FILES['croppedImage']) && $_FILES['croppedImage']['size'] > 0) {
        $fileExt = 'png';  
        $fileName = uniqid("profile_", true) . "." . $fileExt;
        $imgPath = $imgDir . $fileName;

        $imageData = file_get_contents($_FILES['croppedImage']['tmp_name']);
        file_put_contents($imgPath, $imageData);

        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$imgPath, $userId]);
        $profilePicture = $imgPath;
    } elseif (isset($_POST['remove_picture'])) {
        // Remover a foto de perfil
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        $profilePicture = $defaultImage;
    } elseif (empty($user['profile_picture'])) {
        $profilePicture = $defaultImage;
    }

    // Atualiza a descrição se informada
    if (!empty($_POST['description'])) {
        $description = htmlspecialchars($_POST['description']);
        $stmt = $pdo->prepare("UPDATE users SET description = ? WHERE id = ?");
        $stmt->execute([$description, $userId]);
    }

    header("Location: configuration.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações</title>
    <!-- Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Cropper.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <style>
        #main-content {
            flex-grow: 1;
            margin-left: 82px;
            padding: 20px;
            width: calc(100% - 82px);
        }
        .profile-pic-container {
            text-align: center;
        }
        .profile-pic-container img {
            border-radius: 50%;
            cursor: pointer;
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 3px solid #007bff;
        }
        .modal-body {
            text-align: center;
        }
        .cropper-container {
            max-width: 100%;
        }
        #cropImage {
            max-width: 100%;
        }

    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main>
        <div id="main-content">
            <div class="container">
                <h2 class="mb-4">Configurações</h2>
                <form id="profileForm" action="configuration.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 profile-pic-container">
                            <img id="profileImage" src="<?= htmlspecialchars($profilePicture) ?>" alt="Foto de Perfil">
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="profile_picture">Alterar Foto de Perfil:</label>
                                <input type="file" class="form-control-file" name="profile_picture" accept="image/*" id="fileInput">
                            </div>
                            <div class="form-group">
                                <label for="description">Alterar Descrição:</label>
                                <input type="text" class="form-control" name="description" value="<?= htmlspecialchars($description) ?>" placeholder="Digite sua descrição">
                            </div>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            <button type="submit" name="remove_picture" class="btn btn-danger">Remover Foto</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Modal do Cropper -->
    <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajustar Imagem</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img id="cropImage" src="" alt="Imagem para cortar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="saveCrop" class="btn btn-primary">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        let cropper;
        const fileInput = document.getElementById('fileInput');
        const modal = $('#cropperModal');
        const cropImage = document.getElementById('cropImage');
        const profileImage = document.getElementById('profileImage');
        const saveCropBtn = document.getElementById('saveCrop');

        // Exibe a imagem padrão caso não haja foto definida
        document.addEventListener("DOMContentLoaded", function() {
            if (!profileImage.src || profileImage.src.includes("null")) {
                profileImage.src = "<?= $defaultImage ?>";
            }
        });

        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                cropImage.src = e.target.result;
                modal.modal('show');

                if (cropper) {
                    cropper.destroy();
                }

                cropper = new Cropper(cropImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                    responsive: true,
                });
            };

            if (file) {
                reader.readAsDataURL(file);
            }
        });

        saveCropBtn.addEventListener('click', function() {
            const croppedCanvas = cropper.getCroppedCanvas();

            croppedCanvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('croppedImage', blob);

                // Envia a imagem recortada via AJAX
                fetch('configuration.php', {
                    method: 'POST',
                    body: formData,
                }).then(response => response.text())
                  .then(() => {
                    // Atualiza a imagem do perfil com o recorte feito
                    profileImage.src = croppedCanvas.toDataURL();
                    modal.modal('hide');
                });
            });
        });
    </script>
</body>
</html>
