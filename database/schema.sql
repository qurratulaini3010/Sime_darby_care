-- Sime Darby Care demo schema (MySQL 8+ / MariaDB 10.5+)
CREATE DATABASE IF NOT EXISTS sime_darby_care CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sime_darby_care;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS request_status_history;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS service_providers;
DROP TABLE IF EXISTS premises;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL, phone VARCHAR(35) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, role ENUM('owner','operator','provider') NOT NULL DEFAULT 'owner',
 is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_users_role(role)
) ENGINE=InnoDB;

CREATE TABLE premises (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 owner_id BIGINT UNSIGNED NOT NULL, property_type ENUM('Home','Shop') NOT NULL,
 address VARCHAR(255) NOT NULL, township VARCHAR(100) NOT NULL DEFAULT 'Elmina',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_premises_owner FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_premises_township(township)
) ENGINE=InnoDB;

CREATE TABLE categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE,
 slug VARCHAR(50) NOT NULL UNIQUE, description VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE service_providers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 service_type ENUM('Plumbing','Electrical','Security','Maintenance') NOT NULL,
 company_name VARCHAR(160) NOT NULL, availability ENUM('available','busy','offline') NOT NULL DEFAULT 'available',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 CONSTRAINT fk_provider_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_provider_service(service_type,is_active)
) ENGINE=InnoDB;

CREATE TABLE requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_no VARCHAR(24) NOT NULL UNIQUE,
 owner_id BIGINT UNSIGNED NOT NULL, premise_id BIGINT UNSIGNED NULL, category_id INT UNSIGNED NOT NULL,
 provider_id BIGINT UNSIGNED NULL, title VARCHAR(180) NOT NULL, description TEXT NOT NULL,
 location VARCHAR(255) NOT NULL, township VARCHAR(100) NOT NULL DEFAULT 'Elmina',
 status TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 received,1 assigned,2 on the way,3 in progress,4 resolved',
 priority ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_request_owner FOREIGN KEY(owner_id) REFERENCES users(id),
 CONSTRAINT fk_request_premise FOREIGN KEY(premise_id) REFERENCES premises(id) ON DELETE SET NULL,
 CONSTRAINT fk_request_category FOREIGN KEY(category_id) REFERENCES categories(id),
 CONSTRAINT fk_request_provider FOREIGN KEY(provider_id) REFERENCES service_providers(id) ON DELETE SET NULL,
 CONSTRAINT chk_request_status CHECK(status BETWEEN 0 AND 4),
 INDEX idx_request_status_created(status,created_at), INDEX idx_request_owner(owner_id,created_at),
 INDEX idx_request_township(township), INDEX idx_request_category(category_id)
) ENGINE=InnoDB;

CREATE TABLE request_status_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_id BIGINT UNSIGNED NOT NULL,
 status TINYINT UNSIGNED NOT NULL, changed_by BIGINT UNSIGNED NULL, note VARCHAR(500) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_history_request FOREIGN KEY(request_id) REFERENCES requests(id) ON DELETE CASCADE,
 CONSTRAINT fk_history_user FOREIGN KEY(changed_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT chk_history_status CHECK(status BETWEEN 0 AND 4),
 INDEX idx_history_request_time(request_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 request_id BIGINT UNSIGNED NULL, title VARCHAR(160) NOT NULL, body VARCHAR(500) NOT NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_notification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_notification_request FOREIGN KEY(request_id) REFERENCES requests(id) ON DELETE SET NULL,
 INDEX idx_notification_user_read(user_id,is_read,created_at)
) ENGINE=InnoDB;

CREATE TABLE attachments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_id BIGINT UNSIGNED NOT NULL,
 uploaded_by BIGINT UNSIGNED NOT NULL, original_name VARCHAR(255) NOT NULL,
 stored_name VARCHAR(255) NOT NULL UNIQUE, mime_type VARCHAR(100) NOT NULL,
 size_bytes INT UNSIGNED NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_attachment_request FOREIGN KEY(request_id) REFERENCES requests(id) ON DELETE CASCADE,
 CONSTRAINT fk_attachment_user FOREIGN KEY(uploaded_by) REFERENCES users(id),
 INDEX idx_attachment_request(request_id)
) ENGINE=InnoDB;

