-- ============================================================================
-- HRRAMS - Human Rights Reporting & Awareness Management System
-- MySQL 8+ schema (3NF). Engine: InnoDB, Charset: utf8mb4
-- ============================================================================

CREATE DATABASE IF NOT EXISTS hrrams
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrrams;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------- roles / rbac
CREATE TABLE roles (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug          VARCHAR(50)  NOT NULL,
  name          VARCHAR(100) NOT NULL,
  description   VARCHAR(255) NULL,
  is_staff      TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_roles_slug (slug),
  KEY idx_roles_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug          VARCHAR(80)  NOT NULL,
  name          VARCHAR(120) NOT NULL,
  module        VARCHAR(50)  NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_perm_slug (slug),
  KEY idx_perm_module (module)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id       INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by    INT UNSIGNED NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------- geography
CREATE TABLE provinces (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  code          VARCHAR(10)  NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_prov_name (name)
) ENGINE=InnoDB;

CREATE TABLE districts (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  province_id   INT UNSIGNED NOT NULL,
  name          VARCHAR(100) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_dist (province_id, name),
  KEY idx_dist_prov (province_id),
  CONSTRAINT fk_dist_prov FOREIGN KEY (province_id) REFERENCES provinces(id)
) ENGINE=InnoDB;

-- --------------------------------------------------------------------- users
CREATE TABLE users (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name          VARCHAR(80)  NOT NULL,
  last_name           VARCHAR(80)  NOT NULL,
  email               VARCHAR(190) NOT NULL,
  phone               VARCHAR(30)  NULL,
  password_hash       VARCHAR(255) NOT NULL,
  national_id_enc     VARBINARY(512) NULL COMMENT 'AES-encrypted sensitive identifier',
  address             VARCHAR(255) NULL,
  province_id         INT UNSIGNED NULL,
  district_id         INT UNSIGNED NULL,
  nationality         VARCHAR(80)  NULL,
  avatar_path         VARCHAR(255) NULL,
  status              ENUM('pending','active','suspended','locked') NOT NULL DEFAULT 'pending',
  email_verified_at   DATETIME     NULL,
  verify_token        CHAR(64)     NULL,
  twofa_secret        VARCHAR(64)  NULL,
  twofa_enabled       TINYINT(1)   NOT NULL DEFAULT 0,
  failed_attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until        DATETIME     NULL,
  last_login_at       DATETIME     NULL,
  remember_token      CHAR(64)     NULL,
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by          INT UNSIGNED NULL,
  updated_by          INT UNSIGNED NULL,
  deleted_at          DATETIME     NULL,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_status (status),
  KEY idx_users_deleted (deleted_at),
  KEY idx_users_prov (province_id),
  CONSTRAINT fk_users_prov FOREIGN KEY (province_id) REFERENCES provinces(id),
  CONSTRAINT fk_users_dist FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id     INT UNSIGNED NOT NULL,
  role_id     INT UNSIGNED NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by  INT UNSIGNED NULL,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64)     NOT NULL,
  expires_at  DATETIME     NOT NULL,
  used_at     DATETIME     NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pr_user (user_id),
  UNIQUE KEY uq_pr_token (token_hash),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_history (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  email       VARCHAR(190) NULL,
  ip_address  VARCHAR(45)  NULL,
  user_agent  VARCHAR(255) NULL,
  successful  TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lh_user (user_id),
  KEY idx_lh_email_time (email, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------------- reports
CREATE TABLE report_categories (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  slug          VARCHAR(120) NOT NULL,
  description   VARCHAR(500) NULL,
  icon          VARCHAR(60)  NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_cat_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE reports (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no        VARCHAR(30)  NOT NULL COMMENT 'public tracking code',
  reporter_user_id    INT UNSIGNED NULL COMMENT 'NULL when anonymous',
  is_anonymous        TINYINT(1)   NOT NULL DEFAULT 0,
  reporter_name       VARCHAR(160) NULL,
  reporter_phone      VARCHAR(30)  NULL,
  reporter_email      VARCHAR(190) NULL,
  reporter_address    VARCHAR(255) NULL,
  reporter_nationality VARCHAR(80) NULL,
  province_id         INT UNSIGNED NULL,
  district_id         INT UNSIGNED NULL,
  category_id         INT UNSIGNED NOT NULL,
  title               VARCHAR(200) NOT NULL,
  description         TEXT         NOT NULL,
  incident_date       DATE         NULL,
  incident_time       TIME         NULL,
  location            VARCHAR(255) NULL,
  latitude            DECIMAL(10,7) NULL,
  longitude           DECIMAL(10,7) NULL,
  persons_involved    TEXT         NULL,
  organization_involved VARCHAR(200) NULL,
  victim_count        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  witness_details     TEXT         NULL,
  urgency             ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  consent_given       TINYINT(1)   NOT NULL DEFAULT 0,
  status              ENUM('submitted','under_review','assigned','investigating',
                           'awaiting_information','escalated','resolved','closed','rejected')
                      NOT NULL DEFAULT 'submitted',
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by          INT UNSIGNED NULL,
  updated_by          INT UNSIGNED NULL,
  deleted_at          DATETIME     NULL,
  UNIQUE KEY uq_reports_ref (reference_no),
  KEY idx_reports_status (status),
  KEY idx_reports_cat (category_id),
  KEY idx_reports_prov (province_id),
  KEY idx_reports_user (reporter_user_id),
  KEY idx_reports_created (created_at),
  KEY idx_reports_deleted (deleted_at),
  CONSTRAINT fk_rep_cat  FOREIGN KEY (category_id) REFERENCES report_categories(id),
  CONSTRAINT fk_rep_user FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_rep_prov FOREIGN KEY (province_id) REFERENCES provinces(id),
  CONSTRAINT fk_rep_dist FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

-- --------------------------------------------------------------------- cases
CREATE TABLE cases (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  case_number       VARCHAR(30)  NOT NULL,
  report_id         BIGINT UNSIGNED NOT NULL,
  supervisor_id     INT UNSIGNED NULL,
  officer_id        INT UNSIGNED NULL COMMENT 'current owner',
  status            ENUM('submitted','under_review','assigned','investigating',
                         'awaiting_information','escalated','resolved','closed','rejected')
                    NOT NULL DEFAULT 'under_review',
  priority          ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  opened_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  due_at            DATETIME     NULL,
  resolved_at       DATETIME     NULL,
  closed_at         DATETIME     NULL,
  resolution_summary TEXT        NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by        INT UNSIGNED NULL,
  updated_by        INT UNSIGNED NULL,
  deleted_at        DATETIME     NULL,
  UNIQUE KEY uq_cases_number (case_number),
  UNIQUE KEY uq_cases_report (report_id),
  KEY idx_cases_officer (officer_id),
  KEY idx_cases_status (status),
  CONSTRAINT fk_case_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
  CONSTRAINT fk_case_officer FOREIGN KEY (officer_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_case_sup FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE case_assignments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  case_id       BIGINT UNSIGNED NOT NULL,
  officer_id    INT UNSIGNED NOT NULL,
  assigned_by   INT UNSIGNED NOT NULL,
  assigned_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  unassigned_at DATETIME     NULL,
  note          VARCHAR(500) NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  KEY idx_ca_case (case_id),
  KEY idx_ca_officer (officer_id),
  CONSTRAINT fk_ca_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_officer FOREIGN KEY (officer_id) REFERENCES users(id),
  CONSTRAINT fk_ca_by FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE case_activities (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  case_id       BIGINT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NULL,
  type          ENUM('note','status_change','assignment','evidence','interview',
                     'information_request','finding','escalation','closure') NOT NULL,
  title         VARCHAR(200) NULL,
  body          TEXT         NULL,
  old_value     VARCHAR(100) NULL,
  new_value     VARCHAR(100) NULL,
  scheduled_at  DATETIME     NULL COMMENT 'interview scheduling',
  is_internal   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  KEY idx_act_case_time (case_id, created_at),
  KEY idx_act_type (type),
  CONSTRAINT fk_act_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_act_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------------- attachments
CREATE TABLE attachments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type   ENUM('report','case_activity','campaign','resource','user') NOT NULL,
  entity_id     BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name   VARCHAR(255) NOT NULL,
  mime_type     VARCHAR(120) NOT NULL,
  kind          ENUM('image','video','audio','document','other') NOT NULL DEFAULT 'other',
  size_bytes    BIGINT UNSIGNED NOT NULL,
  checksum      CHAR(64)     NULL,
  uploaded_by   INT UNSIGNED NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  KEY idx_att_entity (entity_type, entity_id),
  CONSTRAINT fk_att_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------ awareness
CREATE TABLE awareness_campaigns (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(200) NOT NULL,
  slug          VARCHAR(220) NOT NULL,
  type          ENUM('campaign','news','article','success_story','newsletter') NOT NULL DEFAULT 'campaign',
  summary       VARCHAR(500) NULL,
  body          MEDIUMTEXT   NULL,
  cover_path    VARCHAR(255) NULL,
  status        ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  starts_on     DATE         NULL,
  ends_on       DATE         NULL,
  views         INT UNSIGNED NOT NULL DEFAULT 0,
  published_at  DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_camp_slug (slug),
  KEY idx_camp_status_type (status, type),
  CONSTRAINT fk_camp_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE educational_resources (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(200) NOT NULL,
  slug          VARCHAR(220) NOT NULL,
  resource_type ENUM('document','video','audio','image','link','training') NOT NULL DEFAULT 'document',
  description   TEXT         NULL,
  file_path     VARCHAR(255) NULL,
  external_url  VARCHAR(500) NULL,
  downloads     INT UNSIGNED NOT NULL DEFAULT 0,
  is_published  TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_res_slug (slug),
  KEY idx_res_pub (is_published)
) ENGINE=InnoDB;

CREATE TABLE events (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(200) NOT NULL,
  description   TEXT         NULL,
  venue         VARCHAR(200) NULL,
  province_id   INT UNSIGNED NULL,
  starts_at     DATETIME     NOT NULL,
  ends_at       DATETIME     NULL,
  capacity      INT UNSIGNED NULL,
  status        ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  created_by    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  deleted_at    DATETIME     NULL,
  KEY idx_ev_start (starts_at),
  CONSTRAINT fk_ev_prov FOREIGN KEY (province_id) REFERENCES provinces(id)
) ENGINE=InnoDB;

CREATE TABLE comments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type   ENUM('campaign','resource','event') NOT NULL,
  entity_id     BIGINT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NULL,
  author_name   VARCHAR(120) NULL,
  body          TEXT         NOT NULL,
  is_approved   TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  KEY idx_cm_entity (entity_type, entity_id),
  CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------- notify / audit / cfg
CREATE TABLE notifications (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  channel       ENUM('system','email','sms') NOT NULL DEFAULT 'system',
  title         VARCHAR(200) NOT NULL,
  body          VARCHAR(1000) NULL,
  link          VARCHAR(255) NULL,
  read_at       DATETIME     NULL,
  sent_at       DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  KEY idx_nt_user_read (user_id, read_at),
  CONSTRAINT fk_nt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NULL,
  action        VARCHAR(80)  NOT NULL,
  entity_type   VARCHAR(60)  NULL,
  entity_id     BIGINT UNSIGNED NULL,
  description   VARCHAR(500) NULL,
  old_values    JSON         NULL,
  new_values    JSON         NULL,
  ip_address    VARCHAR(45)  NULL,
  user_agent    VARCHAR(255) NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_al_user_time (user_id, created_at),
  KEY idx_al_action (action),
  KEY idx_al_entity (entity_type, entity_id),
  CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NULL,
  summary       VARCHAR(300) NOT NULL,
  route         VARCHAR(255) NULL,
  method        VARCHAR(10)  NULL,
  ip_address    VARCHAR(45)  NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_actl_time (created_at),
  CONSTRAINT fk_actl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE settings (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skey          VARCHAR(100) NOT NULL,
  svalue        TEXT         NULL,
  stype         ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
  group_name    VARCHAR(60)  NOT NULL DEFAULT 'general',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  updated_by    INT UNSIGNED NULL,
  deleted_at    DATETIME     NULL,
  UNIQUE KEY uq_settings_key (skey)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------ reporting
CREATE OR REPLACE VIEW v_case_overview AS
SELECT c.id, c.case_number, c.status, c.priority, c.opened_at, c.resolved_at,
       r.reference_no, r.title, r.urgency, r.incident_date,
       rc.name  AS category_name,
       p.name   AS province_name,
       CONCAT(u.first_name,' ',u.last_name) AS officer_name,
       TIMESTAMPDIFF(DAY, c.opened_at, COALESCE(c.resolved_at, NOW())) AS age_days
FROM cases c
JOIN reports r            ON r.id  = c.report_id
JOIN report_categories rc ON rc.id = r.category_id
LEFT JOIN provinces p     ON p.id  = r.province_id
LEFT JOIN users u         ON u.id  = c.officer_id
WHERE c.deleted_at IS NULL;
