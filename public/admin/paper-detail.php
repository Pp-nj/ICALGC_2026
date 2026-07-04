<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$paperId = intGet('id');
if (!$paperId) { redirect($appUrl . '/admin/papers.php'); }

try {
    $db = Database::getInstance();

    $stmt = $db->prepare("
        SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS submitter_name, u.email AS submitter_email, u.affiliation AS submitter_affiliation,
               ct.name_th AS theme_th, ct.name_en AS theme_en,
               ps.name_th AS status_th, ps.name_en AS status_en, ps.color_hex, ps.progress_step
        FROM papers p
        JOIN users u ON u.id = p.submitter_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        JOIN paper_statuses ps ON ps.code = p.status_code
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $paperId]);
    $paper = $stmt->fetch();
    if (!$paper) { redirect($appUrl . '/admin/papers.php'); }

    $coAuthors = $db->prepare("SELECT * FROM paper_co_authors WHERE paper_id = :pid ORDER BY sort_order")->execute([':pid' => $paperId]) ? [] : [];
    $cStmt     = $db->prepare("SELECT * FROM paper_co_authors WHERE paper_id = :pid ORDER BY sort_order");
    $cStmt->execute([':pid' => $paperId]);
    $coAuthors = $cStmt->fetchAll();

    $fStmt = $db->prepare("SELECT * FROM paper_files WHERE paper_id = :pid ORDER BY uploaded_at DESC");
    $fStmt->execute([':pid' => $paperId]);
    $files = $fStmt->fetchAll();

    $raStmt = $db->prepare("
        SELECT ra.*, CONCAT(rv.first_name, ' ', rv.last_name) AS reviewer_name, rv.email AS reviewer_email,
               r.id AS review_id, r.recommendation, r.score_overall, r.comment_for_author,
               r.comment_for_editor, r.reviewed_at
        FROM review_assignments ra
        JOIN users rv ON rv.id = ra.reviewer_id
        LEFT JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.paper_id = :pid
        ORDER BY ra.assigned_at DESC
    ");
    $raStmt->execute([':pid' => $paperId]);
    $assignments = $raStmt->fetchAll();

    $allStatuses = $db->query("SELECT * FROM paper_statuses ORDER BY progress_step")->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/admin/papers.php');
}

