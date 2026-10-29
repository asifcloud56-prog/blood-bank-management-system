<?php
require __DIR__ . '/config.php';
$user = auth(['admin']);
$in = input();
$pdo = db();
$action = $_GET['action'] ?? '';

function one(string $sql): int { return (int)db()->query($sql)->fetchColumn(); }

switch ($action) {

case 'dashboard':
    $gap = DONATION_GAP_DAYS;
    out(['success' => true,
        'totalDonors' => one('SELECT COUNT(*) FROM donors'),
        'totalFacilities' => one('SELECT COUNT(*) FROM facilities'),
        'approvedFacilities' => one("SELECT COUNT(*) FROM facilities WHERE status='approved'"),
        'pendingFacilities' => one("SELECT COUNT(*) FROM facilities WHERE status='pending'"),
        'rejectedFacilities' => one("SELECT COUNT(*) FROM facilities WHERE status='rejected'"),
        'hospitals' => one("SELECT COUNT(*) FROM facilities WHERE facility_type='hospital'"),
        'labs' => one("SELECT COUNT(*) FROM facilities WHERE facility_type='blood-lab'"),
        'totalDonations' => one('SELECT COUNT(*) FROM donations'),
        'activeDonors' => one("SELECT COUNT(*) FROM donors WHERE last_donation_date IS NULL OR last_donation_date < DATE_SUB(NOW(), INTERVAL $gap DAY)"),
        'upcomingCamps' => one("SELECT COUNT(*) FROM blood_camps WHERE status='Upcoming' AND camp_date >= CURDATE()"),
        'totalUnits' => one('SELECT COALESCE(SUM(quantity),0) FROM blood_stock'),
        'recentPending' => $pdo->query("SELECT id, name, facility_type, city, created_at FROM facilities WHERE status='pending' ORDER BY created_at DESC LIMIT 5")->fetchAll(),
    ]);

case 'facilities':
    $rows = $pdo->query('SELECT * FROM facilities ORDER BY created_at DESC')->fetchAll();
    out(['success' => true, 'facilities' => array_map('facility_public', $rows)]);

case 'approve':
    $fid = (int)($in['id'] ?? 0);
    $st = $pdo->prepare('SELECT id FROM facilities WHERE id = ?'); $st->execute([$fid]);
    if (!$st->fetch()) fail('Facility not found', 404);
    $pdo->prepare("UPDATE facilities SET status='approved', approved_at=NOW(), rejection_reason=NULL WHERE id=?")->execute([$fid]);
    log_history($fid, 'Verification', 'Registration approved by admin');
    out(['success' => true, 'message' => 'Facility approved']);

case 'reject':
    $fid = (int)($in['id'] ?? 0); $reason = clean($in['rejectionReason'] ?? '');
    if ($reason === '') fail('Rejection reason is required.');
    $st = $pdo->prepare('SELECT id FROM facilities WHERE id = ?'); $st->execute([$fid]);
    if (!$st->fetch()) fail('Facility not found', 404);
    $pdo->prepare("UPDATE facilities SET status='rejected', rejection_reason=? WHERE id=?")->execute([$reason, $fid]);
    log_history($fid, 'Verification', 'Registration rejected by admin');
    out(['success' => true, 'message' => 'Facility rejected and status updated']);

case 'donors':
    $rows = $pdo->query('SELECT d.id, d.full_name, d.email, d.phone, d.blood_group, d.gender, d.age, d.city, d.state, d.last_donation_date, d.created_at,
                                (SELECT COUNT(*) FROM donations WHERE donor_id = d.id) AS total_donations
                         FROM donors d ORDER BY d.created_at DESC')->fetchAll();
    foreach ($rows as &$r) $r['eligible'] = donor_eligibility($r['last_donation_date'])['eligible'];
    out(['success' => true, 'donors' => $rows]);

default:
    fail('Unknown action', 404);
}
