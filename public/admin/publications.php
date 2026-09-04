<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$errors = [];

// Admin creates a brand-new, standalone publication — no paper, no author involved.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'create') {
    Auth::verifyCsrf(post('csrf_token'));

    $titleTh = trim(post('title_th'));
    $titleEn = trim(post('title_en'));
    $authors = trim(post('authors_text'));
    $keywords = trim(post('keywords'));
    $themeId  = intPost('theme_id') ?: null;
    $doi      = trim(post('doi'));

    if (!$titleTh)  $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อเรื่องภาษาไทย' : 'Thai title is required.';
    if (!$titleEn)  $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อเรื่องภาษาอังกฤษ' : 'English title is required.';
    if (!$authors)  $errors[] = $_lang==='th' ? 'กรุณากรอกรายชื่อผู้แต่ง' : 'Authors are required.';

    if (empty($_FILES['publish_file']['name'])) {
        $errors[] = $_lang==='th' ? 'กรุณาอัปโหลดไฟล์ (PDF/DOCX)' : 'Please upload a file (PDF/DOCX).';
    } else {
        $fileError = validateUpload($_FILES['publish_file']);
        if ($fileError) $errors = array_merge($errors, $fileError);
    }

    if (empty($errors)) {
        try {
            $db = Database::getInstance();

            $storedName = moveUpload($_FILES['publish_file']);
            if (!$storedName) throw new \RuntimeException('File upload failed.');

            $ext      = strtolower(pathinfo($_FILES['publish_file']['name'], PATHINFO_EXTENSION));
            $fileType = $ext === 'docx' ? 'docx' : 'pdf';

            $db->prepare("
                INSERT INTO publications
                    (paper_id, title_th, title_en, keywords, authors_text, theme_id, doi,
                     file_type, original_name, stored_name, file_path, file_size, published_by, published_at)
                VALUES
                    (NULL, :tth, :ten, :kw, :auth, :theme, :doi,
                     :ft, :on, :sn, :fp, :fs, :by, NOW())
            ")->execute([
                ':tth'   => $titleTh,
                ':ten'   => $titleEn,
                ':kw'    => $keywords ?: null,
                ':auth'  => $authors,
                ':theme' => $themeId,
                ':doi'   => $doi ?: null,
                ':ft'    => $fileType,
                ':on'    => $_FILES['publish_file']['name'],
                ':sn'    => $storedName,
                ':fp'    => 'uploads/papers/' . $storedName,
                ':fs'    => $_FILES['publish_file']['size'],
                ':by'    => Auth::id(),
            ]);

            auditLog('create_publication', 'publications', "Created standalone publication: {$titleEn}", Auth::id());
            flashSet('success', $_lang==='th' ? 'เพิ่มบทคัดย่อเรียบร้อย' : 'Publication added successfully.');
            redirect($appUrl . '/admin/publications.php');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

// Admin edits an existing publication's details (no file change here).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'edit') {
    Auth::verifyCsrf(post('csrf_token'));

    $pubId    = intPost('pub_id');
    $titleTh  = trim(post('title_th'));
    $titleEn  = trim(post('title_en'));
    $authors  = trim(post('authors_text'));
    $keywords = trim(post('keywords'));
    $themeId  = intPost('theme_id') ?: null;
    $doi      = trim(post('doi'));

    if (!$pubId)   $errors[] = $_lang==='th' ? 'ไม่พบรายการ' : 'Publication not found.';
    if (!$titleTh) $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อเรื่องภาษาไทย' : 'Thai title is required.';
    if (!$titleEn) $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อเรื่องภาษาอังกฤษ' : 'English title is required.';
    if (!$authors) $errors[] = $_lang==='th' ? 'กรุณากรอกรายชื่อผู้แต่ง' : 'Authors are required.';

    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            $db->prepare("
                UPDATE publications
                SET title_th = :tth, title_en = :ten, keywords = :kw,
                    authors_text = :auth, theme_id = :theme, doi = :doi
                WHERE id = :id
            ")->execute([
                ':tth'   => $titleTh,
                ':ten'   => $titleEn,
                ':kw'    => $keywords ?: null,
                ':auth'  => $authors,
                ':theme' => $themeId,
                ':doi'   => $doi ?: null,
                ':id'    => $pubId,
            ]);

            auditLog('edit_publication', 'publications', "Edited publication #{$pubId}", Auth::id());
            flashSet('success', $_lang==='th' ? 'แก้ไขบทคัดย่อเรียบร้อย' : 'Publication updated successfully.');
            redirect($appUrl . '/admin/publications.php');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

