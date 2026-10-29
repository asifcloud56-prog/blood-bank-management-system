# BBMS — Blood Bank Management System (HTML + CSS + JavaScript + PHP + MySQL)

Sirf XAMPP chahiye. Koi React, Node, npm ya MongoDB nahi.

## Setup (5 steps)
1. XAMPP Control Panel mein **Apache** aur **MySQL** start karein.
2. Poora `bbms` folder `C:\xampp\htdocs\` mein copy karein  ->  `C:\xampp\htdocs\bbms`
3. Browser mein `http://localhost/phpmyadmin` kholein -> **Import** -> `database.sql` select karein -> Go.
   (Ye `bbms` database aur saari tables bana dega.)
4. Admin account banane ke liye ek baar kholein: `http://localhost/bbms/seed_admin.php`
   Login: `admin@bbms.com` / `admin123`  (kaam ke baad `seed_admin.php` delete kar dein)
5. Website kholein: `http://localhost/bbms/`

Agar MySQL ka password set hai to `api/config.php` mein `DB_PASS` change karein.

## Kaise test karein
1. Donor register karein (`Become a Donor`).
2. Hospital aur Blood Lab register karein (`Register Hospital / Lab`) — dono "pending" honge.
3. Admin se login karke **Verification** page par dono ko approve karein.
4. Lab se login: Inventory mein units add karein, Donors page par donor search karke donation record karein, Camps banayein.
5. Hospital se login: Request Blood se lab ko request bhejein.
6. Lab: Requests page par Accept karein -> units lab stock se hospital inventory mein chale jayenge.

## Folder structure
```
bbms/
  index.html, login.html, register-donor.html, register-facility.html, about.html, contact.html
  donor/      index, profile, history, camps
  hospital/   index, request, inventory, donors, history, profile
  lab/        index, inventory, donors, camps, requests, profile
  admin/      index, verification, facilities, donors
  assets/     css/style.css, js/app.js
  api/        config.php (DB + helpers), auth.php, donor.php, lab.php, hospital.php, admin.php, facility.php
  database.sql, seed_admin.php
```

## Original project se farq
- Login ab PHP **session** se hota hai (JWT ki jagah), passwords `password_hash()` (bcrypt) se secure hain.
- MongoDB collections ki jagah MySQL tables hain (donations, blood_stock, blood_requests, facility_history alag tables mein).
- Phone/postal code validation Indian format ke bajaye generic rakhi hai. City/State free text hain.
- Original ke kuch adhoore hisse theek kiye gaye: lab par donation record karne se stock ab sach mein badhta hai,
  admin dashboard par "upcoming camps" ab asli count hai, aur admin APIs login ke baghair open nahi hain.
- Hospital ke liye bhi Profile page add kiya hai (password/operating hours change karne ke liye).
