<?php
require 'config.php';
redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

// Manage Users
if (isset($_POST['add_user'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = in_array($_POST['role'], ['faculty', 'student']) ? $_POST['role'] : 'student';
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, approved) VALUES (?, ?, ?, ?, TRUE)");
    $stmt->execute([$name, $email, $password, $role]);
}

if (isset($_POST['update_user'])) {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$_POST['name'], $_POST['email'], $_POST['user_id']]);
}

if (isset($_POST['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
}

// Approve/Reject Student Profiles
if (isset($_POST['approve'])) {
    $stmt = $pdo->prepare("UPDATE users SET approved = TRUE WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
    sendNotification($pdo, "Your profile has been approved.", 'student', $_SESSION['user_id']);
}

if (isset($_POST['reject'])) {
    $stmt = $pdo->prepare("UPDATE users SET approved = FALSE WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
    sendNotification($pdo, "Your profile has been rejected.", 'student', $_SESSION['user_id']);
}

// Approve/Reject Uploaded Documents (Achievements)
if (isset($_POST['approve_document'])) {
    $stmt = $pdo->prepare("UPDATE achievements SET document_path = ? WHERE id = ?");
    $stmt->execute([$_POST['document_path'], $_POST['achievement_id']]); // Already uploaded, just marking as approved
}

if (isset($_POST['reject_document'])) {
    $stmt = $pdo->prepare("DELETE FROM achievements WHERE id = ?");
    $stmt->execute([$_POST['achievement_id']]);
}

// Manage Syllabus
if (isset($_FILES['syllabus_file']) && $_FILES['syllabus_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['syllabus_file'];
    $file_path = "uploads/" . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO syllabus (title, file_path, uploaded_by) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['syllabus_title'], $file_path, $_SESSION['user_id']]);
    }
}

if (isset($_POST['update_syllabus'])) {
    $stmt = $pdo->prepare("UPDATE syllabus SET title = ? WHERE id = ?");
    $stmt->execute([$_POST['syllabus_title'], $_POST['syllabus_id']]);
}

if (isset($_POST['delete_syllabus'])) {
    $stmt = $pdo->prepare("DELETE FROM syllabus WHERE id = ?");
    $stmt->execute([$_POST['syllabus_id']]);
}

// Manage Announcements
if (isset($_POST['create_announcement'])) {
    $stmt = $pdo->prepare("INSERT INTO announcements (content, created_by) VALUES (?, ?)");
    $stmt->execute([$_POST['announcement'], $_SESSION['user_id']]);
}

if (isset($_POST['update_announcement'])) {
    $stmt = $pdo->prepare("UPDATE announcements SET content = ? WHERE id = ?");
    $stmt->execute([$_POST['announcement'], $_POST['announcement_id']]);
}

if (isset($_POST['delete_announcement'])) {
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$_POST['announcement_id']]);
}

// Send Notifications
if (isset($_POST['send_notification'])) {
    sendNotification($pdo, $_POST['notification_message'], $_POST['recipient_role'], $_SESSION['user_id']);
}

// Manage System Settings
if (isset($_POST['update_setting'])) {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$_POST['setting_key'], $_POST['setting_value'], $_POST['setting_value']]);
}

