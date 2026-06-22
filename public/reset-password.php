<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::redirectIfLoggedIn();

$appUrl  = APP_URL;
$_lang   = lang();
$token   = sanitize(get('token'));
$error   = '';
$success = false;
$csrf    = Auth::csrfToken();
$valid   = false;
$userId  = null;

// Validate token
if ($token) {
    try {
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT pr.*, u.email FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.token = :tok AND pr.is_used = FALSE AND pr.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':tok' => $token]);
        $reset = $stmt->fetch();
        if ($reset) {
            $valid  = true;
            $userId = $reset['user_id'];
        }
    } catch (\Throwable $e) {
        error_log($e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    if (!Auth::verifyCsrf(post('csrf_token'))) {
        $error = 'Invalid request.';
    } else {
        $pwd  = post('password');
        $cpwd = post('confirm_password');

        if (strlen($pwd) < 8) {
            $error = $_lang==='th' ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 characters.';
        } elseif ($pwd !== $cpwd) {
            $error = $_lang==='th' ? 'รหัสผ่านไม่ตรงกัน' : 'Passwords do not match.';
        } else {
            try {
                $db   = Database::getInstance();
                $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :uid")
                   ->execute([':hash' => $hash, ':uid' => $userId]);
                $db->prepare("UPDATE password_resets SET is_used = TRUE WHERE token = :tok")
                   ->execute([':tok' => $token]);

                auditLog('reset_password', 'auth', 'Password reset completed', $userId);
                $success = true;
                flashSet('success', t('auth.password_reset_ok'));
                redirect('/login.php');
            } catch (\Throwable $e) {
                $error = 'System error. Please try again.';
                error_log($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $_lang==='th'?'รีเซ็ตรหัสผ่าน':'Reset Password' ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:440px;">

    <div class="auth-logo">
      <div class="text-center mb-3">
        <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
              style="width:64px;height:64px;background:var(--blue-light);">
          <i class="fas fa-key fa-2x" style="color:var(--blue-dark);"></i>
        </span>
      </div>
      <h4>ICALGC 2026</h4>
    </div>

    <h2 class="auth-title"><?= $_lang==='th'?'ตั้งรหัสผ่านใหม่':'Set New Password' ?></h2>

    <?php if (!$valid): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $_lang==='th'
          ? 'ลิงก์ไม่ถูกต้องหรือหมดอายุแล้ว กรุณาส่งคำขอรีเซ็ตรหัสผ่านใหม่'
          : 'This link is invalid or has expired. Please request a new password reset.' ?>
      </div>
      <a href="<?= $appUrl ?>/forgot-password.php" class="btn w-100 mt-3 py-2 fw-bold" style="background:var(--blue-dark);color:var(--white);border-radius:8px;">
        <?= $_lang==='th' ? 'ขอลิงก์ใหม่' : 'Request New Link' ?>
      </a>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="mb-3">
          <label class="form-label"><?= t('auth.password') ?> <span class="required">*</span></label>
          <div class="input-group">
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="<?= $_lang==='th'?'อย่างน้อย 8 ตัวอักษร':'Min 8 characters' ?>" required>
            <button class="input-group-text" type="button" onclick="togglePwd('password',this)" style="cursor:pointer;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label"><?= t('auth.confirm_password') ?> <span class="required">*</span></label>
          <div class="input-group">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                   placeholder="<?= $_lang==='th'?'ยืนยันรหัสผ่าน':'Confirm password' ?>" required>
            <button class="input-group-text" type="button" onclick="togglePwd('confirm_password',this)" style="cursor:pointer;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn w-100 py-3 fw-bold" style="background:var(--blue-dark);color:var(--white);border-radius:8px;">
          <i class="fas fa-save me-2"></i>
          <?= $_lang==='th' ? 'บันทึกรหัสผ่านใหม่' : 'Save New Password' ?>
        </button>
      </form>
    <?php endif; ?>

    <div class="text-center mt-4">
      <a href="<?= $appUrl ?>/login.php" style="font-size:.85rem;color:var(--gray-500);">
        <i class="fas fa-arrow-left me-1"></i><?= $_lang==='th'?'กลับหน้าเข้าสู่ระบบ':'Back to Login' ?>
      </a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
  else { input.type = 'password'; icon.className = 'fas fa-eye'; }
}
</script>
</body>
</html>
