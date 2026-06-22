<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mail;
use App\Core\Notification;

Auth::require('author');

$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$csrf   = Auth::csrfToken();
$errors = [];

// Load themes
try {
    $db     = Database::getInstance();
    $themes = $db->query("SELECT * FROM conference_themes WHERE is_active = TRUE ORDER BY code")->fetchAll();
} catch (\Throwable $e) { $themes = []; }

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf(post('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $titleTh    = sanitize(post('title_th'));
        $titleEn    = sanitize(post('title_en'));
        $abstractTh = sanitize(post('abstract_th'));
        $abstractEn = sanitize(post('abstract_en'));
        $keywords   = sanitize(post('keywords'));
        $themeId    = intPost('theme_id');

        // Co-author data
        $coNames        = $_POST['co_name']        ?? [];
        $coEmails       = $_POST['co_email']       ?? [];
        $coInstitutions = $_POST['co_institution'] ?? [];
        $coCountries    = $_POST['co_country']     ?? [];
        $coCorrespond   = $_POST['co_corresponding']?? [];

        // Validate
        if (!$titleTh)    $errors[] = t('paper.title_th') . ': ' . t('common.required');
        if (!$titleEn)    $errors[] = t('paper.title_en') . ': ' . t('common.required');
        if (!$abstractTh) $errors[] = t('paper.abstract_th') . ': ' . t('common.required');
        if (!$abstractEn) $errors[] = t('paper.abstract_en') . ': ' . t('common.required');
        if (!$keywords)   $errors[] = t('paper.keywords') . ': ' . t('common.required');
        if (!$themeId)    $errors[] = t('paper.theme') . ': ' . t('common.required');

        // File validation
        $fileError = [];
        if (empty($_FILES['paper_file']['name'])) {
            $errors[] = t('paper.upload_file') . ': ' . t('common.required');
        } else {
            $fileError = validateUpload($_FILES['paper_file']);
            if ($fileError) $errors = array_merge($errors, $fileError);
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $paperCode = generatePaperCode();

                // Insert paper
                $ins = $db->prepare("
                    INSERT INTO papers
                        (paper_code, title_th, title_en, abstract_th, abstract_en, keywords, theme_id, submitter_id, status_code)
                    VALUES
                        (:code, :tth, :ten, :ath, :aen, :kw, :tid, :uid, 'submitted')
                    RETURNING id
                ");
                $ins->execute([
                    ':code' => $paperCode,
                    ':tth'  => $titleTh,
                    ':ten'  => $titleEn,
                    ':ath'  => $abstractTh,
                    ':aen'  => $abstractEn,
                    ':kw'   => $keywords,
                    ':tid'  => $themeId,
                    ':uid'  => $user['id'],
                ]);
                $paperId = (int)$ins->fetchColumn();

                // Upload file
                $storedName = moveUpload($_FILES['paper_file']);
                if (!$storedName) throw new \RuntimeException('File upload failed.');

                $ext      = strtolower(pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION));
                $fileType = $ext === 'pdf' ? 'pdf' : 'docx';

                $db->prepare("
                    INSERT INTO paper_files
                        (paper_id, file_type, file_category, original_name, stored_name, file_path, file_size, version_number, uploaded_by)
                    VALUES
                        (:pid, :ft, 'submission', :on, :sn, :fp, :fs, 1, :uid)
                ")->execute([
                    ':pid' => $paperId,
                    ':ft'  => $fileType,
                    ':on'  => $_FILES['paper_file']['name'],
                    ':sn'  => $storedName,
                    ':fp'  => 'uploads/papers/' . $storedName,
                    ':fs'  => $_FILES['paper_file']['size'],
                    ':uid' => $user['id'],
                ]);

                // Insert co-authors
                foreach ($coNames as $i => $coName) {
                    if (!trim($coName)) continue;
                    $db->prepare("
                        INSERT INTO paper_co_authors (paper_id, full_name, email, institution, country, is_corresponding, sort_order)
                        VALUES (:pid, :fn, :em, :ins, :co, :isc, :ord)
                    ")->execute([
                        ':pid' => $paperId,
                        ':fn'  => sanitize($coName),
                        ':em'  => sanitize($coEmails[$i] ?? ''),
                        ':ins' => sanitize($coInstitutions[$i] ?? ''),
                        ':co'  => sanitize($coCountries[$i] ?? ''),
                        ':isc' => in_array((string)$i, $coCorrespond) ? 'TRUE' : 'FALSE',
                        ':ord' => $i,
                    ]);
                }

                $db->commit();

                // Notifications
                // Get first admin
                $adminRow = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
                $adminId  = $adminRow ? (int)$adminRow['id'] : null;
                if ($adminId) {
                    Notification::paperSubmitted($user['id'], $adminId, $paperCode, $titleEn);
                }

                // Email to author
                $authorRow = $db->prepare("SELECT email, first_name, last_name FROM users WHERE id = :uid");
                $authorRow->execute([':uid' => $user['id']]);
                $authorData = $authorRow->fetch();
                if ($authorData) {
                    Mail::sendPaperSubmitted($authorData['email'], $authorData['first_name'].' '.$authorData['last_name'], $paperCode, $titleEn);
                }

                auditLog('submit_paper', 'paper', 'Submitted: ' . $paperCode, $user['id']);

                flashSet('success', $_lang==='th'
                    ? "ส่งบทความสำเร็จ! รหัสบทความ: {$paperCode}"
                    : "Paper submitted successfully! Code: {$paperCode}");
                redirect('/author/my-papers.php');

            } catch (\Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                $errors[] = 'System error: ' . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    }
}

