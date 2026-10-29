<?php
require __DIR__ . '/config.php';
$in = input();
$action = $_GET['action'] ?? '';
$pdo = db();

function redirect_for(string $role): string {
    return ['donor' => 'donor/index.html', 'hospital' => 'hospital/index.html', 'blood-lab' => 'lab/index.html', 'admin' => 'admin/index.html'][$role] ?? 'index.html';
}

switch ($action) {

case 'register_donor':
    $f = [
        'fullName' => clean($in['fullName'] ?? ''), 'email' => strtolower(clean($in['email'] ?? '')),
        'password' => (string)($in['password'] ?? ''), 'phone' => clean($in['phone'] ?? ''),
        'emergencyContact' => clean($in['emergencyContact'] ?? ''), 'bloodGroup' => clean($in['bloodGroup'] ?? ''),
        'dob' => clean($in['dob'] ?? ''), 'gender' => clean($in['gender'] ?? ''),
        'street' => clean($in['street'] ?? ''), 'city' => clean($in['city'] ?? ''),
        'state' => clean($in['state'] ?? ''), 'pincode' => clean($in['pincode'] ?? ''),
        'weight' => $in['weight'] ?? null, 'height' => $in['height'] ?? null,
        'hasDiseases' => !empty($in['hasDiseases']) ? 1 : 0, 'diseaseDetails' => clean($in['diseaseDetails'] ?? ''),
    ];
    foreach (['fullName','email','password','phone','bloodGroup','dob','gender','street','city','state','pincode'] as $k)
        if ($f[$k] === '') fail("Field required: $k");
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email');
    if (strlen($f['password']) < 6) fail('Password must be at least 6 characters');
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $f['phone'])) fail('Please enter a valid phone number');
    if (!valid_group($f['bloodGroup'])) fail('Invalid blood group');
    if (!in_array($f['gender'], ['Male','Female','Other'], true)) fail('Invalid gender');
    $ts = strtotime($f['dob']);
    if (!$ts) fail('Invalid date of birth');
    $age = (int) floor((time() - $ts) / (365.25 * 86400));
    if ($age < 18) fail('Must be at least 18 years old to donate blood');
    if ($age > 65) fail('Age limit for blood donation is 65 years');
    if ($f['weight'] !== null && $f['weight'] !== '' && (float)$f['weight'] < 45) fail('Minimum weight should be 45kg to donate blood');

    $chk = $pdo->prepare('SELECT 1 FROM donors WHERE email = ? UNION SELECT 1 FROM facilities WHERE email = ? UNION SELECT 1 FROM admins WHERE email = ?');
    $chk->execute([$f['email'], $f['email'], $f['email']]);
    if ($chk->fetch()) fail('This email is already registered', 409);

    $st = $pdo->prepare('INSERT INTO donors (full_name,email,password,phone,emergency_contact,street,city,state,pincode,blood_group,dob,age,gender,weight,height,has_diseases,disease_details)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([$f['fullName'], $f['email'], password_hash($f['password'], PASSWORD_BCRYPT), $f['phone'], $f['emergencyContact'] ?: null,
        $f['street'], $f['city'], $f['state'], $f['pincode'], $f['bloodGroup'], date('Y-m-d', $ts), $age, $f['gender'],
        ($f['weight'] === '' ? null : $f['weight']), ($f['height'] === '' ? null : $f['height']), $f['hasDiseases'], $f['diseaseDetails'] ?: null]);
    out(['success' => true, 'message' => 'Donor registered successfully! You can login now.'], 201);

