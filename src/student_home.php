<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-5">

<h2>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</h2>

<p class="text-muted">This is your student homepage.</p>

<a href="index.php" class="btn btn-secondary">Home</a>
<a href="logout.php" class="btn btn-danger">Logout</a>

</body>
</html>
