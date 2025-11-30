<?php
require_once "../src/auth.php";
require_admin();
require_once "../src/users.php";

$id = $_GET["id"];
$user = get_user($pdo, $id);

if (!$user) die("Student not found.");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    update_student($pdo, $id,
        $_POST["student_id"],
        $_POST["name"],
        $_POST["email"]
    );
    header("Location: admin_portal.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Edit Student</title></head>
<body>

<h2>Edit Student</h2>

<form method="POST">
    Student ID: <input type="text" name="student_id" value="<?= $user["student_id"] ?>"><br><br>
    Name: <input type="text" name="name" value="<?= $user["name"] ?>"><br><br>
    Email: <input type="email" name="email" value="<?= $user["email"] ?>"><br><br>

    <button>Save</button>
</form>

</body>
</html>
