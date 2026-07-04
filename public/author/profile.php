<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mail;

Auth::require('author');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

$errors   = [];
$success  = false;
$tabView  = get('tab', 'profile');

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :uid");
    $stmt->execute([':uid' => $uid]);
    $dbUser = $stmt->fetch();
    if (!$dbUser) {
        $dbUser = $user;
    }
} catch (\Throwable $e) {
    error_log($e->getMessage());
    $dbUser = $user;
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form_type') === 'profile') {
    Auth::verifyCsrf(post('csrf_token'));

    $firstName  = trim(post('first_name'));
    $middleName = trim(post('middle_name'));
    $lastName   = trim(post('last_name'));
    $certName   = trim(post('cert_name'));
    $phone     = trim(post('phone'));
    $address   = trim(post('mailing_address'));
    $position  = trim(post('position'));
    $affil     = trim(post('affiliation'));
    $department = trim(post('department'));
    $country   = trim(post('country'));
    $partType  = trim(post('participation_type'));
    $dietary   = trim(post('dietary'));
    $dietaryAllergy = trim(post('dietary_allergy'));
    $newEmail  = sanitizeEmail(post('email'));

    if (!$firstName || !$lastName) $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อและนามสกุล' : 'First and last name are required.';
    if (!$newEmail) $errors[] = $_lang==='th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email address.';

    // Check email uniqueness if changed
    $emailChanged = ($newEmail !== $dbUser['email']);
    if ($emailChanged && empty($errors)) {
        $eCheck = $db->prepare("SELECT id FROM users WHERE email = :em AND id != :uid");
        $eCheck->execute([':em' => $newEmail, ':uid' => $uid]);
        if ($eCheck->fetch()) {
            $errors[] = $_lang==='th' ? 'อีเมลนี้ถูกใช้งานแล้ว' : 'This email is already in use.';
        }
    }

    if (empty($errors)) {
        try {
            $upd = $db->prepare("
                UPDATE users SET
                    first_name = :fn, middle_name = :mn, last_name = :ln, cert_name = :cn,
                    phone = :phone, mailing_address = :addr,
                    position = :pos, affiliation = :aff, department = :dept, country = :ctry,
                    participation_type = :pt, dietary = :diet, dietary_allergy = :dietal,
                    email = :em,
                    email_verified = :ev,
                    updated_at = NOW()
                WHERE id = :uid
            ");
            $upd->execute([
                ':fn' => $firstName, ':mn' => $middleName ?: null, ':ln' => $lastName,
                ':cn' => $certName ?: null,
                ':phone' => $phone,
                ':addr' => $address, ':pos' => $position,
                ':aff'  => $affil, ':dept' => $department ?: null, ':ctry' => $country,
                ':pt'   => $partType ?: null,
                ':diet' => $dietary ?: null,
                ':dietal' => ($dietary === 'allergy' && $dietaryAllergy) ? $dietaryAllergy : null,
                ':em'   => $newEmail,
                ':ev'   => $emailChanged ? 'f' : ($dbUser['email_verified'] ? 't' : 'f'),
                ':uid'  => $uid,
            ]);

            // Re-verify email if changed
            if ($emailChanged) {
                $token   = generateToken();
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :tk, :ex)")
                   ->execute([':uid' => $uid, ':tk' => $token, ':ex' => $expires]);
                Mail::sendEmailVerification($newEmail, trim("$firstName $lastName"), $token);
                flashSet('info', $_lang==='th'
                    ? 'อัปเดตโปรไฟล์แล้ว กรุณายืนยันอีเมลใหม่'
                    : 'Profile updated. Please verify your new email address.');
            } else {
                flashSet('success', $_lang==='th' ? 'บันทึกข้อมูลเรียบร้อย' : 'Profile saved successfully.');
            }

            // Refresh session user
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :uid");
            $stmt->execute([':uid' => $uid]);
            $dbUser = $stmt->fetch();
            $_SESSION['user'] = $dbUser;

            auditLog('profile_update', 'users', "User $uid updated profile", $uid);

        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
    redirect($appUrl . '/author/profile.php');
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form_type') === 'password') {
    Auth::verifyCsrf(post('csrf_token'));
    $tabView = 'security';

    $currentPw  = post('current_password');
    $newPw      = post('new_password');
    $confirmPw  = post('confirm_password');

    if (!$currentPw || !$newPw || !$confirmPw)
        $errors[] = $_lang==='th' ? 'กรุณากรอกข้อมูลให้ครบ' : 'All fields required.';
    elseif (!password_verify($currentPw, $dbUser['password_hash']))
        $errors[] = $_lang==='th' ? 'รหัสผ่านปัจจุบันไม่ถูกต้อง' : 'Current password is incorrect.';
    elseif (strlen($newPw) < 8)
        $errors[] = $_lang==='th' ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 characters.';
    elseif ($newPw !== $confirmPw)
        $errors[] = $_lang==='th' ? 'รหัสผ่านใหม่ไม่ตรงกัน' : 'New passwords do not match.';

    if (empty($errors)) {
        try {
            $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE users SET password_hash = :h, updated_at = NOW() WHERE id = :uid")
               ->execute([':h' => $hash, ':uid' => $uid]);
            auditLog('password_change', 'users', "User $uid changed password", $uid);
            flashSet('success', $_lang==='th' ? 'เปลี่ยนรหัสผ่านเรียบร้อย' : 'Password changed successfully.');
            redirect($appUrl . '/author/profile.php?tab=security');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

$pageTitle  = $_lang==='th' ? 'ข้อมูลส่วนตัว' : 'My Profile';
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
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_author.php'; ?>

  <main class="dashboard-content">
    <div class="dash-header">
      <h1 class="dash-title">
        <i class="fas fa-user-edit me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
      </h1>
      <p class="dash-breadcrumb"><?= e($dbUser['email']) ?></p>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <i class="fas fa-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="d-flex gap-2 mb-4">
      <a href="?tab=profile" class="btn btn-sm rounded-pill fw-bold <?= $tabView==='profile'?'btn-warning':'btn-outline-secondary' ?>"
         style="<?= $tabView==='profile'?'background:var(--gold);color:var(--blue-dark);border-color:var(--gold);':'' ?>">
        <i class="fas fa-user me-1"></i><?= $_lang==='th' ? 'ข้อมูลส่วนตัว' : 'Profile' ?>
      </a>
      <a href="?tab=security" class="btn btn-sm rounded-pill fw-bold <?= $tabView==='security'?'btn-warning':'btn-outline-secondary' ?>"
         style="<?= $tabView==='security'?'background:var(--gold);color:var(--blue-dark);border-color:var(--gold);':'' ?>">
        <i class="fas fa-lock me-1"></i><?= $_lang==='th' ? 'ความปลอดภัย' : 'Security' ?>
      </a>
    </div>

    <?php if ($tabView === 'profile'): ?>
    <!-- Profile Form -->
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="form_type" value="profile">

      <div class="row g-4">
        <div class="col-lg-8">
          <!-- Personal Info -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-id-card me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลส่วนตัว' : 'Personal Information' ?>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ชื่อ' : 'First Name' ?> <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" value="<?= e($dbUser['first_name'] ?? '') ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ชื่อกลาง' : 'Middle Name' ?></label>
                <input type="text" name="middle_name" class="form-control" value="<?= e($dbUser['middle_name'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'นามสกุล' : 'Last Name' ?> <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" value="<?= e($dbUser['last_name'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'อีเมล' : 'Email' ?> <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e($dbUser['email'] ?? '') ?>" required>
                <?php if (!$dbUser['email_verified']): ?>
                  <div class="form-text text-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <?= $_lang==='th' ? 'อีเมลยังไม่ได้รับการยืนยัน' : 'Email not verified' ?>
                  </div>
                <?php else: ?>
                  <div class="form-text text-success">
                    <i class="fas fa-check-circle me-1"></i>
                    <?= $_lang==='th' ? 'อีเมลได้รับการยืนยันแล้ว' : 'Email verified' ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'เบอร์โทรศัพท์' : 'Phone' ?></label>
                <input type="tel" name="phone" class="form-control" value="<?= e($dbUser['phone'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ชื่อสำหรับใบประกาศนียบัตร' : 'Name for Certificate' ?></label>
                <input type="text" name="cert_name" class="form-control" value="<?= e($dbUser['cert_name'] ?? '') ?>"
                       placeholder="<?= $_lang==='th' ? 'ชื่อ-นามสกุลที่จะพิมพ์บนใบประกาศนียบัตร' : 'Full name as printed on the certificate' ?>">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ที่อยู่ติดต่อ' : 'Mailing Address' ?></label>
                <textarea name="mailing_address" class="form-control" rows="3"><?= e($dbUser['mailing_address'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <!-- Academic Info -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-university me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลวิชาการ' : 'Academic Information' ?>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ตำแหน่ง/คุณวุฒิ' : 'Position/Title' ?></label>
                <input type="text" name="position" class="form-control" value="<?= e($dbUser['position'] ?? '') ?>"
                       placeholder="<?= $_lang==='th' ? 'เช่น ผศ.ดร., นักศึกษา' : 'e.g. Asst. Prof., PhD Student' ?>">
              </div>
              <div class="col-md-8">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'สังกัด/สถาบัน' : 'Affiliation/Institution' ?></label>
                <input type="text" name="affiliation" class="form-control" value="<?= e($dbUser['affiliation'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ภาควิชา' : 'Department' ?></label>
                <input type="text" name="department" class="form-control" value="<?= e($dbUser['department'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ประเทศ' : 'Country' ?></label>
                <input type="text" name="country" class="form-control" value="<?= e($dbUser['country'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ประเภทการเข้าร่วม' : 'Participation Type' ?></label>
                <select name="participation_type" class="form-select">
                  <option value=""><?= $_lang==='th' ? '— เลือก —' : '— Select —' ?></option>
                  <?php
                  $partTypes = [
                      'presenter'   => $_lang==='th' ? 'ผู้นำเสนอผลงาน' : 'Presenter',
                      'coauthor'    => $_lang==='th' ? 'ผู้ร่วมแต่ง' : 'Co-author',
                      'participant' => $_lang==='th' ? 'ผู้เข้าร่วม' : 'Participant',
                      'student'     => $_lang==='th' ? 'นักศึกษา' : 'Student',
                  ];
                  foreach ($partTypes as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($dbUser['participation_type'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ความต้องการด้านอาหาร' : 'Dietary Requirements' ?></label>
                <select name="dietary" id="dietarySelect" class="form-select" onchange="document.getElementById('dietAllergyWrap').style.display = this.value==='allergy' ? 'block' : 'none';">
                  <option value=""><?= $_lang==='th' ? '— เลือก —' : '— Select —' ?></option>
                  <?php
                  $dietOpts = [
                      'none'       => $_lang==='th' ? 'ไม่มีข้อจำกัด' : 'No Restriction',
                      'vegetarian' => $_lang==='th' ? 'มังสวิรัติ' : 'Vegetarian',
                      'halal'      => $_lang==='th' ? 'ฮาลาล' : 'Halal',
                      'allergy'    => $_lang==='th' ? 'แพ้อาหาร' : 'Food Allergy',
                  ];
                  foreach ($dietOpts as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($dbUser['dietary'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6" id="dietAllergyWrap" style="display:<?= ($dbUser['dietary'] ?? '') === 'allergy' ? 'block' : 'none' ?>;">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ระบุอาหารที่แพ้' : 'Specify Food Allergy' ?></label>
                <input type="text" name="dietary_allergy" class="form-control" value="<?= e($dbUser['dietary_allergy'] ?? '') ?>"
                       placeholder="<?= $_lang==='th' ? 'เช่น กุ้ง ถั่ว นม...' : 'e.g. shrimp, peanuts, dairy...' ?>">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn-primary-custom">
              <i class="fas fa-save me-2"></i><?= $_lang==='th' ? 'บันทึกข้อมูล' : 'Save Profile' ?>
            </button>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
          <div class="content-card">
            <div class="content-card-title">
              <i class="fas fa-user-circle me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลบัญชี' : 'Account Info' ?>
            </div>
            <div class="d-flex flex-column gap-3" style="font-size:.88rem;">
              <div>
                <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;"><?= $_lang==='th' ? 'บทบาท' : 'Role' ?></div>
                <div style="font-weight:700;color:var(--blue-dark);"><?= ucfirst($dbUser['role']) ?></div>
              </div>
              <div>
                <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;"><?= $_lang==='th' ? 'วันที่สมัคร' : 'Registered' ?></div>
                <div><?= humanDate($dbUser['created_at'], $_lang) ?></div>
              </div>
              <div>
                <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;"><?= $_lang==='th' ? 'สถานะอีเมล' : 'Email Status' ?></div>
                <?php if ($dbUser['email_verified']): ?>
                  <span style="color:#198754;font-weight:600;"><i class="fas fa-check-circle me-1"></i><?= $_lang==='th' ? 'ยืนยันแล้ว' : 'Verified' ?></span>
                <?php else: ?>
                  <span style="color:#dc3545;font-weight:600;"><i class="fas fa-times-circle me-1"></i><?= $_lang==='th' ? 'ยังไม่ยืนยัน' : 'Unverified' ?></span>
                <?php endif; ?>
              </div>
              <?php if (($dbUser['account_status'] ?? '') === 'suspended'): ?>
              <div class="alert alert-danger p-2 mb-0" style="font-size:.82rem;">
                <i class="fas fa-ban me-1"></i><?= $_lang==='th' ? 'บัญชีถูกระงับ' : 'Account suspended' ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </form>

    <?php else: ?>
    <!-- Security / Password Tab -->
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-key me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'เปลี่ยนรหัสผ่าน' : 'Change Password' ?>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="form_type" value="password">
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'รหัสผ่านปัจจุบัน' : 'Current Password' ?> <span class="text-danger">*</span></label>
                <div class="position-relative">
                  <input type="password" name="current_password" class="form-control" id="curPw" required>
                  <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3"
                          onclick="togglePw('curPw', this)" style="color:var(--gray-500);">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'รหัสผ่านใหม่' : 'New Password' ?> <span class="text-danger">*</span></label>
                <div class="position-relative">
                  <input type="password" name="new_password" class="form-control" id="newPw" required minlength="8">
                  <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3"
                          onclick="togglePw('newPw', this)" style="color:var(--gray-500);">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="mt-2" style="height:4px;border-radius:99px;background:var(--gray-200);" id="pwStrengthBar">
                  <div id="pwStrengthFill" style="height:100%;border-radius:99px;width:0%;transition:.3s;"></div>
                </div>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ยืนยันรหัสผ่านใหม่' : 'Confirm New Password' ?> <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <button type="submit" class="btn-primary-custom">
                <i class="fas fa-lock me-2"></i><?= $_lang==='th' ? 'เปลี่ยนรหัสผ่าน' : 'Change Password' ?>
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-shield-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'คำแนะนำด้านความปลอดภัย' : 'Security Tips' ?>
          </div>
          <ul style="font-size:.85rem;color:var(--gray-700);padding-left:1.2rem;line-height:2;">
            <?php if ($_lang==='th'): ?>
              <li>ใช้รหัสผ่านที่มีความยาวอย่างน้อย 8 ตัวอักษร</li>
              <li>ผสมตัวพิมพ์ใหญ่-เล็ก ตัวเลข และอักขระพิเศษ</li>
              <li>อย่าใช้รหัสผ่านเดิมซ้ำกับเว็บไซต์อื่น</li>
              <li>อย่าแชร์รหัสผ่านกับผู้อื่น</li>
              <li>เปลี่ยนรหัสผ่านทุก 3-6 เดือน</li>
            <?php else: ?>
              <li>Use at least 8 characters</li>
              <li>Mix uppercase, lowercase, numbers and symbols</li>
              <li>Don't reuse passwords from other sites</li>
              <li>Never share your password with others</li>
              <li>Change your password every 3–6 months</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
function togglePw(id, btn) {
  const el = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (el.type === 'password') {
    el.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    el.type = 'password';
    icon.className = 'fas fa-eye';
  }
}

const newPwInput = document.getElementById('newPw');
if (newPwInput) {
  newPwInput.addEventListener('input', function() {
    const val = this.value;
    let strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;
    const colors = ['#dc3545','#fd7e14','#ffc107','#198754'];
    const pct    = [25, 50, 75, 100];
    const fill   = document.getElementById('pwStrengthFill');
    if (fill && strength > 0) {
      fill.style.width = pct[strength-1] + '%';
      fill.style.background = colors[strength-1];
    } else if (fill) {
      fill.style.width = '0';
    }
  });
}
</script>
</body>
</html>
