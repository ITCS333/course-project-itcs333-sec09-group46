<?php
require_once "../src/auth.php";
require_admin();
require_once "../src/users.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    create_student($pdo,
        $_POST["student_id"],
        $_POST["name"],
        $_POST["email"],
        $_POST["password"]
    );
    header("Location: admin_portal.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Add Student</title></head>
<body>

<h2>Add New Student</h2>

<form method="POST">
    Student ID: <input type="text" name="student_id" required><br><br>
    Name: <input type="text" name="name" required><br><br>
    Email: <input type="email" name="email" required><br><br>
    Default Password: <input type="text" name="password" required><br><br>

    <button>Add Student</button>
</form>

</body>
</html>
