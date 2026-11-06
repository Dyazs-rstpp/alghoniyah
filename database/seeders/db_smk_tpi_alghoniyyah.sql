-- =========================================================
-- DATABASE AWAL SMK TPI AL-GHONIYYAH
-- Versi sederhana / fondasi tahap pertama
-- Target: MySQL 8+ atau MariaDB yang mendukung InnoDB
-- =========================================================

CREATE DATABASE IF NOT EXISTS db_smk_tpi_alghoniyyah
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_smk_tpi_alghoniyyah;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Jika file ini dijalankan ulang, tabel fondasi akan dibuat ulang.
-- PENTING: backup database terlebih dahulu sebelum menjalankan ulang.
DROP TABLE IF EXISTS class_enrollments;
DROP TABLE IF EXISTS alumni;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS academic_years;
DROP TABLE IF EXISTS school_profile;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. ROLE: satu akun hanya memiliki satu role.
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(60) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. USERS: seluruh akun internal menggunakan tabel yang sama.
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    status ENUM('active','inactive','pending') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role
      FOREIGN KEY (role_id) REFERENCES roles(id)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. PROFIL SEKOLAH: satu baris identitas sekolah.
CREATE TABLE school_profile (
    id TINYINT UNSIGNED PRIMARY KEY,
    school_name VARCHAR(180) NOT NULL,
    npsn VARCHAR(30) NULL,
    address TEXT NULL,
    postal_code VARCHAR(10) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    logo VARCHAR(255) NULL,
    history TEXT NULL,
    vision TEXT NULL,
    mission TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. GURU: data profil pendidik terhubung ke akun.
CREATE TABLE teachers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    employee_number VARCHAR(40) NULL UNIQUE,
    gender ENUM('L','P') NULL,
    birth_place VARCHAR(100) NULL,
    birth_date DATE NULL,
    address TEXT NULL,
    employment_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teachers_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. SISWA: data identitas siswa terhubung ke akun.
CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    nis VARCHAR(30) NULL UNIQUE,
    nisn VARCHAR(30) NULL UNIQUE,
    nik VARCHAR(30) NULL UNIQUE,
    gender ENUM('L','P') NULL,
    birth_place VARCHAR(100) NULL,
    birth_date DATE NULL,
    religion VARCHAR(40) NULL,
    address TEXT NULL,
    parent_name VARCHAR(150) NULL,
    parent_phone VARCHAR(30) NULL,
    admission_year YEAR NULL,
    student_status ENUM('active','graduated','transferred','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 6. ALUMNI: profil alumni merujuk ke data siswa asal jika tersedia.
CREATE TABLE alumni (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    student_id BIGINT UNSIGNED NULL UNIQUE,
    graduation_year YEAR NOT NULL,
    major_name VARCHAR(120) NULL,
    verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_alumni_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_alumni_student
      FOREIGN KEY (student_id) REFERENCES students(id)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. TAHUN AJARAN.
CREATE TABLE academic_years (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year_name VARCHAR(20) NOT NULL UNIQUE,
    start_date DATE NULL,
    end_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 8. KELAS: kelas berada di bawah satu tahun ajaran.
CREATE TABLE classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    grade_level ENUM('X','XI','XII') NOT NULL,
    major_name VARCHAR(120) NOT NULL,
    homeroom_teacher_id BIGINT UNSIGNED NULL,
    capacity SMALLINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_classes_year
      FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_classes_homeroom
      FOREIGN KEY (homeroom_teacher_id) REFERENCES teachers(id)
      ON UPDATE CASCADE ON DELETE SET NULL,
    UNIQUE KEY uq_class_per_year (academic_year_id, class_name)
) ENGINE=InnoDB;

-- 9. MATA PELAJARAN.
CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(30) NULL UNIQUE,
    subject_name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 10. RIWAYAT PENEMPATAN SISWA DI KELAS.
-- Tabel ini menjaga riwayat kelas dari tahun ke tahun.
CREATE TABLE class_enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    enrollment_status ENUM('active','completed','moved') NOT NULL DEFAULT 'active',
    enrolled_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_enrollments_student
      FOREIGN KEY (student_id) REFERENCES students(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_enrollments_class
      FOREIGN KEY (class_id) REFERENCES classes(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_student_class (student_id, class_id),
    INDEX idx_enrollment_class (class_id),
    INDEX idx_enrollment_student_status (student_id, enrollment_status)
) ENGINE=InnoDB;

-- DATA ROLE AWAL
INSERT INTO roles (code, name) VALUES
('superadmin', 'Superadmin'),
('admin', 'Admin'),
('guru', 'Guru'),
('siswa', 'Siswa'),
('alumni', 'Alumni');

-- PROFIL SEKOLAH AWAL (silakan lengkapi melalui panel admin nantinya)
INSERT INTO school_profile (id, school_name)
VALUES (1, 'SMK TPI Al-Ghoniyyah');

-- Belum ada akun pengguna. Akun Superadmin pertama akan dibuat
-- melalui skrip instalasi PHP yang menggunakan password_hash().
