-- ============================================================
-- LibTrack ERP - Database Schema
-- Library Management System for Academic Library
-- ============================================================

CREATE DATABASE IF NOT EXISTS libtrack_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE libtrack_db;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    username   VARCHAR(100)   NOT NULL UNIQUE,
    password   VARCHAR(255)   NOT NULL,
    full_name  VARCHAR(150)   NOT NULL DEFAULT '',
    role       ENUM('admin','librarian') NOT NULL DEFAULT 'librarian',
    created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: books
-- ============================================================
CREATE TABLE IF NOT EXISTS books (
    id         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    title      VARCHAR(255)   NOT NULL,
    author     VARCHAR(150)   NOT NULL,
    isbn       VARCHAR(30)    DEFAULT NULL,
    category   VARCHAR(100)   NOT NULL,
    publisher  VARCHAR(150)   DEFAULT NULL,
    year       YEAR           DEFAULT NULL,
    stock      INT UNSIGNED   NOT NULL DEFAULT 1,
    status     ENUM('Tersedia','Dipinjam') NOT NULL DEFAULT 'Tersedia',
    created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status   (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: members
-- ============================================================
CREATE TABLE IF NOT EXISTS members (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150)  NOT NULL,
    email       VARCHAR(255)  NOT NULL,
    phone       VARCHAR(20)   DEFAULT NULL,
    address     TEXT          DEFAULT NULL,
    member_code VARCHAR(20)   NOT NULL UNIQUE,
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    member_id   INT UNSIGNED  NOT NULL,
    book_id     INT UNSIGNED  NOT NULL,
    borrow_date DATE          NOT NULL,
    due_date    DATE          NOT NULL,
    return_date DATE          DEFAULT NULL,
    status      ENUM('Dipinjam','Dikembalikan') NOT NULL DEFAULT 'Dipinjam',
    notes       TEXT          DEFAULT NULL,
    fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (book_id)   REFERENCES books(id)   ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_status      (status),
    INDEX idx_borrow_date (borrow_date),
    INDEX idx_member_id   (member_id),
    INDEX idx_book_id     (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: settings
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    setting_key   VARCHAR(100)  NOT NULL UNIQUE,
    setting_value TEXT          NOT NULL,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED: Default Admin
-- Password: admin123
-- ============================================================
-- Password: admin123  (bcrypt cost=10)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$PE36nU6nDbjxoj2FK2rzpuY06wDs6wmlQpXUJp9dEDkhz2ua23eTm', 'Administrator', 'admin');

-- ============================================================
-- SEED: Default Settings
-- ============================================================
INSERT INTO settings (setting_key, setting_value) VALUES
('fine_per_day', '1000');

-- ============================================================
-- SEED: Sample Books
-- ============================================================
INSERT INTO books (title, author, isbn, category, publisher, year, stock, status) VALUES
('Pemrograman Web dengan PHP', 'Budi Raharjo', '978-602-1234-01-1', 'Teknologi Informasi', 'Informatika', 2021, 3, 'Tersedia'),
('Algoritma dan Struktur Data', 'Rinaldi Munir', '978-602-1234-02-2', 'Ilmu Komputer', 'Informatika', 2020, 2, 'Tersedia'),
('Basis Data', 'Fathansyah', '978-602-1234-03-3', 'Teknologi Informasi', 'Informatika', 2019, 4, 'Tersedia'),
('Kalkulus Jilid 1', 'James Stewart', '978-602-1234-04-4', 'Matematika', 'Erlangga', 2018, 2, 'Tersedia'),
('Fisika Universitas', 'Hugh D. Young', '978-602-1234-05-5', 'Fisika', 'Erlangga', 2019, 3, 'Tersedia'),
('Pengantar Jaringan Komputer', 'Forouzan', '978-602-1234-06-6', 'Teknologi Informasi', 'Salemba Teknika', 2020, 2, 'Dipinjam'),
('Pemrograman Berorientasi Objek', 'Deitel & Deitel', '978-602-1234-07-7', 'Ilmu Komputer', 'Andi', 2021, 1, 'Tersedia'),
('Sistem Operasi', 'Abraham Silberschatz', '978-602-1234-08-8', 'Teknologi Informasi', 'Wiley', 2018, 2, 'Tersedia');

-- ============================================================
-- SEED: Sample Members
-- ============================================================
INSERT INTO members (name, email, phone, address, member_code, is_active) VALUES
('Andi Prasetyo', 'andi.prasetyo@mahasiswa.ac.id', '081234567890', 'Jl. Merdeka No. 10, Bandung', 'MBR-001', 1),
('Siti Rahayu', 'siti.rahayu@mahasiswa.ac.id', '081234567891', 'Jl. Sudirman No. 5, Bandung', 'MBR-002', 1),
('Budi Santoso', 'budi.santoso@mahasiswa.ac.id', '081234567892', 'Jl. Diponegoro No. 22, Bandung', 'MBR-003', 1),
('Dewi Lestari', 'dewi.lestari@mahasiswa.ac.id', '081234567893', 'Jl. Gajah Mada No. 8, Bandung', 'MBR-004', 1),
('Reza Firmansyah', 'reza.firmansyah@mahasiswa.ac.id', '081234567894', 'Jl. Pahlawan No. 15, Bandung', 'MBR-005', 1);

-- ============================================================
-- SEED: Sample Active Transaction
-- ============================================================
INSERT INTO transactions (member_id, book_id, borrow_date, due_date, return_date, status) VALUES
(1, 6, CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 9 DAY, NULL, 'Dipinjam');
