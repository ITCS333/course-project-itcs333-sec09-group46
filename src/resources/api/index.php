<?php
/**
 * Course Resources API
 * 
 * Handles CRUD operations for:
 *  - resources          (table: resources)
 *  - resource comments  (table: comments_resource)
 * 
 * Uses:
 *  - PDO connection from /includes/connection.php (Replit)
 *  - Sessions for auth (expects $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['is_admin'])
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

// If your frontend and backend are same origin, CORS is not strictly needed.
// If you ever need it, you can uncomment these:
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight (if CORS is enabled)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -----------------------------------------------------------------------------
// DB CONNECTION (PDO from includes/connection.php)
// -----------------------------------------------------------------------------
require_once __DIR__ . '/../../includes/connection.php';

/**
 * On Replit, includes/connection.php defines:
 *   $conn = new PDO(...);
 *   $db   = $conn;
 * We'll use $db as our PDO instance.
 */
if (!isset($db) || !($db instanceof PDO)) {
    sendResponse(['success' => false, 'message' => 'Database connection not available'], 500);
}

// -----------------------------------------------------------------------------
// AUTH CHECKS – support both student_home.php and admin portal session styles
// -----------------------------------------------------------------------------

$userId   = null;
$userName = null;
$userRole = null;
$isAdmin  = false;

// Pattern 1: student_home.php
//   $_SESSION['user_id'], $_SESSION['role'], $_SESSION['username']
if (isset($_SESSION['user_id'])) {
    $userId   = $_SESSION['user_id'];
    $userName = $_SESSION['username'] ?? null;
    $userRole = $_SESSION['role'] ?? null;

    if ($userRole === 'admin') {
        $isAdmin = true;
    }
}

// Pattern 2: admin portal using $_SESSION['user'] array
//   $_SESSION['user']['id'], $_SESSION['user']['name'], $_SESSION['user']['is_admin']
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $userId   = $_SESSION['user']['id']   ?? $userId;
    $userName = $_SESSION['user']['name'] ?? $userName;

    if (isset($_SESSION['user']['is_admin'])) {
        $isAdmin = !empty($_SESSION['user']['is_admin']);
    } else {
        // If this admin portal doesn't store is_admin, we still treat this as admin area
        $isAdmin = true;
    }
}

$isLoggedIn = !empty($userId);

if (!$isLoggedIn) {
    sendResponse(['success' => false, 'message' => 'Not authenticated'], 401);
}


// -----------------------------------------------------------------------------
// REQUEST PARSING
// -----------------------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];

// Query params
$action      = $_GET['action']      ?? null;
$id          = $_GET['id']          ?? null;         // resource id (GET/DELETE)
$resourceId  = $_GET['resource_id'] ?? null;         // comments for resource
$commentId   = $_GET['comment_id']  ?? null;         // delete comment

// Decode JSON body if present
$rawBody = file_get_contents('php://input');
$body    = $rawBody ? json_decode($rawBody, true) : [];
if (!is_array($body)) {
    $body = [];
}

// -----------------------------------------------------------------------------
// MAIN ROUTER
// -----------------------------------------------------------------------------
try {

    if ($method === 'GET') {

        if ($action === 'comments') {
            // GET ?action=comments&resource_id=#
            getCommentsByResourceId($db, $resourceId);
        } elseif (!empty($id)) {
            // GET ?id=#
            getResourceById($db, $id);
        } else {
            // GET (no id) → list all resources
            getAllResources($db);
        }

    } elseif ($method === 'POST') {

        if ($action === 'comment') {
            // POST ?action=comment
            createComment($db, $body);
        } else {
            // POST → create resource (admin only)
            if (!$isAdmin) {
                sendResponse(['success' => false, 'message' => 'Admin access required'], 403);
            }
            createResource($db, $body);
        }

    } elseif ($method === 'PUT') {
        // PUT → update resource (admin only)
        if (!$isAdmin) {
            sendResponse(['success' => false, 'message' => 'Admin access required'], 403);
        }
        updateResource($db, $body);

    } elseif ($method === 'DELETE') {

        if ($action === 'delete_comment') {
            // DELETE ?action=delete_comment&comment_id=#
            if (!$isAdmin) {
                sendResponse(['success' => false, 'message' => 'Admin access required'], 403);
            }
            deleteComment($db, $commentId);
        } else {
            // DELETE ?id=#
            if (!$isAdmin) {
                sendResponse(['success' => false, 'message' => 'Admin access required'], 403);
            }
            deleteResource($db, $id);
        }

    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed'
        ], 405);
    }

} catch (PDOException $e) {
    // Log internally, but don’t expose details to client
    error_log('Resources API PDO Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred'
    ], 500);

} catch (Exception $e) {
    error_log('Resources API General Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Server error occurred'
    ], 500);
}

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

function getAllResources(PDO $db)
{
    $search = $_GET['search'] ?? null;
    $sort   = $_GET['sort']   ?? 'created_at';
    $order  = $_GET['order']  ?? 'desc';

    // Allow only safe sort fields
    $allowedSort = ['title', 'created_at'];
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'created_at';
    }

    // Allow only ASC/DESC
    $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

    $sql    = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    if (!empty($search)) {
        $sql       .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $resources = $stmt->fetchAll();

    sendResponse([
        'success' => true,
        'data'    => $resources
    ]);
}

function getResourceById(PDO $db, $resourceId)
{
    if (empty($resourceId) || !ctype_digit((string)$resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource id'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT id, title, description, link, created_at
        FROM resources
        WHERE id = :id
    ");
    $stmt->execute([':id' => $resourceId]);

    $resource = $stmt->fetch();

    if (!$resource) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found'
        ], 404);
    }

    sendResponse([
        'success' => true,
        'data'    => $resource
    ]);
}

