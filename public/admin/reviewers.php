<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mail;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$errors  = [];

// Create reviewer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form_type') === 'create') {
    Auth::verifyCsrf(post('csrf_token'));

    $title      = trim(post('title'));
    $titleOther = trim(post('title_other'));
    $title      = ($title === 'Other') ? $titleOther : $title;
    $firstName  = trim(post('first_name'));
    $lastName   = trim(post('last_name'));
    $middleName = trim(post('middle_name'));
    $email      = sanitizeEmail(post('email'));
    $affil      = trim(post('affiliation'));
    $country    = trim(post('country'));
    $pw         = post('password');

    if (!$firstName) $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อ' : 'First name required.';
    if (!$lastName)  $errors[] = $_lang==='th' ? 'กรุณากรอกนามสกุล' : 'Last name required.';
    if (!$email) $errors[] = $_lang==='th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email.';
    if (strlen($pw) < 8) $errors[] = $_lang==='th' ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 chars.';

    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            $chk = $db->prepare("SELECT id FROM users WHERE email = :em");
            $chk->execute([':em' => $email]);
            if ($chk->fetch()) {
                $errors[] = $_lang==='th' ? 'อีเมลนี้ถูกใช้งานแล้ว' : 'Email already in use.';
            } else {
                $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $db->prepare("
                    INSERT INTO users (title, first_name, middle_name, last_name, email, password_hash, role, affiliation, country, email_verified)
                    VALUES (:ti, :fn, :mn, :ln, :em, :ph, 'reviewer', :aff, :ctry, TRUE)
                ");
                $ins->execute([':ti'=>$title ?: null,':fn'=>$firstName,':mn'=>$middleName ?: null,':ln'=>$lastName,':em'=>$email,':ph'=>$hash,':aff'=>$affil,':ctry'=>$country]);
                auditLog('create_reviewer', 'users', "Created reviewer: $email", Auth::id());
                flashSet('success', $_lang==='th' ? 'สร้างบัญชีผู้ทรงคุณวุฒิเรียบร้อย' : 'Reviewer account created.');
                redirect($appUrl . '/admin/reviewers.php');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

// Suspend/Activate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form_type') === 'toggle') {
    Auth::verifyCsrf(post('csrf_token'));
    $uid    = intPost('user_id');
    $action = post('action');
    if ($uid && in_array($action, ['suspend','activate'])) {
        try {
            $db  = Database::getInstance();
            $val = $action === 'suspend' ? 'suspended' : 'active';
            $db->prepare("UPDATE users SET account_status = :v WHERE id = :uid AND role = 'reviewer'")
               ->execute([':v'=>$val, ':uid'=>$uid]);
            flashSet('success', $action==='suspend' ? ($_lang==='th'?'ระงับแล้ว':'Suspended.') : ($_lang==='th'?'เปิดใช้งานแล้ว':'Activated.'));
        } catch (\Throwable $e) { error_log($e->getMessage()); }
    }
    redirect($appUrl . '/admin/reviewers.php');
}

try {
    $db = Database::getInstance();
    $reviewers = $db->query("
        SELECT u.*,
               COUNT(DISTINCT ra.id) AS total_assigned,
               COUNT(DISTINCT CASE WHEN ra.assignment_status = 'completed' THEN ra.id END) AS completed
        FROM users u
        LEFT JOIN review_assignments ra ON ra.reviewer_id = u.id
        WHERE u.role = 'reviewer'
        GROUP BY u.id
        ORDER BY u.first_name ASC, u.last_name ASC
    ")->fetchAll();
} catch (\Throwable $e) {
    error_log($e->getMessage());
    $reviewers = [];
}

$titleOptions = $_lang==='th'
    ? [
        ['value' => 'นาย',    'label' => 'นาย'],
        ['value' => 'นาง',    'label' => 'นาง'],
        ['value' => 'นางสาว', 'label' => 'นางสาว'],
        ['value' => 'ศ.',     'label' => 'ศ.'],
        ['value' => 'รศ.',    'label' => 'รศ.'],
        ['value' => 'ผศ.',    'label' => 'ผศ.'],
        ['value' => 'ดร.',    'label' => 'ดร.'],
        ['value' => 'Other',  'label' => 'อื่นๆ'],
      ]
    : [
        ['value' => 'Mr.',          'label' => 'Mr.'],
        ['value' => 'Mrs.',         'label' => 'Mrs.'],
        ['value' => 'Ms.',          'label' => 'Ms.'],
        ['value' => 'Prof.',        'label' => 'Prof.'],
        ['value' => 'Assoc. Prof.', 'label' => 'Assoc. Prof.'],
        ['value' => 'Asst. Prof.',  'label' => 'Asst. Prof.'],
        ['value' => 'Dr.',          'label' => 'Dr.'],
        ['value' => 'Other',        'label' => 'Other'],
      ];

$pageTitle  = $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'Reviewers';
$activeMenu = 'reviewers';
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
      <h1 class="dash-title"><i class="fas fa-user-tie me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
      <p class="dash-breadcrumb"><?= count($reviewers) ?> <?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'reviewer(s)' ?></p>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Create Form -->
      <div class="col-lg-4">
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-user-plus me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'เพิ่มผู้ทรงคุณวุฒิ' : 'Add Reviewer' ?>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="form_type" value="create">
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'คำนำหน้า':'Title / Prefix' ?></label>
                <select name="title" id="rv_title_select" class="form-select" onchange="rvHandleTitleChange(this)">
                  <option value=""><?= $_lang==='th' ? '— เลือกคำนำหน้า —' : '— Select Title —' ?></option>
                  <?php foreach ($titleOptions as $opt):
                    $sel = (post('title') === $opt['value']) ? 'selected' : ''; ?>
                    <option value="<?= e($opt['value']) ?>" <?= $sel ?><?= $opt['value']==='Other' ? ' data-other="1"' : '' ?>><?= e($opt['label']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div id="rvTitleOtherWrap" class="mt-2" style="display:<?= (post('title')==='Other') ? 'block' : 'none' ?>;">
                  <input type="text" name="title_other" id="rvTitleOtherInput" class="form-control"
                         placeholder="<?= $_lang==='th' ? 'ระบุคำนำหน้า...' : 'Specify title...' ?>"
                         value="<?= e(post('title_other')) ?>">
                </div>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อ':'First Name' ?> <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" value="<?= e(post('first_name')) ?>" required>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อกลาง (ถ้ามี)':'Middle Name (optional)' ?> <span class="text-muted" style="font-size:.78rem;font-weight:400;"><?= $_lang==='th'?'(ถ้ามี)':'(optional)' ?></span></label>
                <input type="text" name="middle_name" class="form-control" value="<?= e(post('middle_name')) ?>">
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'นามสกุล':'Last Name' ?> <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" value="<?= e(post('last_name')) ?>" required>
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
              <button type="submit" class="btn-primary-custom">
                <i class="fas fa-user-plus me-2"></i><?= $_lang==='th'?'สร้างบัญชี':'Create Account' ?>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reviewer List -->
      <div class="col-lg-8">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title"><i class="fas fa-list me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'รายชื่อผู้ทรงคุณวุฒิ' : 'Reviewer List' ?></span>
          </div>
          <?php if (empty($reviewers)): ?>
            <div class="p-5 text-center"><h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5></div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th><?= $_lang==='th'?'ชื่อ':'Name' ?></th>
                    <th><?= $_lang==='th'?'สังกัด':'Affiliation' ?></th>
                    <th><?= $_lang==='th'?'ได้รับ':'Assigned' ?></th>
                    <th><?= $_lang==='th'?'เสร็จ':'Done' ?></th>
                    <th><?= $_lang==='th'?'สถานะ':'Status' ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reviewers as $rv): ?>
                    <tr>
                      <td>
                        <div style="font-weight:700;font-size:.88rem;"><?= e(trim(($rv['title'] ? $rv['title'].' ' : '') . $rv['first_name'] . ' ' . ($rv['middle_name'] ? $rv['middle_name'].' ' : '') . $rv['last_name'])) ?></div>
                        <div style="font-size:.75rem;color:var(--gray-500);"><?= e($rv['email']) ?></div>
                      </td>
                      <td style="font-size:.82rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= e($rv['affiliation'] ?? '—') ?>
                      </td>
                      <td style="text-align:center;font-weight:700;"><?= (int)$rv['total_assigned'] ?></td>
                      <td style="text-align:center;font-weight:700;color:#198754;"><?= (int)$rv['completed'] ?></td>
                      <td>
                        <?php if ($rv['account_status'] === 'suspended'): ?>
                          <span class="badge" style="background:#dc3545;color:#fff;font-size:.72rem;"><?= $_lang==='th'?'ระงับ':'Suspended' ?></span>
                        <?php else: ?>
                          <span class="badge" style="background:#198754;color:#fff;font-size:.72rem;"><?= $_lang==='th'?'ใช้งาน':'Active' ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <form method="POST" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                          <input type="hidden" name="form_type" value="toggle">
                          <input type="hidden" name="user_id" value="<?= (int)$rv['id'] ?>">
                          <?php if ($rv['account_status'] === 'suspended'): ?>
                            <input type="hidden" name="action" value="activate">
                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill" style="font-size:.72rem;">
                              <i class="fas fa-check"></i>
                            </button>
                          <?php else: ?>
                            <input type="hidden" name="action" value="suspend">
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;"
                                    data-confirm="<?= $_lang==='th'?'ยืนยันการระงับ?':'Confirm suspend?' ?>">
                              <i class="fas fa-ban"></i>
                            </button>
                          <?php endif; ?>
                        </form>
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

<script>
function rvHandleTitleChange(sel) {
  var wrap = document.getElementById('rvTitleOtherWrap');
  wrap.style.display = sel.selectedOptions[0] && sel.selectedOptions[0].dataset.other ? 'block' : 'none';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
