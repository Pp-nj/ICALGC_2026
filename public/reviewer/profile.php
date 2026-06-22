<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mail;

Auth::require('reviewer');
$_lang  = lang();
$appUrl = APP_URL;

$uid    = Auth::id();
$tab    = in_array(get('tab'), ['profile', 'security']) ? get('tab') : 'profile';
$errors = [];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));
    $formType = post('form_type');

    if ($formType === 'profile') {
        $name    = trim(post('name'));
        $email   = sanitizeEmail(post('email'));
        $phone   = trim(post('phone'));
        $affil   = trim(post('affiliation'));
        $country = trim(post('country'));
        $addr    = trim(post('mailing_address'));
        $pos     = trim(post('position'));

        if (!$name)  $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อ' : 'Name is required.';
        if (!$email) $errors[] = $_lang==='th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email.';

        if (empty($errors)) {
            try {
                $db = Database::getInstance();
                $cur = $db->prepare("SELECT email FROM users WHERE id = :id");
                $cur->execute([':id' => $uid]);
                $curEmail = $cur->fetchColumn();

                $emailChanged = ($email !== $curEmail);
                if ($emailChanged) {
                    $chk = $db->prepare("SELECT id FROM users WHERE email = :em AND id != :id");
                    $chk->execute([':em' => $email, ':id' => $uid]);
                    if ($chk->fetch()) {
                        $errors[] = $_lang==='th' ? 'อีเมลนี้ถูกใช้งานแล้ว' : 'Email already in use.';
                    }
                }

                if (empty($errors)) {
                    if ($emailChanged && EMAIL_VERIFICATION_ENABLED) {
                        $token = generateToken();
                        $db->prepare("UPDATE users SET name=:n, email=:em, phone=:ph, affiliation=:af, country=:co, mailing_address=:ma, position=:po, email_verified=FALSE WHERE id=:id")
                           ->execute([':n'=>$name,':em'=>$email,':ph'=>$phone,':af'=>$affil,':co'=>$country,':ma'=>$addr,':po'=>$pos,':id'=>$uid]);
                        $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :tok, NOW() + INTERVAL '24 hours')")
                           ->execute([':uid'=>$uid, ':tok'=>$token]);
                        Mail::sendEmailVerification($email, $name, $token);
                        $_SESSION['user']['email'] = $email;
                        flashSet('success', $_lang==='th' ? 'อัปเดตโปรไฟล์แล้ว กรุณายืนยันอีเมลใหม่' : 'Profile updated. Please verify your new email.');
                    } elseif ($emailChanged) {
                        // Verification disabled (dev): update the email but keep the
                        // account verified and send no email.
                        $db->prepare("UPDATE users SET name=:n, email=:em, phone=:ph, affiliation=:af, country=:co, mailing_address=:ma, position=:po, email_verified=TRUE WHERE id=:id")
                           ->execute([':n'=>$name,':em'=>$email,':ph'=>$phone,':af'=>$affil,':co'=>$country,':ma'=>$addr,':po'=>$pos,':id'=>$uid]);
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['name']  = $name;
                        flashSet('success', $_lang==='th' ? 'อัปเดตโปรไฟล์แล้ว' : 'Profile updated.');
                    } else {
                        $db->prepare("UPDATE users SET name=:n, phone=:ph, affiliation=:af, country=:co, mailing_address=:ma, position=:po WHERE id=:id")
                           ->execute([':n'=>$name,':ph'=>$phone,':af'=>$affil,':co'=>$country,':ma'=>$addr,':po'=>$pos,':id'=>$uid]);
                        $_SESSION['user']['name'] = $name;
                        flashSet('success', $_lang==='th' ? 'อัปเดตโปรไฟล์แล้ว' : 'Profile updated.');
                    }
                    auditLog('update_profile', 'users', 'Reviewer updated own profile', $uid);
                    redirect($appUrl . '/reviewer/profile.php?tab=profile');
                }
            } catch (\Throwable $e) {
                error_log($e->getMessage());
                $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
            }
        }
        $tab = 'profile';

    } elseif ($formType === 'security') {
        $curPw  = post('current_password');
        $newPw  = post('new_password');
        $confPw = post('confirm_password');

        if (!$curPw || !$newPw || !$confPw) $errors[] = $_lang==='th' ? 'กรุณากรอกข้อมูลให้ครบ' : 'All fields are required.';
        if (strlen($newPw) < 8)             $errors[] = $_lang==='th' ? 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร' : 'New password must be at least 8 characters.';
        if ($newPw !== $confPw)             $errors[] = $_lang==='th' ? 'รหัสผ่านใหม่ไม่ตรงกัน' : 'Passwords do not match.';

        if (empty($errors)) {
            try {
                $db   = Database::getInstance();
                $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
                $stmt->execute([':id' => $uid]);
                $hash = $stmt->fetchColumn();

                if (!password_verify($curPw, $hash)) {
                    $errors[] = $_lang==='th' ? 'รหัสผ่านปัจจุบันไม่ถูกต้อง' : 'Current password is incorrect.';
                } else {
                    $newHash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
                    $db->prepare("UPDATE users SET password_hash = :ph WHERE id = :id")->execute([':ph' => $newHash, ':id' => $uid]);
                    auditLog('change_password', 'users', 'Reviewer changed own password', $uid);
                    flashSet('success', $_lang==='th' ? 'เปลี่ยนรหัสผ่านแล้ว' : 'Password changed.');
                    redirect($appUrl . '/reviewer/profile.php?tab=security');
                }
            } catch (\Throwable $e) {
                error_log($e->getMessage());
                $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
            }
        }
        $tab = 'security';
    }
}