function createResource(PDO $db, array $data)
{
    $requiredCheck = validateRequiredFields($data, ['title', 'link']);
    if (!$requiredCheck['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields',
            'missing' => $requiredCheck['missing']
        ], 400);
    }

    $title       = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link        = trim($data['link']);

    if (!validateUrl($link)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid URL format'
        ], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO resources (title, description, link)
        VALUES (:title, :description, :link)
    ");

    $ok = $stmt->execute([
        ':title'       => $title,
        ':description' => $description,
        ':link'        => $link
    ]);

    if (!$ok) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create resource'
        ], 500);
    }

    $newId = $db->lastInsertId();

    sendResponse([
        'success' => true,
        'message' => 'Resource created successfully',
        'id'      => $newId
    ], 201);
}

function updateResource(PDO $db, array $data)
{
    if (empty($data['id']) || !ctype_digit((string)$data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Resource id is required for update'
        ], 400);
    }

    $resourceId = (int)$data['id'];

    // Check existence
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = :id");
    $checkStmt->execute([':id' => $resourceId]);
    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found'
        ], 404);
    }

    $fields = [];
    $params = [':id' => $resourceId];

    if (isset($data['title']) && $data['title'] !== '') {
        $fields[]        = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[]             = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }

    if (isset($data['link']) && $data['link'] !== '') {
        $link = trim($data['link']);
        if (!validateUrl($link)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid URL format'
            ], 400);
        }
        $fields[]        = "link = :link";
        $params[':link'] = $link;
    }

    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update'
        ], 400);
    }

    $sql = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = :id";

    $stmt = $db->prepare($sql);
    $ok   = $stmt->execute($params);

    if (!$ok) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update resource'
        ], 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Resource updated successfully'
    ]);
}

function deleteResource(PDO $db, $resourceId)
{
    if (empty($resourceId) || !ctype_digit((string)$resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource id'
        ], 400);
    }

    $resourceId = (int)$resourceId;

    // Check existence
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = :id");
    $checkStmt->execute([':id' => $resourceId]);
    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found'
        ], 404);
    }

    try {
        $db->beginTransaction();

        // Delete comments first (FK is already ON DELETE CASCADE, but we show logic)
        $delComments = $db->prepare("DELETE FROM comments_resource WHERE resource_id = :rid");
        $delComments->execute([':rid' => $resourceId]);

        // Delete resource
        $delResource = $db->prepare("DELETE FROM resources WHERE id = :id");
        $delResource->execute([':id' => $resourceId]);

        $db->commit();

        sendResponse([
            'success' => true,
            'message' => 'Resource and its comments deleted successfully'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Delete Resource Error: ' . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete resource'
        ], 500);
    }
}

// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByResourceId(PDO $db, $resourceId)
{
    if (empty($resourceId) || !ctype_digit((string)$resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource id'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT id, resource_id, author, text, created_at
        FROM comments_resource
        WHERE resource_id = :rid
        ORDER BY created_at ASC
    ");
    $stmt->execute([':rid' => $resourceId]);

    $comments = $stmt->fetchAll();

    sendResponse([
        'success' => true,
        'data'    => $comments
    ]);
}

function createComment(PDO $db, array $data)
{
    $requiredCheck = validateRequiredFields($data, ['resource_id', 'text']);
    if (!$requiredCheck['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields',
            'missing' => $requiredCheck['missing']
        ], 400);
    }

    if (!ctype_digit((string)$data['resource_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource id'
        ], 400);
    }

    $resourceId = (int)$data['resource_id'];

    // Check resource exists
    $stmt = $db->prepare("SELECT id FROM resources WHERE id = :id");
    $stmt->execute([':id' => $resourceId]);
    if (!$stmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found'
        ], 404);
    }

    // Author: from session (preferred) or fallback
    global $userName;
    $author = $userName ? trim($userName) : 'Student';

    $text   = sanitizeInput($data['text']);

    if ($text === '') {
        sendResponse([
            'success' => false,
            'message' => 'Comment text cannot be empty'
        ], 400);
    }

    $insert = $db->prepare("
        INSERT INTO comments_resource (resource_id, author, text)
        VALUES (:rid, :author, :text)
    ");
    $ok = $insert->execute([
        ':rid'    => $resourceId,
        ':author' => $author,
        ':text'   => $text
    ]);

    if (!$ok) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create comment'
        ], 500);
    }

    $newId = $db->lastInsertId();

    sendResponse([
        'success' => true,
        'message' => 'Comment added successfully',
        'id'      => $newId
    ], 201);
}

function deleteComment(PDO $db, $commentId)
{
    if (empty($commentId) || !ctype_digit((string)$commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid comment id'
        ], 400);
    }

    $commentId = (int)$commentId;

    // Check exists
    $check = $db->prepare("SELECT id FROM comments_resource WHERE id = :id");
    $check->execute([':id' => $commentId]);
    if (!$check->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found'
        ], 404);
    }

    $del = $db->prepare("DELETE FROM comments_resource WHERE id = :id");
    $ok  = $del->execute([':id' => $commentId]);

    if (!$ok) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment'
        ], 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Comment deleted successfully'
    ]);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, int $statusCode = 200)
{
    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function validateUrl(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput(string $data): string
{
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $data;
}

function validateRequiredFields(array $data, array $requiredFields): array
{
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }

    return [
        'valid'   => count($missing) === 0,
        'missing' => $missing
    ];
}

