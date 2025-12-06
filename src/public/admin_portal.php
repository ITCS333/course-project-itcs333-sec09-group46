<?php
require_once "../src/auth.php";
require_admin();

require_once "../src/users.php";
$students = get_students($pdo);
?>
<!DOCTYPE html>
<html>
<head><title>Admin Portal</title></head>
<body>

<h1>Admin Portal</h1>
<h3>Welcome, <?= $_SESSION["user"]["name"] ?></h3>

<a href="students_create.php">Add New Student</a> |
<a href="admin_change_password.php">Change Password</a> |
<a href="../resources/admin.html">Manage Resources</a> |
<a href="../weekly/admin.php">Manage Weekly Breakdown</a> |  
<a href="logout.php">Logout</a>

<h2>Students</h2>
<table border="1" cellpadding="8">
<tr>
  <th>ID</th><th>Student ID</th><th>Name</th><th>Email</th><th>Actions</th>
</tr>

<?php foreach ($students as $s): ?>
<tr>
  <td><?= $s["id"] ?></td>
  <td><?= $s["student_id"] ?></td>
  <td><?= $s["name"] ?></td>
  <td><?= $s["email"] ?></td>
  <td>
    <a href="students_edit.php?id=<?= $s["id"] ?>">Edit</a> |
    <a href="students_delete.php?id=<?= $s["id"] ?>">Delete</a>
  </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