// Admin deletes a publication entirely (removes its file from disk too).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    Auth::verifyCsrf(post('csrf_token'));

    $pubId = intPost('pub_id');
    if ($pubId) {
        try {
            $db = Database::getInstance();

            $stmt = $db->prepare("SELECT stored_name FROM publications WHERE id = :id");
            $stmt->execute([':id' => $pubId]);
            $pub = $stmt->fetch();

            if ($pub) {
                $db->prepare("DELETE FROM publications WHERE id = :id")->execute([':id' => $pubId]);

                if (!empty($pub['stored_name'])) {
                    $filePath = UPLOADS_PATH . '/' . $pub['stored_name'];
                    if (file_exists($filePath)) @unlink($filePath);
                }

                auditLog('delete_publication', 'publications', "Deleted publication #{$pubId}", Auth::id());
                flashSet('success', $_lang==='th' ? 'ลบบทคัดย่อเรียบร้อย' : 'Publication deleted successfully.');
            } else {
                flashSet('error', $_lang==='th' ? 'ไม่พบรายการ' : 'Publication not found.');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            flashSet('error', $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.');
        }
    }
    redirect($appUrl . '/admin/publications.php');
}

// Admin uploads/replaces the public-facing file for an existing publication
// (standalone or paper-linked). Never touches papers.status_code, never notifies anyone.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'upload_file') {
    Auth::verifyCsrf(post('csrf_token'));

    $pubId = intPost('pub_id');
    $doi   = trim(post('doi'));

    if (!$pubId) $errors[] = $_lang==='th' ? 'ไม่พบรายการ' : 'Publication not found.';

    if (empty($_FILES['publish_file']['name'])) {
        $errors[] = $_lang==='th' ? 'กรุณาอัปโหลดไฟล์ (PDF/DOCX)' : 'Please upload a file (PDF/DOCX).';
    } else {
        $fileError = validateUpload($_FILES['publish_file']);
        if ($fileError) $errors = array_merge($errors, $fileError);
    }

    if (empty($errors)) {
        try {
            $db = Database::getInstance();

            $pStmt = $db->prepare("SELECT * FROM publications WHERE id = :id");
            $pStmt->execute([':id' => $pubId]);
            $pub = $pStmt->fetch();

            if (!$pub) {
                $errors[] = $_lang==='th' ? 'ไม่พบรายการ' : 'Publication not found.';
            } else {
                $storedName = moveUpload($_FILES['publish_file']);
                if (!$storedName) throw new \RuntimeException('File upload failed.');

                $ext      = strtolower(pathinfo($_FILES['publish_file']['name'], PATHINFO_EXTENSION));
                $fileType = $ext === 'docx' ? 'docx' : 'pdf';

                $db->prepare("
                    UPDATE publications
                    SET doi = :doi, file_type = :ft, original_name = :on,
                        stored_name = :sn, file_path = :fp, file_size = :fs
                    WHERE id = :id
                ")->execute([
                    ':doi' => $doi ?: null,
                    ':ft'  => $fileType,
                    ':on'  => $_FILES['publish_file']['name'],
                    ':sn'  => $storedName,
                    ':fp'  => 'uploads/papers/' . $storedName,
                    ':fs'  => $_FILES['publish_file']['size'],
                    ':id'  => $pubId,
                ]);

                auditLog('upload_publication_file', 'publications', "Uploaded file for publication #{$pubId}", Auth::id());
                flashSet('success', $_lang==='th' ? 'อัปโหลดไฟล์เรียบร้อย' : 'File uploaded successfully.');
                redirect($appUrl . '/admin/publications.php');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

$page    = max(1, intGet('page', 1));
$perPage = 15;

try {
    $db = Database::getInstance();

    $total = (int)$db->query("SELECT COUNT(*) FROM publications")->fetchColumn();
    $pg    = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT pub.*
        FROM publications pub
        ORDER BY pub.published_at DESC
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $pubs = $stmt->fetchAll();

    $themes = $db->query("SELECT * FROM conference_themes WHERE is_active = 1 ORDER BY code")->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $pubs = []; $total = 0; $pg = paginate(0,$perPage,1); $themes = [];
}

$pageTitle  = $_lang==='th' ? 'จัดการบทคัดย่อที่เผยแพร่' : 'Manage Publications';
$activeMenu = 'publications';
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
        <h1 class="dash-title"><i class="fas fa-globe me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p style="font-size:.85rem;color:var(--gray-500);">
          <?= $_lang==='th'
            ? 'รายการที่แสดงในหน้า Publication สาธารณะทั้งหมด '
            : 'Everything shown on the public Publication page ' ?>
        </p>
      </div>
      <button type="button" class="btn-primary-custom" onclick="new bootstrap.Modal(document.getElementById('createModal')).show()">
        <i class="fas fa-plus me-2"></i><?= $_lang==='th' ? 'เพิ่มบทคัดย่อใหม่' : 'Add New Publication' ?>
      </button>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="table-card">
      <?php if (empty($pubs)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-globe fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= $_lang==='th'?'ยังไม่มีบทคัดย่อที่เผยแพร่':'No publications yet' ?></h5>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= $_lang==='th' ? 'บทคัดย่อ' : 'Publication' ?></th>
                <th><?= $_lang==='th' ? 'ผู้แต่ง' : 'Authors' ?></th>
                <th><?= $_lang==='th' ? 'ไฟล์' : 'File' ?></th>
                <th><?= $_lang==='th' ? 'ดาวน์โหลด' : 'Downloads' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pubs as $p): ?>
                <tr>
                  <td style="max-width:220px;">
                    <div style="font-weight:600;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= e($_lang==='th'?$p['title_th']:$p['title_en']) ?>
                    </div>
                  </td>
                  <td style="font-size:.8rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($p['authors_text']) ?></td>
                  <td style="font-size:.8rem;">
                    <?php if (!empty($p['stored_name'])): ?>
                      <span class="badge" style="background:#198754;color:#fff;"><i class="fas fa-check me-1"></i><?= $_lang==='th'?'มีไฟล์แล้ว':'Set' ?></span>
                    <?php else: ?>
                      <span class="badge" style="background:#dc3545;color:#fff;"><?= $_lang==='th'?'ยังไม่มีไฟล์':'Not set' ?></span>
                    <?php endif; ?>
                  </td>
                  <td style="font-weight:700;font-size:.88rem;color:var(--blue-dark);"><?= number_format((int)$p['download_count']) ?></td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <button type="button" class="btn btn-sm btn-success rounded-pill" style="font-size:.72rem;"
                              onclick="openUploadModal(<?= (int)$p['id'] ?>, '<?= e(addslashes($_lang==='th'?$p['title_th']:$p['title_en'])) ?>', '<?= e(addslashes($p['doi'] ?? '')) ?>')">
                        <i class="fas fa-file-upload me-1"></i><?= !empty($p['stored_name']) ? ($_lang==='th'?'แทนที่':'Replace') : ($_lang==='th'?'อัปโหลด':'Upload') ?>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.72rem;"
                              data-pub='<?= e(json_encode($p, JSON_UNESCAPED_UNICODE)) ?>' onclick="openEditModal(this)">
                        <i class="fas fa-pen"></i>
                      </button>
                      <a href="<?= $appUrl ?>/publication-detail.php?id=<?= (int)$p['id'] ?>"
                         class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;" target="_blank">
                        <i class="fas fa-eye"></i>
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;"
                              onclick="openDeleteModal(<?= (int)$p['id'] ?>, '<?= e(addslashes($_lang==='th'?$p['title_th']:$p['title_en'])) ?>')">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="p-3 d-flex justify-content-between align-items-center" style="border-top:1px solid var(--gray-200);">
            <span style="font-size:.85rem;color:var(--gray-500);"><?= t('common.page') ?> <?= $pg['page'] ?> <?= t('common.of') ?> <?= $pg['total_pages'] ?></span>
            <div class="d-flex gap-2">
              <?php if ($pg['has_prev']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']-1])) ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
              <?php if ($pg['has_next']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']+1])) ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Upload/Replace File Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header" style="background:var(--blue-dark);color:#fff;">
            <h5 class="modal-title"><i class="fas fa-file-upload me-2"></i><?= $_lang==='th' ? 'อัปโหลดไฟล์' : 'Upload File' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="upload_file">
            <input type="hidden" name="pub_id" id="modalPubId">
            <div class="modal-body">
              <p><strong id="modalPubTitle"></strong></p>
              <p style="font-size:.88rem;color:var(--gray-600);">
                <?= $_lang==='th'
                  ? 'ไฟล์นี้คือไฟล์ที่สาธารณะจะดาวน์โหลดได้'
                  : 'This is the file the public will download.' ?>
              </p>
              <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ไฟล์ (PDF/DOCX)' : 'File (PDF/DOCX)' ?>
                </label>
                <input type="file" name="publish_file" class="form-control" accept=".pdf,.docx" required>
              </div>
              <div class="mb-1">
                <label class="form-label fw-bold" style="font-size:.85rem;">DOI (<?= $_lang==='th'?'ถ้ามี':'optional' ?>)</label>
                <input type="text" name="doi" id="modalDoi" class="form-control" placeholder="10.xxxxx/xxxxx">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></button>
              <button type="submit" class="btn-primary-custom"><i class="fas fa-file-upload me-2"></i><?= $_lang==='th'?'บันทึก':'Save' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Create Standalone Publication Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background:var(--blue-dark);color:#fff;">
            <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?= $_lang==='th' ? 'เพิ่มบทคัดย่อใหม่' : 'Add New Publication' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อเรื่อง (ไทย)':'Title (Thai)' ?></label>
                  <input type="text" name="title_th" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อเรื่อง (อังกฤษ)':'Title (English)' ?></label>
                  <input type="text" name="title_en" class="form-control" required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ผู้แต่ง (คั่นด้วย ;)':'Authors (separate with ;)' ?></label>
                  <input type="text" name="authors_text" class="form-control" placeholder="Author A; Author B" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'คีย์เวิร์ด (ถ้ามี)':'Keywords (optional)' ?></label>
                  <input type="text" name="keywords" class="form-control" placeholder="keyword1, keyword2">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'หัวข้อ (ถ้ามี)':'Theme (optional)' ?></label>
                  <select name="theme_id" class="form-select">
                    <option value=""><?= $_lang==='th'?'— ไม่ระบุ —':'— None —' ?></option>
                    <?php foreach ($themes as $th): ?>
                      <option value="<?= (int)$th['id'] ?>"><?= e($_lang==='th'?$th['name_th']:$th['name_en']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-8">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ไฟล์ (PDF/DOCX)' : 'File (PDF/DOCX)' ?></label>
                  <input type="file" name="publish_file" class="form-control" accept=".pdf,.docx" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold" style="font-size:.85rem;">DOI (<?= $_lang==='th'?'ถ้ามี':'optional' ?>)</label>
                  <input type="text" name="doi" class="form-control" placeholder="10.xxxxx/xxxxx">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></button>
              <button type="submit" class="btn-primary-custom"><i class="fas fa-plus me-2"></i><?= $_lang==='th'?'เพิ่มบทคัดย่อ':'Add Publication' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Publication Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background:var(--blue-dark);color:#fff;">
            <h5 class="modal-title"><i class="fas fa-pen me-2"></i><?= $_lang==='th' ? 'แก้ไขบทคัดย่อ' : 'Edit Publication' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="pub_id" id="editPubId">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อเรื่อง (ไทย)':'Title (Thai)' ?></label>
                  <input type="text" name="title_th" id="editTitleTh" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ชื่อเรื่อง (อังกฤษ)':'Title (English)' ?></label>
                  <input type="text" name="title_en" id="editTitleEn" class="form-control" required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'ผู้แต่ง (คั่นด้วย ;)':'Authors (separate with ;)' ?></label>
                  <input type="text" name="authors_text" id="editAuthorsText" class="form-control" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'คีย์เวิร์ด (ถ้ามี)':'Keywords (optional)' ?></label>
                  <input type="text" name="keywords" id="editKeywords" class="form-control">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th'?'หัวข้อ (ถ้ามี)':'Theme (optional)' ?></label>
                  <select name="theme_id" id="editThemeId" class="form-select">
                    <option value=""><?= $_lang==='th'?'— ไม่ระบุ —':'— None —' ?></option>
                    <?php foreach ($themes as $th): ?>
                      <option value="<?= (int)$th['id'] ?>"><?= e($_lang==='th'?$th['name_th']:$th['name_en']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" style="font-size:.85rem;">DOI (<?= $_lang==='th'?'ถ้ามี':'optional' ?>)</label>
                  <input type="text" name="doi" id="editDoi" class="form-control" placeholder="10.xxxxx/xxxxx">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></button>
              <button type="submit" class="btn-primary-custom"><i class="fas fa-save me-2"></i><?= $_lang==='th'?'บันทึก':'Save' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Publication Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header" style="background:#dc3545;color:#fff;">
            <h5 class="modal-title"><i class="fas fa-trash me-2"></i><?= $_lang==='th' ? 'ลบบทคัดย่อ' : 'Delete Publication' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="pub_id" id="deletePubId">
            <div class="modal-body">
              <p><strong id="deletePubTitle"></strong></p>
              <p style="font-size:.88rem;color:var(--gray-600);">
                <?= $_lang==='th'
                  ? 'บทคัดย่อและไฟล์จะถูกลบออกจากระบบถาวร ไม่สามารถกู้คืนได้'
                  : 'The publication and its file will be permanently deleted. This cannot be undone.' ?>
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></button>
              <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-2"></i><?= $_lang==='th'?'ยืนยันลบ':'Confirm Delete' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
function openUploadModal(pubId, title, doi) {
  document.getElementById('modalPubId').value = pubId;
  document.getElementById('modalPubTitle').textContent = title;
  document.getElementById('modalDoi').value = doi || '';
  new bootstrap.Modal(document.getElementById('uploadModal')).show();
}
function openEditModal(btn) {
  const p = JSON.parse(btn.dataset.pub);
  document.getElementById('editPubId').value = p.id;
  document.getElementById('editTitleTh').value = p.title_th || '';
  document.getElementById('editTitleEn').value = p.title_en || '';
  document.getElementById('editAuthorsText').value = p.authors_text || '';
  document.getElementById('editKeywords').value = p.keywords || '';
  document.getElementById('editThemeId').value = p.theme_id || '';
  document.getElementById('editDoi').value = p.doi || '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
function openDeleteModal(pubId, title) {
  document.getElementById('deletePubId').value = pubId;
  document.getElementById('deletePubTitle').textContent = title;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>