case 'register_facility':
    $f = [
        'name' => clean($in['name'] ?? ''), 'email' => strtolower(clean($in['email'] ?? '')),
        'password' => (string)($in['password'] ?? ''), 'phone' => clean($in['phone'] ?? ''),
        'emergencyContact' => clean($in['emergencyContact'] ?? ''), 'street' => clean($in['street'] ?? ''),
        'city' => clean($in['city'] ?? ''), 'state' => clean($in['state'] ?? ''), 'pincode' => clean($in['pincode'] ?? ''),
        'registrationNumber' => strtoupper(clean($in['registrationNumber'] ?? '')),
        'facilityType' => clean($in['facilityType'] ?? ''), 'facilityCategory' => clean($in['facilityCategory'] ?? 'Private'),
        'proof' => clean($in['registrationProofUrl'] ?? ''),
        'open' => clean($in['openTime'] ?? '09:00'), 'close' => clean($in['closeTime'] ?? '18:00'),
        'days' => is_array($in['workingDays'] ?? null) ? $in['workingDays'] : [],
        'is24x7' => !empty($in['is24x7']) ? 1 : 0, 'emergency' => !empty($in['emergencyServices']) ? 1 : 0,
    ];
    foreach (['name','email','password','phone','emergencyContact','street','city','state','pincode','registrationNumber','facilityType','proof'] as $k)
        if ($f[$k] === '') fail("Field required: $k");
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email');
    if (strlen($f['password']) < 6) fail('Password must be at least 6 characters');
    if (!in_array($f['facilityType'], ['hospital','blood-lab'], true)) fail('Invalid facility type');
    if (!preg_match('~^https?://~i', $f['proof'])) fail('Registration proof must be a link starting with http:// or https://');
    if (!in_array($f['facilityCategory'], ['Government','Private','Trust','Charity','Other'], true)) $f['facilityCategory'] = 'Private';
    $validDays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $days = implode(',', array_values(array_intersect($validDays, $f['days'])));
    if ($days === '') $days = 'Mon,Tue,Wed,Thu,Fri';

    $chk = $pdo->prepare('SELECT 1 FROM donors WHERE email = ? UNION SELECT 1 FROM facilities WHERE email = ? UNION SELECT 1 FROM admins WHERE email = ?');
    $chk->execute([$f['email'], $f['email'], $f['email']]);
    if ($chk->fetch()) fail('This email is already registered', 409);
    $chk = $pdo->prepare('SELECT 1 FROM facilities WHERE registration_number = ?');
    $chk->execute([$f['registrationNumber']]);
    if ($chk->fetch()) fail('This registration number already exists', 409);

    $st = $pdo->prepare('INSERT INTO facilities (name,email,password,phone,emergency_contact,street,city,state,pincode,registration_number,facility_type,facility_category,registration_proof_url,open_time,close_time,working_days,is_24x7,emergency_services)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([$f['name'], $f['email'], password_hash($f['password'], PASSWORD_BCRYPT), $f['phone'], $f['emergencyContact'], $f['street'], $f['city'],
        $f['state'], $f['pincode'], $f['registrationNumber'], $f['facilityType'], $f['facilityCategory'], $f['proof'], $f['open'], $f['close'], $days, $f['is24x7'], $f['emergency']]);
    out(['success' => true, 'message' => 'Facility registered successfully! Please wait for admin approval.'], 201);

case 'login':
    $email = strtolower(clean($in['email'] ?? ''));
    $password = (string)($in['password'] ?? '');
    if ($email === '' || $password === '') fail('Email and password are required');

    $user = null; $role = null; $table = null;
    foreach ([['donors', 'donor'], ['admins', 'admin'], ['facilities', null]] as [$t, $r]) {
        $st = $pdo->prepare("SELECT * FROM $t WHERE email = ? LIMIT 1");
        $st->execute([$email]);
        if ($row = $st->fetch()) { $user = $row; $table = $t; $role = $r ?? $row['facility_type']; break; }
    }
    if (!$user) fail('User not found', 404);
    if (!password_verify($password, $user['password'])) fail('Invalid credentials', 401);
    if (isset($user['is_active']) && !$user['is_active']) fail('Your account is deactivated', 403);

    if ($table === 'facilities') {
        if ($user['status'] === 'pending') fail('Your account is awaiting admin approval. Please wait before logging in.', 403);
        if ($user['status'] === 'rejected') fail('Your registration has been rejected by admin. Contact support for details.', 403);
    }
    $pdo->prepare("UPDATE $table SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
    if ($table === 'facilities') log_history((int)$user['id'], 'Login', 'Facility logged in successfully');

    session_regenerate_id(true);
    $name = $user['full_name'] ?? $user['name'];
    $_SESSION['user'] = ['id' => (int)$user['id'], 'role' => $role, 'name' => $name, 'email' => $user['email']];
    out(['success' => true, 'message' => 'Login successful', 'user' => $_SESSION['user'], 'redirect' => redirect_for($role)]);

case 'me':
    if (empty($_SESSION['user'])) fail('Not logged in', 401);
    out(['success' => true, 'user' => $_SESSION['user']]);

case 'logout':
    $_SESSION = [];
    session_destroy();
    out(['success' => true]);

case 'contact':
    $name = clean($in['name'] ?? ''); $email = clean($in['email'] ?? ''); $msg = clean($in['message'] ?? '');
    if ($name === '' || $email === '' || $msg === '') fail('Name, email and message are required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email');
    $pdo->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)')->execute([$name, $email, clean($in['subject'] ?? ''), $msg]);
    out(['success' => true, 'message' => 'Thank you! Your message has been sent.']);

case 'public_stats':
    out(['success' => true,
        'donors' => (int)$pdo->query('SELECT COUNT(*) FROM donors')->fetchColumn(),
        'facilities' => (int)$pdo->query("SELECT COUNT(*) FROM facilities WHERE status='approved'")->fetchColumn(),
        'donations' => (int)$pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn(),
        'units' => (int)$pdo->query('SELECT COALESCE(SUM(quantity),0) FROM blood_stock')->fetchColumn()]);

default:
    fail('Unknown action', 404);
}
