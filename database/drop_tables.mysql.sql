-- ============================================================
-- ICALGC 2026 - Drop all tables (reverse of schema.mysql.sql)
-- Run this to wipe the schema before re-running schema.mysql.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS publications;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS review_assignments;
DROP TABLE IF EXISTS paper_files;
DROP TABLE IF EXISTS paper_co_authors;
DROP TABLE IF EXISTS papers;
DROP TABLE IF EXISTS email_verifications;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS important_dates;
DROP TABLE IF EXISTS conference_themes;
DROP TABLE IF EXISTS paper_statuses;

SET FOREIGN_KEY_CHECKS = 1;