// Fetch Data
$users = $pdo->query("SELECT * FROM users WHERE role != 'admin'")->fetchAll(PDO::FETCH_ASSOC);
$achievements = $pdo->query("SELECT a.*, u.name FROM achievements a JOIN users u ON a.user_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
$syllabi = $pdo->query("SELECT * FROM syllabus")->fetchAll(PDO::FETCH_ASSOC);
$announcements = $pdo->query("SELECT * FROM announcements")->fetchAll(PDO::FETCH_ASSOC);
$notifications = $pdo->query("SELECT * FROM notifications")->fetchAll(PDO::FETCH_ASSOC);
$activities = $pdo->query("SELECT id, name, role, last_login FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container mt-5">
        <h1>Admin Panel</h1>

        <!-- Manage Users -->
        <h2>Manage Users</h2>
        <form method="POST" class="mb-3">
            <div class="row">
                <div class="col"><input type="text" name="name" class="form-control" placeholder="Name" required></div>
                <div class="col"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                <div class="col"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                <div class="col">
                    <select name="role" class="form-control">
                        <option value="faculty">Faculty</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="col"><button type="submit" name="add_user" class="btn btn-primary">Add User</button></div>
            </div>
        </form>
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['role']; ?></td>
                        <td><?php echo $user['approved'] ? 'Approved' : 'Pending'; ?></td>
                        <td>
                            <?php if ($user['role'] === 'student' && !$user['approved']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                                    <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="form-control d-inline w-auto">
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control d-inline w-auto">
                                <button type="submit" name="update_user" class="btn btn-primary btn-sm">Update</button>
                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Approve/Reject Uploaded Documents -->
        <h2>Manage Student Documents</h2>
        <table class="table">
            <thead><tr><th>ID</th><th>Student</th><th>Title</th><th>Document</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($achievements as $achievement): ?>
                    <tr>
                        <td><?php echo $achievement['id']; ?></td>
                        <td><?php echo htmlspecialchars($achievement['name']); ?></td>
                        <td><?php echo htmlspecialchars($achievement['title']); ?></td>
                        <td><a href="<?php echo $achievement['document_path']; ?>" target="_blank">View</a></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="achievement_id" value="<?php echo $achievement['id']; ?>">
                                <input type="hidden" name="document_path" value="<?php echo $achievement['document_path']; ?>">
                                <button type="submit" name="approve_document" class="btn btn-success btn-sm">Approve</button>
                                <button type="submit" name="reject_document" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Manage Syllabus -->
        <h2>Manage Syllabus</h2>
        <form method="POST" enctype="multipart/form-data" class="mb-3">
            <div class="row">
                <div class="col"><input type="text" name="syllabus_title" class="form-control" placeholder="Title" required></div>
                <div class="col"><input type="file" name="syllabus_file" class="form-control" required></div>
                <div class="col"><button type="submit" class="btn btn-primary">Upload</button></div>
            </div>
        </form>
        <table class="table">
            <thead><tr><th>ID</th><th>Title</th><th>File</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($syllabi as $syllabus): ?>
                    <tr>
                        <td><?php echo $syllabus['id']; ?></td>
                        <td><?php echo htmlspecialchars($syllabus['title']); ?></td>
                        <td><a href="<?php echo $syllabus['file_path']; ?>" target="_blank">Download</a></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="syllabus_id" value="<?php echo $syllabus['id']; ?>">
                                <input type="text" name="syllabus_title" value="<?php echo htmlspecialchars($syllabus['title']); ?>" class="form-control d-inline w-auto">
                                <button type="submit" name="update_syllabus" class="btn btn-primary btn-sm">Update</button>
                                <button type="submit" name="delete_syllabus" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Manage Announcements -->
        <h2>Manage Announcements</h2>
        <form method="POST" class="mb-3">
            <div class="row">
                <div class="col"><textarea name="announcement" class="form-control" placeholder="Announcement" required></textarea></div>
                <div class="col"><button type="submit" name="create_announcement" class="btn btn-primary">Create</button></div>
            </div>
        </form>
        <table class="table">
            <thead><tr><th>ID</th><th>Content</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($announcements as $announcement): ?>
                    <tr>
                        <td><?php echo $announcement['id']; ?></td>
                        <td><?php echo htmlspecialchars($announcement['content']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <textarea name="announcement" class="form-control d-inline w-auto"><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                                <button type="submit" name="update_announcement" class="btn btn-primary btn-sm">Update</button>
                                <button type="submit" name="delete_announcement" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Send Notifications -->
        <h2>Send Notifications</h2>
        <form method="POST" class="mb-3">
            <div class="row">
                <div class="col"><textarea name="notification_message" class="form-control" placeholder="Notification Message" required></textarea></div>
                <div class="col">
                    <select name="recipient_role" class="form-control">
                        <option value="faculty">Faculty</option>
                        <option value="student">Students</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="col"><button type="submit" name="send_notification" class="btn btn-primary">Send</button></div>
            </div>
        </form>

        <!-- View Activities -->
        <h2>Recent Activities</h2>
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Last Login</th></tr></thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?php echo $activity['id']; ?></td>
                        <td><?php echo htmlspecialchars($activity['name']); ?></td>
                        <td><?php echo $activity['role']; ?></td>
                        <td><?php echo $activity['last_login']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Manage System Settings -->
        <h2>System Settings</h2>
        <form method="POST" class="mb-3">
            <div class="row">
                <div class="col"><input type="text" name="setting_key" class="form-control" placeholder="Key (e.g., site_title)" required></div>
                <div class="col"><input type="text" name="setting_value" class="form-control" placeholder="Value" required></div>
                <div class="col"><button type="submit" name="update_setting" class="btn btn-primary">Update</button></div>
            </div>
        </form>
        <table class="table">
            <thead><tr><th>Key</th><th>Value</th></tr></thead>
            <tbody>
                <?php foreach ($settings as $setting): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($setting['setting_key']); ?></td>
                        <td><?php echo htmlspecialchars($setting['setting_value']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>