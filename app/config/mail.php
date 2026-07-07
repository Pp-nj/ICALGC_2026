<?php
/**
 * Mail Configuration (PHPMailer / SMTP)
 */

return [
    'host'       => getenv('MAIL_HOST')       ?: 'smtp.gmail.com',
    'port'       => (int)(getenv('MAIL_PORT') ?: 587),
    'username'   => getenv('MAIL_USERNAME')   ?: '',      // ← ใส่อีเมล Gmail จริง
    'password'   => getenv('MAIL_PASSWORD')   ?: '',          // ← ใส่ App Password 16 หลัก
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls', // 'tls' or 'ssl'
    'from_email' => getenv('MAIL_FROM')       ?: '',       // ← ใส่อีเมล Gmail จริง
    'from_name'  => getenv('MAIL_FROM_NAME')  ?: 'ICALGC 2026',
    'debug'      => (APP_ENV === 'development') ? 2 : 0,
];
