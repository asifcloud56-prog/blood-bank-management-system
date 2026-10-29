<?php
// ---------- Database settings (XAMPP default) ----------
const DB_HOST = 'localhost';
const DB_NAME = 'bbms';
const DB_USER = 'root';
const DB_PASS = '';

const BLOOD_GROUPS = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
const DONATION_GAP_DAYS = 90;   // 3 mahine ka gap
const STOCK_EXPIRY_DAYS = 42;   // blood unit expiry

// Warnings JSON response ko kharab na karein (errors server log mein jayenge)
ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Karachi');

session_start();
header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
});

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $msg, int $code = 400): void {
    out(['success' => false, 'message' => $msg], $code);
}

// JSON body + form-data + query string, sab ek array mein
function input(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);
    return array_merge($_GET, $_POST, is_array($json) ? $json : []);
}

// Logged-in user chahiye. $roles = allowed roles (null = koi bhi)
function auth(?array $roles = null): array {
    if (empty($_SESSION['user'])) fail('Please login first', 401);
    $u = $_SESSION['user'];
    if ($roles && !in_array($u['role'], $roles, true)) fail('Access denied for your role', 403);
    return $u;
}

function log_history(int $facilityId, string $type, string $desc, ?int $ref = null): void {
    $st = db()->prepare('INSERT INTO facility_history (facility_id, event_type, description, reference_id) VALUES (?,?,?,?)');
    $st->execute([$facilityId, $type, $desc, $ref]);
}

function clean($v): string { return trim((string)$v); }

function valid_group(string $g): bool { return in_array($g, BLOOD_GROUPS, true); }

// Stock mein units add karo (lab ya hospital ke liye). $col = 'lab_id' | 'hospital_id'
function stock_add(string $col, int $facilityId, string $group, int $qty): void {
    if (!in_array($col, ['lab_id', 'hospital_id'], true)) throw new Exception('bad column');
    $pdo = db();
    $expiry = date('Y-m-d', strtotime('+' . STOCK_EXPIRY_DAYS . ' days'));
    $st = $pdo->prepare("SELECT id FROM blood_stock WHERE $col = ? AND blood_group = ? LIMIT 1");
    $st->execute([$facilityId, $group]);
    $row = $st->fetch();
    if ($row) {
        $pdo->prepare('UPDATE blood_stock SET quantity = quantity + ?, expiry_date = ? WHERE id = ?')->execute([$qty, $expiry, $row['id']]);
    } else {
        $pdo->prepare("INSERT INTO blood_stock (blood_group, quantity, expiry_date, $col) VALUES (?,?,?,?)")->execute([$group, $qty, $expiry, $facilityId]);
    }
}

function days_since(?string $date): ?int {
    if (!$date) return null;
    return (int) floor((time() - strtotime($date)) / 86400);
}

function donor_eligibility(?string $lastDonation): array {
    $days = days_since($lastDonation);
    $eligible = $days === null || $days >= DONATION_GAP_DAYS;
    $next = $lastDonation ? date('Y-m-d', strtotime($lastDonation . ' +' . DONATION_GAP_DAYS . ' days')) : null;
    return ['eligible' => $eligible, 'nextEligibleDate' => $next, 'daysLeft' => $eligible ? 0 : DONATION_GAP_DAYS - $days];
}

function facility_public(array $f): array {
    return [
        'id' => (int)$f['id'], 'name' => $f['name'], 'email' => $f['email'], 'phone' => $f['phone'],
        'emergencyContact' => $f['emergency_contact'], 'registrationNumber' => $f['registration_number'],
        'facilityType' => $f['facility_type'], 'facilityCategory' => $f['facility_category'],
        'status' => $f['status'], 'rejectionReason' => $f['rejection_reason'],
        'address' => ['street' => $f['street'], 'city' => $f['city'], 'state' => $f['state'], 'pincode' => $f['pincode']],
        'operatingHours' => ['open' => $f['open_time'], 'close' => $f['close_time'], 'workingDays' => array_values(array_filter(explode(',', $f['working_days'])))],
        'is24x7' => (bool)$f['is_24x7'], 'emergencyServices' => (bool)$f['emergency_services'],
        'registrationProofUrl' => $f['registration_proof_url'],
        'lastLogin' => $f['last_login'], 'createdAt' => $f['created_at'],
    ];
}
