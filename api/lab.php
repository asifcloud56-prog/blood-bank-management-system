<?php
require __DIR__ . '/config.php';
$user = auth(['blood-lab']);
$in = input();
$pdo = db();
$lab = $user['id'];
$action = $_GET['action'] ?? '';

function q(string $sql, array $p = []): PDOStatement { $st = db()->prepare($sql); $st->execute($p); return $st; }

switch ($action) {

/* ---------------- Dashboard & history ---------------- */
case 'dashboard':
    $camps = q('SELECT * FROM blood_camps WHERE facility_id = ? ORDER BY created_at DESC', [$lab])->fetchAll();
    $stats = [
        'totalCamps' => count($camps),
        'upcomingCamps' => count(array_filter($camps, fn($c) => $c['status'] === 'Upcoming')),
        'completedCamps' => count(array_filter($camps, fn($c) => $c['status'] === 'Completed')),
        'totalDonors' => array_sum(array_column($camps, 'actual_donors')),
        'totalUnits' => (int)q('SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE lab_id = ?', [$lab])->fetchColumn(),
        'pendingRequests' => (int)q("SELECT COUNT(*) FROM blood_requests WHERE lab_id = ? AND status='pending'", [$lab])->fetchColumn(),
    ];
    $facility = q('SELECT name, email, phone, street, city, state, status, last_login FROM facilities WHERE id = ?', [$lab])->fetch();
    $history = q('SELECT event_type, description, created_at FROM facility_history WHERE facility_id = ? ORDER BY created_at DESC, id DESC LIMIT 8', [$lab])->fetchAll();
    $stock = q('SELECT blood_group, quantity FROM blood_stock WHERE lab_id = ? ORDER BY blood_group', [$lab])->fetchAll();
    out(['success' => true, 'stats' => $stats, 'recentCamps' => array_slice($camps, 0, 5), 'facility' => $facility, 'history' => $history, 'stock' => $stock]);

case 'history':
    $rows = q("SELECT event_type, description, created_at FROM facility_history WHERE facility_id = ? ORDER BY created_at DESC, id DESC LIMIT 100", [$lab])->fetchAll();
    out(['success' => true, 'activity' => $rows]);

/* ---------------- Blood camps ---------------- */
case 'camps':
    $status = clean($in['status'] ?? 'all');
    $page = max(1, (int)($in['page'] ?? 1)); $limit = min(50, max(1, (int)($in['limit'] ?? 10)));
    $where = 'WHERE facility_id = ?'; $p = [$lab];
    if ($status !== 'all' && $status !== '') { $where .= ' AND status = ?'; $p[] = $status; }
    $total = (int)q("SELECT COUNT(*) FROM blood_camps $where", $p)->fetchColumn();
    $camps = q("SELECT * FROM blood_camps $where ORDER BY camp_date DESC LIMIT $limit OFFSET " . (($page - 1) * $limit), $p)->fetchAll();
    out(['success' => true, 'camps' => $camps, 'pagination' => ['total' => $total, 'currentPage' => $page, 'totalPages' => (int)ceil($total / $limit)]]);

case 'camp_create':
case 'camp_update':
    $title = clean($in['title'] ?? ''); $date = clean($in['date'] ?? '');
    $start = clean($in['startTime'] ?? ''); $end = clean($in['endTime'] ?? '');
    $venue = clean($in['venue'] ?? ''); $city = clean($in['city'] ?? ''); $state = clean($in['state'] ?? '');
    $missing = [];
    foreach (['title' => $title, 'date' => $date, 'start time' => $start, 'end time' => $end, 'venue' => $venue, 'city' => $city, 'state' => $state] as $k => $v)
        if ($v === '') $missing[] = $k;
    if ($missing) fail('Missing required: ' . implode(', ', $missing));
    if (!strtotime($date)) fail('Invalid date');
    if ($action === 'camp_create' && strtotime($date) < strtotime('today')) fail('Camp date cannot be in the past');
    if ($start >= $end) fail('End time must be after start time');
    $desc = clean($in['description'] ?? ''); $pin = clean($in['pincode'] ?? '');
    $exp = max(0, (int)($in['expectedDonors'] ?? 0));

    if ($action === 'camp_create') {
        q('INSERT INTO blood_camps (facility_id,title,description,camp_date,start_time,end_time,venue,city,state,pincode,expected_donors) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$lab, $title, $desc, $date, $start, $end, $venue, $city, $state, $pin ?: null, $exp]);
        $cid = (int)$pdo->lastInsertId();
        log_history($lab, 'Blood Camp', "Organized \"$title\" at $venue, $city", $cid);
        out(['success' => true, 'message' => 'Blood camp created successfully'], 201);
    }
    $cid = (int)($in['id'] ?? 0);
    if (!q('SELECT id FROM blood_camps WHERE id = ? AND facility_id = ?', [$cid, $lab])->fetch()) fail('Camp not found', 404);
    $actual = max(0, (int)($in['actualDonors'] ?? 0));
    q('UPDATE blood_camps SET title=?, description=?, camp_date=?, start_time=?, end_time=?, venue=?, city=?, state=?, pincode=?, expected_donors=?, actual_donors=? WHERE id=?',
        [$title, $desc, $date, $start, $end, $venue, $city, $state, $pin ?: null, $exp, $actual, $cid]);
    log_history($lab, 'Blood Camp', "Updated camp: $title", $cid);
    out(['success' => true, 'message' => 'Camp updated successfully']);

case 'camp_status':
    $cid = (int)($in['id'] ?? 0); $status = clean($in['status'] ?? '');
    if (!in_array($status, ['Upcoming','Ongoing','Completed','Cancelled'], true)) fail('Invalid status');
    $camp = q('SELECT title FROM blood_camps WHERE id = ? AND facility_id = ?', [$cid, $lab])->fetch();
    if (!$camp) fail('Camp not found', 404);
    q('UPDATE blood_camps SET status = ? WHERE id = ?', [$status, $cid]);
    log_history($lab, 'Blood Camp', "Changed camp status to: $status - {$camp['title']}", $cid);
    out(['success' => true, 'message' => "Camp status updated to $status"]);

case 'camp_delete':
    $cid = (int)($in['id'] ?? 0);
    $camp = q('SELECT title FROM blood_camps WHERE id = ? AND facility_id = ?', [$cid, $lab])->fetch();
    if (!$camp) fail('Camp not found', 404);
    q('DELETE FROM blood_camps WHERE id = ?', [$cid]);
    log_history($lab, 'Blood Camp', "Deleted camp: {$camp['title']}");
    out(['success' => true, 'message' => 'Camp deleted successfully']);

/* ---------------- Blood stock ---------------- */
case 'stock':
    out(['success' => true, 'data' => q('SELECT id, blood_group, quantity, expiry_date, updated_at FROM blood_stock WHERE lab_id = ? ORDER BY blood_group', [$lab])->fetchAll()]);

case 'stock_add':
    $g = clean($in['bloodType'] ?? ''); $n = (int)($in['quantity'] ?? 0);
    if (!valid_group($g) || $n <= 0) fail('Please provide valid bloodType and quantity');
    stock_add('lab_id', $lab, $g, $n);
    log_history($lab, 'Stock Update', "Added $n units of $g");
    out(['success' => true, 'message' => 'Blood stock added successfully']);

case 'stock_remove':
    $g = clean($in['bloodType'] ?? ''); $n = (int)($in['quantity'] ?? 0);
    if (!valid_group($g) || $n <= 0) fail('Please provide valid bloodType and quantity');
    $s = q('SELECT id, quantity FROM blood_stock WHERE lab_id = ? AND blood_group = ?', [$lab, $g])->fetch();
    if (!$s) fail("No stock found for blood type $g", 404);
    if ($s['quantity'] < $n) fail("Insufficient stock. Available: {$s['quantity']} units");
    if ($s['quantity'] == $n) q('DELETE FROM blood_stock WHERE id = ?', [$s['id']]);
    else q('UPDATE blood_stock SET quantity = quantity - ? WHERE id = ?', [$n, $s['id']]);
    log_history($lab, 'Stock Update', "Removed $n units of $g");
    out(['success' => true, 'message' => 'Blood stock removed successfully']);

/* ---------------- Hospital requests ---------------- */
case 'requests':
    $rows = q("SELECT r.*, f.name AS hospital_name, f.email AS hospital_email, f.phone AS hospital_phone, f.city AS hospital_city
               FROM blood_requests r JOIN facilities f ON f.id = r.hospital_id WHERE r.lab_id = ? ORDER BY r.created_at DESC", [$lab])->fetchAll();
    out(['success' => true, 'requests' => $rows]);

case 'request_update':
    $rid = (int)($in['id'] ?? 0); $act = clean($in['action'] ?? '');
    if (!in_array($act, ['accept', 'reject'], true)) fail("Invalid action. Must be 'accept' or 'reject'");
    $r = q('SELECT r.*, f.name AS hospital_name FROM blood_requests r JOIN facilities f ON f.id = r.hospital_id WHERE r.id = ? AND r.lab_id = ?', [$rid, $lab])->fetch();
    if (!$r) fail('Request not found', 404);
    if ($r['status'] !== 'pending') fail('Request already processed');

    $pdo->beginTransaction();
    try {
        if ($act === 'accept') {
            $s = q('SELECT id, quantity FROM blood_stock WHERE lab_id = ? AND blood_group = ? FOR UPDATE', [$lab, $r['blood_type']])->fetch();
            $avail = $s['quantity'] ?? 0;
            if (!$s || $avail < $r['units']) { $pdo->rollBack(); fail("Insufficient stock. Available: $avail units, Requested: {$r['units']} units"); }
            if ($avail == $r['units']) q('DELETE FROM blood_stock WHERE id = ?', [$s['id']]);
            else q('UPDATE blood_stock SET quantity = quantity - ? WHERE id = ?', [$r['units'], $s['id']]);
            stock_add('hospital_id', (int)$r['hospital_id'], $r['blood_type'], (int)$r['units']);
            log_history($lab, 'Stock Update', "Transferred {$r['units']} units of {$r['blood_type']} to {$r['hospital_name']}", $rid);
            log_history((int)$r['hospital_id'], 'Stock Update', "Received {$r['units']} units of {$r['blood_type']} from blood lab", $rid);
        } else {
            log_history($lab, 'Stock Update', "Rejected blood request for {$r['units']} units of {$r['blood_type']} from {$r['hospital_name']}", $rid);
        }
        q('UPDATE blood_requests SET status = ?, processed_at = NOW() WHERE id = ?', [$act === 'accept' ? 'accepted' : 'rejected', $rid]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    out(['success' => true, 'message' => 'Request ' . ($act === 'accept' ? 'accepted' : 'rejected') . ' successfully']);

/* ---------------- Donors: search + record donation ---------------- */
case 'donor_search':
    $term = clean($in['term'] ?? '');
    if ($term === '') fail('Search term required');
    $like = '%' . $term . '%';
    $rows = q('SELECT id, full_name, email, phone, blood_group, last_donation_date FROM donors
               WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY last_donation_date DESC LIMIT 20', [$like, $like, $like])->fetchAll();
    foreach ($rows as &$r) {
        $el = donor_eligibility($r['last_donation_date']);
        $r['eligible'] = $el['eligible']; $r['nextEligibleDate'] = $el['nextEligibleDate'];
        $r['totalDonations'] = (int)q('SELECT COUNT(*) FROM donations WHERE donor_id = ?', [$r['id']])->fetchColumn();
    }
    out(['success' => true, 'donors' => $rows]);

case 'donate':
    $did = (int)($in['id'] ?? 0); $qty = max(1, (int)($in['quantity'] ?? 1));
    $remarks = clean($in['remarks'] ?? ''); $grp = clean($in['bloodGroup'] ?? '');
    $d = q('SELECT * FROM donors WHERE id = ?', [$did])->fetch();
    if (!$d) fail('Donor not found', 404);
    if ($grp !== '' && !valid_group($grp)) fail('Invalid blood group');
    $grp = $grp ?: $d['blood_group'];
    $el = donor_eligibility($d['last_donation_date']);
    if (!$el['eligible']) fail('Donor cannot donate yet. Minimum 3 months required between donations.');

    $pdo->beginTransaction();
    try {
        q('INSERT INTO donations (donor_id, facility_id, blood_group, quantity, remarks, verified) VALUES (?,?,?,?,?,1)', [$did, $lab, $grp, $qty, $remarks ?: null]);
        q('UPDATE donors SET last_donation_date = NOW(), blood_group = ? WHERE id = ?', [$grp, $did]);
        stock_add('lab_id', $lab, $grp, $qty);
        log_history($lab, 'Donation', "Recorded donation from {$d['full_name']} - $qty unit(s) of $grp", $did);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    out(['success' => true, 'message' => 'Donation recorded successfully']);

case 'recent_donations':
    $today = (int)q('SELECT COUNT(*) FROM donations WHERE facility_id = ? AND DATE(donation_date) = CURDATE()', [$lab])->fetchColumn();
    $week = (int)q('SELECT COUNT(*) FROM donations WHERE facility_id = ? AND YEARWEEK(donation_date, 0) = YEARWEEK(CURDATE(), 0)', [$lab])->fetchColumn();
    $total = (int)q('SELECT COUNT(*) FROM donations WHERE facility_id = ?', [$lab])->fetchColumn();
    $rows = q('SELECT dn.donation_date, dn.blood_group, dn.quantity, dn.remarks, d.full_name FROM donations dn JOIN donors d ON d.id = dn.donor_id
               WHERE dn.facility_id = ? ORDER BY dn.donation_date DESC LIMIT 10', [$lab])->fetchAll();
    out(['success' => true, 'stats' => ['today' => $today, 'thisWeek' => $week, 'total' => $total], 'donations' => $rows]);

default:
    fail('Unknown action', 404);
}
