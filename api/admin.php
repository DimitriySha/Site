<?php
/**
 * Uyut Rental Agency - Admin API
 * Handles admin-specific operations
 */

require_once __DIR__ . '/../database/db_connect.php';
session_start();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Check admin access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }

    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));

    switch ($method) {
        case 'GET':
            if (isset($segments[2]) && $segments[2] === 'analytics') {
                getAnalytics();
            } elseif (isset($segments[2]) && $segments[2] === 'users') {
                listUsers();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Not found']);
            }
            break;

        case 'PUT':
            if (isset($segments[2]) && $segments[2] === 'booking') {
                updateBookingStatus();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Not found']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getAnalytics() {
    global $pdo;

    // Get total apartments
    $stmt = $pdo->query("SELECT COUNT(*) FROM apartments");
    $totalApartments = $stmt->fetchColumn();

    // Get total bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $totalBookings = $stmt->fetchColumn();

    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $totalUsers = $stmt->fetchColumn();

    // Monthly revenue (last 6 months)
    $stmt = $pdo->query("
        SELECT strftime('%Y-%m', created_at) as month, SUM(total_price) as revenue
        FROM bookings
        WHERE status = 'confirmed'
        GROUP BY month
        ORDER BY month DESC
        LIMIT 6
    ");
    $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Most popular apartments
    $stmt = $pdo->query("
        SELECT a.id, a.title, COUNT(b.id) as booking_count
        FROM apartments a
        LEFT JOIN bookings b ON a.id = b.apartment_id AND b.status = 'confirmed'
        GROUP BY a.id
        ORDER BY booking_count DESC
        LIMIT 5
    ");
    $popularApartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'analytics' => [
            'total_apartments' => $totalApartments,
            'total_bookings' => $totalBookings,
            'total_users' => $totalUsers,
            'monthly_revenue' => $monthlyRevenue,
            'popular_apartments' => $popularApartments
        ]
    ]);
}

function listUsers() {
    global $pdo;

    $stmt = $pdo->query("
        SELECT id, email, first_name, last_name, phone, created_at
        FROM users
        WHERE role = 'guest'
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);
}

function updateBookingStatus() {
    global $pdo;

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || !isset($input['status'])) {
        throw new Exception('Booking ID and status are required');
    }

    $allowedStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($input['status'], $allowedStatuses)) {
        throw new Exception('Invalid status');
    }

    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$input['status'], $input['id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Booking not found');
    }

    echo json_encode(['success' => true, 'message' => 'Booking status updated']);
}

?>
