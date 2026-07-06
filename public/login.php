<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::redirectIfLoggedIn();

$error   = '';
$appUrl  = APP_URL;
$_lang   = lang();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!Auth::verifyCsrf(post('csrf_token'))) {
        $error = $_lang === 'th' ? 'คำขอไม่ถูกต้อง กรุณาลองใหม่' : 'Invalid request. Please try again.';
    } else {
        $email    = sanitizeEmail(post('email'));
        $password = post('password');

        if (!$email || !$password) {
            $error = $_lang === 'th' ? 'กรุณากรอกอีเมลและรหัสผ่าน' : 'Please enter your email and password.';
        } else {
            try {
                $db   = Database::getInstance();
                $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND account_status = 'active' LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    Auth::login($user);
                    auditLog('login', 'auth', 'User logged in: ' . $email, $user['id']);

                    // Redirect
                    $redirect = get('redirect');
                    if ($redirect && strpos($redirect, '/') === 0) {
                        redirect($redirect);
                    }
                    redirect(Auth::dashboardUrl());
                } else {
                    $error = t('auth.login_error');
                    auditLog('login_failed', 'auth', 'Failed login: ' . $email);
                }
            } catch (\Throwable $e) {
                $error = 'System error. Please try again.';
                error_log($e->getMessage());
            }
        }
    }
}

$csrf = Auth::csrfToken();
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('auth.login') ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css?v=<?= CSS_VER ?>">
</head>
<body>

<div class="auth-page">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <div class="d-flex justify-content-center gap-3 align-items-center mb-2">
        <img src="<?= $appUrl ?>/assets/images/swu_Logo.png" alt="SWU" style="height:50px;" onerror="this.style.display='none'">
        <img src="<?= $appUrl ?>/assets/images/Guangdong University of Foreign Studies 02.png" alt="GDUF" style="height:50px;" onerror="this.style.display='none'">
      </div>
      <h4>ICALGC 2026</h4>
      <p><?= $_lang==='th' ? 'การประชุมวิชาการนานาชาติ' : 'International Academic Conference' ?></p>
    </div>

    <h2 class="auth-title"><?= t('auth.login') ?></h2>
    <p class="auth-subtitle">
      <?= $_lang==='th' ? 'ยินดีต้อนรับกลับ กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ' : 'Welcome back. Please login to continue.' ?>
    </p>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error']) && $_GET['error'] === 'timeout'): ?>
      <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-clock"></i>
        <span><?= $_lang==='th' ? 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่' : 'Your session has expired. Please login again.' ?></span>
      </div>
    <?php endif; ?>

    <?= flashHtml() ?>

    <form method="POST" action="" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <div class="mb-4">
        <label class="form-label" for="email">
          <i class="fas fa-envelope me-1" style="color:var(--blue-mid);"></i>
          <?= t('auth.email') ?> <span class="required">*</span>
        </label>
        <input type="email"
               id="email"
               name="email"
               class="form-control"
               value="<?= e(post('email')) ?>"
               placeholder="your@email.com"
               required
               autocomplete="email">
      </div>

      <div class="mb-2">
        <label class="form-label" for="password">
          <i class="fas fa-lock me-1" style="color:var(--blue-mid);"></i>
          <?= t('auth.password') ?> <span class="required">*</span>
        </label>
        <div class="input-group">
          <input type="password"
                 id="password"
                 name="password"
                 class="form-control"
                 placeholder="••••••••"
                 required
                 autocomplete="current-password">
          <button class="input-group-text" type="button" onclick="togglePwd('password', this)" style="cursor:pointer;">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="mb-4 text-end">
        <a href="<?= $appUrl ?>/forgot-password.php" style="font-size:.85rem;color:var(--blue-mid);">
          <?= t('auth.forgot_password') ?>
        </a>
      </div>

      <button type="submit" class="btn w-100 py-3 fw-bold" style="background:var(--blue-dark);color:var(--white);border-radius:8px;font-size:1rem;">
        <i class="fas fa-sign-in-alt me-2"></i><?= t('auth.login') ?>
      </button>
    </form>

    <div class="text-center mt-4" style="font-size:.88rem;color:var(--gray-700);">
      <?= t('auth.no_account') ?>
      <a href="<?= $appUrl ?>/register.php" style="color:var(--blue-mid);font-weight:700;">
        <?= t('auth.register') ?>
      </a>
    </div>

    <div class="text-center mt-3">
      <a href="<?= $appUrl ?>/" style="font-size:.8rem;color:var(--gray-500);">
        <i class="fas fa-arrow-left me-1"></i>
        <?= $_lang==='th' ? 'กลับหน้าหลัก' : 'Back to Home' ?>
      </a>
    </div>

    <!-- Lang Switch -->
    <div class="text-center mt-3 d-flex justify-content-center gap-2">
      <a href="?lang=th" class="nav-link nav-lang-switch <?= $_lang==='th'?'active-lang':'' ?>" style="display:inline-block;border:1px solid var(--blue-mid);color:var(--blue-dark);border-radius:20px;padding:3px 12px;font-size:.8rem;font-weight:700;">TH</a>
      <a href="?lang=en" class="nav-link nav-lang-switch <?= $_lang==='en'?'active-lang':'' ?>" style="display:inline-block;border:1px solid var(--blue-mid);color:var(--blue-dark);border-radius:20px;padding:3px 12px;font-size:.8rem;font-weight:700;">EN</a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fas fa-eye';
  }
}
</script>
</body>
</html>
