<?php
require_once __DIR__ . '/config.php';

function login_user($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            "id" => $user["id"],
            "name" => $user["name"],
            "role" => $user["role"],
            "email" => $user["email"]
        ];
        return true;
    }
    return false;
}

function require_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION["user"]["role"] !== "teacher") {
        die("403 - Unauthorized");
    }
}

function logout_user() {
    session_destroy();
    header("Location: login.php");
    exit;
}
