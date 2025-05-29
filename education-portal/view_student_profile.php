<?php
require 'config.php';
redirectIfNotLoggedIn();
if (!isFaculty()) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['student_id'])) {
    header("Location: faculty.php");
    exit;
}

$student_id = filter_var($_GET['student_id'], FILTER_SANITIZE_NUMBER_INT);
$stmt = $pdo->prepare("SELECT name, email, bio FROM users WHERE id = ? AND role = 'student' AND approved = TRUE");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: faculty.php");
    exit;
}

$stmt = $pdo->prepare("SELECT title, document_path FROM achievements WHERE user_id = ?");
$stmt->execute([$student_id]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Profile</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container mt-5">
        <h1>Student Profile: <?php echo htmlspecialchars($student['name']); ?></h1>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
        <p><strong>Bio:</strong> <?php echo htmlspecialchars($student['bio'] ?? 'No bio available'); ?></p>

        <h2>Achievements</h2>
        <?php if (!empty($achievements)): ?>
            <ul>
                <?php foreach ($achievements as $achievement): ?>
                    <li><?php echo htmlspecialchars($achievement['title']); ?> - <a href="<?php echo $achievement['document_path']; ?>" target="_blank">View Document</a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No achievements available.</p>
        <?php endif; ?>

        <a href="faculty.php" class="btn btn-primary">Back to Faculty Panel</a>
    </div>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>