-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS student_management_db;
USE student_management_db;

-- 2. Create Roles Table (For assigning permissions)
CREATE TABLE IF NOT EXISTS role (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Insert Default Roles
INSERT INTO role (role_name) VALUES ('Admin'), ('Faculty');

-- 3. Create Users Table (System Administrators & Faculty Staff)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Will store PHP password_hash()
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NULL,
    status ENUM('active', 'inactive', 'deactivated') DEFAULT 'active',
    two_fa_enabled TINYINT(1) DEFAULT 0, -- 0 = Disabled, 1 = Enabled
    two_fa_type ENUM('email', 'sms', 'none') DEFAULT 'none',
    otp_code VARCHAR(10) NULL,
    otp_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. Create Students Table
CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(50) PRIMARY KEY, -- Custom Format (e.g., "2026-0001")
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    year_level INT NOT NULL, -- 1, 2, 3, 4
    semester VARCHAR(50) NOT NULL, -- e.g., "1st Semester 2026"
    status ENUM('enrolled', 'dropout', 'graduating', 'graduated') DEFAULT 'enrolled',
    supporting_document VARCHAR(255) NULL, -- Stores file path (e.g., "uploads/docs/doc_123.pdf")
    gpa DECIMAL(3,2) DEFAULT 0.00, -- Automatically computed/updated via backend
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Create Grades Table (For individual subject encoding)
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_description VARCHAR(100) NOT NULL,
    units INT NOT NULL, -- Used for weighted GPA calculations
    final_grade DECIMAL(3,2) NOT NULL, -- e.g., 1.00, 1.75, 3.00
    encoded_by INT NOT NULL, -- Tracks which Faculty/Admin entered the grade
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (encoded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

SELECT * from teachers;

SELECT * from users;


select otp_code, otp_expires_at status from users;


use student_management_db;

ALTER TABLE users 
ADD COLUMN email_otp VARCHAR(6) NULL,
ADD COLUMN otp_expiry DATETIME NULL;



ALTER TABLE users 
ADD COLUMN sms_code VARCHAR(6) NULL DEFAULT NULL AFTER otp_expires_at,
ADD COLUMN sms_expires_at DATETIME NULL DEFAULT NULL AFTER sms_code;

ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'User';

ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'User';

ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'User';

ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'User';
ALTER TABLE users ADD COLUMN sms_code VARCHAR(6) NULL;

ALTER TABLE users 
ADD COLUMN first_name VARCHAR(100),
ADD COLUMN last_name VARCHAR(100),
ADD COLUMN middle_name VARCHAR(100),
ADD COLUMN address VARCHAR(255);

select * from students;

ALTER TABLE students 
MODIFY COLUMN year_level VARCHAR(20) DEFAULT '1st Year',
MODIFY COLUMN semester VARCHAR(20) DEFAULT '1st Sem';
	
ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT 'default.png';

ALTER TABLE students 
ADD COLUMN gender VARCHAR(20),
ADD COLUMN email VARCHAR(100),
ADD COLUMN phone_number VARCHAR(20),
ADD COLUMN address TEXT,
ADD COLUMN section VARCHAR(50),
ADD COLUMN course VARCHAR(100);

CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id VARCHAR(50) UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    department VARCHAR(100),
    email VARCHAR(100),
    phone_number VARCHAR(20)
);

alter table teachers
add column address varchar(100);

DELETE FROM teachers
WHERE id = 3;

select * from students;
CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL
);
UPDATE teachers 
SET password = username, username = password;

UPDATE teachers 
SET password = username, username = password 
WHERE id = 1;

ALTER TABLE teachers MODIFY COLUMN password VARCHAR(255) NOT NULL;
ALTER TABLE teachers 
ADD COLUMN otp_code VARCHAR(10) NULL,
ADD COLUMN otp_expires_at DATETIME NULL;

ALTER TABLE teachers ADD COLUMN username VARCHAR(100) UNIQUE NOT NULL;

ALTER TABLE teachers ADD COLUMN password VARCHAR(255) NOT NULL;

select * from users;

select * from students;

select * from teachers;

select * from subjects;


CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    section_name VARCHAR(50),
    student_name VARCHAR(100),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    section_name VARCHAR(50) NOT NULL,
    student_name VARCHAR(100) NOT NULL
);

SHOW CREATE TABLE subjects;

select * from teachers;

select * from subjects;

select * from students;

ALTER TABLE subjects ADD COLUMN student_id_number VARCHAR(20) NULL AFTER student_name;

ALTER TABLE subjects
DROP COLUMN faculty_id;

ALTER TABLE subjects ADD COLUMN faculty_id INT NOT NULL AFTER id;

select * from subjects;

select * from users;

select * from users;

select * from ;

DELETE FROM teachers
WHERE id in (5);

ALTER TABLE subjects ADD COLUMN teacher_id INT NOT NULL AFTER id;

alter table subjects add column 
year_level varchar(100);

select * from grades;

drop table grades;

CREATE TABLE IF NOT EXISTS grades (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    student_name       VARCHAR(255)  NOT NULL,
    student_id_number  VARCHAR(50)   NOT NULL,
    subject_name       VARCHAR(255)  NOT NULL,
    section            VARCHAR(100)  NOT NULL,
    teacher_id         INT           NOT NULL,
    quizzes            JSON          DEFAULT NULL,
    projects           JSON          DEFAULT NULL,
    midterm_score      DECIMAL(6,2)  DEFAULT NULL,
    midterm_total      DECIMAL(6,2)  DEFAULT NULL,
    final_score        DECIMAL(6,2)  DEFAULT NULL,
    final_total        DECIMAL(6,2)  DEFAULT NULL,
    created_at         DATETIME      DEFAULT NULL,
    updated_at         DATETIME      DEFAULT NULL,
    UNIQUE KEY unique_grade (student_id_number, subject_name, section, teacher_id)
);
 
select * from grades;

select * from users;


alter table subjects add column 
student_name varchar(100);