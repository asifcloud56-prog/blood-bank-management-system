<?php
// Browser mein ek baar kholein: http://localhost/bbms/seed_admin.php
// Admin account ban jayega. Kaam hone ke baad is file ko delete kar dein.
require __DIR__ . '/api/config.php';
header('Content-Type: text/html; charset=utf-8');

$name = 'BBMS Admin';
$email = 'admin@bbms.com';
$password = 'admin123';

$pdo = db();
$pdo->prepare('DELETE FROM admins WHERE email = ?')->execute([$email]);
$pdo->prepare('INSERT INTO admins (name, email, password) VALUES (?,?,?)')
    ->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT)]);

echo "<h3>Admin created</h3><p>Email: <b>$email</b><br>Password: <b>$password</b></p><p>Ab <a href='login.html'>Login</a> karein aur ye file delete kar dein.</p>";
