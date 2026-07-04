-- ============================================================
-- ICALGC 2026 - MySQL Database Schema (converted from PostgreSQL)
-- International Conference on ASEAN Languages in Global Contexts 2026
-- Timezone: Asia/Bangkok
-- Target: MySQL 8.0+ / MariaDB 10.3+ (e.g. Hostinger shared hosting)
-- ============================================================

SET time_zone = '+07:00';
SET NAMES utf8mb4;

-- ============================================================
-- 1. PAPER STATUSES (Master table)
-- ============================================================
CREATE TABLE IF NOT EXISTS paper_statuses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30) UNIQUE NOT NULL,
    name_th     VARCHAR(100) NOT NULL,
    name_en     VARCHAR(100) NOT NULL,
    color_hex   VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    css_class   VARCHAR(50) NOT NULL DEFAULT 'status-submitted',
    progress_step SMALLINT NOT NULL DEFAULT 0,
    description TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO paper_statuses (code, name_th, name_en, color_hex, css_class, progress_step) VALUES
('submitted',        'ส่งแล้ว',              'Submitted',        '#0d6efd', 'status-submitted',  1),
('under_review',     'อยู่ระหว่างพิจารณา',   'Under Review',     '#6f42c1', 'status-review',     2),
('revision_required','ต้องการแก้ไข',          'Revision Required','#fd7e14', 'status-revision',   3),
('accepted',         'ได้รับการยอมรับ',       'Accepted',         '#198754', 'status-accepted',   4),
('rejected',         'ถูกปฏิเสธ',            'Rejected',         '#dc3545', 'status-rejected',   4),
('published',        'เผยแพร่แล้ว',          'Published',        '#0f5132', 'status-published',  5);

-- ============================================================
-- 2. CONFERENCE THEMES
-- ============================================================
CREATE TABLE IF NOT EXISTS conference_themes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) UNIQUE NOT NULL,
    name_th     TEXT NOT NULL,
    name_en     TEXT NOT NULL,
    description TEXT,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO conference_themes (code, name_th, name_en) VALUES
('THEME01', 'ภาษาศาสตร์อาเซียนและการสื่อสารข้ามวัฒนธรรม', 'ASEAN Linguistics and Cross-Cultural Communication'),
('THEME02', 'การเรียนการสอนภาษาในบริบทโลกาภิวัตน์', 'Language Teaching and Learning in a Globalized World'),
('THEME03', 'วรรณกรรมและวัฒนธรรมอาเซียน', 'ASEAN Literature and Culture'),
('THEME04', 'เทคโนโลยีภาษาและนวัตกรรมดิจิทัล', 'Language Technology and Digital Innovation'),
('THEME05', 'การแปลและล่ามในบริบทอาเซียน', 'Translation and Interpretation in ASEAN Contexts'),
('THEME06', 'นโยบายภาษาและการวางแผนภาษา', 'Language Policy and Language Planning'),
('THEME07', 'ภาษาชนกลุ่มน้อยและการอนุรักษ์ภาษา', 'Minority Languages and Language Preservation');

-- ============================================================
-- 3. IMPORTANT DATES
-- ============================================================
CREATE TABLE IF NOT EXISTS important_dates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title_th    VARCHAR(255) NOT NULL,
    title_en    VARCHAR(255) UNIQUE NOT NULL,
    event_date  DATE NOT NULL,
    description_th TEXT,
    description_en TEXT,
    icon        VARCHAR(50) DEFAULT 'calendar',
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO important_dates (title_th, title_en, event_date, sort_order) VALUES
('เปิดรับลงทะเบียน',              'Registration Opens',                '2026-07-01', 1),
('หมดเขตส่งบทคัดย่อ',             'Abstract Submission Deadline',       '2026-08-30', 2),
('แจ้งผลการพิจารณาบทคัดย่อ',      'Abstract Acceptance Notification',   '2026-09-30', 3),
('วันจัดการประชุม (ONSITE)',       'Conference Date (ONSITE)',            '2026-11-25', 4),
('ดาวน์โหลดใบประกาศนียบัตร',      'Certificate Download Available',      '2026-12-02', 5);

