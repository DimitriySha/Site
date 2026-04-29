<?php
/**
 * Uyut Rental Agency - Favorites API
 * Handles saving and managing favorite apartments
 */

require_once __DIR__ . '/../database/db_connect.php';
session_start();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            getFavorites();
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            addFavorite($input);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['apartment_id'])) {
                removeFavorite($input['apartment_id']);
            } else {
                throw new Exception('Apartment ID required');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getFavorites() {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $stmt = $pdo->prepare("
        SELECT f.*, a.*
        FROM favorites f
        JOIN apartments a ON f.apartment_id = a.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);

    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON fields
    foreach ($favorites as &$fav) {
        $fav['amenities'] = json_decode($fav['amenities'], true) ?: [];
        $fav['images'] = json_decode($fav['images'], true) ?: [];
    }

    echo json_encode(['success' => true, 'favorites' => $favorites]);
}

function addFavorite($data) {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    if (!isset($data['apartment_id'])) {
        throw new Exception('Apartment ID required');
    }

    // Verify apartment exists
    $stmt = $pdo->prepare("SELECT id FROM apartments WHERE id = ?");
    $stmt->execute([$data['apartment_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Apartment not found');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, apartment_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $data['apartment_id']]);
        echo json_encode(['success' => true, 'message' => 'Added to favorites']);
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            echo json_encode(['success' => false, 'error' => 'Already in favorites']);
        } else {
            throw $e;
        }
    }
}

function removeFavorite($apartmentId) {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND apartment_id = ?");
    $stmt->execute([$_SESSION['user_id'], $apartmentId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Not in favorites']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
    }
}

?>
