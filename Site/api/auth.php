<?php
/**
 * Uyut Rental Agency - Authentication API
 * Handles user registration, login, and session management
 */

require_once __DIR__ . '/../database/db_connect.php';
session_start();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if (isset($input['action'])) {
                switch ($input['action']) {
                    case 'register':
                        handleRegistration($input);
                        break;
                    case 'login':
                        handleLogin($input);
                        break;
                    case 'logout':
                        handleLogout();
                        break;
                    case 'profile':
                        if (isset($input['update'])) {
                            updateProfile($input);
                        } else {
                            getProfile();
                        }
                        break;
                    default:
                        throw new Exception('Invalid action');
                }
            }
            break;

        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'session') {
                checkSession();
            } else {
                getProfile();
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleRegistration($data) {
    global $pdo;

    // Validate required fields
    $required = ['email', 'password', 'first_name', 'last_name'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("$field is required");
        }
    }

    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    if (strlen($data['password']) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('User with this email already exists');
    }

    // Create user
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $email,
        $hashedPassword,
        htmlspecialchars($data['first_name']),
        htmlspecialchars($data['last_name']),
        isset($data['phone']) ? htmlspecialchars($data['phone']) : null
    ]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = 'guest';

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $_SESSION['user_id'],
            'email' => $email,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => 'guest'
        ]
    ]);
}

function handleLogin($data) {
    global $pdo;

    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required');
    }

    $stmt = $pdo->prepare("SELECT id, password, first_name, last_name, role FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data['password'], $user['password'])) {
        throw new Exception('Invalid email or password');
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'email' => $data['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role']
        ]
    ]);
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
}

function checkSession() {
    if (isset($_SESSION['user_id'])) {
        $user = [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role']
        ];
        echo json_encode(['logged_in' => true, 'user' => $user]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}

function getProfile() {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    echo json_encode(['success' => true, 'user' => $user]);
}

function updateProfile($data) {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    global $pdo;

    $allowedFields = ['first_name', 'last_name', 'phone'];
    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = htmlspecialchars($data[$field]);
        }
    }

    if (empty($updates)) {
        throw new Exception('No fields to update');
    }

    $params[] = $_SESSION['user_id'];
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Profile updated']);
}

?>
