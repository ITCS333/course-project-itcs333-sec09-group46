<?php
// ============================================================================
// HEADERS (JSON + CORS)
// ============================================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================
require_once __DIR__ . "/../../includes/connection.php"; // عدّلي المسار حسب مشروعك

$db = $conn; // من ملف connection.php

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents("php://input"), true);
$query = $_GET;

// ============================================================================
// GET ALL STUDENTS
// ============================================================================
function getStudents($db)
{
    $search = $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? "name";
    $order = $_GET['order'] ?? "asc";

    // تأمين المدخلات
    $allowedSort = ["name", "student_id", "email"];
    if (!in_array($sort, $allowedSort)) $sort = "name";

    $allowedOrder = ["asc", "desc"];
    if (!in_array($order, $allowedOrder)) $order = "asc";

    $sql = "SELECT id, student_id, name, email, created_at FROM students";

    if ($search) {
        $sql .= " WHERE name LIKE :s OR student_id LIKE :s OR email LIKE :s";
    }

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    if ($search) {
        $s = "%{$search}%";
        $stmt->bindParam(":s", $s);
    }

    $stmt->execute();
    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ============================================================================
// GET STUDENT BY ID
// ============================================================================
function getStudentById($db, $studentId)
{
    $stmt = $db->prepare("SELECT id, student_id, name, email, created_at FROM students WHERE student_id = :id");
    $stmt->bindParam(":id", $studentId);
    $stmt->execute();

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse(["error" => "Student not found"], 404);
    }

    sendResponse(["success" => true, "data" => $student]);
}

// ============================================================================
// CREATE STUDENT
// ============================================================================
function createStudent($db, $data)
{
    if (!isset($data["student_id"], $data["name"], $data["email"], $data["password"])) {
        sendResponse(["error" => "Missing fields"], 400);
    }

    $student_id = sanitizeInput($data["student_id"]);
    $name = sanitizeInput($data["name"]);
    $email = sanitizeInput($data["email"]);

    if (!validateEmail($email)) {
        sendResponse(["error" => "Invalid email format"], 400);
    }

    // Check duplicates
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid OR email = :email");
    $stmt->execute([":sid" => $student_id, ":email" => $email]);

    if ($stmt->rowCount() > 0) {
        sendResponse(["error" => "Student ID or email already exists"], 409);
    }

    $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO students (student_id, name, email, password)
        VALUES (:sid, :name, :email, :pass)
    ");

    $ok = $stmt->execute([
        ":sid" => $student_id,
        ":name" => $name,
        ":email" => $email,
        ":pass" => $hashedPassword
    ]);

    if (!$ok) {
        sendResponse(["error" => "Failed to create student"], 500);
    }

    sendResponse(["success" => true, "message" => "Student created"], 201);
}

// ============================================================================
// UPDATE STUDENT
// ============================================================================
function updateStudent($db, $data)
{
    if (!isset($data["student_id"])) {
        sendResponse(["error" => "student_id is required"], 400);
    }

    $sid = $data["student_id"];

    // Check student exists
    $check = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $check->execute([":sid" => $sid]);

    if ($check->rowCount() == 0) {
        sendResponse(["error" => "Student not found"], 404);
    }

    $fields = [];
    $params = [":sid" => $sid];

    if (isset($data["name"])) {
        $fields[] = "name = :name";
        $params[":name"] = sanitizeInput($data["name"]);
    }

    if (isset($data["email"])) {
        if (!validateEmail($data["email"])) {
            sendResponse(["error" => "Invalid email"], 400);
        }
        $fields[] = "email = :email";
        $params[":email"] = sanitizeInput($data["email"]);
    }

    if (empty($fields)) {
        sendResponse(["error" => "No fields to update"], 400);
    }

    $sql = "UPDATE students SET " . implode(", ", $fields) . " WHERE student_id = :sid";
    $stmt = $db->prepare($sql);

    if ($stmt->execute($params)) {
        sendResponse(["success" => true, "message" => "Student updated"]);
    } else {
        sendResponse(["error" => "Update failed"], 500);
    }
}

// ============================================================================
// DELETE STUDENT
// ============================================================================
function deleteStudent($db, $studentId)
{
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $stmt->execute([":sid" => $studentId]);

    if ($stmt->rowCount() == 0) {
        sendResponse(["error" => "Student not found"], 404);
    }

    $delete = $db->prepare("DELETE FROM students WHERE student_id = :sid");
    $ok = $delete->execute([":sid" => $studentId]);

    if ($ok) {
        sendResponse(["success" => true, "message" => "Student deleted"]);
    } else {
        sendResponse(["error" => "Delete failed"], 500);
    }
}

// ============================================================================
// CHANGE PASSWORD
// ============================================================================
function changePassword($db, $data)
{
    if (!isset($data["student_id"], $data["current_password"], $data["new_password"])) {
        sendResponse(["error" => "Missing fields"], 400);
    }

    if (strlen($data["new_password"]) < 8) {
        sendResponse(["error" => "New password must be at least 8 characters"], 400);
    }

    $sid = $data["student_id"];

    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :sid");
    $stmt->execute([":sid" => $sid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data["current_password"], $user["password"])) {
        sendResponse(["error" => "Incorrect current password"], 401);
    }

    $newHash = password_hash($data["new_password"], PASSWORD_DEFAULT);

    $update = $db->prepare("UPDATE students SET password = :pass WHERE student_id = :sid");
    $ok = $update->execute([":pass" => $newHash, ":sid" => $sid]);

    if ($ok) {
        sendResponse(["success" => true, "message" => "Password updated"]);
    }

    sendResponse(["error" => "Failed to update password"], 500);
}

// ============================================================================
// ROUTER
// ============================================================================
try {
    if ($method === "GET") {
        if (isset($_GET["student_id"])) {
            getStudentById($db, $_GET["student_id"]);
        } else {
            getStudents($db);
        }
    }

    elseif ($method === "POST") {
        if (isset($_GET["action"]) && $_GET["action"] === "change_password") {
            changePassword($db, $body);
        } else {
            createStudent($db, $body);
        }
    }

    elseif ($method === "PUT") {
        updateStudent($db, $body);
    }

    elseif ($method === "DELETE") {
        $sid = $_GET["student_id"] ?? ($body["student_id"] ?? null);
        if (!$sid) sendResponse(["error" => "student_id required"], 400);
        deleteStudent($db, $sid);
    }

    else {
        sendResponse(["error" => "Method not allowed"], 405);
    }

} catch (Exception $e) {
    sendResponse(["error" => "Server error", "details" => $e->getMessage()], 500);
}

// ============================================================================
// HELPERS
// ============================================================================
function sendResponse($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>
