<?php
require 'config.php';
redirectIfNotLoggedIn();
if (!isStudent()) {
    header("Location: login.php");
    exit;
}

if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['assignment_file'];
    $file_path = "uploads/" . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO assignments (title, file_path, submitted_by) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['assignment_title'], $file_path, $_SESSION['user_id']]);
    }
}

$syllabi = $pdo->query("SELECT s.*, u.name FROM syllabus s JOIN users u ON s.uploaded_by = u.id")->fetchAll(PDO::FETCH_ASSOC);
$announcements = $pdo->query("SELECT a.*, u.name FROM announcements a JOIN users u ON a.created_by = u.id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Panel</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container student-panel">
        <h1>Student Panel</h1>
        <h2>Upload Assignment</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="assignment_title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>File</label>
                <input type="file" name="assignment_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>

        <h2>Syllabus</h2>
        <ul>
            <?php foreach ($syllabi as $syllabus): ?>
                <li><?php echo htmlspecialchars($syllabus['title']); ?> - <a href="<?php echo $syllabus['file_path']; ?>" target="_blank">Download</a></li>
            <?php endforeach; ?>
        </ul>

        <h2>Announcements</h2>
        <ul>
            <?php foreach ($announcements as $announcement): ?>
                <li><?php echo htmlspecialchars($announcement['content']); ?> (by <?php echo htmlspecialchars($announcement['name']); ?> on <?php echo $announcement['created_at']; ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>