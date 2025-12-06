<?php

session_start();
require_once '../connection.php';

if (!$conn) {
    die("Database connection error.");
}

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$weekId = (int)$_GET['id'];
if ($weekId <= 0) {
    header('Location: list.php');
    exit;
}

// إضافة تعليق
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');

    if ($comment !== '') {
        $author_name = 'Anonymous';
        $user_type   = 'guest';

        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            $author_name = $_SESSION['username'] ?? 'Admin';
            $user_type   = 'admin';
        } elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student') {
            $author_name = $_SESSION['student_name'] ?? ($_SESSION['student_id'] ?? 'Student');
            $user_type   = 'student';
        }

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO week_comments (week_id, author_name, user_type, comment_text)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "isss", $weekId, $author_name, $user_type, $comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: week.php?id=' . $weekId);
        exit;
    }
}

// جلب بيانات الأسبوع
$week = null;
$stmt = mysqli_prepare($conn, "SELECT * FROM weeks WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $weekId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$week = mysqli_fetch_assoc($result);
mysqli_free_result($result);
mysqli_stmt_close($stmt);

if (!$week) {
    echo "Week not found.";
    exit;
}

// جلب التعليقات
$comments = [];
$stmt = mysqli_prepare(
    $conn,
    "SELECT author_name, user_type, comment_text, created_at
     FROM week_comments
     WHERE week_id = ?
     ORDER BY created_at ASC, id ASC"
);
mysqli_stmt_bind_param($stmt, "i", $weekId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $comments[] = $row;
}
mysqli_free_result($result);
mysqli_stmt_close($stmt);

// تجهيز اللينكات
$links = [];
if (!empty($week['links'])) {
    $lines = preg_split('/\r\n|\r|\n/', $week['links']);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $links[] = $line;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($week['title']); ?> - Week Details</title>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($week['title']); ?></h1>
        <p><a href="list.php">← Back to Weekly Breakdown</a></p>
    </header>

    <main>
        <section>
            <p><strong>Start Date:</strong> <?php echo htmlspecialchars($week['start_date']); ?></p>

            <?php if (!empty($week['description'])): ?>
                <h2>Description &amp; Notes</h2>
                <p><?php echo nl2br(htmlspecialchars($week['description'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($links)): ?>
                <h2>Resources &amp; Exercises</h2>
                <ul>
                    <?php foreach ($links as $url): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank">
                                <?php echo htmlspecialchars($url); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <hr>

        <section>
            <h2>Discussion</h2>

            <?php if (count($comments) === 0): ?>
                <p>No comments yet. Be the first to ask a question!</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($comments as $c): ?>
                        <li style="margin-bottom: 0.75rem;">
                            <strong><?php echo htmlspecialchars($c['author_name']); ?></strong>
                            (<?php echo htmlspecialchars($c['user_type']); ?>)
                            <br>
                            <small><?php echo htmlspecialchars($c['created_at']); ?></small>
                            <br>
                            <?php echo nl2br(htmlspecialchars($c['comment_text'])); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php
            $logged_in = isset($_SESSION['user_type']) &&
                         in_array($_SESSION['user_type'], ['admin', 'student'], true);
            ?>

            <?php if ($logged_in): ?>
                <h3>Add a Comment</h3>
                <form method="post" action="week.php?id=<?php echo $weekId; ?>">
                    <textarea name="comment" rows="4" required></textarea><br>
                    <button type="submit">Post Comment</button>
                </form>
            <?php else: ?>
                <p>You must be logged in as a student or admin to post comments.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
