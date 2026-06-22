<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));
    $action = post('action');

    // Delete
    if ($action === 'delete') {
        $id = intPost('id');
        if ($id) {
            try {
                $db = Database::getInstance();
                $db->prepare("DELETE FROM important_dates WHERE id = :id")->execute([':id' => $id]);
                flashSet('success', $_lang==='th' ? 'ลบวันสำคัญแล้ว' : 'Date deleted.');
            } catch (\Throwable $e) { error_log($e->getMessage()); }
        }
        redirect($appUrl . '/admin/important-dates.php');
    }

    // Create / Update
    $nameTh    = trim(post('name_th'));
    $nameEn    = trim(post('name_en'));
    $dateVal   = post('date_value');
    $sortOrder = intPost('sort_order', 0);
    $editId    = intPost('edit_id');

    if (!$nameTh || !$nameEn) $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อทั้งภาษาไทยและอังกฤษ' : 'Both Thai and English names required.';
    if (!$dateVal)             $errors[] = $_lang==='th' ? 'กรุณาเลือกวันที่' : 'Date is required.';

    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            if ($editId) {
                $db->prepare("UPDATE important_dates SET name_th=:nt, name_en=:ne, date_value=:dv, sort_order=:so WHERE id=:id")
                   ->execute([':nt'=>$nameTh,':ne'=>$nameEn,':dv'=>$dateVal,':so'=>$sortOrder,':id'=>$editId]);
                flashSet('success', $_lang==='th' ? 'อัปเดตแล้ว' : 'Updated.');
            } else {
                $db->prepare("INSERT INTO important_dates (name_th, name_en, date_value, sort_order) VALUES (:nt, :ne, :dv, :so)")
                   ->execute([':nt'=>$nameTh,':ne'=>$nameEn,':dv'=>$dateVal,':so'=>$sortOrder]);
                flashSet('success', $_lang==='th' ? 'เพิ่มวันสำคัญแล้ว' : 'Date added.');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
        if (empty($errors)) redirect($appUrl . '/admin/important-dates.php');
    }
}

$editItem = null;
$editId   = intGet('edit');

try {
    $db    = Database::getInstance();
    $dates = $db->query("SELECT * FROM important_dates ORDER BY sort_order, date_value")->fetchAll();
    if ($editId) {
        $eStmt = $db->prepare("SELECT * FROM important_dates WHERE id = :id");
        $eStmt->execute([':id' => $editId]);
        $editItem = $eStmt->fetch();
    }
} catch (\Throwable $e) {
    error_log($e->getMessage());
    $dates = [];
}

$pageTitle  = $_lang==='th' ? 'จัดการวันสำคัญ' : 'Important Dates';
$activeMenu = 'important-dates';
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
      <h1 class="dash-title"><i class="fas fa-calendar-alt me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Form -->
      <div class="col-lg-4">
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-<?= $editItem ? 'edit' : 'plus' ?> me-2" style="color:var(--gold);"></i>
            <?= $editItem ? ($_lang==='th'?'แก้ไขวันสำคัญ':'Edit Date') : ($_lang==='th'?'เพิ่มวันสำคัญ':'Add Date') ?>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="edit_id" value="<?= $editItem ? (int)$editItem['id'] : '' ?>">
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อ (ไทย)':'Name (Thai)' ?> <span class="text-danger">*</span></label>
                <input type="text" name="name_th" class="form-control" value="<?= e($editItem['name_th'] ?? post('name_th')) ?>" required>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อ (อังกฤษ)':'Name (English)' ?> <span class="text-danger">*</span></label>
                <input type="text" name="name_en" class="form-control" value="<?= e($editItem['name_en'] ?? post('name_en')) ?>" required>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'วันที่':'Date' ?> <span class="text-danger">*</span></label>
                <input type="date" name="date_value" class="form-control" value="<?= e($editItem['date_value'] ?? post('date_value')) ?>" required>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ลำดับ':'Sort Order' ?></label>
                <input type="number" name="sort_order" class="form-control" value="<?= e($editItem['sort_order'] ?? post('sort_order', 0)) ?>">
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn-primary-custom flex-fill">
                  <i class="fas fa-save me-2"></i><?= $editItem ? ($_lang==='th'?'บันทึก':'Save') : ($_lang==='th'?'เพิ่ม':'Add') ?>
                </button>
                <?php if ($editItem): ?>
                  <a href="?" class="btn-outline-custom"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></a>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- List -->
      <div class="col-lg-8">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title"><i class="fas fa-list me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'รายการวันสำคัญ':'Date List' ?></span>
          </div>
          <?php if (empty($dates)): ?>
            <div class="p-5 text-center"><h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5></div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th><?= $_lang==='th'?'ลำดับ':'Order' ?></th>
                    <th><?= $_lang==='th'?'ชื่อ (ไทย)':'Name (TH)' ?></th>
                    <th><?= $_lang==='th'?'ชื่อ (อังกฤษ)':'Name (EN)' ?></th>
                    <th><?= $_lang==='th'?'วันที่':'Date' ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dates as $d):
                    $isPast   = strtotime($d['date_value']) < strtotime('today');
                    $isToday  = date('Y-m-d', strtotime($d['date_value'])) === date('Y-m-d');
                  ?>
                    <tr>
                      <td style="text-align:center;"><?= (int)$d['sort_order'] ?></td>
                      <td style="font-weight:600;font-size:.88rem;"><?= e($d['name_th']) ?></td>
                      <td style="font-size:.88rem;"><?= e($d['name_en']) ?></td>
                      <td style="font-size:.85rem;white-space:nowrap;">
                        <span style="color:<?= $isToday?'var(--gold)':($isPast?'var(--gray-400)':'var(--blue-dark)') ?>;font-weight:<?= $isToday?'700':'400' ?>;">
                          <?= humanDate($d['date_value'], $_lang) ?>
                        </span>
                        <?php if ($isToday): ?><span class="badge ms-1" style="background:var(--gold);color:var(--blue-dark);font-size:.65rem;">TODAY</span><?php endif; ?>
                        <?php if ($isPast && !$isToday): ?><span class="badge ms-1" style="background:var(--gray-300);color:var(--gray-600);font-size:.65rem;"><?= $_lang==='th'?'ผ่านแล้ว':'Past' ?></span><?php endif; ?>
                      </td>
                      <td>
                        <div class="d-flex gap-1">
                          <a href="?edit=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.72rem;">
                            <i class="fas fa-edit"></i>
                          </a>
                          <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;"
                                    data-confirm="<?= $_lang==='th'?'ยืนยันการลบ?':'Confirm delete?' ?>">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
                        </div>
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
