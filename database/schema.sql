-- Local Tourist Day-Visit Planner - database schema
-- ITE2953, J A S R Perera (E2410994)
DROP DATABASE IF EXISTS mattegoda_planner;
CREATE DATABASE mattegoda_planner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mattegoda_planner;

CREATE TABLE category (
  category_id  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(30) NOT NULL,
  badge_colour CHAR(7)     NOT NULL,
  marker_icon  VARCHAR(50) NULL,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB;

CREATE TABLE place (
  place_id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name               VARCHAR(120) NOT NULL,
  summary            VARCHAR(200) NOT NULL,
  description        TEXT         NOT NULL,
  latitude           DECIMAL(9,6) NOT NULL,
  longitude          DECIMAL(9,6) NOT NULL,
  access_point       VARCHAR(150) NULL,
  opening_time       TIME NULL,
  closing_time       TIME NULL,
  closed_days        SET('Mon','Tue','Wed','Thu','Fri','Sat','Sun','Poya') NOT NULL DEFAULT '',
  entry_fee          VARCHAR(120) NULL,
  best_time          VARCHAR(120) NULL,
  visit_duration_min SMALLINT UNSIGNED NULL,
  travel_tips        TEXT NULL,
  facilities         VARCHAR(255) NULL,
  distance_km        DECIMAL(5,2) NULL,
  travel_min         SMALLINT UNSIGNED NULL,
  info_source        VARCHAR(255) NULL,
  is_active          TINYINT(1) NOT NULL DEFAULT 1,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (place_id),
  KEY idx_place_active_distance (is_active, distance_km)
) ENGINE=InnoDB;

CREATE TABLE place_category (
  place_id    INT UNSIGNED     NOT NULL,
  category_id TINYINT UNSIGNED NOT NULL,
  is_primary  TINYINT(1)       NOT NULL DEFAULT 0,
  PRIMARY KEY (place_id, category_id),
  CONSTRAINT fk_pc_place    FOREIGN KEY (place_id)    REFERENCES place(place_id)       ON DELETE CASCADE,
  CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE place_photo (
  photo_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  place_id    INT UNSIGNED NOT NULL,
  file_path   VARCHAR(255) NOT NULL,
  caption     VARCHAR(150) NULL,
  source      VARCHAR(255) NOT NULL,
  sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (photo_id),
  CONSTRAINT fk_photo_place FOREIGN KEY (place_id) REFERENCES place(place_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admin_user (
  admin_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username        VARCHAR(50)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until    DATETIME NULL,
  last_login      DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

CREATE TABLE visit_plan (
  plan_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ref_code   CHAR(8)  NOT NULL,
  visit_date DATE     NOT NULL,
  start_time TIME     NOT NULL DEFAULT '08:00:00',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (plan_id),
  UNIQUE KEY uq_plan_ref (ref_code)
) ENGINE=InnoDB;

CREATE TABLE visit_plan_item (
  plan_id     INT UNSIGNED     NOT NULL,
  sequence_no TINYINT UNSIGNED NOT NULL,
  place_id    INT UNSIGNED     NOT NULL,
  PRIMARY KEY (plan_id, sequence_no),
  CONSTRAINT fk_item_plan  FOREIGN KEY (plan_id)  REFERENCES visit_plan(plan_id) ON DELETE CASCADE,
  CONSTRAINT fk_item_place FOREIGN KEY (place_id) REFERENCES place(place_id)     ON DELETE RESTRICT
) ENGINE=InnoDB;