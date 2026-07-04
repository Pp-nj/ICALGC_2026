<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;
$uid    = intGet('id');

if (!$uid) redirect($appUrl . '/admin/users.php');

$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM users WHERE id = :uid");
$stmt->execute([':uid' => $uid]);
$dbUser = $stmt->fetch();

if (!$dbUser) redirect($appUrl . '/admin/users.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));

    $firstName  = trim(post('first_name'));
    $middleName = trim(post('middle_name'));
    $lastName   = trim(post('last_name'));
    $certName   = trim(post('cert_name'));
    $phone      = trim(post('phone'));
    $address    = trim(post('mailing_address'));
    $position   = trim(post('position'));
    $affil      = trim(post('affiliation'));
    $department = trim(post('department'));
    $country    = trim(post('country'));
    $expertise  = trim(post('expertise'));
    $newEmail   = sanitizeEmail(post('email'));

    if (!$firstName || !$lastName) $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อและนามสกุล' : 'First and last name are required.';
    if (!$newEmail) $errors[] = $_lang==='th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email address.';

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
                    expertise = :exp,
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
                ':exp'  => $expertise ?: null,
                ':em'   => $newEmail,
                ':ev'   => $emailChanged ? 'f' : ($dbUser['email_verified'] ? 't' : 'f'),
                ':uid'  => $uid,
            ]);

            auditLog('admin_update_user', 'users', "Admin edited profile of user $uid", Auth::id());

            flashSet('success', $_lang==='th' ? 'บันทึกข้อมูลเรียบร้อย' : 'Profile saved successfully.');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }

    if (empty($errors)) {
        redirect($appUrl . '/admin/user-detail.php?id=' . $uid);
    }

    // keep entered values on error
    $dbUser = array_merge($dbUser, [
        'first_name' => $firstName, 'middle_name' => $middleName, 'last_name' => $lastName,
        'cert_name' => $certName, 'phone' => $phone, 'mailing_address' => $address,
        'position' => $position, 'affiliation' => $affil, 'department' => $department,
        'country' => $country, 'expertise' => $expertise, 'email' => $newEmail,
    ]);
}

$pageTitle  = $_lang==='th' ? 'แก้ไขข้อมูลผู้ใช้' : 'Edit User';
$activeMenu = 'users';
$isReviewer = $dbUser['role'] === 'reviewer';
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
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_admin.php'; ?>

  <main class="dashboard-content">
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title"><i class="fas fa-user-edit me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb"><?= e($dbUser['email']) ?> &middot; <?= ucfirst($dbUser['role']) ?></p>
      </div>
      <a href="<?= $appUrl ?>/admin/users.php" class="btn-outline-custom">
        <i class="fas fa-arrow-left me-2"></i><?= $_lang==='th' ? 'กลับ' : 'Back' ?>
      </a>
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

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div class="row g-4">
        <div class="col-lg-8">
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
                <input type="text" name="cert_name" class="form-control" value="<?= e($dbUser['cert_name'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ที่อยู่ติดต่อ' : 'Mailing Address' ?></label>
                <textarea name="mailing_address" class="form-control" rows="3"><?= e($dbUser['mailing_address'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-university me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลวิชาการ' : 'Academic Information' ?>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ตำแหน่ง/คุณวุฒิ' : 'Position/Title' ?></label>
                <input type="text" name="position" class="form-control" value="<?= e($dbUser['position'] ?? '') ?>">
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
              <?php if ($isReviewer): ?>
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ความเชี่ยวชาญ' : 'Expertise' ?></label>
                <textarea name="expertise" class="form-control" rows="2"><?= e($dbUser['expertise'] ?? '') ?></textarea>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn-primary-custom">
              <i class="fas fa-save me-2"></i><?= $_lang==='th' ? 'บันทึกข้อมูล' : 'Save Changes' ?>
            </button>
          </div>
        </div>

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
                <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;"><?= $_lang==='th' ? 'สถานะบัญชี' : 'Account Status' ?></div>
                <?php if ($dbUser['account_status'] === 'suspended'): ?>
                  <span style="color:#dc3545;font-weight:600;"><i class="fas fa-ban me-1"></i><?= $_lang==='th' ? 'ระงับ' : 'Suspended' ?></span>
                <?php else: ?>
                  <span style="color:#198754;font-weight:600;"><i class="fas fa-check-circle me-1"></i><?= $_lang==='th' ? 'ใช้งาน' : 'Active' ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
