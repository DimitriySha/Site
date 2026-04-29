<?php
/**
 * Uyut Rental Agency - Bookings API
 * Handles user bookings, viewing, and cancellation
 */

require_once __DIR__ . '/../database/db_connect.php';
session_start();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            getBookings();
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            createBooking($input);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['id'])) {
                cancelBooking($input['id']);
            } else {
                throw new Exception('Booking ID required');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getBookings() {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $userId = $_SESSION['user_id'];
    $isAdmin = ($_SESSION['role'] === 'admin');

    $sql = "
        SELECT b.*, a.title as apartment_title, a.address, a.images, a.price_per_night
        FROM bookings b
        JOIN apartments a ON b.apartment_id = a.id
        WHERE " . ($isAdmin ? "1=1" : "b.user_id = ?") . "
        ORDER BY b.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    if (!$isAdmin) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bookings as &$booking) {
        $booking['images'] = json_decode($booking['images'], true) ?: [];
        if (isset($booking['images'][0])) {
            $booking['thumbnail'] = 'images/apartments/' . $booking['images'][0];
        } else {
            $booking['thumbnail'] = 'images/no-image.jpg';
        }
    }

    echo json_encode(['success' => true, 'bookings' => $bookings]);
}

function createBooking($data) {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $required = ['apartment_id', 'check_in', 'check_out', 'guests'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Get apartment details
    $stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ? AND is_available = 1");
    $stmt->execute([$data['apartment_id']]);
    $apartment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$apartment) {
        throw new Exception('Apartment not available');
    }

    // Validate dates
    $checkIn = new DateTime($data['check_in']);
    $checkOut = new DateTime($data['check_out']);
    $today = new DateTime();

    if ($checkIn <= $today) {
        throw new Exception('Check-in date must be in the future');
    }
    if ($checkOut <= $checkIn) {
        throw new Exception('Check-out date must be after check-in');
    }

    // Check for conflicting bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE apartment_id = ? AND status = 'confirmed' AND ((check_in <= ? AND check_out >= ?) OR (check_in <= ? AND check_out >= ?) OR (check_in >= ? AND check_out <= ?))");
    $stmt->execute([
        $data['apartment_id'],
        $checkOut->format('Y-m-d'),
        $checkIn->format('Y-m-d'),
        $checkOut->format('Y-m-d'),
        $checkIn->format('Y-m-d'),
        $checkIn->format('Y-m-d'),
        $checkOut->format('Y-m-d')
    ]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Apartment is not available for selected dates');
    }

    // Calculate total price
    $nights = $checkIn->diff($checkOut)->days;
    $totalPrice = $nights * $apartment['price_per_night'];

    // Create booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, apartment_id, check_in, check_out, guests, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $data['apartment_id'],
        $checkIn->format('Y-m-d'),
        $checkOut->format('Y-m-d'),
        $data['guests'],
        $totalPrice
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully',
        'booking_id' => $pdo->lastInsertId(),
        'total_nights' => $nights,
        'total_price' => $totalPrice
    ]);
}

function cancelBooking($id) {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    // Check if booking exists and belongs to user (or user is admin)
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    $isAdmin = ($_SESSION['role'] === 'admin');
    if (!$isAdmin && $booking['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized');
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Booking cancelled']);
}

?>