INSERT INTO categories(name,slug,description) VALUES
('Fire / Safety Concern','fire-safety','Fire, accident or urgent safety concern'),
('Plumbing Leakage','plumbing','Burst pipe, water leak or drainage issue'),
('Electrical Issue','electrical','Power outage, exposed wiring or electrical fault'),
('Security Concern','security','Break-in, suspicious activity or security concern'),
('Maintenance & Repair','maintenance','Property maintenance and repairs'),
('Other Issue','other','General enquiry or other township issue');

-- Demo password for all seeded accounts: Demo123!
-- Hash generated with PHP password_hash('Demo123!', PASSWORD_DEFAULT).
INSERT INTO users(id,full_name,phone,email,password_hash,role) VALUES
(1,'Amirah Zikri','012-345 6789','amira.owner@example.com','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','owner'),
(2,'Daniel Tan','012-555 0101','daniel.owner@example.com','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','owner'),
(3,'Nurul Hassan','012-555 0102','nurul.owner@example.com','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','owner'),
(4,'Elmina Centre Operator','03-5555 0100','operator@simecare.test','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','operator'),
(5,'Ravi Kumar','012-700 1001','plumber@simecare.test','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','provider'),
(6,'Lim Wei Jian','012-700 1002','electrician@simecare.test','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','provider'),
(7,'Farid Security','012-700 1003','security@simecare.test','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','provider'),
(8,'Siti Maintenance','012-700 1004','maintenance@simecare.test','$2y$12$XMk5AxxPTCheJmq12FytquQgrFVUyaTaz5l6Ya8jAuW3Gol1XXTzC','provider');

INSERT INTO premises(id,owner_id,property_type,address,township) VALUES
(1,1,'Home','A-12, Elmina Green, Shah Alam, Selangor','Elmina'),
(2,2,'Shop','Shop 08, Elmina Lakeside Mall, Shah Alam, Selangor','Elmina'),
(3,3,'Home','B-06, Elmina Valley, Shah Alam, Selangor','Elmina');

INSERT INTO service_providers(id,user_id,service_type,company_name,availability) VALUES
(1,5,'Plumbing','Elmina Plumbing Services','available'),
(2,6,'Electrical','Elmina Electrical Services','available'),
(3,7,'Security','Elmina Security Services','available'),
(4,8,'Maintenance','Elmina Property Maintenance','available');

-- 10 sample requests across all statuses; status history below records each completed stage.
INSERT INTO requests(id,request_no,owner_id,premise_id,category_id,provider_id,title,description,location,township,status,priority,created_at,updated_at) VALUES
(1,'SDC2026-00101',1,1,2,1,'Water leaking under kitchen sink','Water is leaking from the pipe under the kitchen sink.','A-12, Elmina Green, Shah Alam','Elmina',0,'urgent',NOW()-INTERVAL 20 MINUTE,NOW()-INTERVAL 20 MINUTE),
(2,'SDC2026-00102',2,2,3,2,'Shop power outage','Lights and sockets stopped working at the shop.','Shop 08, Elmina Lakeside Mall','Elmina',1,'urgent',NOW()-INTERVAL 2 HOUR,NOW()-INTERVAL 90 MINUTE),
(3,'SDC2026-00103',3,3,5,4,'Gate motor malfunction','The residential gate does not open reliably.','B-06, Elmina Valley','Elmina',2,'normal',NOW()-INTERVAL 4 HOUR,NOW()-INTERVAL 2 HOUR),
(4,'SDC2026-00104',1,1,4,3,'Suspicious activity near house','Resident reported a person attempting door handles.','A-12, Elmina Green, Shah Alam','Elmina',3,'urgent',NOW()-INTERVAL 1 DAY,NOW()-INTERVAL 3 HOUR),
(5,'SDC2026-00105',2,2,5,4,'Broken common-area light','Light fixture near shop entrance is damaged.','Shop 08, Elmina Lakeside Mall','Elmina',4,'normal',NOW()-INTERVAL 2 DAY,NOW()-INTERVAL 1 DAY),
(6,'SDC2026-00106',3,3,1,NULL,'Smoke alarm triggered','Smoke alarm triggered; owner reports no visible fire.','B-06, Elmina Valley','Elmina',0,'urgent',NOW()-INTERVAL 8 MINUTE,NOW()-INTERVAL 8 MINUTE),
(7,'SDC2026-00107',1,1,2,1,'Low water pressure','Water pressure has been low since this morning.','A-12, Elmina Green, Shah Alam','Elmina',1,'normal',NOW()-INTERVAL 5 HOUR,NOW()-INTERVAL 4 HOUR),
(8,'SDC2026-00108',2,2,6,NULL,'Waste collection enquiry','Shop owner asks about collection schedule.','Shop 08, Elmina Lakeside Mall','Elmina',0,'normal',NOW()-INTERVAL 35 MINUTE,NOW()-INTERVAL 35 MINUTE),
(9,'SDC2026-00109',3,3,3,2,'Exposed electrical wire','A damaged light fitting has exposed wiring.','B-06, Elmina Valley','Elmina',3,'urgent',NOW()-INTERVAL 1 DAY,NOW()-INTERVAL 2 HOUR),
(10,'SDC2026-00110',1,1,5,4,'Leaking roof after rain','Water entered the porch after heavy rain.','A-12, Elmina Green, Shah Alam','Elmina',4,'normal',NOW()-INTERVAL 3 DAY,NOW()-INTERVAL 2 DAY);

