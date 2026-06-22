-- ============================================================
-- ICALGC 2026 - PostgreSQL Database Schema
-- International Conference on ASEAN Languages in Global Contexts 2026
-- Timezone: Asia/Bangkok
-- ============================================================

SET timezone = 'Asia/Bangkok';

-- Enable extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";  -- for full-text search

-- ============================================================
-- 1. PAPER STATUSES (Master table)
-- ============================================================
CREATE TABLE IF NOT EXISTS paper_statuses (
    id          SERIAL PRIMARY KEY,
    code        VARCHAR(30) UNIQUE NOT NULL,
    name_th     VARCHAR(100) NOT NULL,
    name_en     VARCHAR(100) NOT NULL,
    color_hex   VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    css_class   VARCHAR(50) NOT NULL DEFAULT 'status-draft',
    progress_step SMALLINT NOT NULL DEFAULT 0,
    description TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO paper_statuses (code, name_th, name_en, color_hex, css_class, progress_step) VALUES
('draft',            'ฉบับร่าง',             'Draft',            '#6c757d', 'status-draft',     0),
('submitted',        'ส่งแล้ว',              'Submitted',        '#0d6efd', 'status-submitted',  1),
('screening',        'กำลังคัดกรอง',          'Screening',        '#ffc107', 'status-screening',  2),
('under_review',     'อยู่ระหว่างการพิจารณา', 'Under Review',     '#6f42c1', 'status-review',     3),
('revision_required','ต้องการแก้ไข',           'Revision Required','#fd7e14', 'status-revision',   4),
('accepted',         'ได้รับการยอมรับ',        'Accepted',         '#198754', 'status-accepted',   5),
('rejected',         'ถูกปฏิเสธ',             'Rejected',         '#dc3545', 'status-rejected',   5),
('published',        'เผยแพร่แล้ว',           'Published',        '#0f5132', 'status-published',  6)
ON CONFLICT (code) DO NOTHING;

-- ============================================================
-- 2. CONFERENCE THEMES
-- ============================================================
CREATE TABLE IF NOT EXISTS conference_themes (
    id          SERIAL PRIMARY KEY,
    code        VARCHAR(20) UNIQUE NOT NULL,
    name_th     TEXT NOT NULL,
    name_en     TEXT NOT NULL,
    description TEXT,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO conference_themes (code, name_th, name_en) VALUES
('THEME01', 'ภาษาศาสตร์อาเซียนและการสื่อสารข้ามวัฒนธรรม', 'ASEAN Linguistics and Cross-Cultural Communication'),
('THEME02', 'การเรียนการสอนภาษาในบริบทโลกาภิวัตน์', 'Language Teaching and Learning in a Globalized World'),
('THEME03', 'วรรณกรรมและวัฒนธรรมอาเซียน', 'ASEAN Literature and Culture'),
('THEME04', 'เทคโนโลยีภาษาและนวัตกรรมดิจิทัล', 'Language Technology and Digital Innovation'),
('THEME05', 'การแปลและล่ามในบริบทอาเซียน', 'Translation and Interpretation in ASEAN Contexts'),
('THEME06', 'นโยบายภาษาและการวางแผนภาษา', 'Language Policy and Language Planning'),
('THEME07', 'ภาษาชนกลุ่มน้อยและการอนุรักษ์ภาษา', 'Minority Languages and Language Preservation')
ON CONFLICT (code) DO NOTHING;

-- ============================================================
-- 3. IMPORTANT DATES
-- ============================================================
CREATE TABLE IF NOT EXISTS important_dates (
    id          SERIAL PRIMARY KEY,
    title_th    VARCHAR(255) NOT NULL,
    title_en    VARCHAR(255) NOT NULL,
    event_date  DATE NOT NULL,
    description_th TEXT,
    description_en TEXT,
    icon        VARCHAR(50) DEFAULT 'calendar',
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO important_dates (title_th, title_en, event_date, sort_order) VALUES
('เปิดรับลงทะเบียน',              'Registration Opens',                 '2026-07-01', 1),
('หมดเขตส่งบทคัดย่อ',             'Abstract Submission Deadline',        '2026-08-30', 2),
('แจ้งผลการพิจารณาบทคัดย่อ',      'Abstract Acceptance Notification',    '2026-09-30', 3),
('วันจัดการประชุม (ONSITE)',       'Conference Date (ONSITE)',             '2026-11-25', 4),
('ดาวน์โหลดใบประกาศนียบัตร',      'Certificate Download Available',       '2026-12-02', 5)
ON CONFLICT DO NOTHING;

-- ============================================================
-- 4. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              SERIAL PRIMARY KEY,
    role            VARCHAR(20) NOT NULL DEFAULT 'author' CHECK (role IN ('author','reviewer','admin')),
    title           VARCHAR(20),
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(255) UNIQUE NOT NULL,
    phone           VARCHAR(30),
    mailing_address TEXT,
    position        VARCHAR(100),
    affiliation     VARCHAR(255),
    expertise       TEXT,
    country         VARCHAR(100) DEFAULT 'Thailand',
    attend_conference BOOLEAN DEFAULT TRUE,
    password_hash   VARCHAR(255) NOT NULL,
    email_verified  BOOLEAN NOT NULL DEFAULT FALSE,
    account_status  VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (account_status IN ('active','suspended','pending')),
    preferred_lang  VARCHAR(5) NOT NULL DEFAULT 'th' CHECK (preferred_lang IN ('th','en')),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_users_email  ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role   ON users(role);

-- Default admin account (password: Admin@2026! — change immediately)
INSERT INTO users (role, title, first_name, last_name, email, password_hash, email_verified, account_status)
VALUES ('admin', 'ดร.', 'ผู้ดูแล', 'ระบบ', 'admin@icalgc2026.com',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, 'active')
ON CONFLICT (email) DO NOTHING;

-- ============================================================
-- 5. PASSWORD RESETS
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       VARCHAR(255) UNIQUE NOT NULL,
    expires_at  TIMESTAMPTZ NOT NULL,
    is_used     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_password_resets_token   ON password_resets(token);
CREATE INDEX IF NOT EXISTS idx_password_resets_user_id ON password_resets(user_id);

-- ============================================================
-- 6. EMAIL VERIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS email_verifications (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       VARCHAR(255) UNIQUE NOT NULL,
    expires_at  TIMESTAMPTZ NOT NULL,
    is_used     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_email_verifications_token ON email_verifications(token);

-- ============================================================
-- 7. PAPERS
-- ============================================================
CREATE TABLE IF NOT EXISTS papers (
    id              SERIAL PRIMARY KEY,
    paper_code      VARCHAR(30) UNIQUE NOT NULL,
    title_th        TEXT NOT NULL,
    title_en        TEXT NOT NULL,
    abstract_th     TEXT NOT NULL,
    abstract_en     TEXT NOT NULL,
    keywords        TEXT NOT NULL,
    theme_id        INTEGER NOT NULL REFERENCES conference_themes(id),
    submitter_id    INTEGER NOT NULL REFERENCES users(id),
    status_code     VARCHAR(30) NOT NULL DEFAULT 'submitted' REFERENCES paper_statuses(code),
    admin_note      TEXT,
    submitted_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_papers_submitter ON papers(submitter_id);
CREATE INDEX IF NOT EXISTS idx_papers_status    ON papers(status_code);
CREATE INDEX IF NOT EXISTS idx_papers_theme     ON papers(theme_id);
CREATE INDEX IF NOT EXISTS idx_papers_code      ON papers(paper_code);
CREATE INDEX IF NOT EXISTS idx_papers_title_en  ON papers USING gin(to_tsvector('english', title_en));
CREATE INDEX IF NOT EXISTS idx_papers_abstract_en ON papers USING gin(to_tsvector('english', abstract_en));

-- ============================================================
-- 8. CO-AUTHORS
-- ============================================================
CREATE TABLE IF NOT EXISTS paper_co_authors (
    id              SERIAL PRIMARY KEY,
    paper_id        INTEGER NOT NULL REFERENCES papers(id) ON DELETE CASCADE,
    full_name       VARCHAR(255) NOT NULL,
    email           VARCHAR(255),
    institution     VARCHAR(255),
    country         VARCHAR(100),
    is_corresponding BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order      SMALLINT NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_co_authors_paper ON paper_co_authors(paper_id);

-- ============================================================
-- 9. PAPER FILES
-- ============================================================
CREATE TABLE IF NOT EXISTS paper_files (
    id              SERIAL PRIMARY KEY,
    paper_id        INTEGER NOT NULL REFERENCES papers(id) ON DELETE CASCADE,
    file_type       VARCHAR(10) NOT NULL CHECK (file_type IN ('pdf','docx')),
    file_category   VARCHAR(20) NOT NULL DEFAULT 'submission' CHECK (file_category IN ('submission','revision','camera_ready')),
    original_name   VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) UNIQUE NOT NULL,
    file_path       TEXT NOT NULL,
    file_size       BIGINT NOT NULL DEFAULT 0,
    version_number  SMALLINT NOT NULL DEFAULT 1,
    uploaded_by     INTEGER NOT NULL REFERENCES users(id),
    uploaded_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_paper_files_paper ON paper_files(paper_id);

-- ============================================================
-- 10. REVIEW ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS review_assignments (
    id              SERIAL PRIMARY KEY,
    paper_id        INTEGER NOT NULL REFERENCES papers(id) ON DELETE CASCADE,
    reviewer_id     INTEGER NOT NULL REFERENCES users(id),
    assigned_by     INTEGER NOT NULL REFERENCES users(id),
    assigned_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    due_date        DATE,
    assignment_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (assignment_status IN ('pending','in_progress','completed','declined')),
    UNIQUE(paper_id, reviewer_id)
);

CREATE INDEX IF NOT EXISTS idx_assignments_paper    ON review_assignments(paper_id);
CREATE INDEX IF NOT EXISTS idx_assignments_reviewer ON review_assignments(reviewer_id);

-- ============================================================
-- 11. REVIEWS
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id                  SERIAL PRIMARY KEY,
    assignment_id       INTEGER NOT NULL UNIQUE REFERENCES review_assignments(id) ON DELETE CASCADE,
    paper_id            INTEGER NOT NULL REFERENCES papers(id) ON DELETE CASCADE,
    reviewer_id         INTEGER NOT NULL REFERENCES users(id),
    score_originality   SMALLINT CHECK (score_originality BETWEEN 1 AND 10),
    score_relevance     SMALLINT CHECK (score_relevance BETWEEN 1 AND 10),
    score_methodology   SMALLINT CHECK (score_methodology BETWEEN 1 AND 10),
    score_writing       SMALLINT CHECK (score_writing BETWEEN 1 AND 10),
    score_contribution  SMALLINT CHECK (score_contribution BETWEEN 1 AND 10),
    score_overall       SMALLINT CHECK (score_overall BETWEEN 1 AND 10),
    final_score         NUMERIC(4,2),
    recommendation      VARCHAR(20) NOT NULL CHECK (recommendation IN ('accept','minor_revision','major_revision','reject')),
    comment_for_author  TEXT,
    comment_for_editor  TEXT,
    review_status       VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (review_status IN ('draft','submitted')),
    reviewed_at         TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_reviews_paper    ON reviews(paper_id);
CREATE INDEX IF NOT EXISTS idx_reviews_reviewer ON reviews(reviewer_id);

-- ============================================================
-- 12. NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type            VARCHAR(50) NOT NULL,
    title_th        VARCHAR(255) NOT NULL,
    title_en        VARCHAR(255) NOT NULL,
    message_th      TEXT NOT NULL,
    message_en      TEXT NOT NULL,
    related_paper_id INTEGER REFERENCES papers(id) ON DELETE SET NULL,
    is_read         BOOLEAN NOT NULL DEFAULT FALSE,
    channel         VARCHAR(20) NOT NULL DEFAULT 'system' CHECK (channel IN ('system','email','both')),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_notifications_user   ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_unread ON notifications(user_id, is_read);

-- ============================================================
-- 13. PUBLICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS publications (
    id              SERIAL PRIMARY KEY,
    paper_id        INTEGER NOT NULL UNIQUE REFERENCES papers(id) ON DELETE CASCADE,
    doi             VARCHAR(255),
    published_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    published_by    INTEGER NOT NULL REFERENCES users(id),
    download_count  INTEGER NOT NULL DEFAULT 0,
    view_count      INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_publications_paper ON publications(paper_id);

-- ============================================================
-- 14. CERTIFICATES
-- ============================================================
CREATE TABLE IF NOT EXISTS certificates (
    id              SERIAL PRIMARY KEY,
    cert_type       VARCHAR(30) NOT NULL CHECK (cert_type IN ('attendance','presentation','reviewer','acceptance')),
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    paper_id        INTEGER REFERENCES papers(id) ON DELETE SET NULL,
    recipient_name  VARCHAR(255) NOT NULL,
    generated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    pdf_path        TEXT,
    UNIQUE(cert_type, user_id, paper_id)
);

CREATE INDEX IF NOT EXISTS idx_certificates_user ON certificates(user_id);

-- ============================================================
-- 15. AUDIT LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGSERIAL PRIMARY KEY,
    user_id     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action      VARCHAR(100) NOT NULL,
    module      VARCHAR(50) NOT NULL,
    detail      TEXT,
    ip_address  INET,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_audit_user    ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_logs(created_at DESC);

-- ============================================================
-- UTILITY: auto-update updated_at columns
-- ============================================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_papers_updated_at
    BEFORE UPDATE ON papers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_reviews_updated_at
    BEFORE UPDATE ON reviews
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_important_dates_updated_at
    BEFORE UPDATE ON important_dates
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
