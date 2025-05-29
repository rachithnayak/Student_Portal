<?php
require 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) {
    header("Location: login.php");
    exit;
}

// Ensure uploads directory exists
$upload_dir = 'uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle syllabus upload
if (isset($_FILES['syllabus_file']) && $_FILES['syllabus_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['syllabus_file'];
    $file_path = "$upload_dir/" . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO syllabus (title, file_path, uploaded_by) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['syllabus_title'], $file_path, $_SESSION['user_id']]);
    } else {
        $upload_error = "Failed to upload syllabus.";
    }
}

// Handle announcement creation
if (isset($_POST['create_announcement'])) {
    $stmt = $pdo->prepare("INSERT INTO announcements (content, created_by) VALUES (?, ?)");
    $stmt->execute([$_POST['announcement'], $_SESSION['user_id']]);
}

// Handle announcement editing
if (isset($_POST['edit_announcement'])) {
    $stmt = $pdo->prepare("UPDATE announcements SET content = ? WHERE id = ? AND created_by = ?");
    $stmt->execute([$_POST['announcement_content'], $_POST['announcement_id'], $_SESSION['user_id']]);
}

// Fetch data
$assignments = $pdo->query("SELECT a.*, u.name FROM assignments a JOIN users u ON a.submitted_by = u.id")->fetchAll(PDO::FETCH_ASSOC);
$notifications = $pdo->query("SELECT * FROM notifications WHERE recipient_role IN ('faculty', 'all') ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Fix: Use prepare() and execute() for parameterized query
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE created_by = ?");
$stmt->execute([$_SESSION['user_id']]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$students = $pdo->query("SELECT id, name, email, bio FROM users WHERE role = 'student' AND approved = TRUE")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Faculty Panel</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container faculty-panel">
        <h1>Faculty Panel</h1>

        <!-- Notifications -->
        <h2>Notifications</h2>
        <?php if (!empty($notifications)): ?>
            <ul>
                <?php foreach ($notifications as $notification): ?>
                    <li><?php echo htmlspecialchars($notification['message']); ?> (<?php echo $notification['created_at']; ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No notifications available.</p>
        <?php endif; ?>

        <!-- Upload Syllabus -->
        <h2>Upload Syllabus</h2>
        <?php if (isset($upload_error)) echo "<p class='text-danger'>$upload_error</p>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="syllabus_title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>File</label>
                <input type="file" name="syllabus_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>

        <!-- Create Announcement -->
        <h2>Create Announcement</h2>
        <form method="POST">
            <div class="mb-3">
                <textarea name="announcement" class="form-control" placeholder="Enter announcement" required></textarea>
            </div>
            <button type="submit" name="create_announcement" class="btn btn-primary">Post</button>
        </form>

        <!-- Edit Announcements -->
        <h2>Edit Your Announcements</h2>
        <?php if (!empty($announcements)): ?>
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Content</th><th>Created At</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                        <tr>
                            <td><?php echo $announcement['id']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                    <textarea name="announcement_content" class="form-control"><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                            </td>
                            <td><?php echo $announcement['created_at']; ?></td>
                            <td>
                                    <button type="submit" name="edit_announcement" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No announcements created yet.</p>
        <?php endif; ?>

        <!-- Student Assignments -->
        <h2>Student Assignments</h2>
        <?php if (!empty($assignments)): ?>
            <table class="table">
                <thead>
                    <tr><th>Title</th><th>Submitted By</th><th>File</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['name']); ?></td>
                            <td><a href="<?php echo $assignment['file_path']; ?>" target="_blank">Download</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments submitted yet.</p>
        <?php endif; ?>

        <!-- View Student Profiles -->
        <h2>View Student Profiles</h2>
        <?php if (!empty($students)): ?>
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Bio</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['bio'] ?? 'No bio available'); ?></td>
                            <td>
                                <a href="view_student_profile.php?student_id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm">View Full Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No approved students found.</p>
        <?php endif; ?>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>