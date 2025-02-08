<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
    
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php echo file_get_contents('sidebar.html'); ?>

    <main>
        <h1>Eventos</h1>
        <p>Em breve, você poderá definir e gerenciar seus alarmes.</p>
    </main>

    <script src="assets/js/scripts.js"></script>
</body>
</html>
