<?php
/**
 * Uyut Rental Agency - Messages API
 * Handles user-admin communication
 */

require_once __DIR__ . '/../database/db_connect.php';
session_start();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            getMessages();
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            sendMessage($input);
            break;

        case 'PUT':
            markAsRead();
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getMessages() {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $userId = $_SESSION['user_id'];
    $isAdmin = ($_SESSION['role'] === 'admin');

    if ($isAdmin) {
        // Admin sees all messages sent to them
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name, a.title as apartment_title
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN apartments a ON m.apartment_id = a.id
            WHERE m.receiver_id = ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
    } else {
        // Guest sees their sent messages and admin replies
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name, a.title as apartment_title
            FROM messages m
            JOIN users u ON m.receiver_id = u.id
            LEFT JOIN apartments a ON m.apartment_id = a.id
            WHERE m.sender_id = ?
            UNION
            SELECT m.*, u.first_name, u.last_name, a.title as apartment_title
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN apartments a ON m.apartment_id = a.id
            WHERE m.receiver_id = ? AND m.receiver_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId]);
    }

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
}

function sendMessage($data) {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $required = ['receiver_id', 'message'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Verify receiver exists and is admin if sender is guest
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$data['receiver_id']]);
    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiver) {
        throw new Exception('Receiver not found');
    }

    // If sender is guest, receiver must be admin
    if ($_SESSION['role'] === 'guest' && $receiver['role'] !== 'admin') {
        throw new Exception('Can only message administrators');
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, apartment_id, subject, message) VALUES (?, ?, ?, ?, ?)");

    $apartmentId = isset($data['apartment_id']) ? $data['apartment_id'] : null;
    $subject = isset($data['subject']) ? htmlspecialchars($data['subject']) : null;

    $stmt->execute([
        $_SESSION['user_id'],
        $data['receiver_id'],
        $apartmentId,
        $subject,
        htmlspecialchars($data['message'])
    ]);

    echo json_encode(['success' => true, 'message' => 'Message sent']);
}

function markAsRead() {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['ids']) && is_array($input['ids'])) {
        $placeholders = implode(',', array_fill(0, count($input['ids']), '?'));
        $params = array_merge([1], $input['ids']);
        $params[] = $_SESSION['user_id'];

        $sql = "UPDATE messages SET is_read = 1 WHERE id IN ($placeholders) AND receiver_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'count' => $stmt->rowCount()]);
    } else {
        throw new Exception('Message IDs required');
    }
}

?>
