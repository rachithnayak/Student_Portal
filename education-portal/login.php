<?php
require 'config.php';
if (isLoggedIn()) {
    header("Location: " . $_SESSION['role'] . ".php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "No user found with email: '$email'. Please check your email or sign up.";
    } elseif (!password_verify($password, $user['password'])) {
        $error = "Invalid password for email: '$email'.<br>";
        $error .= "Stored password hash: " . $user['password'] . "<br>";
        $error .= "Provided password: '$password'";
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        header("Location: " . $user['role'] . ".php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container">
        <h1>Login</h1>
        <?php if (isset($error)): ?>
            <p class="text-danger"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['signup'])): ?>
            <p class="text-success">Signup successful! Please login.</p>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
            <a href="forgot_password.php" class="btn btn-link">Forgot Password?</a>
            <a href="signup.php" class="btn btn-link">Sign Up</a>
        </form>
        <div class="footer-links">
            <a href="contact_us.php" class="btn btn-link">Contact Us</a>
            <a href="terms_conditions.php" class="btn btn-link">Terms and Conditions</a>
        </div>
    </div>
</body>
</html>