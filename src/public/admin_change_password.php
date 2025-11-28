<?php
require_once "../src/auth.php";
require_admin();
require_once "../src/users.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    change_admin_password($pdo, $_SESSION["user"]["id"], $_POST["password"]);
    $message = "Password updated successfully!";
}

?>
<!DOCTYPE html>
<html>
<head><title>Change Password</title></head>
<body>

<h2>Change Admin Password</h2>

<?php if ($message): ?>
<p style="color:green;"><?= $message ?></p>
<?php endif; ?>

<form method="POST">
    New Password:<br>
    <input type="password" name="password" required><br><br>
    <button>Update</button>
</form>

</body>
</html>
