<?php
require 'config.php';
if (isLoggedIn()) {
    header("Location: " . $_SESSION['role'] . ".php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
        $stmt->execute([$token, $expiry, $email]);

        // Simulate sending email (replace with actual mail function in production)
        $reset_link = "http://localhost/education-portal/reset_password.php?token=$token";
        echo "<p class='text-success'>Reset link: <a href='$reset_link'>$reset_link</a> (Check your email in production)</p>";
    } else {
        $error = "Email not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Forgot Password</h1>
        <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
            <a href="login.php" class="btn btn-link">Back to Login</a>
        </form>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>