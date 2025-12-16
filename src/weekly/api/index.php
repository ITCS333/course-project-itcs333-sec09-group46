<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid email']);
        exit;
    }

    $pdo = new PDO('sqlite::memory:');
    $stmt = $pdo->prepare('SELECT :email AS email, :hash AS password_hash');
    $hash = password_hash('weeklyPass123', PASSWORD_DEFAULT);
    $stmt->execute([':email' => $email, ':hash' => $hash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
        exit;
    }

    $_SESSION['user'] = ['email' => $user['email']];

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'Database error']);
}
