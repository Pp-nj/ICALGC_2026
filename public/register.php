<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mail;
use App\Core\Notification;

Auth::redirectIfLoggedIn();

$error   = '';
$success = '';
$appUrl  = APP_URL;
$_lang   = lang();
$csrf    = Auth::csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf(post('csrf_token'))) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Collect and sanitize fields
        $title    = sanitize(post('title'));
        $fname    = sanitize(post('first_name'));
        $lname    = sanitize(post('last_name'));
        $email    = sanitizeEmail(post('email'));
        $phone    = sanitize(post('phone'));
        $address  = sanitize(post('mailing_address'));
        $position = sanitize(post('position'));
        $affil    = sanitize(post('affiliation'));
        $country  = sanitize(post('country', 'Thailand'));
        $attend   = (post('attend') === '1') ? true : false;
        $pwd      = post('password');
        $cpwd     = post('confirm_password');

        // Validation
        $errors = [];
        if (!$fname)           $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อ' : 'First name is required.';
        if (!$lname)           $errors[] = $_lang==='th' ? 'กรุณากรอกนามสกุล' : 'Last name is required.';
        if (!validateEmail($email)) $errors[] = $_lang==='th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email address.';
        if (strlen($pwd) < 8)  $errors[] = $_lang==='th' ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 characters.';
        if ($pwd !== $cpwd)    $errors[] = $_lang==='th' ? 'รหัสผ่านไม่ตรงกัน' : 'Passwords do not match.';
        if (!$affil)           $errors[] = $_lang==='th' ? 'กรุณากรอกสังกัด' : 'Affiliation is required.';

        if (empty($errors)) {
            try {
                $db = Database::getInstance();

                // Check duplicate email
                $chk = $db->prepare("SELECT id FROM users WHERE email = :email");
                $chk->execute([':email' => $email]);
                if ($chk->fetch()) {
                    $errors[] = $_lang==='th' ? 'อีเมลนี้ถูกใช้แล้ว' : 'This email is already registered.';
                }

                if (empty($errors)) {
                    $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);

                    // Feature flag: when email verification is disabled (e.g. local
                    // development) the account is created already verified so the
                    // user can proceed immediately. In production the flag is on and
                    // the account starts unverified, exactly as before.
                    $isVerified = EMAIL_VERIFICATION_ENABLED ? 'FALSE' : 'TRUE';

                    $ins = $db->prepare("
                        INSERT INTO users
                            (role, title, first_name, last_name, email, phone, mailing_address,
                             position, affiliation, country, attend_conference, password_hash,
                             email_verified, account_status)
                        VALUES
                            ('author', :title, :fn, :ln, :email, :phone, :addr,
                             :pos, :affil, :country, :attend, :hash,
                             {$isVerified}, 'active')
                        RETURNING id
                    ");
                    $ins->execute([
                        ':title'   => $title,
                        ':fn'      => $fname,
                        ':ln'      => $lname,
                        ':email'   => $email,
                        ':phone'   => $phone,
                        ':addr'    => $address,
                        ':pos'     => $position,
                        ':affil'   => $affil,
                        ':country' => $country,
                        ':attend'  => $attend ? 'TRUE' : 'FALSE',
                        ':hash'    => $hash,
                    ]);
                    $userId = (int)$ins->fetchColumn();

                    // Email verification token + email — only when verification is
                    // enabled. The implementation is preserved untouched for
                    // production; it is simply skipped while the flag is off.
                    if (EMAIL_VERIFICATION_ENABLED) {
                        $token   = generateToken();
                        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :tok, :exp)")
                           ->execute([':uid' => $userId, ':tok' => $token, ':exp' => $expires]);

                        // Send verification email (non-blocking — if fails, user can resend)
                        Mail::sendEmailVerification($email, $fname . ' ' . $lname, $token);
                    }

                    auditLog('register', 'auth', 'New author: ' . $email, $userId);

                    flashSet('success', t('auth.register_success'));
                    redirect('/login.php');
                }
            } catch (\Throwable $e) {
                $errors[] = 'System error. Please try again.';
                error_log($e->getMessage());
            }
        }

        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('auth.register') ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
</head>
<body>

