<?php
require 'config.php';
redirectIfNotLoggedIn();

// Ensure uploads directory exists
$upload_dir = 'uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = filter_var($_POST['bio'], FILTER_SANITIZE_STRING);
    $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $stmt->execute([$bio, $_SESSION['user_id']]);

    if (isset($_FILES['achievement_file']) && $_FILES['achievement_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['achievement_file'];
        $file_path = "$upload_dir/" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $stmt = $pdo->prepare("INSERT INTO achievements (user_id, title, document_path) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $_POST['achievement_title'], $file_path]);
        } else {
            $upload_error = "Failed to upload achievement document.";
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC); // Corrected: fetch() on PDOStatement

// Fetch achievements
$stmt = $pdo->prepare("SELECT * FROM achievements WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container mt-5">
        <h1>Profile</h1>
        <?php if (isset($upload_error)) echo "<p class='text-danger'>$upload_error</p>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label>Bio</label>
                <textarea name="bio" class="form-control"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>
            <h2>Add Achievement</h2>
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="achievement_title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Document</label>
                <input type="file" name="achievement_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>

        <h2>Achievements</h2>
        <?php if (!empty($achievements)): ?>
            <ul>
                <?php foreach ($achievements as $achievement): ?>
                    <li><?php echo htmlspecialchars($achievement['title']); ?> - <a href="<?php echo $achievement['document_path']; ?>" target="_blank">View</a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No achievements added yet.</p>
        <?php endif; ?>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>