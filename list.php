<?php

session_start();
require_once '../connection.php';

if (!$conn) {
    die("Database connection error.");
}

// جلب جميع الأسابيع
$weeks = [];
$result = mysqli_query($conn, "SELECT * FROM weeks ORDER BY start_date ASC, id ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $weeks[] = $row;
    }
    mysqli_free_result($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Course Breakdown</title>
</head>
<body>
    <header>
        <h1>Weekly Course Breakdown</h1>
        <p><a href="../student_home.php">← Back to Student Home</a></p>
    </header>

    <main>
        <section>
            <?php if (count($weeks) === 0): ?>
                <p>No weeks have been added yet.</p>
            <?php else: ?>
                <?php foreach ($weeks as $week): ?>
                    <article style="border:1px solid #ccc; padding:1rem; margin-bottom:1rem;">
                        <h2><?php echo htmlspecialchars($week['title']); ?></h2>
                        <p><strong>Starts on:</strong> <?php echo htmlspecialchars($week['start_date']); ?></p>
                        <p>
                            <?php
                            $desc = $week['description'] ?? '';
                            $short = mb_substr($desc, 0, 120);
                            echo htmlspecialchars($short);
                            if (mb_strlen($desc) > 120) {
                                echo '...';
                            }
                            ?>
                        </p>
                        <a href="week.php?id=<?php echo htmlspecialchars($week['id']); ?>">
                            View Details &amp; Discussion
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