<div class="auth-page" style="align-items:flex-start;padding:40px 16px;">
  <div class="auth-card" style="max-width:680px;">

    <!-- Logo -->
    <div class="auth-logo">
      <div class="d-flex justify-content-center gap-3 align-items-center mb-2">
        <img src="<?= $appUrl ?>/assets/images/logo-swu.png" alt="SWU" style="height:50px;" onerror="this.style.display='none'">
        <img src="<?= $appUrl ?>/assets/images/logo-gduf.png" alt="GDUF" style="height:50px;" onerror="this.style.display='none'">
      </div>
      <h4>ICALGC 2026</h4>
    </div>

    <h2 class="auth-title"><?= t('auth.register') ?></h2>
    <p class="auth-subtitle">
      <?= $_lang==='th'
        ? 'สร้างบัญชีสำหรับผู้แต่งเพื่อส่งบทความและติดตามผลการพิจารณา'
        : 'Create an author account to submit papers and track review status.' ?>
    </p>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
        <i class="fas fa-exclamation-circle mt-1"></i>
        <span><?= $error ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <!-- Personal Info -->
      <div class="content-card-title" style="font-size:.95rem;font-weight:700;color:var(--blue-dark);border-bottom:2px solid var(--gray-200);padding-bottom:12px;margin-bottom:20px;">
        <i class="fas fa-user me-2 text-gold" style="color:var(--gold);"></i>
        <?= $_lang==='th' ? 'ข้อมูลส่วนตัว' : 'Personal Information' ?>
      </div>

      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label"><?= t('auth.title') ?></label>
          <select name="title" class="form-select">
            <option value="">—</option>
            <?php
            $titles = $_lang==='th'
              ? ['นาย','นาง','นางสาว','ดร.','รศ.ดร.','ศ.ดร.','ผศ.ดร.','อ.','Prof.','Assoc. Prof.','Asst. Prof.','Mr.','Mrs.','Ms.','Dr.']
              : ['Mr.','Mrs.','Ms.','Dr.','Prof.','Assoc. Prof.','Asst. Prof.'];
            foreach ($titles as $ttl): ?>
              <option value="<?= e($ttl) ?>" <?= post('title')===$ttl?'selected':'' ?>><?= e($ttl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label"><?= t('auth.first_name') ?> <span class="required">*</span></label>
          <input type="text" name="first_name" class="form-control" value="<?= e(post('first_name')) ?>" required>
        </div>
        <div class="col-md-5">
          <label class="form-label"><?= t('auth.last_name') ?> <span class="required">*</span></label>
          <input type="text" name="last_name" class="form-control" value="<?= e(post('last_name')) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label"><?= t('auth.email') ?> <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" value="<?= e(post('email')) ?>" required placeholder="your@email.com">
        </div>
        <div class="col-md-6">
          <label class="form-label"><?= t('auth.phone') ?></label>
          <input type="tel" name="phone" class="form-control" value="<?= e(post('phone')) ?>" placeholder="+66 XX XXX XXXX">
        </div>
        <div class="col-12">
          <label class="form-label"><?= t('auth.mailing_address') ?></label>
          <textarea name="mailing_address" class="form-control" rows="2"
                    placeholder="<?= $_lang==='th' ? 'ที่อยู่สำหรับจัดส่งใบประกาศนียบัตร' : 'Address for certificate delivery' ?>"><?= e(post('mailing_address')) ?></textarea>
        </div>
      </div>

      <!-- Academic Info -->
      <div class="content-card-title" style="font-size:.95rem;font-weight:700;color:var(--blue-dark);border-bottom:2px solid var(--gray-200);padding-bottom:12px;margin-bottom:20px;margin-top:28px;">
        <i class="fas fa-university me-2" style="color:var(--gold);"></i>
        <?= $_lang==='th' ? 'ข้อมูลทางวิชาการ' : 'Academic Information' ?>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label"><?= t('auth.position') ?></label>
          <input type="text" name="position" class="form-control" value="<?= e(post('position')) ?>"
                 placeholder="<?= $_lang==='th' ? 'อาจารย์ / นักวิจัย / นักศึกษา' : 'Lecturer / Researcher / Student' ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label"><?= t('auth.affiliation') ?> <span class="required">*</span></label>
          <input type="text" name="affiliation" class="form-control" value="<?= e(post('affiliation')) ?>"
                 placeholder="<?= $_lang==='th' ? 'มหาวิทยาลัย / สถาบัน' : 'University / Institution' ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label"><?= t('auth.country') ?></label>
          <input type="text" name="country" class="form-control" value="<?= e(post('country', 'Thailand')) ?>">
        </div>
        <div class="col-12">
          <label class="form-label"><?= t('auth.attend') ?></label>
          <div class="d-flex gap-3 mt-1">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="attend" id="attend_yes" value="1"
                     <?= post('attend','1')==='1'?'checked':'' ?>>
              <label class="form-check-label" for="attend_yes"><?= t('auth.attend_yes') ?></label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="attend" id="attend_no" value="0"
                     <?= post('attend')==='0'?'checked':'' ?>>
              <label class="form-check-label" for="attend_no"><?= t('auth.attend_no') ?></label>
            </div>
          </div>
        </div>
      </div>

      <!-- Password -->
      <div class="content-card-title" style="font-size:.95rem;font-weight:700;color:var(--blue-dark);border-bottom:2px solid var(--gray-200);padding-bottom:12px;margin-bottom:20px;margin-top:28px;">
        <i class="fas fa-shield-alt me-2" style="color:var(--gold);"></i>
        <?= $_lang==='th' ? 'ความปลอดภัย' : 'Security' ?>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label"><?= t('auth.password') ?> <span class="required">*</span></label>
          <div class="input-group">
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="<?= $_lang==='th' ? 'อย่างน้อย 8 ตัวอักษร' : 'Min 8 characters' ?>" required>
            <button class="input-group-text" type="button" onclick="togglePwd('password',this)" style="cursor:pointer;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="mt-1" id="pwdStrength" style="font-size:.75rem;"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label"><?= t('auth.confirm_password') ?> <span class="required">*</span></label>
          <div class="input-group">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                   placeholder="<?= $_lang==='th' ? 'ยืนยันรหัสผ่าน' : 'Confirm password' ?>" required>
            <button class="input-group-text" type="button" onclick="togglePwd('confirm_password',this)" style="cursor:pointer;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="mt-4 p-3 rounded" style="background:var(--blue-light);font-size:.83rem;color:var(--blue-dark);">
        <i class="fas fa-info-circle me-1"></i>
        <?php if (EMAIL_VERIFICATION_ENABLED): ?>
          <?= $_lang==='th'
            ? 'หลังจากลงทะเบียน ระบบจะส่งอีเมลยืนยันไปที่อีเมลของคุณ กรุณายืนยันก่อนเข้าสู่ระบบ'
            : 'After registration, a verification email will be sent to you. Please verify before logging in.' ?>
        <?php else: ?>
          <?= $_lang==='th'
            ? 'หลังจากลงทะเบียน คุณสามารถเข้าสู่ระบบได้ทันที'
            : 'After registration, you can log in immediately.' ?>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn w-100 py-3 fw-bold mt-4" style="background:var(--blue-dark);color:var(--white);border-radius:8px;font-size:1rem;">
        <i class="fas fa-user-plus me-2"></i><?= t('auth.register') ?>
      </button>
    </form>

    <div class="text-center mt-4" style="font-size:.88rem;">
      <?= t('auth.have_account') ?>
      <a href="<?= $appUrl ?>/login.php" style="color:var(--blue-mid);font-weight:700;"><?= t('auth.login') ?></a>
    </div>

    <div class="text-center mt-2">
      <a href="<?= $appUrl ?>/" style="font-size:.8rem;color:var(--gray-500);">
        <i class="fas fa-arrow-left me-1"></i>
        <?= $_lang==='th' ? 'กลับหน้าหลัก' : 'Back to Home' ?>
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
// Password strength indicator
document.getElementById('password')?.addEventListener('input', function() {
  const val = this.value;
  const el  = document.getElementById('pwdStrength');
  if (!el) return;
  let strength = 0;
  if (val.length >= 8) strength++;
  if (/[A-Z]/.test(val)) strength++;
  if (/[0-9]/.test(val)) strength++;
  if (/[^A-Za-z0-9]/.test(val)) strength++;
  const levels = [
    { label: '<?= $_lang==="th"?"อ่อนมาก":"Very Weak" ?>',   color: '#dc3545' },
    { label: '<?= $_lang==="th"?"อ่อน":"Weak" ?>',           color: '#fd7e14' },
    { label: '<?= $_lang==="th"?"ปานกลาง":"Fair" ?>',        color: '#ffc107' },
    { label: '<?= $_lang==="th"?"แข็งแรง":"Strong" ?>',      color: '#198754' },
    { label: '<?= $_lang==="th"?"แข็งแรงมาก":"Very Strong" ?>', color: '#0f5132' },
  ];
  if (val.length === 0) { el.innerHTML = ''; return; }
  const l = levels[strength] || levels[0];
  el.innerHTML = `<span style="color:${l.color};">● ${l.label}</span>`;
});
</script>
</body>
</html>
