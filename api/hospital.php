<?php
require __DIR__ . '/config.php';
$user = auth(['hospital']);
$in = input();
$pdo = db();
$hid = $user['id'];
$action = $_GET['action'] ?? '';

function q(string $sql, array $p = []): PDOStatement { $st = db()->prepare($sql); $st->execute($p); return $st; }

switch ($action) {

case 'dashboard':
    $inventory = q('SELECT blood_group, quantity, expiry_date FROM blood_stock WHERE hospital_id = ? ORDER BY blood_group', [$hid])->fetchAll();
    $requests = q("SELECT r.*, f.name AS lab_name FROM blood_requests r JOIN facilities f ON f.id = r.lab_id WHERE r.hospital_id = ? ORDER BY r.created_at DESC", [$hid])->fetchAll();
    $hospital = q('SELECT name, email, phone, street, city, state, last_login FROM facilities WHERE id = ?', [$hid])->fetch();
    $history = q('SELECT event_type, description, created_at FROM facility_history WHERE facility_id = ? ORDER BY created_at DESC, id DESC LIMIT 8', [$hid])->fetchAll();
    out(['success' => true,
        'stats' => [
            'totalUnits' => array_sum(array_column($inventory, 'quantity')),
            'pendingRequests' => count(array_filter($requests, fn($r) => $r['status'] === 'pending')),
            'totalRequests' => count($requests),
        ],
        'inventory' => $inventory, 'recentRequests' => array_slice($requests, 0, 5), 'hospital' => $hospital, 'history' => $history]);

case 'request':
    $lab = (int)($in['labId'] ?? 0); $type = clean($in['bloodType'] ?? ''); $units = (int)($in['units'] ?? 0);
    if (!$lab || $type === '' || !$units) fail('Please provide labId, bloodType, and units');
    if (!valid_group($type)) fail('Invalid blood type');
    if ($units < 1) fail('Units must be at least 1');
    $l = q("SELECT name FROM facilities WHERE id = ? AND facility_type='blood-lab' AND status='approved'", [$lab])->fetch();
    if (!$l) fail('Blood lab not found or not approved', 404);
    q('INSERT INTO blood_requests (hospital_id, lab_id, blood_type, units) VALUES (?,?,?,?)', [$hid, $lab, $type, $units]);
    log_history($hid, 'Stock Update', "Requested $units units of $type from {$l['name']}", (int)$pdo->lastInsertId());
    out(['success' => true, 'message' => 'Blood request sent to lab successfully'], 201);

case 'requests':
    $rows = q("SELECT r.*, f.name AS lab_name, f.email AS lab_email, f.phone AS lab_phone, f.city AS lab_city
               FROM blood_requests r JOIN facilities f ON f.id = r.lab_id WHERE r.hospital_id = ? ORDER BY r.created_at DESC", [$hid])->fetchAll();
    out(['success' => true, 'data' => $rows]);

case 'stock':
    out(['success' => true, 'data' => q('SELECT id, blood_group, quantity, expiry_date, updated_at FROM blood_stock WHERE hospital_id = ? ORDER BY blood_group', [$hid])->fetchAll()]);

case 'history':
    out(['success' => true, 'history' => q('SELECT event_type, description, created_at FROM facility_history WHERE facility_id = ? ORDER BY created_at DESC, id DESC LIMIT 100', [$hid])->fetchAll()]);

case 'donors':
    $search = clean($in['search'] ?? ''); $bg = clean($in['bloodGroup'] ?? 'all'); $city = clean($in['city'] ?? 'all');
    $avail = clean($in['availability'] ?? 'all'); $sort = clean($in['sortBy'] ?? 'lastDonation');
    $page = max(1, (int)($in['page'] ?? 1)); $limit = min(100, max(1, (int)($in['limit'] ?? 20)));

    $where = ['is_active = 1']; $p = [];
    if ($search !== '') { $where[] = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)'; $l = "%$search%"; array_push($p, $l, $l, $l, $l); }
    if ($bg !== 'all' && $bg !== '') { $where[] = 'blood_group = ?'; $p[] = $bg; }
    if ($city !== 'all' && $city !== '') { $where[] = 'city LIKE ?'; $p[] = "%$city%"; }
    if ($avail === 'available') $where[] = '(last_donation_date IS NULL OR last_donation_date < DATE_SUB(NOW(), INTERVAL ' . DONATION_GAP_DAYS . ' DAY))';
    elseif ($avail === 'soon')  $where[] = '(last_donation_date BETWEEN DATE_SUB(NOW(), INTERVAL ' . DONATION_GAP_DAYS . ' DAY) AND DATE_SUB(NOW(), INTERVAL ' . (DONATION_GAP_DAYS - 30) . ' DAY))'; // 30 din mein eligible
    elseif ($avail === 'unavailable') $where[] = '(last_donation_date >= DATE_SUB(NOW(), INTERVAL ' . DONATION_GAP_DAYS . ' DAY))';
    $w = 'WHERE ' . implode(' AND ', $where);

    $order = ['name' => 'full_name ASC', 'bloodGroup' => 'blood_group ASC', 'city' => 'city ASC'][$sort] ?? 'last_donation_date DESC';
    $total = (int)q("SELECT COUNT(*) FROM donors $w", $p)->fetchColumn();
    $rows = q("SELECT id, full_name, email, phone, blood_group, last_donation_date, city, state,
                      (SELECT COUNT(*) FROM donations WHERE donor_id = donors.id) AS total_donations
               FROM donors $w ORDER BY $order LIMIT $limit OFFSET " . (($page - 1) * $limit), $p)->fetchAll();
    foreach ($rows as &$r) { $el = donor_eligibility($r['last_donation_date']); $r['eligible'] = $el['eligible']; $r['nextEligibleDate'] = $el['nextEligibleDate']; }

    $gap = DONATION_GAP_DAYS;
    out(['success' => true, 'donors' => $rows,
        'pagination' => ['total' => $total, 'currentPage' => $page, 'totalPages' => (int)ceil($total / $limit)],
        'stats' => [
            'total' => $total,
            'available' => (int)$pdo->query("SELECT COUNT(*) FROM donors WHERE is_active=1 AND (last_donation_date IS NULL OR last_donation_date < DATE_SUB(NOW(), INTERVAL $gap DAY))")->fetchColumn(),
            'rareBlood' => (int)$pdo->query("SELECT COUNT(*) FROM donors WHERE is_active=1 AND blood_group IN ('O-','AB-','B-','A-')")->fetchColumn(),
        ]]);

case 'contact_donor':
    $did = (int)($in['id'] ?? 0);
    $d = q('SELECT full_name, blood_group FROM donors WHERE id = ?', [$did])->fetch();
    if (!$d) fail('Donor not found', 404);
    q('INSERT INTO donor_contacts (donor_id, facility_id) VALUES (?,?)', [$did, $hid]);
    log_history($hid, 'Contact', "Contacted donor {$d['full_name']} ({$d['blood_group']})", $did);
    out(['success' => true, 'message' => 'Contact logged successfully']);

default:
    fail('Unknown action', 404);
}
