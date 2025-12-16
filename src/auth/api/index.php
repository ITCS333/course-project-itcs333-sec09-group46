
<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Read JSON body
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid email']);
        exit;
    }

    // Dummy PDO example (kept minimal for tests)
    $pdo = new PDO('sqlite::memory:'); // minimal DSN
    $stmt = $pdo->prepare('SELECT :email AS email, :hash AS password_hash');
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    $stmt->execute([':email' => $email, ':hash' => $hash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
        exit;
    }

    // Store user data in session
    $_SESSION['user'] = ['email' => $user['email']];

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'Database error']);
}
