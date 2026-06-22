<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notification;
use App\Core\Mail;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$paperId = intGet('paper_id') ?: intPost('paper_id');
if (!$paperId) { redirect($appUrl . '/admin/papers.php'); }

$errors = [];

try {
    $db    = Database::getInstance();
    $pStmt = $db->prepare("
        SELECT p.*, u.name AS submitter_name, u.email AS submitter_email,
               ct.name_th AS theme_th, ct.name_en AS theme_en
        FROM papers p
        JOIN users u ON u.id = p.submitter_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        WHERE p.id = :id
    ");
    $pStmt->execute([':id' => $paperId]);
    $paper = $pStmt->fetch();
    if (!$paper) { redirect($appUrl . '/admin/papers.php'); }

    // Reviews
    $revStmt = $db->prepare("
        SELECT r.*, u.name AS reviewer_name, ra.status AS assign_status
        FROM reviews r
        JOIN review_assignments ra ON ra.id = r.assignment_id
        JOIN users u ON u.id = ra.reviewer_id
        WHERE ra.paper_id = :pid AND ra.status = 'completed'
        ORDER BY r.submitted_at DESC
    ");
    $revStmt->execute([':pid' => $paperId]);
    $reviews = $revStmt->fetchAll();

    // Average score
    $avgScore = empty($reviews) ? null : array_sum(array_column($reviews, 'overall_score')) / count($reviews);

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/admin/papers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));

    $decision       = post('decision');
    $editorNote     = trim(post('editor_note'));
    $pIdPost        = intPost('paper_id');

    $allowedDecisions = ['accepted', 'rejected', 'revision_required'];
    if (!in_array($decision, $allowedDecisions))
        $errors[] = $_lang==='th' ? 'กรุณาเลือกผลการตัดสิน' : 'Please select a decision.';

    if (empty($errors)) {
        try {
            $db->prepare("UPDATE papers SET status_code = :dec, updated_at = NOW() WHERE id = :pid")
               ->execute([':dec' => $decision, ':pid' => $pIdPost]);

            // Log editor note
            if ($editorNote) {
                auditLog("final_decision_{$decision}", 'papers', "Paper $pIdPost: $editorNote");
            }

            // Notify author
            $paper_code = $paper['paper_code'];
            switch ($decision) {
                case 'accepted':
                    Notification::paperAccepted($paper['submitter_id'], $pIdPost, $paper_code);
                    Mail::sendAccepted($paper['submitter_email'], $paper['submitter_name'], $paper_code);
                    break;
                case 'revision_required':
                    Notification::revisionRequired($paper['submitter_id'], $pIdPost, $paper_code);
                    break;
                case 'rejected':
                    Notification::create(
                        $paper['submitter_id'], 'review_result',
                        'บทความของท่านไม่ผ่านการพิจารณา',
                        'Paper Not Accepted',
                        "บทความ $paper_code ไม่ผ่านการพิจารณา",
                        "Paper $paper_code was not accepted after review.",
                        $pIdPost, 'both'
                    );
                    break;
            }

            $decLabels = [
                'accepted'         => [$_lang==='th'?'ยอมรับบทความแล้ว':'Paper accepted.'],
                'rejected'         => [$_lang==='th'?'ปฏิเสธบทความแล้ว':'Paper rejected.'],
                'revision_required'=> [$_lang==='th'?'ส่งคืนให้แก้ไขแล้ว':'Paper sent back for revision.'],
            ];
            flashSet('success', $decLabels[$decision][0] ?? 'Done.');
            redirect($appUrl . '/admin/paper-detail.php?id=' . $pIdPost);

        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

$pageTitle  = $_lang==='th' ? 'ตัดสินผลบทความ' : 'Final Decision';
$activeMenu = 'final-decision';

$recColors = ['accept'=>'#198754','minor_revision'=>'#fd7e14','major_revision'=>'#dc3545','reject'=>'#6c757d'];
$recLabels = ['accept'=>'Accept','minor_revision'=>'Minor Revision','major_revision'=>'Major Revision','reject'=>'Reject'];
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
        <h1 class="dash-title"><i class="fas fa-gavel me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb">
          <a href="<?= $appUrl ?>/admin/paper-detail.php?id=<?= $paperId ?>" style="color:var(--blue-mid);">
            <code><?= e($paper['paper_code']) ?></code>
          </a>
        </p>
      </div>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Reviews Summary -->
      <div class="col-lg-7">
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทความ' : 'Paper' ?>
          </div>
          <code style="color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code>
          <h5 style="font-weight:700;color:var(--blue-dark);margin:8px 0 4px;"><?= e($_lang==='th'?$paper['title_th']:$paper['title_en']) ?></h5>
          <div style="font-size:.83rem;color:var(--gray-500);">
            <?= e($paper['submitter_name']) ?> &bull; <?= e($_lang==='th'?$paper['theme_th']:$paper['theme_en']) ?>
          </div>
          <div class="mt-2"><?= statusBadge($paper['status_code']) ?></div>
        </div>

        <?php if (!empty($reviews)): ?>
          <!-- Reviews -->
          <?php foreach ($reviews as $rv): ?>
            <div class="content-card mb-3">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <div style="font-weight:700;color:var(--blue-dark);"><?= e($rv['reviewer_name']) ?></div>
                  <div style="font-size:.78rem;color:var(--gray-500);"><?= humanDate($rv['submitted_at'], $_lang) ?></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <div style="background:var(--blue-dark);color:#fff;border-radius:50%;width:46px;height:46px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800;">
                    <?= number_format($rv['overall_score'],1) ?>
                    <span style="font-size:.55rem;font-weight:400;">/10</span>
                  </div>
                  <?php if ($rv['recommendation']): ?>
                    <span class="badge" style="background:<?= $recColors[$rv['recommendation']]??'#6c757d' ?>;color:#fff;">
                      <?= $recLabels[$rv['recommendation']] ?? ucfirst($rv['recommendation']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              <?php if ($rv['comments_to_author']): ?>
                <div style="font-size:.85rem;line-height:1.7;padding:10px;background:var(--gray-100);border-radius:var(--radius);border-left:3px solid var(--blue-mid);">
                  <?= nl2br(e($rv['comments_to_author'])) ?>
                </div>
              <?php endif; ?>
              <?php if ($rv['comments_to_editor']): ?>
                <div class="mt-2 p-2 rounded" style="background:#fff3cd;border-left:3px solid var(--warning);font-size:.82rem;">
                  <strong style="font-size:.73rem;color:#856404;"><?= $_lang==='th'?'(ลับ - ถึงบรรณาธิการ):':'(Confidential - to editor):' ?></strong><br>
                  <?= nl2br(e($rv['comments_to_editor'])) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <!-- Aggregate -->
          <div class="content-card" style="border:2px solid var(--gold);">
            <div class="content-card-title"><i class="fas fa-calculator me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'สรุปผลประเมิน' : 'Review Summary' ?></div>
            <div class="row g-3 text-center">
              <div class="col-4">
                <div style="font-size:2rem;font-weight:800;color:var(--blue-dark);"><?= number_format($avgScore, 1) ?></div>
                <div style="font-size:.78rem;color:var(--gray-500);"><?= $_lang==='th' ? 'คะแนนเฉลี่ย' : 'Avg Score' ?> /10</div>
              </div>
              <div class="col-4">
                <div style="font-size:2rem;font-weight:800;color:var(--blue-dark);"><?= count($reviews) ?></div>
                <div style="font-size:.78rem;color:var(--gray-500);"><?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'Reviewers' ?></div>
              </div>
              <div class="col-4">
                <?php $acceptCount = count(array_filter($reviews, fn($r) => str_starts_with($r['recommendation']??'', 'accept'))); ?>
                <div style="font-size:2rem;font-weight:800;color:<?= $acceptCount > count($reviews)/2 ? '#198754' : '#dc3545' ?>;"><?= $acceptCount ?>/<?= count($reviews) ?></div>
                <div style="font-size:.78rem;color:var(--gray-500);"><?= $_lang==='th' ? 'โหวตยอมรับ' : 'Accept Votes' ?></div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $_lang==='th' ? 'ยังไม่มีผลประเมิน กรุณาตรวจสอบว่ามีผู้ทรงคุณวุฒิส่งผลแล้วก่อนตัดสิน' : 'No reviews yet. Please ensure reviewers have submitted before making a decision.' ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Decision Form -->
      <div class="col-lg-5">
        <div class="content-card" style="border-left:4px solid var(--gold);">
          <div class="content-card-title">
            <i class="fas fa-gavel me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ผลการตัดสิน' : 'Make Decision' ?>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="paper_id" value="<?= $paperId ?>">
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ผลการตัดสิน' : 'Decision' ?> <span class="text-danger">*</span></label>
                <div class="d-flex flex-column gap-2">
                  <?php
                  $decisions = [
                    'accepted'          => ['color'=>'#198754','icon'=>'fa-check-circle','th'=>'ยอมรับบทความ','en'=>'Accept Paper'],
                    'revision_required' => ['color'=>'#fd7e14','icon'=>'fa-edit','th'=>'ส่งคืนให้แก้ไข','en'=>'Require Revision'],
                    'rejected'          => ['color'=>'#dc3545','icon'=>'fa-times-circle','th'=>'ปฏิเสธบทความ','en'=>'Reject Paper'],
                  ];
                  foreach ($decisions as $dc => $di): ?>
                    <label style="cursor:pointer;">
                      <input type="radio" name="decision" value="<?= $dc ?>" class="d-none decision-radio">
                      <div class="p-3 rounded d-flex align-items-center gap-3 decision-option"
                           style="border:2px solid <?= $di['color'] ?>;background:transparent;transition:.2s;">
                        <i class="fas <?= $di['icon'] ?>" style="color:<?= $di['color'] ?>;font-size:1.2rem;flex-shrink:0;"></i>
                        <span style="font-weight:700;color:var(--blue-dark);"><?= $_lang==='th'?$di['th']:$di['en'] ?></span>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'หมายเหตุบรรณาธิการ (ไม่บังคับ)' : 'Editor Note (optional)' ?></label>
                <textarea name="editor_note" class="form-control" rows="4"
                          placeholder="<?= $_lang==='th' ? 'หมายเหตุสำหรับบันทึกภายใน...' : 'Internal notes for record...' ?>"></textarea>
              </div>
              <button type="submit" class="btn-primary-custom"
                      data-confirm="<?= $_lang==='th' ? 'ยืนยันผลการตัดสิน? ผู้แต่งจะได้รับการแจ้งเตือน' : 'Confirm decision? The author will be notified.' ?>">
                <i class="fas fa-gavel me-2"></i><?= $_lang==='th' ? 'ยืนยันผลการตัดสิน' : 'Confirm Decision' ?>
              </button>
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
document.querySelectorAll('.decision-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    const colors = {
      'accepted': '#198754', 'revision_required': '#fd7e14', 'rejected': '#dc3545'
    };
    document.querySelectorAll('.decision-option').forEach(opt => {
      const r = opt.closest('label').querySelector('input');
      const c = colors[r.value] || '#6c757d';
      if (r.checked) {
        opt.style.background = c + '15';
        opt.style.borderWidth = '2px';
      } else {
        opt.style.background = 'transparent';
      }
    });
  });
});
</script>
</body>
</html>
