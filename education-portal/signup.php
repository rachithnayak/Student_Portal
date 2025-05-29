<?php
require 'config.php';
if (isLoggedIn()) {
    header("Location: " . $_SESSION['role'] . ".php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = in_array($_POST['role'], ['student', 'faculty']) ? $_POST['role'] : 'student';

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
        header("Location: login.php?signup=success");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . ($e->getCode() == 23000 ? "Email already exists" : $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signup</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Signup</h1>
        <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Signup</button>
            <a href="login.php" class="btn btn-link">Already have an account?</a>
        </form>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>