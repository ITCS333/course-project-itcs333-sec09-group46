<?php
// src/weekly/admin.php

session_start();

require_once '../connection.php'; 

if (!$conn) {
    die("Database connection error.");
}

// معالجة الفورم: إضافة / تعديل / حذف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title       = trim($_POST['title'] ?? '');
        $start_date  = $_POST['start_date'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $links_raw   = trim($_POST['links'] ?? '');

        if ($title !== '' && $start_date !== null) {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO weeks (title, start_date, description, links) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ssss", $title, $start_date, $description, $links_raw);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: admin.php');
        exit;
    }

    if ($action === 'update') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $start_date  = $_POST['start_date'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $links_raw   = trim($_POST['links'] ?? '');

        if ($id > 0 && $title !== '' && $start_date !== null) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE weeks
                 SET title = ?, start_date = ?, description = ?, links = ?
                 WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $start_date, $description, $links_raw, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: admin.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM weeks WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: admin.php');
        exit;
    }
}

// لو فيه Week نبي نعدّله
$editingWeek = null;
if (isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    if ($editId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM weeks WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $editId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $editingWeek = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);
    }
}

// جلب كل الأسابيع
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
    <title>Manage Weekly Breakdown</title>
</head>
<body>
    <header>
        <h1>Manage Weekly Breakdown</h1>
        <p><a href="../public/admin_portal.php">← Back to Admin Portal</a></p>
    </header>

    <main>
        <!-- فورم الإضافة / التعديل -->
        <section>
            <h2><?php echo $editingWeek ? 'Edit Week' : 'Add a New Week'; ?></h2>

            <form id="week-form" method="post" action="admin.php<?php echo $editingWeek ? '?id=' . htmlspecialchars($editingWeek['id']) : ''; ?>">
                <fieldset>
                    <legend>Weekly Details</legend>

                    <?php if ($editingWeek): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($editingWeek['id']); ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create">
                    <?php endif; ?>

                    <div>
                        <label for="week-title">Week Title</label><br>
                        <input
                            id="week-title"
                            name="title"
                            type="text"
                            required
                            placeholder="Week 1: Introduction to HTML"
                            value="<?php echo $editingWeek ? htmlspecialchars($editingWeek['title']) : ''; ?>"
                        >
                    </div>

                    <div>
                        <label for="week-start-date">Start Date</label><br>
                        <input
                            id="week-start-date"
                            name="start_date"
                            type="date"
                            required
                            value="<?php echo $editingWeek ? htmlspecialchars($editingWeek['start_date']) : ''; ?>"
                        >
                    </div>

                    <div>
                        <label for="week-description">Description &amp; Notes</label><br>
                        <textarea
                            id="week-description"
                            name="description"
                            rows="5"
                        ><?php echo $editingWeek ? htmlspecialchars($editingWeek['description']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label for="week-links">Links (one per line)</label><br>
                        <textarea
                            id="week-links"
                            name="links"
                            rows="3"
                        ><?php echo $editingWeek ? htmlspecialchars($editingWeek['links']) : ''; ?></textarea>
                    </div>

                    <button id="add-week" type="submit">
                        <?php echo $editingWeek ? 'Update Week' : 'Add Week'; ?>
                    </button>
                </fieldset>
            </form>
        </section>

        <!-- جدول عرض الأسابيع -->
        <section>
            <h2>Current Weekly Breakdown</h2>

            <?php if (count($weeks) === 0): ?>
                <p>No weeks found yet. Add the first week using the form above.</p>
            <?php else: ?>
                <table id="weeks-table" border="1" cellpadding="8">
                    <thead>
                        <tr>
                            <th>Week Title</th>
                            <th>Start Date</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weeks as $week): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($week['title']); ?></td>
                                <td><?php echo htmlspecialchars($week['start_date']); ?></td>
                                <td>
                                    <?php
                                    $desc = $week['description'] ?? '';
                                    $short = mb_substr($desc, 0, 80);
                                    echo htmlspecialchars($short);
                                    if (mb_strlen($desc) > 80) {
                                        echo '...';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="admin.php?id=<?php echo htmlspecialchars($week['id']); ?>">Edit</a>

                                    <form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Delete this week?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($week['id']); ?>">
                                        <button type="submit">Delete</button>
                                    </form>

                                    <a href="week.php?id=<?php echo htmlspecialchars($week['id']); ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
