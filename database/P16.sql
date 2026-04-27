DROP DATABASE IF EXISTS student_portal;
CREATE DATABASE student_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_portal;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    personal_email VARCHAR(100) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    password_changed TINYINT(1) DEFAULT 0,
    role ENUM('student','teacher','admin') NOT NULL,
    is_active INT DEFAULT 0,
    verification_code VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE teacher (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    employee_number VARCHAR(50) UNIQUE,
    hire_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    admin_level ENUM('super','regular') DEFAULT 'regular',
    permissions TEXT,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE speciality (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    year_number INT NOT NULL
);

CREATE TABLE section (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    speciality_id INT NOT NULL,
    year_id INT NOT NULL,
    FOREIGN KEY (speciality_id) REFERENCES speciality(id),
    FOREIGN KEY (year_id) REFERENCES years(id)
);

CREATE TABLE `group` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    section_id INT NOT NULL,
    FOREIGN KEY (section_id) REFERENCES section(id) ON DELETE CASCADE
);

CREATE TABLE student (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    student_number VARCHAR(50) UNIQUE,
    section_id INT NOT NULL,
    group_id INT NOT NULL,
    birth_date DATE,
    enrollment_year YEAR,
    FOREIGN KEY (group_id)  REFERENCES `group`(id),
    FOREIGN KEY (section_id) REFERENCES section(id),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    credits INT NOT NULL,
    coefficient INT NOT NULL,
    hours INT NOT NULL,
    semester ENUM('S1','S2') NOT NULL,
    speciality_id INT NOT NULL,
    year_id INT NOT NULL,
    FOREIGN KEY (speciality_id) REFERENCES speciality(id),
    FOREIGN KEY (year_id) REFERENCES years(id)
);

CREATE TABLE course_assignment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL,
    section_id INT NOT NULL,
    academic_year YEAR NOT NULL,
    group_id INT NOT NULL,
    semester ENUM('S1','S2') NOT NULL,
    teaching_type ENUM('C','TD','TP') NOT NULL,
    hours_per_week DECIMAL(4,2),
    FOREIGN KEY (teacher_id) REFERENCES teacher(id),
    FOREIGN KEY (course_id)  REFERENCES courses(id),
    FOREIGN KEY (section_id) REFERENCES section(id),
    FOREIGN KEY (group_id)   REFERENCES `group`(id)
);

CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    grade DECIMAL(5, 2) NULL,
    td_grade DECIMAL(5, 2) NULL,
    tp_grade DECIMAL(5, 2) NULL,
    final_grade DECIMAL(5, 2) NULL,
    rattrapage_grade DECIMAL(5, 2) NULL,
    is_dette BOOLEAN DEFAULT FALSE,
    comment VARCHAR(255),
    date_recorded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY student_course_uidx (student_id, course_id)
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    date_recorded DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'Excused') DEFAULT 'Absent',
    remarks VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_date DATETIME NOT NULL,
    exam_type ENUM('Final','Resit') DEFAULT 'Final',
    room VARCHAR(50),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
);

CREATE TABLE password_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
