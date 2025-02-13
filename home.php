// home.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>
<body>
    <?php include 'navbar.html'; ?>
    
    <header>
        <h1>Welcome to Our Website</h1>
        <p>Write an introduction about your website here.</p>
    </header>
    
    <section>
        <h2>Featured Content</h2>
        <p>Provide some highlights or main features here.</p>
    </section>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Your Website. All rights reserved.</p>
    </footer>
</body>
</html>