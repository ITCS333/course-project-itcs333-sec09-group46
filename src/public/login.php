<?php
require_once "../src/auth.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (login_user($pdo, $_POST["email"], $_POST["password"])) {
        header("Location: admin_portal.php");
        exit;
    }
    $error = "Invalid email or password.";
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
<h2>Login</h2>

<?php if ($error): ?>
<p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    Email:<br>
    <input type="email" name="email" required><br><br>

    Password:<br>
    <input type="password" name="password" required><br><br>

    <button>Login</button>
</form>

</body>
</html>