$pageTitle  = t('author.submit_paper');
$activeMenu = 'submit';
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
      <h1 class="dash-title"><i class="fas fa-file-upload me-2 text-gold" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
      <p class="dash-breadcrumb">
        <a href="<?= $appUrl ?>/author/dashboard.php" style="color:var(--blue-mid);"><?= t('author.dashboard') ?></a>
        / <?= e($pageTitle) ?>
      </p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong><?= $_lang==='th'?'พบข้อผิดพลาด':'Errors found' ?>:</strong>
        <ul class="mt-2 mb-0 ps-4">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <!-- Paper Information -->
      <div class="content-card mb-4">
        <div class="content-card-title">
          <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i>
          <?= $_lang==='th'?'ข้อมูลบทความ':'Paper Information' ?>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?= t('paper.title_th') ?> <span class="required">*</span></label>
            <input type="text" name="title_th" class="form-control" value="<?= e(post('title_th')) ?>" required
                   placeholder="ชื่อบทความภาษาไทย">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= t('paper.title_en') ?> <span class="required">*</span></label>
            <input type="text" name="title_en" class="form-control" value="<?= e(post('title_en')) ?>" required
                   placeholder="Paper title in English">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= t('paper.abstract_th') ?> <span class="required">*</span></label>
            <textarea name="abstract_th" class="form-control" rows="5" required
                      placeholder="บทคัดย่อภาษาไทย (250–350 คำ)"><?= e(post('abstract_th')) ?></textarea>
            <div style="font-size:.75rem;color:var(--gray-500);margin-top:4px;">
              <span id="wordCountTh">0</span> <?= $_lang==='th'?'คำ (แนะนำ 250–350 คำ)':'words (recommended: 250–350)' ?>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= t('paper.abstract_en') ?> <span class="required">*</span></label>
            <textarea name="abstract_en" class="form-control" rows="5" required
                      placeholder="Abstract in English (250–350 words)"><?= e(post('abstract_en')) ?></textarea>
            <div style="font-size:.75rem;color:var(--gray-500);margin-top:4px;">
              <span id="wordCountEn">0</span> <?= $_lang==='th'?'คำ (แนะนำ 250–350 คำ)':'words (recommended: 250–350)' ?>
            </div>
          </div>
          <div class="col-md-8">
            <label class="form-label"><?= t('paper.keywords') ?> <span class="required">*</span></label>
            <input type="text" name="keywords" class="form-control" value="<?= e(post('keywords')) ?>" required
                   placeholder="<?= $_lang==='th'?'คำสำคัญ 3-5 คำ คั่นด้วยเครื่องหมาย ,':'3-5 keywords, separated by comma' ?>">
            <div style="font-size:.75rem;color:var(--gray-500);margin-top:4px;">
              <?= $_lang==='th'?'เช่น: ภาษาอาเซียน, ภาษาศาสตร์, โลกาภิวัตน์':'E.g.: ASEAN languages, linguistics, globalization' ?>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label"><?= t('paper.theme') ?> <span class="required">*</span></label>
            <select name="theme_id" class="form-select" required>
              <option value=""><?= $_lang==='th'?'— เลือกหัวข้อ —':'— Select Theme —' ?></option>
              <?php foreach ($themes as $th): ?>
                <option value="<?= (int)$th['id'] ?>" <?= intPost('theme_id')===(int)$th['id']?'selected':'' ?>>
                  <?= e($_lang==='th'?$th['name_th']:$th['name_en']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Co-Authors -->
      <div class="content-card mb-4">
        <div class="content-card-title d-flex align-items-center justify-content-between">
          <span><i class="fas fa-users me-2" style="color:var(--gold);"></i><?= t('paper.co_authors') ?></span>
          <button type="button" id="addCoAuthor" class="btn btn-sm btn-outline-primary rounded-pill">
            <i class="fas fa-plus me-1"></i><?= $_lang==='th'?'เพิ่มผู้แต่งร่วม':'Add Co-Author' ?>
          </button>
        </div>
        <p style="font-size:.85rem;color:var(--gray-500);margin-bottom:16px;">
          <?= $_lang==='th'?'กรุณาระบุผู้แต่งร่วมทุกคน (ไม่รวมตัวคุณเอง)':'List all co-authors (excluding yourself).' ?>
        </p>
        <div id="coAuthorList">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- File Upload -->
      <div class="content-card mb-4">
        <div class="content-card-title"><i class="fas fa-file-upload me-2" style="color:var(--gold);"></i><?= t('paper.upload_file') ?></div>
        <div class="file-drop-wrapper p-5 rounded text-center" style="border:2px dashed var(--gray-200);cursor:pointer;transition:all .3s;"
             onclick="document.getElementById('paper_file').click()">
          <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color:var(--gray-300);"></i>
          <div style="font-weight:600;color:var(--gray-700);margin-bottom:8px;">
            <?= $_lang==='th'?'คลิกหรือลากไฟล์มาวางที่นี่':'Click or drag file here' ?>
          </div>
          <div class="file-label" style="font-size:.85rem;color:var(--gray-500);">
            PDF, DOCX — <?= $_lang==='th'?'ไม่เกิน 20 MB':'Max 20 MB' ?>
          </div>
          <input type="file" id="paper_file" name="paper_file" class="file-drop-zone d-none"
                 accept=".pdf,.docx" required>
        </div>
        <p class="mt-2 mb-0" style="font-size:.8rem;color:var(--gray-500);">
          <i class="fas fa-shield-alt me-1 text-success"></i>
          <?= $_lang==='th'?'ไฟล์ของคุณจะถูกเก็บอย่างปลอดภัยและไม่สามารถเข้าถึงได้จากภายนอก':'Files are stored securely and not publicly accessible.' ?>
        </p>
      </div>

      <!-- Submit -->
      <div class="d-flex gap-3 justify-content-end">
        <a href="<?= $appUrl ?>/author/my-papers.php" class="btn-outline-custom">
          <i class="fas fa-times me-2"></i><?= t('common.cancel') ?>
        </a>
        <button type="submit" class="btn-primary-custom">
          <i class="fas fa-paper-plane me-2"></i><?= t('paper.submit') ?>
        </button>
      </div>

    </form>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
// Word counters
function countWords(str) {
  return str.trim() === '' ? 0 : str.trim().split(/\s+/).length;
}
document.querySelector('[name="abstract_th"]')?.addEventListener('input', function() {
  document.getElementById('wordCountTh').textContent = countWords(this.value);
});
document.querySelector('[name="abstract_en"]')?.addEventListener('input', function() {
  document.getElementById('wordCountEn').textContent = countWords(this.value);
});
// File drop zone styling
const wrapper = document.querySelector('.file-drop-wrapper');
if (wrapper) {
  ['dragenter','dragover'].forEach(e => {
    wrapper.addEventListener(e, ev => {
      ev.preventDefault();
      wrapper.style.borderColor = 'var(--blue-mid)';
      wrapper.style.background  = 'var(--blue-light)';
    });
  });
  ['dragleave','drop'].forEach(e => {
    wrapper.addEventListener(e, ev => {
      ev.preventDefault();
      wrapper.style.borderColor = '';
      wrapper.style.background  = '';
    });
  });
}
</script>
</body>
</html>
