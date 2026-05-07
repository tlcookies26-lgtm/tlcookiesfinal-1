<?php
// Function to generate a unique ID
if (!function_exists('unique_id')) {
    function unique_id()
    {
        return bin2hex(random_bytes(10));
    }
}

// Railway MySQL environment variables (set automatically when you add a MySQL plugin)
// Fallback to localhost defaults for local development
$servername = getenv('MYSQLHOST')     ?: (getenv('DB_HOST')     ?: 'localhost');
$username   = getenv('MYSQLUSER')     ?: (getenv('DB_USER')     ?: 'root');
$password   = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASSWORD') ?: '');
$dbname     = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME')     ?: 'tlcookies_db');
$port       = getenv('MYSQLPORT')     ?: (getenv('DB_PORT')     ?: '3306');

try {
    $conn = new PDO(
        "mysql:host=$servername;port=$port;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create DB if needed (local dev); on Railway the DB already exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE `$dbname`");

    // ── Schema ──────────────────────────────────────────────────────────────

    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(20) PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        surname VARCHAR(50) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        phone VARCHAR(15) NOT NULL,
        barangay VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        profile_picture VARCHAR(255) DEFAULT NULL,
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        ingredients TEXT NOT NULL,
        benefits TEXT NOT NULL,
        steps TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
        images TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS discounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        discount_percentage INT NOT NULL CHECK (discount_percentage BETWEEN 1 AND 100),
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        image VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(20) NOT NULL,
        product_id INT NOT NULL,
        price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
        qty INT NOT NULL CHECK (qty > 0),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(20) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL CHECK (total_price >= 0),
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        qty INT NOT NULL CHECK (qty > 0),
        sub_total DECIMAL(10,2) NOT NULL CHECK (sub_total >= 0),
        status ENUM('pending','processing','delivered','cancelled') NOT NULL DEFAULT 'pending',
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // ── Seed admin account ───────────────────────────────────────────────────

    $admin_email    = 'tlcookies26@gmail.com';
    $admin_username = 'admin';
    $admin_password = '@tlc2026';

    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute(['email' => $admin_email]);
    $admin_exists = $stmt->fetchColumn();

    if ($admin_exists == 0) {
        $admin_id        = unique_id();
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users
            (id, username, email, password, surname, first_name, phone, barangay, address, is_admin)
            VALUES (:id, :username, :email, :password, :surname, :first_name, :phone, :barangay, :address, :is_admin)");

        $stmt->execute([
            'id'         => $admin_id,
            'username'   => $admin_username,
            'email'      => $admin_email,
            'password'   => $hashed_password,
            'surname'    => 'Admin',
            'first_name' => 'TLC Master',
            'phone'      => '0935-967-6696',
            'barangay'   => 'Cabatangan',
            'address'    => 'TLC Bakery',
            'is_admin'   => 1,
        ]);
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
