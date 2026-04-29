<?php
/**
 * Uyut Rental Agency - Apartments API
 * Handles apartment listings, CRUD operations, and search
 */

require_once __DIR__ . '/../database/db_connect.php';
session_start();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Get apartment ID from URL if present
$apartmentId = null;
if (isset($segments[2]) && is_numeric($segments[2])) {
    $apartmentId = (int)$segments[2];
}

try {
    switch ($method) {
        case 'GET':
            if ($apartmentId) {
                getApartment($apartmentId);
            } else {
                listApartments();
            }
            break;

        case 'POST':
            if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
                if ($apartmentId) {
                    updateApartment($apartmentId);
                } else {
                    createApartment();
                }
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            }
            break;

        case 'DELETE':
            if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin' && $apartmentId) {
                deleteApartment($apartmentId);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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

function listApartments() {
    global $pdo;

    $params = [];
    $where = [];

    // Search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $where[] = "(title LIKE ? OR description LIKE ? OR address LIKE ?)";
        $search = '%' . $_GET['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    // City filter
    if (isset($_GET['city']) && !empty($_GET['city'])) {
        $where[] = "city = ?";
        $params[] = $_GET['city'];
    }

    // Guests filter
    if (isset($_GET['guests']) && is_numeric($_GET['guests'])) {
        $where[] = "guests >= ?";
        $params[] = (int)$_GET['guests'];
    }

    // Price range filter
    if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $where[] = "price_per_night >= ?";
        $params[] = (float)$_GET['min_price'];
    }
    if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $where[] = "price_per_night <= ?";
        $params[] = (float)$_GET['max_price'];
    }

    // Availability filter
    if (!isset($_GET['include_unavailable'])) {
        $where[] = "is_available = 1";
    }

    // Sorting
    $sortBy = $_GET['sort'] ?? 'created_at';
    $sortOrder = $_GET['order'] ?? 'DESC';
    $allowedSorts = ['price_per_night', 'title', 'created_at', 'guests'];
    $allowedOrders = ['ASC', 'DESC'];

    if (!in_array($sortBy, $allowedSorts)) $sortBy = 'created_at';
    if (!in_array($sortOrder, $allowedOrders)) $sortOrder = 'DESC';

    $sql = "SELECT * FROM apartments";
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY $sortBy $sortOrder";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON fields
    foreach ($apartments as &$apt) {
        $apt['amenities'] = json_decode($apt['amenities'], true) ?: [];
        $apt['images'] = json_decode($apt['images'], true) ?: [];
    }

    echo json_encode(['success' => true, 'apartments' => $apartments]);
}

function getApartment($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ?");
    $stmt->execute([$id]);
    $apartment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$apartment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Apartment not found']);
        return;
    }

    // Decode JSON fields
    $apartment['amenities'] = json_decode($apartment['amenities'], true) ?: [];
    $apartment['images'] = json_decode($apartment['images'], true) ?: [];

    // Check availability for date range if provided
    if (isset($_GET['check_in']) && isset($_GET['check_out'])) {
        $checkIn = $_GET['check_in'];
        $checkOut = $_GET['check_out'];

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE apartment_id = ? AND status = 'confirmed' AND ((check_in <= ? AND check_out >= ?) OR (check_in <= ? AND check_out >= ?) OR (check_in >= ? AND check_out <= ?))");
        $stmt->execute([$id, $checkOut, $checkIn, $checkOut, $checkOut, $checkIn, $checkOut]);
        $conflictingBookings = $stmt->fetchColumn();

        $apartment['available'] = ($conflictingBookings == 0);
    } else {
        $apartment['available'] = ($apartment['is_available'] == 1);
    }

    echo json_encode(['success' => true, 'apartment' => $apartment]);
}

function createApartment() {
    global $pdo;

    $input = json_decode(file_get_contents('php://input'), true);

    $required = ['title', 'address', 'price_per_night'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("$field is required");
        }
    }

    $stmt = $pdo->prepare("INSERT INTO apartments (title, description, address, city, latitude, longitude, price_per_night, guests, bedrooms, beds, bathrooms, amenities, images, owner_id, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $amenities = isset($input['amenities']) ? json_encode($input['amenities']) : json_encode([]);
    $images = isset($input['images']) ? json_encode($input['images']) : json_encode([]);

    $stmt->execute([
        htmlspecialchars($input['title']),
        isset($input['description']) ? htmlspecialchars($input['description']) : '',
        htmlspecialchars($input['address']),
        isset($input['city']) ? htmlspecialchars($input['city']) : 'Astana',
        isset($input['latitude']) ? (float)$input['latitude'] : 51.1694,
        isset($input['longitude']) ? (float)$input['longitude'] : 71.4131,
        (float)$input['price_per_night'],
        isset($input['guests']) ? (int)$input['guests'] : 2,
        isset($input['bedrooms']) ? (int)$input['bedrooms'] : 1,
        isset($input['beds']) ? (int)$input['beds'] : 1,
        isset($input['bathrooms']) ? (int)$input['bathrooms'] : 1,
        $amenities,
        $images,
        $_SESSION['user_id'],
        isset($input['is_available']) ? (int)$input['is_available'] : 1
    ]);

    echo json_encode(['success' => true, 'message' => 'Apartment created', 'id' => $pdo->lastInsertId()]);
}

function updateApartment($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT id FROM apartments WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Apartment not found']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $allowedFields = ['title', 'description', 'address', 'city', 'latitude', 'longitude', 'price_per_night', 'guests', 'bedrooms', 'beds', 'bathrooms', 'amenities', 'images', 'is_available'];
    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if (in_array($field, ['amenities', 'images'])) {
                $updates[] = "$field = ?";
                $params[] = json_encode($input[$field]);
            } elseif (in_array($field, ['latitude', 'longitude', 'price_per_night'])) {
                $updates[] = "$field = ?";
                $params[] = (float)$input[$field];
            } elseif (in_array($field, ['guests', 'bedrooms', 'beds', 'bathrooms', 'is_available'])) {
                $updates[] = "$field = ?";
                $params[] = (int)$input[$field];
            } else {
                $updates[] = "$field = ?";
                $params[] = htmlspecialchars($input[$field]);
            }
        }
    }

    if (empty($updates)) {
        throw new Exception('No fields to update');
    }

    $params[] = $id;
    $sql = "UPDATE apartments SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Apartment updated']);
}

function deleteApartment($id) {
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM apartments WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Apartment not found']);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'Apartment deleted']);
}

?>
