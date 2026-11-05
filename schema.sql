-- Sistema de marcações - Salão Aurora (fictício)
-- mysql -u root -p < database/schema.sql

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS marcacoes
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE marcacoes;

CREATE TABLE IF NOT EXISTS services (
  id           SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(80) NOT NULL,
  duration_min SMALLINT UNSIGNED NOT NULL,
  price_cents  INT UNSIGNED NOT NULL,
  active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id   SMALLINT UNSIGNED NOT NULL,
  client_name  VARCHAR(80)  NOT NULL,
  client_email VARCHAR(190) NOT NULL,
  client_phone VARCHAR(20)  NOT NULL,
  notes        VARCHAR(300) NULL,
  start_at     DATETIME NOT NULL,
  end_at       DATETIME NOT NULL,
  status       ENUM('confirmada', 'concluida', 'cancelada') NOT NULL DEFAULT 'confirmada',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bookings_start (start_at, status),
  CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
  id            SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB;

-- registo de pedidos por IP para limitar abusos (marcações e login)
CREATE TABLE IF NOT EXISTS rate_limits (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action     VARCHAR(30) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  hit_at     DATETIME    NOT NULL,
  KEY idx_rate_lookup (action, ip_address, hit_at)
) ENGINE=InnoDB;

INSERT INTO services (name, duration_min, price_cents) VALUES
  ('Corte de homem', 30, 1200),
  ('Corte de senhora', 45, 1800),
  ('Barba', 30, 800),
  ('Corte + barba', 60, 1800),
  ('Coloração', 90, 3500);
