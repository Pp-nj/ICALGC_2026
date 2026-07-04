<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$errors = [];

// Create admin account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form_type') === 'create') {
    Auth::verifyCsrf(post('csrf_token'));

    $name    = trim(post('name'));
    $email   = sanitizeEmail(post('email'));
    $affil   = trim(post('affiliation'));
    $country = trim(post('country'));
    $pw      = post('password');

    if (!$name)         $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อ' : 'Name required.';
    if (!$email)        $errors[] = $_lang==='th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email.';
    if (strlen($pw) < 8) $errors[] = $_lang==='th' ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 chars.';

    if (empty($errors)) {
        try {
            $db  = Database::getInstance();
            $chk = $db->prepare("SELECT id FROM users WHERE email = :em");
            $chk->execute([':em' => $email]);
            if ($chk->fetch()) {
                $errors[] = $_lang==='th' ? 'อีเมลนี้ถูกใช้งานแล้ว' : 'Email already in use.';
            } else {
                $hash      = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
                $nameParts = explode(' ', $name, 2);
                $firstName = $nameParts[0];
                $lastName  = $nameParts[1] ?? '-';
                $ins  = $db->prepare("
                    INSERT INTO users (first_name, last_name, email, password_hash, role, affiliation, country, email_verified)
                    VALUES (:fn, :ln, :em, :ph, 'admin', :aff, :ctry, TRUE)
                ");
                $ins->execute([':fn'=>$firstName,':ln'=>$lastName,':em'=>$email,':ph'=>$hash,':aff'=>$affil,':ctry'=>$country]);
                auditLog('create_admin', 'users', "Created admin: $email", Auth::id());
                flashSet('success', $_lang==='th' ? 'สร้างบัญชีผู้ดูแลระบบเรียบร้อย' : 'Admin account created successfully.');
                redirect($appUrl . '/admin/admins.php');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

// Suspend/Activate admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form_type') === 'toggle') {
    Auth::verifyCsrf(post('csrf_token'));
    $uid    = intPost('user_id');
    $action = post('action');
    if ($uid && in_array($action, ['suspend', 'activate'])) {
        if ($uid === Auth::id()) {
            flashSet('danger', $_lang==='th' ? 'ไม่สามารถระงับบัญชีของตนเองได้' : 'Cannot suspend your own account.');
        } else {
            try {
                $db  = Database::getInstance();
                $val = $action === 'suspend' ? 'suspended' : 'active';
                $db->prepare("UPDATE users SET account_status = :v WHERE id = :uid AND role = 'admin'")
                   ->execute([':v' => $val, ':uid' => $uid]);
                flashSet('success', $action === 'suspend'
                    ? ($_lang==='th' ? 'ระงับแล้ว' : 'Suspended.')
                    : ($_lang==='th' ? 'เปิดใช้งานแล้ว' : 'Activated.'));
            } catch (\Throwable $e) { error_log($e->getMessage()); }
        }
    }
    redirect($appUrl . '/admin/admins.php');
}

try {
    $db     = Database::getInstance();
    $admins = $db->query("
        SELECT u.*
        FROM users u
        WHERE u.role = 'admin'
        ORDER BY u.created_at ASC
    ")->fetchAll();
} catch (\Throwable $e) {
    error_log($e->getMessage());
    $admins = [];
}

$pageTitle  = $_lang==='th' ? 'จัดการผู้ดูแลระบบ' : 'Manage Admins';
$activeMenu = 'admins';
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
    <div class="dash-header">
      <h1 class="dash-title"><i class="fas fa-user-shield me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
      <p class="dash-breadcrumb"><?= count($admins) ?> <?= $_lang==='th' ? 'ผู้ดูแลระบบ' : 'admin(s)' ?></p>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <!-- Warning -->
    <div class="alert d-flex align-items-center gap-3 mb-4"
         style="background:#fff3cd;border-left:4px solid var(--warning);border-radius:var(--radius);color:#664d03;">
      <i class="fas fa-exclamation-triangle fa-lg flex-shrink-0"></i>
      <div style="font-size:.88rem;">
        <strong><?= $_lang==='th' ? 'คำเตือน:' : 'Warning:' ?></strong>
        <?= $_lang==='th'
          ? 'บัญชีผู้ดูแลระบบมีสิทธิ์เข้าถึงทุกส่วนของระบบ กรุณาสร้างบัญชีให้เฉพาะผู้ที่ได้รับอนุญาตเท่านั้น'
          : 'Admin accounts have full system access. Only create accounts for authorized personnel.' ?>
      </div>
    </div>

    <div class="row g-4">
      <!-- Create Form -->
      <div class="col-lg-4">
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-user-plus me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'เพิ่มผู้ดูแลระบบ' : 'Add Admin' ?>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="form_type" value="create">
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อ-นามสกุล':'Full Name' ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e(post('name')) ?>" required>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'อีเมล':'Email' ?> <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e(post('email')) ?>" required>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'สังกัด':'Affiliation' ?></label>
                <input type="text" name="affiliation" class="form-control" value="<?= e(post('affiliation')) ?>">
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ประเทศ':'Country' ?></label>
                <input type="text" name="country" class="form-control" value="<?= e(post('country')) ?>">
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'รหัสผ่านเริ่มต้น':'Initial Password' ?> <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" minlength="8" required>
                <div class="form-text"><?= $_lang==='th'?'ต้องมีอย่างน้อย 8 ตัวอักษร':'At least 8 characters' ?></div>
              </div>
              <button type="submit" class="btn-primary-custom"
                      data-confirm="<?= $_lang==='th'?'ยืนยันการสร้างบัญชีผู้ดูแลระบบ?':'Confirm creating admin account?' ?>">
                <i class="fas fa-user-shield me-2"></i><?= $_lang==='th'?'สร้างบัญชี':'Create Account' ?>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Admin List -->
      <div class="col-lg-8">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title">
              <i class="fas fa-list me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'รายชื่อผู้ดูแลระบบ' : 'Admin List' ?>
            </span>
          </div>
          <?php if (empty($admins)): ?>
            <div class="p-5 text-center"><h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5></div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th><?= $_lang==='th'?'ชื่อ':'Name' ?></th>
                    <th><?= $_lang==='th'?'อีเมล':'Email' ?></th>
                    <th><?= $_lang==='th'?'สังกัด':'Affiliation' ?></th>
                    <th><?= $_lang==='th'?'สถานะ':'Status' ?></th>
                    <th><?= $_lang==='th'?'สมัคร':'Joined' ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($admins as $adm): ?>
                    <tr>
                      <td>
                        <div style="font-weight:700;font-size:.88rem;"><?= e($adm['first_name'] . ' ' . $adm['last_name']) ?></div>
                        <?php if ($adm['id'] === Auth::id()): ?>
                          <span style="font-size:.7rem;color:var(--gold);font-weight:600;">(<?= $_lang==='th'?'คุณ':'You' ?>)</span>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:.82rem;"><?= e($adm['email']) ?></td>
                      <td style="font-size:.8rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= e($adm['affiliation'] ?? '—') ?>
                      </td>
                      <td>
                        <?php if ($adm['account_status'] === 'suspended'): ?>
                          <span class="badge" style="background:#dc3545;color:#fff;font-size:.72rem;"><?= $_lang==='th'?'ระงับ':'Suspended' ?></span>
                        <?php else: ?>
                          <span class="badge" style="background:#198754;color:#fff;font-size:.72rem;"><?= $_lang==='th'?'ใช้งาน':'Active' ?></span>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:.78rem;"><?= humanDate($adm['created_at'], $_lang) ?></td>
                      <td>
                        <?php if ($adm['id'] !== Auth::id()): ?>
                          <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="form_type" value="toggle">
                            <input type="hidden" name="user_id" value="<?= (int)$adm['id'] ?>">
                            <?php if ($adm['account_status'] === 'suspended'): ?>
                              <input type="hidden" name="action" value="activate">
                              <button type="submit" class="btn btn-sm btn-outline-success rounded-pill" style="font-size:.72rem;">
                                <i class="fas fa-check"></i>
                              </button>
                            <?php else: ?>
                              <input type="hidden" name="action" value="suspend">
                              <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;"
                                      data-confirm="<?= $_lang==='th'?'ยืนยันการระงับแอดมิน?':'Confirm suspend admin?' ?>">
                                <i class="fas fa-ban"></i>
                              </button>
                            <?php endif; ?>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
