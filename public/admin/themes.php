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

    if ($action === 'delete') {
        $id = intPost('id');
        if ($id) {
            try {
                $db = Database::getInstance();
                $chk = $db->prepare("SELECT COUNT(*) FROM papers WHERE theme_id = :id");
                $chk->execute([':id' => $id]);
                if ((int)$chk->fetchColumn() > 0) {
                    flashSet('error', $_lang==='th' ? 'ไม่สามารถลบหัวข้อที่มีบทความอยู่' : 'Cannot delete theme that has papers.');
                } else {
                    $db->prepare("DELETE FROM conference_themes WHERE id = :id")->execute([':id' => $id]);
                    flashSet('success', $_lang==='th' ? 'ลบหัวข้อแล้ว' : 'Theme deleted.');
                }
            } catch (\Throwable $e) { error_log($e->getMessage()); }
        }
        redirect($appUrl . '/admin/themes.php');
    }

    $nameTh  = trim(post('name_th'));
    $nameEn  = trim(post('name_en'));
    $descTh  = trim(post('description_th'));
    $descEn  = trim(post('description_en'));
    $editId  = intPost('edit_id');

    if (!$nameTh || !$nameEn) $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อทั้งสองภาษา' : 'Both names required.';

    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            if ($editId) {
                $db->prepare("UPDATE conference_themes SET name_th=:nt, name_en=:ne, description_th=:dt, description_en=:de WHERE id=:id")
                   ->execute([':nt'=>$nameTh,':ne'=>$nameEn,':dt'=>$descTh,':de'=>$descEn,':id'=>$editId]);
                flashSet('success', $_lang==='th' ? 'อัปเดตแล้ว' : 'Updated.');
            } else {
                $db->prepare("INSERT INTO conference_themes (name_th, name_en, description_th, description_en) VALUES (:nt, :ne, :dt, :de)")
                   ->execute([':nt'=>$nameTh,':ne'=>$nameEn,':dt'=>$descTh,':de'=>$descEn]);
                flashSet('success', $_lang==='th' ? 'เพิ่มหัวข้อแล้ว' : 'Theme added.');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
        if (empty($errors)) redirect($appUrl . '/admin/themes.php');
    }
}

$editItem = null;
$editId   = intGet('edit');

try {
    $db     = Database::getInstance();
    $themes = $db->query("
        SELECT ct.*, COUNT(p.id) AS paper_count
        FROM conference_themes ct
        LEFT JOIN papers p ON p.theme_id = ct.id
        GROUP BY ct.id
        ORDER BY ct.id
    ")->fetchAll();
    if ($editId) {
        $eStmt = $db->prepare("SELECT * FROM conference_themes WHERE id = :id");
        $eStmt->execute([':id' => $editId]);
        $editItem = $eStmt->fetch();
    }
} catch (\Throwable $e) {
    error_log($e->getMessage());
    $themes = [];
}

$pageTitle  = $_lang==='th' ? 'จัดการหัวข้อการประชุม' : 'Conference Themes';
$activeMenu = 'themes';
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
      <h1 class="dash-title"><i class="fas fa-tags me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
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
            <?= $editItem ? ($_lang==='th'?'แก้ไขหัวข้อ':'Edit Theme') : ($_lang==='th'?'เพิ่มหัวข้อ':'Add Theme') ?>
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
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'คำอธิบาย (ไทย)':'Description (Thai)' ?></label>
                <textarea name="description_th" class="form-control" rows="3"><?= e($editItem['description_th'] ?? post('description_th')) ?></textarea>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'คำอธิบาย (อังกฤษ)':'Description (English)' ?></label>
                <textarea name="description_en" class="form-control" rows="3"><?= e($editItem['description_en'] ?? post('description_en')) ?></textarea>
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
            <span class="table-card-title"><i class="fas fa-list me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'รายการหัวข้อ':'Theme List' ?></span>
          </div>
          <?php if (empty($themes)): ?>
            <div class="p-5 text-center"><h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5></div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th>#</th>
                    <th><?= $_lang==='th'?'ชื่อ (ไทย)':'Name (TH)' ?></th>
                    <th><?= $_lang==='th'?'ชื่อ (อังกฤษ)':'Name (EN)' ?></th>
                    <th><?= $_lang==='th'?'บทความ':'Papers' ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($themes as $t): ?>
                    <tr>
                      <td style="color:var(--gray-500);"><?= (int)$t['id'] ?></td>
                      <td style="font-weight:600;font-size:.88rem;"><?= e($t['name_th']) ?></td>
                      <td style="font-size:.88rem;"><?= e($t['name_en']) ?></td>
                      <td style="text-align:center;font-weight:700;color:var(--blue-dark);"><?= (int)$t['paper_count'] ?></td>
                      <td>
                        <div class="d-flex gap-1">
                          <a href="?edit=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.72rem;">
                            <i class="fas fa-edit"></i>
                          </a>
                          <?php if ((int)$t['paper_count'] === 0): ?>
                            <form method="POST" class="d-inline">
                              <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;"
                                      data-confirm="<?= $_lang==='th'?'ยืนยันการลบ?':'Confirm delete?' ?>">
                                <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          <?php else: ?>
                            <span title="<?= $_lang==='th'?'ลบไม่ได้ เพราะมีบทความ':'Cannot delete, has papers' ?>">
                              <button class="btn btn-sm btn-outline-secondary rounded-pill" disabled style="font-size:.72rem;opacity:.4;">
                                <i class="fas fa-lock"></i>
                              </button>
                            </span>
                          <?php endif; ?>
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
