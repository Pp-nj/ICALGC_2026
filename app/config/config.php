<?php
/**
 * Application Configuration
 * ICALGC 2026
 */

// ── Timezone ──────────────────────────────────────────────
date_default_timezone_set('Asia/Bangkok');

// ── Environment ───────────────────────────────────────────
define('APP_ENV',     getenv('APP_ENV')  ?: 'development'); // 'production' in prod
define('APP_DEBUG',   APP_ENV === 'development');

// ── Application ───────────────────────────────────────────
define('APP_NAME',    'ICALGC 2026');
$_detectedUrl = (
    !empty(getenv('APP_URL'))
        ? getenv('APP_URL')
        : (function () {
            $host   = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scheme = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
                        ? $_SERVER['HTTP_X_FORWARDED_PROTO']
                        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'));
            return $scheme . '://' . $host;
        })()
);
define('APP_URL', rtrim($_detectedUrl, '/'));
unset($_detectedUrl);
define('APP_VERSION', '1.0.0');

// ── Paths ─────────────────────────────────────────────────
define('ROOT_PATH',        dirname(__DIR__, 2));   // /ICALGC_2026
define('APP_PATH',         dirname(__DIR__));       // /ICALGC_2026/app
define('PUBLIC_PATH',      ROOT_PATH . '/public');
define('UPLOADS_PATH',     ROOT_PATH . '/uploads/papers');
define('CERT_PATH',        ROOT_PATH . '/certificates');
define('VENDOR_PATH',      ROOT_PATH . '/vendor');
define('LANG_PATH',        APP_PATH  . '/lang');

// ── Session ───────────────────────────────────────────────
define('SESSION_NAME',     'icalgc_sess');
define('SESSION_LIFETIME', 7200); // 2 hours

// ── Upload Limits ─────────────────────────────────────────
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024); // 20 MB
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
]);
define('ALLOWED_EXTENSIONS', ['pdf', 'docx']);

// ── Conference Info ───────────────────────────────────────
define('CONF_NAME_TH', 'การประชุมวิชาการนานาชาติว่าด้วยภาษาอาเซียนในบริบทโลก 2026');
define('CONF_NAME_EN', 'International Conference on ASEAN Languages in Global Contexts 2026');
define('CONF_SHORT',   'ICALGC 2026');
define('CONF_DATE',    '2026-11-25');
define('CONF_DATE_TH', '25 พฤศจิกายน 2569');
define('CONF_DATE_EN', 'November 25, 2026');
define('CONF_VENUE_TH','มหาวิทยาลัยศรีนครินทรวิโรฒ ประสานมิตร กรุงเทพมหานคร ประเทศไทย');
define('CONF_VENUE_EN','Srinakharinwirot University Prasarnmit Campus, Bangkok, Thailand');
define('CONF_TIME',    '08:30 AM – 05:00 PM');
define('CONF_YEAR',    '2026');

// ── Default Language ──────────────────────────────────────
define('DEFAULT_LANG', 'th');

// ── Paper Code Prefix ────────────────────────────────────
define('PAPER_CODE_PREFIX', 'ICALGC2026-');

// ── Email (override in mail.php) ──────────────────────────
define('MAIL_FROM',       getenv('MAIL_FROM')      ?: 'noreply@icalgc2026.com');
define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME') ?: 'ICALGC 2026');

// ── Error handling ────────────────────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
