<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notification;

Auth::require('author');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

$paperId = intGet('id');
if (!$paperId) { redirect($appUrl . '/author/my-papers.php'); }

try {
    $db = Database::getInstance();

    $stmt = $db->prepare("
        SELECT p.*, ct.name_th AS theme_th, ct.name_en AS theme_en
        FROM papers p
        JOIN conference_themes ct ON ct.id = p.theme_id
        WHERE p.id = :id AND p.submitter_id = :uid AND p.status_code = 'revision_required'
    ");
    $stmt->execute([':id' => $paperId, ':uid' => $uid]);
    $paper = $stmt->fetch();

    if (!$paper) {
        flashSet('error', $_lang==='th' ? 'ไม่พบบทคัดย่อหรือไม่อยู่ในสถานะที่ต้องแก้ไข' : 'Paper not found or not requiring revision.');
        redirect($appUrl . '/author/my-papers.php');
    }

    // Most recent review with comments
    $reviewStmt = $db->prepare("
        SELECT r.comment_for_author, r.recommendation, r.score_overall
        FROM reviews r
        JOIN review_assignments ra ON ra.id = r.assignment_id
        WHERE ra.paper_id = :pid
        ORDER BY r.reviewed_at DESC NULLS LAST
        LIMIT 1
    ");
    $reviewStmt->execute([':pid' => $paperId]);
    $latestReview = $reviewStmt->fetch();

    // Existing co-authors
    $coStmt = $db->prepare("SELECT id, full_name, email, institution, country, is_corresponding, sort_order FROM paper_co_authors WHERE paper_id = :pid ORDER BY sort_order");
    $coStmt->execute([':pid' => $paperId]);
    $coAuthors = $coStmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/author/my-papers.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));

    $titleTh    = trim(post('title_th'));
    $titleEn    = trim(post('title_en'));
    $abstractTh = trim(post('abstract_th'));
    $abstractEn = trim(post('abstract_en'));
    $keywords   = trim(post('keywords'));
    $note       = trim(post('revision_note'));

    if (!$titleTh)    $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อบทคัดย่อภาษาไทย' : 'Thai title is required.';
    if (!$titleEn)    $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อบทคัดย่อภาษาอังกฤษ' : 'English title is required.';
    if (!$abstractTh) $errors[] = $_lang==='th' ? 'กรุณากรอกบทคัดย่อภาษาไทย' : 'Thai abstract is required.';
    if (!$abstractEn) $errors[] = $_lang==='th' ? 'กรุณากรอกบทคัดย่อภาษาอังกฤษ' : 'English abstract is required.';
    if (!$keywords)   $errors[] = $_lang==='th' ? 'กรุณากรอกคำสำคัญ' : 'Keywords are required.';

    // File upload required
    $hasFile = !empty($_FILES['paper_file']['name']);
    if (!$hasFile) $errors[] = $_lang==='th' ? 'กรุณาอัปโหลดไฟล์บทคัดย่อที่แก้ไขแล้ว' : 'Please upload the revised paper file.';

    if ($hasFile) {
        $uploadErrors = validateUpload($_FILES['paper_file']);
        if (!empty($uploadErrors)) $errors = array_merge($errors, $uploadErrors);
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update paper fields + status back to under_review
            $upd = $db->prepare("
                UPDATE papers SET
                    title_th = :tth, title_en = :ten,
                    abstract_th = :ath, abstract_en = :aen,
                    keywords = :kw, status_code = 'under_review',
                    updated_at = NOW()
                WHERE id = :pid
            ");
            $upd->execute([
                ':tth' => $titleTh, ':ten' => $titleEn,
                ':ath' => $abstractTh, ':aen' => $abstractEn,
                ':kw'  => $keywords, ':pid' => $paperId,
            ]);

            // Co-authors: delete old, re-insert
            $db->prepare("DELETE FROM paper_co_authors WHERE paper_id = :pid")->execute([':pid' => $paperId]);
            $coNames = $_POST['co_name'] ?? [];
            $coEmails = $_POST['co_email'] ?? [];
            $coAffs   = $_POST['co_affiliation'] ?? [];
            $coCtries = $_POST['co_country'] ?? [];
            foreach ($coNames as $i => $cName) {
                $cName = trim($cName);
                if ($cName) {
                    $db->prepare("
                        INSERT INTO paper_co_authors (paper_id, full_name, email, institution, country, sort_order)
                        VALUES (:pid, :name, :email, :aff, :ctry, :sort)
                    ")->execute([
                        ':pid'   => $paperId,
                        ':name'  => $cName,
                        ':email' => trim($coEmails[$i] ?? ''),
                        ':aff'   => trim($coAffs[$i] ?? ''),
                        ':ctry'  => trim($coCtries[$i] ?? ''),
                        ':sort'  => $i,
                    ]);
                }
            }

            // Upload revised file
            $storedName = moveUpload($_FILES['paper_file']);
            if (!$storedName) throw new \RuntimeException('File upload failed');

            $ext      = strtolower(pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION));
            $fileType = $ext === 'pdf' ? 'pdf' : 'docx';
            $db->prepare("
                INSERT INTO paper_files (paper_id, file_type, file_category, original_name, stored_name, file_path, file_size, version_number, uploaded_by)
                VALUES (:pid, :ft, 'revision', :on, :sn, :fp, :fs, 1, :uid)
            ")->execute([
                ':pid' => $paperId,
                ':ft'  => $fileType,
                ':on'  => $_FILES['paper_file']['name'],
                ':sn'  => $storedName,
                ':fp'  => 'uploads/papers/' . $storedName,
                ':fs'  => $_FILES['paper_file']['size'],
                ':uid' => $uid,
            ]);

            // Log revision note if provided
            if ($note) {
                auditLog('revision_submitted', 'papers', "Paper ID: $paperId | Note: $note", $uid);
            }

            // Notify admin
            Notification::create(
                null, 'revision_submitted',
                'บทคัดย่อส่งแก้ไขแล้ว',
                'Paper Revision Submitted',
                "บทคัดย่อ {$paper['paper_code']} ส่งการแก้ไขแล้ว",
                "Paper {$paper['paper_code']} has been revised and resubmitted.",
                $paperId, 'system'
            );

            $db->commit();

            flashSet('success', $_lang==='th'
                ? 'ส่งบทคัดย่อที่แก้ไขแล้วเรียบร้อย อยู่ระหว่างการพิจารณาใหม่'
                : 'Revised paper submitted successfully. It is now under review again.');
            redirect($appUrl . '/author/paper-detail.php?id=' . $paperId);

        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด กรุณาลองใหม่' : 'An error occurred. Please try again.';
        }
    }
}