$pageTitle  = $_lang==='th' ? 'รายละเอียดบทความ' : 'Paper Detail';
$activeMenu = 'papers';
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
        <h1 class="dash-title"><i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb">
          <a href="<?= $appUrl ?>/admin/papers.php" style="color:var(--blue-mid);"><?= $_lang==='th' ? 'จัดการบทความ' : 'Manage Papers' ?></a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <code style="color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code>
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= $appUrl ?>/admin/assign-reviewer.php?paper_id=<?= $paperId ?>" class="btn-outline-custom">
          <i class="fas fa-user-plus me-2"></i><?= $_lang==='th' ? 'มอบหมายผู้ทรง' : 'Assign Reviewer' ?>
        </a>
        <a href="<?= $appUrl ?>/admin/final-decision.php?paper_id=<?= $paperId ?>" class="btn-primary-custom">
          <i class="fas fa-gavel me-2"></i><?= $_lang==='th' ? 'ตัดสินผล' : 'Final Decision' ?>
        </a>
      </div>
    </div>

    <?= flashHtml() ?>

    <!-- Progress Track -->
    <div class="content-card mb-4">
      <div class="content-card-title"><i class="fas fa-tasks me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'สถานะ' : 'Status' ?></div>
      <div class="progress-track">
        <?php
          $isRejected     = $paper['status_code'] === 'rejected';
          $filteredStatuses = array_values(array_filter($allStatuses, function($s) use ($isRejected) {
              if ($s['code'] === 'rejected' && !$isRejected) return false;
              if (in_array($s['code'], ['accepted','published']) && $isRejected) return false;
              return true;
          }));
        ?>
        <?php foreach ($filteredStatuses as $i => $s):
          $currentStep = (int)$paper['progress_step'];
          $thisStep    = (int)$s['progress_step'];
          $isDone      = $thisStep < $currentStep;
          $isCurrent   = $s['code'] === $paper['status_code'];
        ?>
          <div class="progress-step <?= $isDone?'done':($isCurrent?'active':'') ?>">
            <div class="progress-circle" style="<?= $isCurrent?"background:{$s['color_hex']};color:#fff;border-color:{$s['color_hex']};":($isDone?'background:#198754;color:#fff;border-color:#198754;':'') ?>">
              <?= $isDone ? '<i class="fas fa-check"></i>' : $thisStep ?>
            </div>
            <div class="progress-label" style="<?= $isCurrent?"color:{$s['color_hex']};font-weight:700;":'' ?>">
              <?= e($_lang==='th'?$s['name_th']:$s['name_en']) ?>
            </div>
          </div>
          <?php if ($i < count($filteredStatuses)-1): ?>
            <div class="progress-connector <?= $isDone?'done':'' ?>"></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="row g-4">
      <!-- Left: Paper Info -->
      <div class="col-lg-8">

        <div class="content-card mb-4">
          <div class="content-card-title"><i class="fas fa-info-circle me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลบทความ' : 'Paper Info' ?></div>
          <div class="row g-3">
            <div class="col-12"><label style="font-size:.78rem;color:var(--gray-500);"><?= $_lang==='th'?'ชื่อภาษาไทย':'Thai Title' ?></label>
              <div style="font-weight:600;"><?= e($paper['title_th']) ?></div></div>
            <div class="col-12"><label style="font-size:.78rem;color:var(--gray-500);"><?= $_lang==='th'?'ชื่อภาษาอังกฤษ':'English Title' ?></label>
              <div style="font-weight:600;"><?= e($paper['title_en']) ?></div></div>
            <div class="col-12"><label style="font-size:.78rem;color:var(--gray-500);"><?= $_lang==='th'?'บทคัดย่อไทย':'Abstract (TH)' ?></label>
              <div style="font-size:.87rem;line-height:1.8;padding:10px;background:var(--gray-100);border-radius:var(--radius);"><?= nl2br(e($paper['abstract_th'])) ?></div></div>
            <div class="col-12"><label style="font-size:.78rem;color:var(--gray-500);"><?= $_lang==='th'?'บทคัดย่อภาษาอังกฤษ':'Abstract (EN)' ?></label>
              <div style="font-size:.87rem;line-height:1.8;padding:10px;background:var(--gray-100);border-radius:var(--radius);"><?= nl2br(e($paper['abstract_en'])) ?></div></div>
            <?php if ($paper['keywords']): ?>
            <div class="col-12"><label style="font-size:.78rem;color:var(--gray-500);"><?= t('paper.keywords') ?></label>
              <div class="d-flex flex-wrap gap-1">
                <?php foreach (explode(',', $paper['keywords']) as $kw): ?>
                  <span class="badge" style="background:var(--blue-dark);color:#fff;font-size:.75rem;"><?= e(trim($kw)) ?></span>
                <?php endforeach; ?>
              </div></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Files -->
        <div class="content-card mb-4">
          <div class="content-card-title"><i class="fas fa-paperclip me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ไฟล์' : 'Files' ?></div>
          <?php foreach ($files as $f): ?>
            <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded" style="background:var(--gray-100);">
              <div class="d-flex align-items-center gap-2">
                <i class="fas fa-file-pdf" style="color:var(--blue-mid);"></i>
                <div>
                  <div style="font-weight:600;font-size:.85rem;"><?= e($f['original_name']) ?></div>
                  <div style="font-size:.75rem;color:var(--gray-500);">
                    <?= formatFileSize($f['file_size']) ?> &bull; <?= humanDate($f['uploaded_at'], $_lang) ?>
                    <?php if ($f['file_category'] === 'revision'): ?><span class="badge ms-1" style="background:#fd7e14;color:#fff;font-size:.68rem;">Revision</span><?php endif; ?>
                  </div>
                </div>
              </div>
              <a href="<?= $appUrl ?>/download.php?file_id=<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.75rem;">
                <i class="fas fa-download"></i>
              </a>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Review Assignments -->
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-star me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'การประเมิน' : 'Reviews' ?></div>
          <?php if (empty($assignments)): ?>
            <div class="p-3 text-center" style="color:var(--gray-500);font-size:.88rem;">
              <?= $_lang==='th' ? 'ยังไม่มีผู้ทรงคุณวุฒิ' : 'No reviewers assigned yet' ?>
            </div>
          <?php else: ?>
            <?php foreach ($assignments as $ra):
              $statusColors = ['pending'=>'#fd7e14','in_progress'=>'#0057b7','completed'=>'#198754','declined'=>'#6c757d'];
              $statusLabels = ['pending'=>['th'=>'รอตอบรับ','en'=>'Pending'],'in_progress'=>['th'=>'กำลังประเมิน','en'=>'In Progress'],'completed'=>['th'=>'เสร็จสิ้น','en'=>'Completed'],'declined'=>['th'=>'ปฏิเสธ','en'=>'Declined']];
            ?>
              <div class="p-4 mb-3 rounded" style="border:1px solid var(--gray-200);background:var(--gray-100);">
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                  <div>
                    <div style="font-weight:700;color:var(--blue-dark);"><?= e($ra['reviewer_name']) ?></div>
                    <div style="font-size:.8rem;color:var(--gray-500);"><?= e($ra['reviewer_email']) ?></div>
                  </div>
                  <span class="badge rounded-pill" style="background:<?= $statusColors[$ra['assignment_status']]??'#6c757d' ?>;color:#fff;font-size:.75rem;">
                    <?= $_lang==='th' ? ($statusLabels[$ra['assignment_status']]['th']??$ra['assignment_status']) : ($statusLabels[$ra['assignment_status']]['en']??$ra['assignment_status']) ?>
                  </span>
                </div>
                <?php if ($ra['review_id']): ?>
                  <div class="d-flex gap-3 align-items-center mt-2 flex-wrap">
                    <?php if ($ra['score_overall']): ?>
                      <div class="text-center" style="background:var(--blue-dark);color:#fff;border-radius:50%;width:46px;height:46px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800;">
                        <?= number_format($ra['score_overall'],1) ?>
                        <span style="font-size:.55rem;font-weight:400;">/10</span>
                      </div>
                    <?php endif; ?>
                    <?php if ($ra['recommendation']): ?>
                      <span class="badge rounded-pill" style="background:#0057b7;color:#fff;font-size:.78rem;">
                        <?= ucfirst(str_replace('_',' ',$ra['recommendation'])) ?>
                      </span>
                    <?php endif; ?>
                    <span style="font-size:.78rem;color:var(--gray-500);"><?= humanDate($ra['reviewed_at'], $_lang) ?></span>
                  </div>
                  <?php if ($ra['comment_for_editor']): ?>
                    <div class="mt-2 p-2 rounded" style="background:#fff;border-left:3px solid var(--gold);font-size:.82rem;">
                      <strong style="font-size:.75rem;color:var(--gold);"><?= $_lang==='th' ? 'ความเห็นถึงบรรณาธิการ (ลับ):' : 'Confidential comments:' ?></strong><br>
                      <?= nl2br(e($ra['comment_for_editor'])) ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($ra['comment_for_author']): ?>
                    <div class="mt-2 p-2 rounded" style="background:#fff;border-left:3px solid var(--blue-mid);font-size:.82rem;">
                      <strong style="font-size:.75rem;color:var(--blue-mid);"><?= $_lang==='th' ? 'ความเห็นถึงผู้แต่ง:' : 'Comments to author:' ?></strong><br>
                      <?= nl2br(e($ra['comment_for_author'])) ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <div class="mt-2">
            <a href="<?= $appUrl ?>/admin/assign-reviewer.php?paper_id=<?= $paperId ?>" class="btn-outline-custom">
              <i class="fas fa-user-plus me-2"></i><?= $_lang==='th' ? 'มอบหมายผู้ทรงคุณวุฒิ' : 'Assign Reviewer' ?>
            </a>
          </div>
        </div>

      </div>

      <!-- Right: Metadata & Actions -->
      <div class="col-lg-4">
        <div class="content-card mb-4">
          <div class="content-card-title"><i class="fas fa-tag me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลสรุป' : 'Summary' ?></div>
          <div class="d-flex flex-column gap-3" style="font-size:.88rem;">
            <div><div style="font-size:.73rem;color:var(--gray-500);"><?= t('paper.code') ?></div>
              <code style="color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code></div>
            <div><div style="font-size:.73rem;color:var(--gray-500);"><?= t('paper.status') ?></div>
              <?= statusBadge($paper['status_code']) ?></div>
            <div><div style="font-size:.73rem;color:var(--gray-500);"><?= $_lang==='th' ? 'ผู้ส่ง' : 'Submitter' ?></div>
              <div style="font-weight:600;"><?= e($paper['submitter_name']) ?></div>
              <div style="font-size:.78rem;color:var(--gray-500);"><?= e($paper['submitter_email']) ?></div></div>
            <div><div style="font-size:.73rem;color:var(--gray-500);"><?= $_lang==='th' ? 'หัวข้อ' : 'Theme' ?></div>
              <div><?= e($_lang==='th'?$paper['theme_th']:$paper['theme_en']) ?></div></div>
            <div><div style="font-size:.73rem;color:var(--gray-500);"><?= t('paper.submitted_date') ?></div>
              <div><?= humanDate($paper['submitted_at'], $_lang) ?></div></div>
            <div><div style="font-size:.73rem;color:var(--gray-500);"><?= $_lang==='th' ? 'ผู้ร่วมวิจัย' : 'Co-Authors' ?></div>
              <div><?= count($coAuthors) ?></div></div>
          </div>
        </div>

        <div class="content-card mb-4">
          <div class="content-card-title"><i class="fas fa-bolt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'การดำเนินการ' : 'Actions' ?></div>
          <div class="d-flex flex-column gap-2">
            <a href="<?= $appUrl ?>/admin/assign-reviewer.php?paper_id=<?= $paperId ?>" class="btn-outline-custom text-center">
              <i class="fas fa-user-plus me-2"></i><?= $_lang==='th' ? 'มอบหมายผู้ทรงคุณวุฒิ' : 'Assign Reviewer' ?>
            </a>
            <a href="<?= $appUrl ?>/admin/final-decision.php?paper_id=<?= $paperId ?>" class="btn-primary-custom text-center">
              <i class="fas fa-gavel me-2"></i><?= $_lang==='th' ? 'ตัดสินผลบทความ' : 'Final Decision' ?>
            </a>
            <?php if ($paper['status_code'] === 'accepted'): ?>
              <a href="<?= $appUrl ?>/admin/publications.php?paper_id=<?= $paperId ?>" class="btn btn-success rounded-pill text-center py-2 text-decoration-none">
                <i class="fas fa-globe me-2"></i><?= $_lang==='th' ? 'เผยแพร่บทความ' : 'Publish Paper' ?>
              </a>
            <?php endif; ?>
            <a href="<?= $appUrl ?>/admin/papers.php" class="btn-outline-custom text-center">
              <i class="fas fa-arrow-left me-2"></i><?= $_lang==='th' ? 'กลับรายการ' : 'Back to List' ?>
            </a>
          </div>
        </div>

        <?php if (!empty($coAuthors)): ?>
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-users me-2" style="color:var(--gold);"></i><?= t('paper.co_authors') ?></div>
          <?php foreach ($coAuthors as $ca): ?>
            <div class="mb-2 pb-2" style="border-bottom:1px solid var(--gray-200);font-size:.84rem;">
              <div style="font-weight:600;"><?= e($ca['full_name']) ?></div>
              <div style="color:var(--gray-500);"><?= e($ca['email']) ?></div>
              <div style="color:var(--gray-500);"><?= e($ca['institution']) ?> <?= $ca['country'] ? "· {$ca['country']}" : '' ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
