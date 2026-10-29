<?php
require __DIR__ . '/config.php';
$user = auth(['hospital', 'blood-lab']);
$in = input();
$pdo = db();
$id = $user['id'];
$action = $_GET['action'] ?? '';

switch ($action) {

case 'profile':
    $st = $pdo->prepare('SELECT * FROM facilities WHERE id = ?');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) fail('Facility not found', 404);
    out(['success' => true, 'facility' => facility_public($f)]);

case 'update_profile':
    $st = $pdo->prepare('SELECT * FROM facilities WHERE id = ?');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) fail('Facility not found', 404);

    $name = clean($in['name'] ?? $f['name']);
    $phone = clean($in['phone'] ?? $f['phone']);
    $emg = clean($in['emergencyContact'] ?? $f['emergency_contact']);
    $a = $in['address'] ?? [];
    $street = clean($a['street'] ?? $f['street']); $city = clean($a['city'] ?? $f['city']);
    $state = clean($a['state'] ?? $f['state']); $pin = clean($a['pincode'] ?? $f['pincode']);
    $oh = $in['operatingHours'] ?? [];
    $open = clean($oh['open'] ?? $f['open_time']); $close = clean($oh['close'] ?? $f['close_time']);
    $days = isset($oh['workingDays']) && is_array($oh['workingDays'])
        ? implode(',', array_intersect(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], $oh['workingDays'])) : $f['working_days'];
    $is24 = isset($in['is24x7']) ? (int)!empty($in['is24x7']) : $f['is_24x7'];
    $emgSvc = isset($in['emergencyServices']) ? (int)!empty($in['emergencyServices']) : $f['emergency_services'];
    if ($name === '' || $phone === '') fail('Name and phone are required');

    $pdo->prepare('UPDATE facilities SET name=?, phone=?, emergency_contact=?, street=?, city=?, state=?, pincode=?, open_time=?, close_time=?, working_days=?, is_24x7=?, emergency_services=? WHERE id=?')
        ->execute([$name, $phone, $emg, $street, $city, $state, $pin, $open, $close, $days, $is24, $emgSvc, $id]);

    if (!empty($in['password'])) {
        if (strlen($in['password']) < 6) fail('Password must be at least 6 characters long');
        $pdo->prepare('UPDATE facilities SET password=? WHERE id=?')->execute([password_hash($in['password'], PASSWORD_BCRYPT), $id]);
    }
    log_history($id, 'Profile Update', 'Facility profile updated by user');
    $_SESSION['user']['name'] = $name;
    out(['success' => true, 'message' => 'Profile updated successfully']);

case 'labs':   // hospital ke liye approved blood labs ki list
    $st = $pdo->query("SELECT id, name, email, phone, street, city, state, open_time, close_time, working_days, is_24x7 FROM facilities
                       WHERE facility_type='blood-lab' AND status='approved' AND is_active=1 ORDER BY name");
    out(['success' => true, 'labs' => $st->fetchAll()]);

default:
    fail('Unknown action', 404);
}