$pageTitle  = $_lang==='th' ? 'ส่งบทคัดย่อแก้ไข' : 'Submit Revision';
$activeMenu = 'my-papers';
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
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title">
          <i class="fas fa-edit me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
        </h1>
        <p class="dash-breadcrumb">
          <a href="<?= $appUrl ?>/author/paper-detail.php?id=<?= $paperId ?>" style="color:var(--blue-mid);">
            <code><?= e($paper['paper_code']) ?></code>
          </a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <?= e($pageTitle) ?>
        </p>
      </div>
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

    <!-- Reviewer Comments -->
    <?php if ($latestReview && $latestReview['comment_for_author']): ?>
    <div class="content-card mb-4" style="border-left:4px solid var(--warning);">
      <div class="content-card-title">
        <i class="fas fa-comment-alt me-2" style="color:var(--warning);"></i>
        <?= $_lang==='th' ? 'ความเห็นจากผู้ทรงคุณวุฒิ' : 'Reviewer Comments' ?>
      </div>
      <div style="font-size:.9rem;line-height:1.8;padding:12px;background:var(--gray-100);border-radius:var(--radius);">
        <?= nl2br(e($latestReview['comment_for_author'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div class="row g-4">
        <!-- Left: Form -->
        <div class="col-lg-8">

          <!-- Titles -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-heading me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ชื่อบทคัดย่อ' : 'Paper Title' ?>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ชื่อบทคัดย่อ (ภาษาไทย)' : 'Title (Thai)' ?> <span class="text-danger">*</span>
                </label>
                <input type="text" name="title_th" class="form-control"
                       value="<?= e(post('title_th', $paper['title_th'])) ?>" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ชื่อบทคัดย่อ (ภาษาอังกฤษ)' : 'Title (English)' ?> <span class="text-danger">*</span>
                </label>
                <input type="text" name="title_en" class="form-control"
                       value="<?= e(post('title_en', $paper['title_en'])) ?>" required>
              </div>
            </div>
          </div>

          <!-- Abstracts -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-align-left me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทคัดย่อ' : 'Abstract' ?>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'บทคัดย่อ (ภาษาไทย)' : 'Abstract (Thai)' ?> <span class="text-danger">*</span>
                </label>
                <textarea name="abstract_th" class="form-control" rows="6" required
                          data-word-counter="counter-th"><?= e(post('abstract_th', $paper['abstract_th'])) ?></textarea>
                <div class="d-flex justify-content-end mt-1">
                  <span id="counter-th" style="font-size:.75rem;color:var(--gray-500);">0 words</span>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'บทคัดย่อ (ภาษาอังกฤษ)' : 'Abstract (English)' ?> <span class="text-danger">*</span>
                </label>
                <textarea name="abstract_en" class="form-control" rows="6" required
                          data-word-counter="counter-en"><?= e(post('abstract_en', $paper['abstract_en'])) ?></textarea>
                <div class="d-flex justify-content-end mt-1">
                  <span id="counter-en" style="font-size:.75rem;color:var(--gray-500);">0 words</span>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= t('paper.keywords') ?> <span class="text-danger">*</span>
                </label>
                <input type="text" name="keywords" class="form-control"
                       value="<?= e(post('keywords', $paper['keywords'])) ?>"
                       placeholder="<?= $_lang==='th' ? 'คำสำคัญ 1, คำสำคัญ 2, ...' : 'keyword1, keyword2, ...' ?>" required>
                <div class="form-text"><?= $_lang==='th' ? 'คั่นด้วยเครื่องหมายจุลภาค (,)' : 'Separate with commas' ?></div>
              </div>
            </div>
          </div>

          <!-- Co-Authors -->
          <div class="content-card mb-4">
            <div class="content-card-title d-flex justify-content-between align-items-center">
              <span><i class="fas fa-users me-2" style="color:var(--gold);"></i><?= t('paper.co_authors') ?></span>
              <button type="button" id="addCoAuthor" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="fas fa-plus me-1"></i><?= $_lang==='th' ? 'เพิ่ม' : 'Add' ?>
              </button>
            </div>
            <div id="coAuthorList">
              <?php foreach ($coAuthors as $i => $ca): ?>
              <div class="co-author-row p-3 mb-2 rounded" style="background:var(--gray-100);border:1px solid var(--gray-200);">
                <div class="row g-2 align-items-end">
                  <div class="col-md-4">
                    <label class="form-label fw-bold" style="font-size:.78rem;"><?= $_lang==='th' ? 'ชื่อ-นามสกุล' : 'Full Name' ?> <span class="text-danger">*</span></label>
                    <input type="text" name="co_name[]" class="form-control form-control-sm" value="<?= e($ca['full_name']) ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold" style="font-size:.78rem;"><?= $_lang==='th' ? 'อีเมล' : 'Email' ?></label>
                    <input type="email" name="co_email[]" class="form-control form-control-sm" value="<?= e($ca['email']) ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold" style="font-size:.78rem;"><?= $_lang==='th' ? 'สังกัด' : 'Affiliation' ?></label>
                    <input type="text" name="co_affiliation[]" class="form-control form-control-sm" value="<?= e($ca['institution']) ?>">
                  </div>
                  <div class="col-md-1">
                    <label class="form-label fw-bold" style="font-size:.78rem;"><?= $_lang==='th' ? 'ประเทศ' : 'Country' ?></label>
                    <input type="text" name="co_country[]" class="form-control form-control-sm" value="<?= e($ca['country']) ?>">
                  </div>
                  <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle remove-co" style="width:32px;height:32px;padding:0;">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Revised File Upload -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-file-upload me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ไฟล์บทคัดย่อที่แก้ไขแล้ว' : 'Revised Paper File' ?>
            </div>
            <div class="upload-zone" id="uploadZone">
              <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:var(--blue-mid);"></i>
              <div style="font-weight:700;color:var(--blue-dark);"><?= $_lang==='th' ? 'ลากวางไฟล์ที่นี่ หรือ' : 'Drag & drop or' ?></div>
              <label for="paperFile" class="btn-primary-custom mt-2 d-inline-block cursor-pointer" style="cursor:pointer;">
                <i class="fas fa-folder-open me-2"></i><?= $_lang==='th' ? 'เลือกไฟล์' : 'Choose File' ?>
              </label>
              <input type="file" id="paperFile" name="paper_file" class="d-none" accept=".pdf,.doc,.docx" required>
              <div id="fileNameDisplay" class="mt-2" style="font-size:.85rem;color:var(--gray-600);"></div>
              <div class="mt-2" style="font-size:.78rem;color:var(--gray-500);">
                <?= $_lang==='th' ? 'รองรับ PDF, DOC, DOCX (สูงสุด 20MB)' : 'Accepts PDF, DOC, DOCX (max 20MB)' ?>
              </div>
            </div>

            <!-- Revision Note -->
            <div class="mt-3">
              <label class="form-label fw-bold" style="font-size:.85rem;">
                <?= $_lang==='th' ? 'บันทึกการแก้ไข (ถึงบรรณาธิการ)' : 'Revision Note (to editor)' ?>
              </label>
              <textarea name="revision_note" class="form-control" rows="4"
                        placeholder="<?= $_lang==='th' ? 'อธิบายสิ่งที่แก้ไขตามข้อเสนอแนะของผู้ทรงคุณวุฒิ...' : 'Describe what was changed in response to reviewer comments...' ?>"><?= e(post('revision_note')) ?></textarea>
            </div>
          </div>

          <div class="d-flex gap-3 justify-content-end">
            <a href="<?= $appUrl ?>/author/paper-detail.php?id=<?= $paperId ?>" class="btn-outline-custom">
              <i class="fas fa-times me-2"></i><?= $_lang==='th' ? 'ยกเลิก' : 'Cancel' ?>
            </a>
            <button type="submit" class="btn-primary-custom">
              <i class="fas fa-paper-plane me-2"></i><?= $_lang==='th' ? 'ส่งบทคัดย่อแก้ไข' : 'Submit Revision' ?>
            </button>
          </div>

        </div>

        <!-- Right: Info -->
        <div class="col-lg-4">
          <div class="content-card mb-4" style="border-left:4px solid var(--gold);">
            <div class="content-card-title">
              <i class="fas fa-info-circle me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลบทคัดย่อเดิม' : 'Current Paper Info' ?>
            </div>
            <div style="font-size:.85rem;">
              <div class="mb-2"><code style="color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code></div>
              <div class="fw-bold mb-1" style="color:var(--blue-dark);"><?= e($_lang==='th' ? $paper['title_th'] : $paper['title_en']) ?></div>
              <div style="color:var(--gray-500);font-size:.8rem;"><?= e($_lang==='th' ? $paper['theme_th'] : $paper['theme_en']) ?></div>
            </div>
          </div>

          <div class="content-card">
            <div class="content-card-title">
              <i class="fas fa-lightbulb me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'คำแนะนำ' : 'Tips' ?>
            </div>
            <ul style="font-size:.84rem;color:var(--gray-700);padding-left:1.2rem;line-height:1.8;">
              <?php if ($_lang==='th'): ?>
                <li>อ่านความเห็นของผู้ทรงคุณวุฒิโดยละเอียด</li>
                <li>แก้ไขทุกประเด็นที่ผู้ทรงคุณวุฒิระบุ</li>
                <li>บันทึกการแก้ไขเพื่อให้บรรณาธิการทราบ</li>
                <li>ตรวจสอบรูปแบบตาม Template ของการประชุม</li>
                <li>อัปโหลดไฟล์ PDF หรือ DOCX ที่แก้ไขแล้ว</li>
              <?php else: ?>
                <li>Read all reviewer comments carefully</li>
                <li>Address every point raised by reviewers</li>
                <li>Add a revision note explaining changes</li>
                <li>Ensure formatting matches the conference template</li>
                <li>Upload the revised PDF or DOCX file</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

      </div>
    </form>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
// File name display
document.getElementById('paperFile').addEventListener('change', function() {
  const disp = document.getElementById('fileNameDisplay');
  if (this.files[0]) {
    disp.innerHTML = '<i class="fas fa-check-circle me-1" style="color:#198754;"></i>' + this.files[0].name;
  }
});

// Co-author row counter for generating names
let coAuthorCount = document.querySelectorAll('.co-author-row').length;

document.getElementById('addCoAuthor').addEventListener('click', function() {
  const html = coAuthorRowHtml(coAuthorCount++);
  document.getElementById('coAuthorList').insertAdjacentHTML('beforeend', html);
  bindRemoveButtons();
});

function bindRemoveButtons() {
  document.querySelectorAll('.remove-co').forEach(btn => {
    btn.onclick = function() { this.closest('.co-author-row').remove(); };
  });
}
bindRemoveButtons();

// Word counters
document.querySelectorAll('[data-word-counter]').forEach(ta => {
  const counterId = ta.dataset.wordCounter;
  const counter   = document.getElementById(counterId);
  function update() {
    const words = ta.value.trim().split(/\s+/).filter(w => w).length;
    counter.textContent = words + ' words';
  }
  ta.addEventListener('input', update);
  update();
});
</script>
</body>
</html>
