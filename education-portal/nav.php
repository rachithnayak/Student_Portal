<nav class="navbar">
    <a class="navbar-brand" href="index.php">EduSphere</a>
    <ul class="navbar-nav ml-auto">
        <?php if (isLoggedIn()): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo $_SESSION['role']; ?>.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
            <?php if (isAdmin()): ?>
                <li class="nav-item"><a class="nav-link" href="admin_panel.php">Admin Panel</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="signup.php">Signup</a></li>
        <?php endif; ?>
    </ul>
</nav>