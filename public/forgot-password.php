<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mail;

Auth::redirectIfLoggedIn();

$appUrl = APP_URL;
$_lang  = lang();
$csrf   = Auth::csrfToken();
$sent   = false;
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf(post('csrf_token'))) {
        $error = 'Invalid request.';
    } else {
        $email = sanitizeEmail(post('email'));
        if (!validateEmail($email)) {
            $error = $_lang==='th' ? 'กรุณากรอกอีเมลที่ถูกต้อง' : 'Please enter a valid email address.';
        } else {
            try {
                $db   = Database::getInstance();
                $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE email = :email AND account_status = 'active' LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Invalidate old tokens
                    $db->prepare("UPDATE password_resets SET is_used = TRUE WHERE user_id = :uid AND is_used = FALSE")
                       ->execute([':uid' => $user['id']]);

                    // Create new token
                    $token   = generateToken();
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :tok, :exp)")
                       ->execute([':uid' => $user['id'], ':tok' => $token, ':exp' => $expires]);

                    Mail::sendPasswordReset($email, $user['first_name'] . ' ' . $user['last_name'], $token);
                    auditLog('forgot_password', 'auth', 'Password reset requested: ' . $email, $user['id']);
                }
                // Always show sent message to prevent email enumeration
                $sent = true;
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
  <title><?= $_lang==='th'?'ลืมรหัสผ่าน':'Forgot Password' ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css?v=<?= CSS_VER ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:440px;">

    <div class="auth-logo">
      <div class="text-center mb-3">
        <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
              style="width:64px;height:64px;background:var(--blue-light);">
          <i class="fas fa-lock fa-2x" style="color:var(--blue-dark);"></i>
        </span>
      </div>
      <h4>ICALGC 2026</h4>
    </div>

    <h2 class="auth-title">
      <?= $_lang==='th' ? 'ลืมรหัสผ่าน?' : 'Forgot Password?' ?>
    </h2>

    <?php if ($sent): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <?= t('auth.reset_link_sent') ?><br>
        <small style="font-size:.82rem;opacity:.8;">
          <?= $_lang==='th' ? '(ตรวจสอบกล่องจดหมายของคุณ รวมถึงโฟลเดอร์ Spam)' : '(Check your inbox, including the Spam folder.)' ?>
        </small>
      </div>
      <a href="<?= $appUrl ?>/login.php" class="btn w-100 mt-3 py-2 fw-bold" style="background:var(--blue-dark);color:var(--white);border-radius:8px;">
        <i class="fas fa-sign-in-alt me-2"></i><?= t('auth.login') ?>
      </a>
    <?php else: ?>
      <p class="auth-subtitle">
        <?= $_lang==='th'
          ? 'กรอกอีเมลที่ลงทะเบียนไว้ เราจะส่งลิงก์สำหรับรีเซ็ตรหัสผ่านให้คุณ'
          : 'Enter your registered email and we will send you a password reset link.' ?>
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="mb-4">
          <label class="form-label" for="email">
            <i class="fas fa-envelope me-1" style="color:var(--blue-mid);"></i>
            <?= t('auth.email') ?> <span class="required">*</span>
          </label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e(post('email')) ?>"
                 placeholder="your@email.com" required>
        </div>
        <button type="submit" class="btn w-100 py-3 fw-bold" style="background:var(--blue-dark);color:var(--white);border-radius:8px;">
          <i class="fas fa-paper-plane me-2"></i>
          <?= $_lang==='th' ? 'ส่งลิงก์รีเซ็ต' : 'Send Reset Link' ?>
        </button>
      </form>
    <?php endif; ?>

    <div class="text-center mt-4">
      <a href="<?= $appUrl ?>/login.php" style="font-size:.85rem;color:var(--gray-500);">
        <i class="fas fa-arrow-left me-1"></i>
        <?= $_lang==='th' ? 'กลับหน้าเข้าสู่ระบบ' : 'Back to Login' ?>
      </a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
