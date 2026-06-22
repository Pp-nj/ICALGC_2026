<?php
require_once __DIR__ . '/../app/helpers/init.php';
use App\Core\Auth;

if (Auth::isLoggedIn()) {
    auditLog('logout', 'auth', 'User logged out');
    Auth::logout();
}

flashSet('success', lang() === 'th' ? 'ออกจากระบบเรียบร้อยแล้ว' : 'You have been logged out successfully.');
redirect(APP_URL . '/login.php');
