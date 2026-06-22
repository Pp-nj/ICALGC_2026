<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Database;

$appUrl = APP_URL;
$_lang  = lang();
$token  = sanitize(get('token'));
$result = 'error'; // 'success', 'already', 'expired', 'error'

if ($token) {
    try {
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ev.*, u.email_verified FROM email_verifications ev
            JOIN users u ON u.id = ev.user_id
            WHERE ev.token = :tok
            LIMIT 1
        ");
        $stmt->execute([':tok' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            $result = 'error';
        } elseif ($row['email_verified']) {
            $result = 'already';
        } elseif ($row['is_used'] || $row['expires_at'] < date('Y-m-d H:i:s')) {
            $result = 'expired';
        } else {
            $db->prepare("UPDATE users SET email_verified = TRUE, updated_at = NOW() WHERE id = :uid")
               ->execute([':uid' => $row['user_id']]);
            $db->prepare("UPDATE email_verifications SET is_used = TRUE WHERE token = :tok")
               ->execute([':tok' => $token]);
            auditLog('email_verified', 'auth', 'Email verified', $row['user_id']);
            $result = 'success';
        }
    } catch (\Throwable $e) {
        $result = 'error';
        error_log($e->getMessage());
    }
}

$messages = [
    'success' => [
        'icon'  => 'check-circle text-success',
        'title' => $_lang==='th' ? 'ยืนยันอีเมลสำเร็จ' : 'Email Verified',
        'msg'   => $_lang==='th' ? 'อีเมลของคุณได้รับการยืนยันเรียบร้อยแล้ว คุณสามารถเข้าสู่ระบบได้ทันที' : 'Your email has been verified. You can now log in.',
        'alert' => 'alert-success',
    ],
    'already' => [
        'icon'  => 'info-circle text-info',
        'title' => $_lang==='th' ? 'ยืนยันแล้ว' : 'Already Verified',
        'msg'   => $_lang==='th' ? 'อีเมลของคุณได้รับการยืนยันแล้ว' : 'Your email is already verified.',
        'alert' => 'alert-info',
    ],
    'expired' => [
        'icon'  => 'clock text-warning',
        'title' => $_lang==='th' ? 'ลิงก์หมดอายุ' : 'Link Expired',
        'msg'   => $_lang==='th' ? 'ลิงก์ยืนยันหมดอายุแล้ว กรุณาส่งคำขอยืนยันใหม่' : 'The verification link has expired. Please request a new one.',
        'alert' => 'alert-warning',
    ],
    'error' => [
        'icon'  => 'exclamation-circle text-danger',
        'title' => $_lang==='th' ? 'ลิงก์ไม่ถูกต้อง' : 'Invalid Link',
        'msg'   => $_lang==='th' ? 'ลิงก์ยืนยันไม่ถูกต้อง' : 'The verification link is invalid.',
        'alert' => 'alert-danger',
    ],
];

$m = $messages[$result];
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $_lang==='th'?'ยืนยันอีเมล':'Email Verification' ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:440px;text-align:center;">
    <div class="mb-4">
      <i class="fas fa-<?= $m['icon'] ?>" style="font-size:4rem;"></i>
    </div>
    <h2 style="font-size:1.5rem;font-weight:800;color:var(--blue-dark);margin-bottom:16px;">
      <?= e($m['title']) ?>
    </h2>
    <div class="alert <?= $m['alert'] ?>"><?= e($m['msg']) ?></div>

    <a href="<?= $appUrl ?>/login.php" class="btn w-100 py-3 fw-bold mt-3" style="background:var(--blue-dark);color:var(--white);border-radius:8px;">
      <i class="fas fa-sign-in-alt me-2"></i><?= t('auth.login') ?>
    </a>
    <a href="<?= $appUrl ?>/" class="d-block mt-3" style="font-size:.85rem;color:var(--gray-500);">
      <i class="fas fa-home me-1"></i><?= $_lang==='th'?'หน้าหลัก':'Home' ?>
    </a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