INSERT INTO request_status_history(request_id,status,changed_by,note,created_at) VALUES
(1,0,1,'Request submitted by owner.',NOW()-INTERVAL 20 MINUTE),
(2,0,2,'Request submitted by owner.',NOW()-INTERVAL 2 HOUR),(2,1,4,'Assigned to electrical provider.',NOW()-INTERVAL 90 MINUTE),
(3,0,3,'Request submitted by owner.',NOW()-INTERVAL 4 HOUR),(3,1,4,'Assigned to maintenance provider.',NOW()-INTERVAL 3 HOUR),(3,2,5,'Provider marked on the way.',NOW()-INTERVAL 2 HOUR),
(4,0,1,'Request submitted by owner.',NOW()-INTERVAL 1 DAY),(4,1,4,'Assigned to security provider.',NOW()-INTERVAL 20 HOUR),(4,2,7,'Security team on the way.',NOW()-INTERVAL 8 HOUR),(4,3,7,'Security team is attending.',NOW()-INTERVAL 3 HOUR),
(5,0,2,'Request submitted by owner.',NOW()-INTERVAL 2 DAY),(5,1,4,'Assigned to maintenance provider.',NOW()-INTERVAL 40 HOUR),(5,2,8,'Provider on the way.',NOW()-INTERVAL 30 HOUR),(5,3,8,'Repair underway.',NOW()-INTERVAL 20 HOUR),(5,4,8,'Repair completed.',NOW()-INTERVAL 1 DAY),
(6,0,3,'Request submitted by owner.',NOW()-INTERVAL 8 MINUTE),
(7,0,1,'Request submitted by owner.',NOW()-INTERVAL 5 HOUR),(7,1,4,'Assigned to plumbing provider.',NOW()-INTERVAL 4 HOUR),
(8,0,2,'Request submitted by owner.',NOW()-INTERVAL 35 MINUTE),
(9,0,3,'Request submitted by owner.',NOW()-INTERVAL 1 DAY),(9,1,4,'Assigned to electrical provider.',NOW()-INTERVAL 20 HOUR),(9,2,6,'Provider on the way.',NOW()-INTERVAL 6 HOUR),(9,3,6,'Electrical inspection in progress.',NOW()-INTERVAL 2 HOUR),
(10,0,1,'Request submitted by owner.',NOW()-INTERVAL 3 DAY),(10,1,4,'Assigned to maintenance provider.',NOW()-INTERVAL 60 HOUR),(10,2,8,'Provider on the way.',NOW()-INTERVAL 48 HOUR),(10,3,8,'Roof repair underway.',NOW()-INTERVAL 30 HOUR),(10,4,8,'Repair completed and checked.',NOW()-INTERVAL 2 DAY);

INSERT INTO notifications(user_id,request_id,title,body,created_at) VALUES
(1,1,'Request received','Your request SDC2026-00101 has been received.',NOW()-INTERVAL 20 MINUTE),
(2,2,'Responder assigned','Your request SDC2026-00102 has been assigned to an authorised provider.',NOW()-INTERVAL 90 MINUTE),
(3,6,'Request received','Your request SDC2026-00106 has been received.',NOW()-INTERVAL 8 MINUTE),
(5,1,'New job assigned','You have been assigned request SDC2026-00101.',NOW()-INTERVAL 20 MINUTE);
