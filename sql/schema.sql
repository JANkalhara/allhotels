-- ============================================================
-- AllHotels.lk  |  Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS allhotels_lk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE allhotels_lk;

-- ------------------------------------------------------------
-- users : customers, owners, admins
-- ------------------------------------------------------------
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(30)  DEFAULT NULL,
    whatsapp        VARCHAR(30)  DEFAULT NULL,
    business_address VARCHAR(255) DEFAULT NULL,
    role            ENUM('customer','owner','admin') NOT NULL DEFAULT 'customer',
    is_verified     TINYINT(1) NOT NULL DEFAULT 0,
    verify_token    VARCHAR(100) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- hotels
-- ------------------------------------------------------------
CREATE TABLE hotels (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    name            VARCHAR(180) NOT NULL,
    address         VARCHAR(255) NOT NULL,
    district        VARCHAR(100) NOT NULL,
    contact_number  VARCHAR(30)  DEFAULT NULL,
    starting_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_guests      INT DEFAULT 0,
    max_guests      INT DEFAULT 0,
    description     TEXT,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    is_premium      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- function_types (Wedding, Meeting, Picnic, Accommodation, Birthday...)
-- ------------------------------------------------------------
CREATE TABLE function_types (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO function_types (name) VALUES
('Wedding'),('Meeting & Events'),('Picnic'),('Accommodation'),('Birthday');

-- ------------------------------------------------------------
-- hotel_function_types (junction)
-- ------------------------------------------------------------
CREATE TABLE hotel_function_types (
    hotel_id         INT NOT NULL,
    function_type_id INT NOT NULL,
    PRIMARY KEY (hotel_id, function_type_id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (function_type_id) REFERENCES function_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- hotel_images (main + gallery, gallery only meaningful for premium)
-- ------------------------------------------------------------
CREATE TABLE hotel_images (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id    INT NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    is_main     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- reviews
-- ------------------------------------------------------------
CREATE TABLE reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id    INT NOT NULL,
    user_id     INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- bookings (premium hotels only)
-- ------------------------------------------------------------
CREATE TABLE bookings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id       INT NOT NULL,
    user_id        INT NOT NULL,
    function_type_id INT DEFAULT NULL,
    event_date     DATE NOT NULL,
    guest_count    INT NOT NULL,
    status         ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (function_type_id) REFERENCES function_types(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- notifications (audit log of email / whatsapp dispatch)
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        VARCHAR(60) NOT NULL,
    message     VARCHAR(255) NOT NULL,
    channel     ENUM('email','whatsapp','both') NOT NULL DEFAULT 'both',
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- password_reset_tokens
-- ------------------------------------------------------------
CREATE TABLE password_reset_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(100) NOT NULL,
    expires_at  DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- contact_messages (public "Contact Us" inquiries)
-- ------------------------------------------------------------
CREATE TABLE contact_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    message     TEXT NOT NULL,
    is_handled  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- demo admin account (password: Admin@123 -- change after first login)
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, role, is_verified)
VALUES ('System Admin', 'admin@allhotels.lk', '$2y$10$xVjWzX2q5S1E1z0f8b2GbeR1lTz1oQe1zKq6b2h0m2QeQe1lTz1oQ', 'admin', 1);
