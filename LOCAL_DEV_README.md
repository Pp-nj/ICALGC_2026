# Local Development — Email Verification Bypass

This project now supports **Development** and **Production** configurations driven by
environment variables, so it can run on localhost **without sending real emails** and
**without requiring email verification** — while keeping the full verification/email
implementation intact for production.

## How it works

Two feature flags (defined in `app/config/config.php`) control everything:

| Flag | Development | Production | Effect |
|------|-------------|------------|--------|
| `MAIL_ENABLED` | `false` | `true` | When `false`, no SMTP connection is made. Every outgoing email (verification, password reset, paper notifications, etc.) is written to `storage/maillog/emails-YYYY-MM-DD.log` instead. |
| `EMAIL_VERIFICATION_ENABLED` | `false` | `true` | When `false`, new accounts are created already verified (`email_verified = TRUE`) and no verification email/token is generated. |

Both flags **default automatically** from `APP_ENV`: `development` → both OFF, `production` → both ON.
So even with **no `.env` file at all**, localhost is already safe. The flags can be
overridden individually in `.env`.

The mail switch lives in the single `Mail::send()` method, so it governs *all* email
templates at once. The existing PHPMailer code and templates are untouched and resume
working the moment `MAIL_ENABLED=true`.

## Quick start (localhost)

```bash
# 1. Use the development config
cp .env.development .env

# 2. Install dependencies (PHPMailer, mPDF)
composer install

# 3. Create the PostgreSQL database and load the schema
createdb icalgc2026
psql icalgc2026 < database/schema.sql

# 4. Edit .env with your DB credentials (DB_USER / DB_PASS), then serve public/
php -S localhost:8000 -t public
```

Visit http://localhost:8000/register.php — register an account, then log in
immediately. No email is sent; you can inspect what *would* have been sent in
`storage/maillog/`.

## Switching to production

```bash
cp .env.production .env   # then fill in real DB + SMTP secrets
```

Real emails will be sent and verification enforced again — no code changes required.

## Rollback

To restore the original always-send / always-verify behaviour without removing the
flags, set in `.env`:

```
MAIL_ENABLED=true
EMAIL_VERIFICATION_ENABLED=true
```

Or revert the modified files (`app/config/config.php`, `app/core/Mail.php`,
`public/register.php`, `public/author/profile.php`, `public/reviewer/profile.php`)
and delete the added files (`app/helpers/env.php`, the `.env*` templates, and
`storage/maillog/`).
