<?php
require __DIR__ . '/config.php';
$user = auth(['donor']);
$in = input();
$pdo = db();
$id = $user['id'];
$action = $_GET['action'] ?? '';

function donor_row(int $id): array {
    $st = db()->prepare('SELECT * FROM donors WHERE id = ?');
    $st->execute([$id]);
    $d = $st->fetch();
    if (!$d) fail('Donor not found', 404);
    return $d;
}

switch ($action) {

case 'profile':
    $d = donor_row($id);
    $el = donor_eligibility($d['last_donation_date']);
    $st = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE donor_id = ?');
    $st->execute([$id]);
    out(['success' => true, 'donor' => [
        'id' => $id, 'fullName' => $d['full_name'], 'email' => $d['email'], 'phone' => $d['phone'],
        'bloodGroup' => $d['blood_group'], 'age' => (int)$d['age'], 'gender' => $d['gender'],
        'weight' => $d['weight'], 'height' => $d['height'],
        'address' => ['street' => $d['street'], 'city' => $d['city'], 'state' => $d['state'], 'pincode' => $d['pincode']],
        'totalDonations' => (int)$st->fetchColumn(), 'lastDonationDate' => $d['last_donation_date'],
        'nextEligibleDate' => $el['nextEligibleDate'],
        'eligibleToDonate' => $el['eligible'] && $d['eligible_to_donate'], 'createdAt' => $d['created_at'],
    ]]);

case 'update_profile':
    $d = donor_row($id);
    $fullName = clean($in['fullName'] ?? $d['full_name']);
    $phone = clean($in['phone'] ?? $d['phone']);
    $a = $in['address'] ?? [];
    $street = clean($a['street'] ?? $d['street']); $city = clean($a['city'] ?? $d['city']);
    $state = clean($a['state'] ?? $d['state']); $pin = clean($a['pincode'] ?? $d['pincode']);
    $age = (int)($in['age'] ?? $d['age']);
    $gender = clean($in['gender'] ?? $d['gender']);
    $weight = ($in['weight'] ?? '') === '' ? $d['weight'] : $in['weight'];

    if ($fullName === '' || $phone === '') fail('Name and phone are required');
    if ($age < 18 || $age > 65) fail('Age must be between 18 and 65');
    if (!in_array($gender, ['Male','Female','Other'], true)) fail('Invalid gender');
    if ($weight !== null && (float)$weight < 45) fail('Minimum weight should be 45kg to donate blood');

    $pdo->prepare('UPDATE donors SET full_name=?, phone=?, street=?, city=?, state=?, pincode=?, age=?, gender=?, weight=? WHERE id=?')
        ->execute([$fullName, $phone, $street, $city, $state, $pin, $age, $gender, $weight, $id]);

    if (!empty($in['password'])) {
        if (strlen($in['password']) < 6) fail('Password must be at least 6 characters');
        $pdo->prepare('UPDATE donors SET password=? WHERE id=?')->execute([password_hash($in['password'], PASSWORD_BCRYPT), $id]);
    }
    $_SESSION['user']['name'] = $fullName;
    out(['success' => true, 'message' => 'Profile updated successfully']);

case 'camps':
    $status = clean($in['status'] ?? 'all');
    $page = max(1, (int)($in['page'] ?? 1)); $limit = min(50, max(1, (int)($in['limit'] ?? 10)));
    $where = ''; $params = [];
    if ($status !== 'all' && $status !== '') { $where = 'WHERE c.status = ?'; $params[] = $status; }
    $total = $pdo->prepare("SELECT COUNT(*) FROM blood_camps c $where");
    $total->execute($params);
    $total = (int)$total->fetchColumn();
    $st = $pdo->prepare("SELECT c.*, f.name AS organizer FROM blood_camps c JOIN facilities f ON f.id = c.facility_id $where
                         ORDER BY c.camp_date ASC LIMIT $limit OFFSET " . (($page - 1) * $limit));
    $st->execute($params);
    out(['success' => true, 'camps' => $st->fetchAll(), 'pagination' => ['total' => $total, 'currentPage' => $page, 'totalPages' => (int)ceil($total / $limit)]]);

case 'history':
    $page = max(1, (int)($in['page'] ?? 1)); $limit = min(50, max(1, (int)($in['limit'] ?? 10)));
    $total = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE donor_id = ?');
    $total->execute([$id]);
    $total = (int)$total->fetchColumn();
    $st = $pdo->prepare("SELECT d.id, d.donation_date, d.blood_group, d.quantity, d.remarks, d.verified,
                                COALESCE(f.name,'N/A') AS facility, f.city, f.state
                         FROM donations d LEFT JOIN facilities f ON f.id = d.facility_id
                         WHERE d.donor_id = ? ORDER BY d.donation_date DESC LIMIT $limit OFFSET " . (($page - 1) * $limit));
    $st->execute([$id]);
    out(['success' => true, 'history' => $st->fetchAll(), 'pagination' => ['total' => $total, 'currentPage' => $page, 'totalPages' => (int)ceil($total / $limit)]]);

case 'stats':
    $d = donor_row($id);
    $st = $pdo->prepare('SELECT COUNT(*) AS c, MAX(donation_date) AS last FROM donations WHERE donor_id = ?');
    $st->execute([$id]);
    $s = $st->fetch();
    $last = $s['last'] ?: $d['last_donation_date'];
    $el = donor_eligibility($last);
    $status = 'Eligible';
    if (!$el['eligible']) $status = "Ineligible (Cooldown: {$el['daysLeft']} days remaining)";
    if ($d['age'] < 18 || $d['age'] > 65) $status = 'Ineligible (Age constraint)';
    elseif ($d['weight'] !== null && $d['weight'] < 45) $status = 'Ineligible (Weight constraint)';
    $upcoming = (int)$pdo->query("SELECT COUNT(*) FROM blood_camps WHERE status='Upcoming' AND camp_date >= CURDATE()")->fetchColumn();
    out(['success' => true, 'dashboard' => [
        'totalDonations' => (int)$s['c'], 'lastDonationDate' => $last, 'nextEligibleDonationDate' => $el['nextEligibleDate'],
        'eligibilityStatus' => $status, 'eligible' => $status === 'Eligible', 'bloodGroup' => $d['blood_group'], 'upcomingCamps' => $upcoming,
    ]]);

default:
    fail('Unknown action', 404);
}
