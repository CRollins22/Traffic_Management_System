-- OWNER: Claude Rollins | run once in phpMyAdmin (Import) or: mysql -u root < database/schema.sql
CREATE DATABASE IF NOT EXISTS tms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tms;

-- Accounts (passwords are stored as hashes, never as plain text). Only 'operator' accounts can open the dashboard.
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('operator','road_user') NOT NULL DEFAULT 'road_user',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Every traffic reading an operator submits, with the green time before and after (BR-01)
CREATE TABLE IF NOT EXISTS traffic_log (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  queue_m      INT NOT NULL,
  minutes      INT NOT NULL,
  green_before INT NOT NULL,
  green_after  INT NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Emergency overrides and restores: the permanent audit trail (BR-02)
CREATE TABLE IF NOT EXISTS emergency_log (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  vehicle_id  VARCHAR(30) NOT NULL,
  event       ENUM('OVERRIDE','RESTORED') NOT NULL,
  distance_m  INT NULL,
  speed_kmh   INT NULL,
  eta_seconds INT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Motorist details (one row per road_user account). Phone and plate must be unique.
CREATE TABLE IF NOT EXISTS motorists (
  user_id   INT PRIMARY KEY,
  full_name VARCHAR(60) NOT NULL,
  phone     VARCHAR(15) NOT NULL,
  plate     VARCHAR(10) NOT NULL,
  UNIQUE KEY uq_phone (phone),
  UNIQUE KEY uq_plate (plate),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Incidents reported by motorists; operators mark them RESOLVED
CREATE TABLE IF NOT EXISTS incident_reports (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  type       ENUM('CRASH','BREAKDOWN','HAZARD','CONGESTION') NOT NULL,
  location   VARCHAR(100) NOT NULL,
  details    VARCHAR(255) NULL,
  status     ENUM('OPEN','RESOLVED') NOT NULL DEFAULT 'OPEN',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- The one junction's live state (signal, green time, emergency), shared by operators and motorists
CREATE TABLE IF NOT EXISTS junction_state (
  id         INT PRIMARY KEY,
  data       MEDIUMBLOB NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Pre-registered operator account: username 'operator', password 'traffic123' (change it after first login)
INSERT IGNORE INTO users (username, password_hash, role) VALUES ('operator', '$2y$10$LAOUFwOH20x3QJTOijZd.O8TABELpQM.5l8Q7JMGJlcoH9fXcNijK', 'operator');
