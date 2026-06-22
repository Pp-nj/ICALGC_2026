<?php
/**
 * Mail Configuration (PHPMailer / SMTP)
 */

return [
    'host'       => getenv('MAIL_HOST')       ?: 'smtp.gmail.com',
    'port'       => (int)(getenv('MAIL_PORT') ?: 587),
    'username'   => getenv('MAIL_USERNAME')   ?: 'your-email@gmail.com',
    'password'   => getenv('MAIL_PASSWORD')   ?: 'your-app-password',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls', // 'tls' or 'ssl'
    'from_email' => getenv('MAIL_FROM')       ?: 'noreply@icalgc2026.com',
    'from_name'  => getenv('MAIL_FROM_NAME')  ?: 'ICALGC 2026',
    'debug'      => (APP_ENV === 'development') ? 2 : 0,
];
