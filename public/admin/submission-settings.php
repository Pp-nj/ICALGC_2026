<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $open = post('submission_open') === '1';
    try {
        settingSet('submission_open', $open ? '1' : '0');
        auditLog($open ? 'open_submission' : 'close_submission', 'settings', $open ? 'Submissions opened' : 'Submissions closed');
        flashSet('success', $open
            ? ($_lang==='th' ? 'เปิดรับบทคัดย่อแล้ว' : 'Submissions are now open.')
            : ($_lang==='th' ? 'ปิดรับบทคัดย่อแล้ว' : 'Submissions are now closed.'));
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        flashSet('danger', $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.');
    }
    redirect($appUrl . '/admin/submission-settings.php');
}

$isOpen     = isSubmissionOpen();
$pageTitle  = $_lang==='th' ? 'เปิด/ปิดรับบทคัดย่อ' : 'Submission Status';
$activeMenu = 'submission-settings';
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
      <h1 class="dash-title"><i class="fas fa-door-open me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
    </div>

    <?= flashHtml() ?>

    <div class="row">
      <div class="col-lg-7">
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-file-upload me-2" style="color:var(--gold);"></i>
            <?= $_lang==='th' ? 'สถานะการรับบทคัดย่อ' : 'Paper Submission' ?>
          </div>

          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="badge rounded-pill" style="font-size:.9rem;padding:8px 16px;background:<?= $isOpen ? '#198754' : '#dc3545' ?>;">
              <i class="fas fa-<?= $isOpen ? 'lock-open' : 'lock' ?> me-1"></i>
              <?= $isOpen
                  ? ($_lang==='th' ? 'เปิดรับอยู่' : 'Open')
                  : ($_lang==='th' ? 'ปิดรับแล้ว' : 'Closed') ?>
            </span>
          </div>

          <p style="color:var(--gray-500);font-size:.9rem;">
            <?= $_lang==='th'
                ? 'เมื่อปิดรับ ผู้เขียนจะส่งบทคัดย่อใหม่ไม่ได้ แต่ยังดูสถานะและส่งฉบับแก้ไขของบทคัดย่อเดิมได้ตามปกติ'
                : 'When closed, authors cannot submit new papers, but can still view status and upload revisions of existing papers.' ?>
          </p>

          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="submission_open" value="<?= $isOpen ? '0' : '1' ?>">
            <?php if ($isOpen): ?>
              <button type="submit" class="btn btn-danger rounded-pill px-4"
                      data-confirm="<?= $_lang==='th' ? 'ยืนยันการปิดรับบทคัดย่อ?' : 'Close submissions?' ?>">
                <i class="fas fa-lock me-2"></i><?= $_lang==='th' ? 'ปิดรับบทคัดย่อ' : 'Close Submissions' ?>
              </button>
            <?php else: ?>
              <button type="submit" class="btn btn-success rounded-pill px-4"
                      data-confirm="<?= $_lang==='th' ? 'ยืนยันการเปิดรับบทคัดย่อ?' : 'Open submissions?' ?>">
                <i class="fas fa-lock-open me-2"></i><?= $_lang==='th' ? 'เปิดรับบทคัดย่อ' : 'Open Submissions' ?>
              </button>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
