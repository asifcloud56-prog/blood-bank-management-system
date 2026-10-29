-- BBMS - Blood Bank Management System (XAMPP / MySQL)
-- phpMyAdmin > Import mein ye file import karein.

CREATE DATABASE IF NOT EXISTS bbms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bbms;

DROP TABLE IF EXISTS contact_messages, donor_contacts, blood_requests, blood_camps, blood_stock,
  donations, facility_history, facilities, donors, admins;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  last_login DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE donors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  emergency_contact VARCHAR(20) NULL,
  street VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  pincode VARCHAR(10) NOT NULL,
  blood_group ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
  dob DATE NULL,
  age INT NOT NULL,
  gender ENUM('Male','Female','Other') NOT NULL,
  weight DECIMAL(5,1) NULL,
  height DECIMAL(5,1) NULL,
  has_diseases TINYINT(1) NOT NULL DEFAULT 0,
  disease_details TEXT NULL,
  last_donation_date DATETIME NULL,
  eligible_to_donate TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (blood_group), INDEX (city)
) ENGINE=InnoDB;

CREATE TABLE facilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  emergency_contact VARCHAR(20) NOT NULL,
  street VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  pincode VARCHAR(10) NOT NULL,
  registration_number VARCHAR(100) NOT NULL UNIQUE,
  facility_type ENUM('hospital','blood-lab') NOT NULL,
  facility_category ENUM('Government','Private','Trust','Charity','Other') NOT NULL DEFAULT 'Private',
  registration_proof_url VARCHAR(500) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  approved_at DATETIME NULL,
  rejection_reason VARCHAR(500) NULL,
  open_time VARCHAR(5) NOT NULL DEFAULT '09:00',
  close_time VARCHAR(5) NOT NULL DEFAULT '18:00',
  working_days VARCHAR(40) NOT NULL DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  is_24x7 TINYINT(1) NOT NULL DEFAULT 0,
  emergency_services TINYINT(1) NOT NULL DEFAULT 0,
  last_login DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (facility_type, status)
) ENGINE=InnoDB;

CREATE TABLE facility_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  event_type VARCHAR(50) NOT NULL,
  description VARCHAR(500) NOT NULL,
  reference_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
  INDEX (facility_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE donations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  donor_id INT NOT NULL,
  facility_id INT NULL,
  donation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  blood_group ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  remarks VARCHAR(500) NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
  FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL,
  INDEX (donor_id, donation_date), INDEX (facility_id, donation_date)
) ENGINE=InnoDB;

-- Stock: ek row ya to blood-lab ki hoti hai (lab_id) ya hospital ki (hospital_id)
CREATE TABLE blood_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  blood_group ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  expiry_date DATE NOT NULL,
  lab_id INT NULL,
  hospital_id INT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (lab_id) REFERENCES facilities(id) ON DELETE CASCADE,
  FOREIGN KEY (hospital_id) REFERENCES facilities(id) ON DELETE CASCADE,
  INDEX (lab_id, blood_group), INDEX (hospital_id, blood_group)
) ENGINE=InnoDB;

CREATE TABLE blood_camps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  camp_date DATE NOT NULL,
  start_time VARCHAR(5) NOT NULL,
  end_time VARCHAR(5) NOT NULL,
  venue VARCHAR(200) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  pincode VARCHAR(10) NULL,
  expected_donors INT NOT NULL DEFAULT 0,
  actual_donors INT NOT NULL DEFAULT 0,
  status ENUM('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
  INDEX (camp_date), INDEX (status)
) ENGINE=InnoDB;

CREATE TABLE blood_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT NOT NULL,
  lab_id INT NOT NULL,
  blood_type ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
  units INT NOT NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  processed_at DATETIME NULL,
  notes VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (hospital_id) REFERENCES facilities(id) ON DELETE CASCADE,
  FOREIGN KEY (lab_id) REFERENCES facilities(id) ON DELETE CASCADE,
  INDEX (hospital_id), INDEX (lab_id, status)
) ENGINE=InnoDB;

CREATE TABLE donor_contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  donor_id INT NOT NULL,
  facility_id INT NOT NULL,
  contacted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
  FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(200) NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