-- ============================================================
-- 4. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role            VARCHAR(20) NOT NULL DEFAULT 'author' CHECK (role IN ('author','reviewer','admin')),
    title           VARCHAR(20),
    first_name      VARCHAR(100) NOT NULL,
    middle_name     VARCHAR(100),
    last_name       VARCHAR(100) NOT NULL,
    cert_name       VARCHAR(255),
    email           VARCHAR(255) UNIQUE NOT NULL,
    phone           VARCHAR(30),
    affiliation     VARCHAR(255),
    department      VARCHAR(100),
    position        VARCHAR(100),
    country         VARCHAR(100) DEFAULT 'Thailand',
    expertise       TEXT,
    participation_type VARCHAR(20) DEFAULT NULL CHECK (participation_type IN ('presenter','coauthor','participant','student')),
    dietary         VARCHAR(30) DEFAULT NULL,
    dietary_allergy TEXT DEFAULT NULL,
    mailing_address TEXT DEFAULT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    email_verified  BOOLEAN NOT NULL DEFAULT FALSE,
    account_status  VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (account_status IN ('active','suspended','pending')),
    preferred_lang  VARCHAR(5) NOT NULL DEFAULT 'th' CHECK (preferred_lang IN ('th','en')),
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account (password: Admin@2026! — change immediately)
INSERT IGNORE INTO users (role, title, first_name, last_name, email, password_hash, email_verified, account_status)
VALUES ('admin', 'ดร.', 'ผู้ดูแล', 'ระบบ', 'admin@icalgc2026.com',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, 'active');

-- ============================================================
-- 5. PASSWORD RESETS
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(255) UNIQUE NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    is_used     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_token   (token),
    INDEX idx_password_resets_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. EMAIL VERIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS email_verifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(255) UNIQUE NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    is_used     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_email_verifications_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. PAPERS
