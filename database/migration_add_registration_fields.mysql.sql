-- Migration: Add proper registration fields to users table (MySQL version)
-- Run this on existing databases

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS middle_name     VARCHAR(100),
    ADD COLUMN IF NOT EXISTS cert_name       VARCHAR(255),
    ADD COLUMN IF NOT EXISTS department      VARCHAR(100),
    ADD COLUMN IF NOT EXISTS participation_type VARCHAR(20) DEFAULT NULL
        CHECK (participation_type IN ('presenter','coauthor','participant','student')),
    ADD COLUMN IF NOT EXISTS dietary         VARCHAR(30) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS dietary_allergy TEXT DEFAULT NULL;

-- Migrate existing data: mailing_address was used as cert_name workaround
UPDATE users SET cert_name = mailing_address WHERE cert_name IS NULL AND mailing_address IS NOT NULL;

-- Remove unused "attending in person" toggle (superseded by participation_type)
ALTER TABLE users DROP COLUMN IF EXISTS attend_conference;
