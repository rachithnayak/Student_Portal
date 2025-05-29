<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Education Portal</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="index.php">EduSphere</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $_SESSION['role']; ?>.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="signup.php">Signup</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <h1>Welcome to EduSphere</h1>
        <p>This is a platform for your futuristic growth.</p>
        <?php if (!isLoggedIn()): ?>
            <a href="signup.php" class="btn btn-primary">Get Started</a>
        <?php endif; ?>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>