// Fetch user data
try {
    $db   = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $uid]);
    $user = $stmt->fetch();
} catch (\Throwable $e) {
    error_log($e->getMessage());
    $user = Auth::user();
}

$pageTitle  = $_lang==='th' ? 'โปรไฟล์ของฉัน' : 'My Profile';
$activeMenu = 'profile';
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
</head>
<body>

<div class="dashboard-wrap">
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_reviewer.php'; ?>

  <main class="dashboard-content">
    <div class="dash-header">
      <h1 class="dash-title"><i class="fas fa-user-circle me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 gap-2">
      <li class="nav-item">
        <a href="?tab=profile" class="nav-link <?= $tab==='profile'?'active':'' ?>" style="<?= $tab==='profile'?'background:var(--blue-dark);':'color:var(--blue-dark);border:1px solid var(--blue-dark);' ?>">
          <i class="fas fa-user me-2"></i><?= $_lang==='th'?'ข้อมูลส่วนตัว':'Profile Info' ?>
        </a>
      </li>
      <li class="nav-item">
        <a href="?tab=security" class="nav-link <?= $tab==='security'?'active':'' ?>" style="<?= $tab==='security'?'background:var(--blue-dark);':'color:var(--blue-dark);border:1px solid var(--blue-dark);' ?>">
          <i class="fas fa-lock me-2"></i><?= $_lang==='th'?'ความปลอดภัย':'Security' ?>
        </a>
      </li>
    </ul>

    <div class="row g-4">

      <?php if ($tab === 'profile'): ?>
        <!-- Profile Form -->
        <div class="col-lg-8">
          <div class="content-card">
            <div class="content-card-title">
              <i class="fas fa-edit me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'แก้ไขข้อมูลส่วนตัว':'Edit Profile Information' ?>
            </div>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
              <input type="hidden" name="form_type" value="profile">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อ-นามสกุล':'Full Name' ?> <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'อีเมล':'Email' ?> <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                  <?php if (!($user['email_verified'] ?? true)): ?>
                    <div class="form-text text-warning"><i class="fas fa-exclamation-triangle me-1"></i><?= $_lang==='th'?'อีเมลยังไม่ได้รับการยืนยัน':'Email not verified' ?></div>
                  <?php endif; ?>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'เบอร์โทรศัพท์':'Phone' ?></label>
                  <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ตำแหน่ง':'Position/Title' ?></label>
                  <input type="text" name="position" class="form-control" value="<?= e($user['position'] ?? '') ?>">
                </div>
                <div class="col-md-8">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'สังกัด/มหาวิทยาลัย':'Affiliation / Institution' ?></label>
                  <input type="text" name="affiliation" class="form-control" value="<?= e($user['affiliation'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ประเทศ':'Country' ?></label>
                  <input type="text" name="country" class="form-control" value="<?= e($user['country'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ที่อยู่':'Mailing Address' ?></label>
                  <textarea name="mailing_address" class="form-control" rows="2"><?= e($user['mailing_address'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn-primary-custom">
                    <i class="fas fa-save me-2"></i><?= $_lang==='th'?'บันทึก':'Save Changes' ?>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Account Info -->
        <div class="col-lg-4">
          <div class="content-card">
            <div class="content-card-title">
              <i class="fas fa-info-circle me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'ข้อมูลบัญชี':'Account Info' ?>
            </div>
            <div class="d-flex flex-column gap-3">
              <div>
                <div style="font-size:.75rem;color:var(--gray-500);margin-bottom:2px;"><?= $_lang==='th'?'บทบาท':'Role' ?></div>
                <span class="badge" style="background:var(--gold);color:var(--blue-dark);font-size:.8rem;">
                  <i class="fas fa-user-tie me-1"></i><?= $_lang==='th'?'ผู้ทรงคุณวุฒิ':'Reviewer' ?>
                </span>
              </div>
              <div>
                <div style="font-size:.75rem;color:var(--gray-500);margin-bottom:2px;"><?= $_lang==='th'?'สถานะอีเมล':'Email Status' ?></div>
                <?php if ($user['email_verified'] ?? false): ?>
                  <span class="badge" style="background:#198754;color:#fff;font-size:.78rem;"><i class="fas fa-check-circle me-1"></i><?= $_lang==='th'?'ยืนยันแล้ว':'Verified' ?></span>
                <?php else: ?>
                  <span class="badge" style="background:#dc3545;color:#fff;font-size:.78rem;"><i class="fas fa-times-circle me-1"></i><?= $_lang==='th'?'ยังไม่ยืนยัน':'Not Verified' ?></span>
                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:.75rem;color:var(--gray-500);margin-bottom:2px;"><?= $_lang==='th'?'สมัครเมื่อ':'Registered' ?></div>
                <div style="font-size:.85rem;font-weight:600;"><?= humanDate($user['created_at'] ?? '', $_lang) ?></div>
              </div>
              <div>
                <div style="font-size:.75rem;color:var(--gray-500);margin-bottom:2px;"><?= $_lang==='th'?'สถานะบัญชี':'Account Status' ?></div>
                <?php if ($user['is_suspended'] ?? false): ?>
                  <span class="badge" style="background:#dc3545;color:#fff;font-size:.78rem;"><?= $_lang==='th'?'ถูกระงับ':'Suspended' ?></span>
                <?php else: ?>
                  <span class="badge" style="background:#198754;color:#fff;font-size:.78rem;"><?= $_lang==='th'?'ใช้งานได้':'Active' ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- Security Form -->
        <div class="col-lg-6">
          <div class="content-card">
            <div class="content-card-title">
              <i class="fas fa-key me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'เปลี่ยนรหัสผ่าน':'Change Password' ?>
            </div>
            <form method="POST" id="securityForm">
              <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
              <input type="hidden" name="form_type" value="security">
              <div class="d-flex flex-column gap-3">
                <div>
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'รหัสผ่านปัจจุบัน':'Current Password' ?> <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" id="curPw" name="current_password" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePw('curPw')"><i class="fas fa-eye"></i></button>
                  </div>
                </div>
                <div>
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'รหัสผ่านใหม่':'New Password' ?> <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" id="newPw" name="new_password" class="form-control" minlength="8" required oninput="checkStrength(this.value)">
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePw('newPw')"><i class="fas fa-eye"></i></button>
                  </div>
                  <div class="mt-2">
                    <div style="height:6px;background:var(--gray-200);border-radius:99px;overflow:hidden;">
                      <div id="strengthBar" style="height:100%;width:0%;border-radius:99px;transition:.3s;background:#dc3545;"></div>
                    </div>
                    <div id="strengthLabel" style="font-size:.75rem;margin-top:4px;color:var(--gray-500);"><?= $_lang==='th'?'ความแข็งแรงรหัสผ่าน':'Password strength' ?></div>
                  </div>
                </div>
                <div>
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ยืนยันรหัสผ่านใหม่':'Confirm New Password' ?> <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" id="confPw" name="confirm_password" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePw('confPw')"><i class="fas fa-eye"></i></button>
                  </div>
                </div>
                <div class="form-text"><?= $_lang==='th'?'ต้องมีอย่างน้อย 8 ตัวอักษร':'Minimum 8 characters' ?></div>
                <button type="submit" class="btn-primary-custom">
                  <i class="fas fa-lock me-2"></i><?= $_lang==='th'?'เปลี่ยนรหัสผ่าน':'Change Password' ?>
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
function togglePw(id) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
}
function checkStrength(val) {
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  const pct   = (score / 5) * 100;
  const colors = ['#dc3545','#dc3545','#fd7e14','#ffc107','#198754'];
  const labels = {
    th: ['อ่อนมาก','อ่อน','พอใช้','ดี','ดีมาก'],
    en: ['Very Weak','Weak','Fair','Good','Strong']
  };
  bar.style.width = pct + '%';
  bar.style.background = colors[score - 1] || '#dc3545';
  label.textContent = (labels['<?= $_lang ?>'][score - 1] || '');
}
</script>
</body>
</html>
