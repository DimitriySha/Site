<?php
/**
 * Uyut Rental Agency - Database Initialization
 * Creates SQLite database and tables
 */

header('Content-Type: application/json');

$dbFile = '../database/uyut.db';

// Ensure database directory exists
if (!is_dir('../database')) {
    mkdir('../database', 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        phone TEXT,
        role TEXT DEFAULT 'guest' CHECK(role IN ('guest', 'admin')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create apartments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS apartments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        address TEXT NOT NULL,
        city TEXT DEFAULT 'Astana',
        latitude REAL DEFAULT 51.1694,
        longitude REAL DEFAULT 71.4131,
        price_per_night REAL NOT NULL,
        guests INTEGER DEFAULT 2,
        bedrooms INTEGER DEFAULT 1,
        beds INTEGER DEFAULT 1,
        bathrooms INTEGER DEFAULT 1,
        amenities TEXT,
        images TEXT,
        owner_id INTEGER,
        is_available INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id)
    )");

    // Create bookings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        apartment_id INTEGER NOT NULL,
        check_in DATE NOT NULL,
        check_out DATE NOT NULL,
        guests INTEGER NOT NULL,
        total_price REAL NOT NULL,
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'confirmed', 'cancelled', 'completed')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (apartment_id) REFERENCES apartments(id)
    )");

    // Create favorites table
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        apartment_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, apartment_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (apartment_id) REFERENCES apartments(id)
    )");

    // Create messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        apartment_id INTEGER,
        subject TEXT,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id),
        FOREIGN KEY (apartment_id) REFERENCES apartments(id)
    )");

    // Insert sample data for apartments
    $stmt = $pdo->query("SELECT COUNT(*) FROM apartments");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $sampleData = [
            [
                'Modern Downtown Apartment',
                'Spacious apartment in the heart of Astana with stunning city views. Fully furnished with modern amenities, perfect for business travelers and tourists.',
                '50 Kunayev St, Astana',
                'Astana',
                51.1694,
                71.4131,
                8500,
                4, 2, 2, 1,
                json_encode(['wifi', 'kitchen', 'parking', 'ac', 'tv', 'washer']),
                json_encode(['img1.jpg', 'img2.jpg', 'img3.jpg']),
                1
            ],
            [
                'Cozy One-Bedroom Studio',
                'Comfortable studio apartment near the Bayterek tower. Walking distance to major attractions and public transport.',
                '23 Mangilik El Ave, Astana',
                'Astana',
                51.1278,
                71.4156,
                5500,
                2, 1, 1, 1,
                json_encode(['wifi', 'kitchen', 'tv']),
                json_encode(['img4.jpg', 'img5.jpg']),
                1
            ],
            [
                'Luxury Penthouse Suite',
                'Exclusive penthouse with panoramic views of Astana. Premium finishes, private elevator access, and 24/7 concierge service.',
                '8 Kabanbai Batyr Ave, Astana',
                'Astana',
                51.1700,
                71.4200,
                15000,
                6, 4, 4, 2,
                json_encode(['wifi', 'kitchen', 'parking', 'ac', 'tv', 'washer', 'gym', 'pool']),
                json_encode(['img6.jpg', 'img7.jpg', 'img8.jpg']),
                1
            ],
            [
                'Family-Friendly Apartment',
                ' spacious 3-bedroom apartment perfect for families. Located in a quiet residential area with playground nearby.',
                '45 Abai Ave, Astana',
                'Astana',
                51.1500,
                71.4000,
                9000,
                6, 3, 3, 2,
                json_encode(['wifi', 'kitchen', 'parking', 'washer', 'tv']),
                json_encode(['img9.jpg', 'img10.jpg']),
                1
            ],
            [
                'Business Traveler Studio',
                'Modern studio designed for business trips. High-speed internet, work desk, and close to business district.',
                '12 Dostyk St, Astana',
                'Astana',
                51.1650,
                71.4350,
                4500,
                1, 1, 1, 1,
                json_encode(['wifi', 'desk', 'ac', 'tv']),
                json_encode(['img11.jpg']),
                1
            ]
        ];

        $insert = $pdo->prepare("INSERT INTO apartments (title, description, address, city, latitude, longitude, price_per_night, guests, bedrooms, beds, bathrooms, amenities, images, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($sampleData as $data) {
            $insert->execute($data);
        }
    }

    // Insert admin user (password: admin123)
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)");
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt->execute(['admin@uyut.kz', $hashedPassword, 'Admin', 'User', 'admin']);

    echo json_encode(['success' => true, 'message' => 'Database initialized successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