-- ============================================================
CREATE TABLE IF NOT EXISTS papers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paper_code      VARCHAR(30) UNIQUE NOT NULL,
    title_th        TEXT NOT NULL,
    title_en        TEXT NOT NULL,
    abstract_th     TEXT NOT NULL,
    abstract_en     TEXT NOT NULL,
    keywords        TEXT NOT NULL,
    theme_id        INT UNSIGNED NOT NULL,
    submitter_id    INT UNSIGNED NOT NULL,
    status_code     VARCHAR(30) NOT NULL DEFAULT 'submitted',
    admin_note      TEXT,
    submitted_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_papers_theme     FOREIGN KEY (theme_id)     REFERENCES conference_themes(id),
    CONSTRAINT fk_papers_submitter FOREIGN KEY (submitter_id) REFERENCES users(id),
    CONSTRAINT fk_papers_status    FOREIGN KEY (status_code)  REFERENCES paper_statuses(code),
    INDEX idx_papers_submitter (submitter_id),
    INDEX idx_papers_status    (status_code),
    INDEX idx_papers_theme     (theme_id),
    INDEX idx_papers_code      (paper_code),
    FULLTEXT INDEX idx_papers_title_en    (title_en),
    FULLTEXT INDEX idx_papers_abstract_en (abstract_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. CO-AUTHORS
-- ============================================================
CREATE TABLE IF NOT EXISTS paper_co_authors (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paper_id        INT UNSIGNED NOT NULL,
    full_name       VARCHAR(255) NOT NULL,
    email           VARCHAR(255),
    institution     VARCHAR(255),
    country         VARCHAR(100),
    is_corresponding BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order      SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_co_authors_paper FOREIGN KEY (paper_id) REFERENCES papers(id) ON DELETE CASCADE,
    INDEX idx_co_authors_paper (paper_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. PAPER FILES
-- ============================================================
CREATE TABLE IF NOT EXISTS paper_files (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paper_id        INT UNSIGNED NOT NULL,
    file_type       VARCHAR(10) NOT NULL CHECK (file_type IN ('pdf','docx')),
    file_category   VARCHAR(20) NOT NULL DEFAULT 'submission' CHECK (file_category IN ('submission','revision','camera_ready')),
    original_name   VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) UNIQUE NOT NULL,
    file_path       TEXT NOT NULL,
    file_size       BIGINT NOT NULL DEFAULT 0,
    version_number  SMALLINT NOT NULL DEFAULT 1,
    uploaded_by     INT UNSIGNED NOT NULL,
    uploaded_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_paper_files_paper    FOREIGN KEY (paper_id)    REFERENCES papers(id) ON DELETE CASCADE,
    CONSTRAINT fk_paper_files_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_paper_files_paper (paper_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. REVIEW ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS review_assignments (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paper_id          INT UNSIGNED NOT NULL,
    reviewer_id       INT UNSIGNED NOT NULL,
    assigned_by       INT UNSIGNED NOT NULL,
    assigned_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date          DATE,
    assignment_status VARCHAR(20) NOT NULL DEFAULT 'pending'
                      CHECK (assignment_status IN ('pending','in_progress','completed','declined')),
    CONSTRAINT fk_assignments_paper    FOREIGN KEY (paper_id)    REFERENCES papers(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id),
    CONSTRAINT fk_assignments_assigner FOREIGN KEY (assigned_by) REFERENCES users(id),
    UNIQUE KEY uq_assignments_paper_reviewer (paper_id, reviewer_id),
    INDEX idx_assignments_paper    (paper_id),
    INDEX idx_assignments_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. REVIEWS
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id       INT UNSIGNED NOT NULL UNIQUE,
    paper_id            INT UNSIGNED NOT NULL,
    reviewer_id         INT UNSIGNED NOT NULL,
    score_originality   NUMERIC(5,2) CHECK (score_originality  BETWEEN 0 AND 25),
    score_relevance     NUMERIC(5,2) CHECK (score_relevance    BETWEEN 0 AND 20),
    score_methodology   NUMERIC(5,2) CHECK (score_methodology  BETWEEN 0 AND 20),
    score_writing       NUMERIC(5,2) CHECK (score_writing      BETWEEN 0 AND 10),
    score_contribution  NUMERIC(5,2) CHECK (score_contribution BETWEEN 0 AND 25),
    score_overall       NUMERIC(5,2) CHECK (score_overall      BETWEEN 0 AND 100),
    final_score         NUMERIC(4,2),
    recommendation      VARCHAR(20) NOT NULL
                        CHECK (recommendation IN ('accept','minor_revision','major_revision','reject')),
    comment_for_author  TEXT,
    comment_for_editor  TEXT,
    review_status       VARCHAR(20) NOT NULL DEFAULT 'submitted',
    reviewed_at         TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_assignment FOREIGN KEY (assignment_id) REFERENCES review_assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_paper      FOREIGN KEY (paper_id)      REFERENCES papers(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer   FOREIGN KEY (reviewer_id)   REFERENCES users(id),
    INDEX idx_reviews_paper    (paper_id),
    INDEX idx_reviews_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED,
    type             VARCHAR(50) NOT NULL,
    title_th         VARCHAR(255) NOT NULL,
    title_en         VARCHAR(255) NOT NULL,
    message_th       TEXT NOT NULL,
    message_en       TEXT NOT NULL,
    related_paper_id INT UNSIGNED,
    is_read          BOOLEAN NOT NULL DEFAULT FALSE,
    channel          VARCHAR(20) NOT NULL DEFAULT 'system' CHECK (channel IN ('system','email','both')),
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_paper FOREIGN KEY (related_paper_id) REFERENCES papers(id) ON DELETE SET NULL,
    INDEX idx_notifications_user   (user_id),
    INDEX idx_notifications_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. PUBLICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS publications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paper_id        INT UNSIGNED NOT NULL UNIQUE,
    doi             VARCHAR(255),
    published_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    published_by    INT UNSIGNED NOT NULL,
    download_count  INT NOT NULL DEFAULT 0,
    view_count      INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_publications_paper     FOREIGN KEY (paper_id)     REFERENCES papers(id) ON DELETE CASCADE,
    CONSTRAINT fk_publications_publisher FOREIGN KEY (published_by) REFERENCES users(id),
    INDEX idx_publications_paper (paper_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. CERTIFICATES
-- ============================================================
CREATE TABLE IF NOT EXISTS certificates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cert_type       VARCHAR(30) NOT NULL CHECK (cert_type IN ('attendance','presentation','reviewer','acceptance')),
    user_id         INT UNSIGNED NOT NULL,
    paper_id        INT UNSIGNED,
    recipient_name  VARCHAR(255) NOT NULL,
    generated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pdf_path        TEXT,
    CONSTRAINT fk_certificates_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificates_paper FOREIGN KEY (paper_id) REFERENCES papers(id) ON DELETE SET NULL,
    UNIQUE KEY uq_certificates_type_user_paper (cert_type, user_id, paper_id),
    INDEX idx_certificates_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. AUDIT LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED,
    action      VARCHAR(100) NOT NULL,
    module      VARCHAR(50) NOT NULL,
    detail      TEXT,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_user    (user_id),
    INDEX idx_audit_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTE: `updated_at` columns use MySQL's built-in
-- "ON UPDATE CURRENT_TIMESTAMP" instead of PostgreSQL triggers,
-- so no separate trigger/function definitions are needed.
-- ============================================================
