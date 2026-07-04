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

$errors = [];

// ── No paper_id: show list of papers ready for decision ──────────
if (!$paperId) {
    try {
        $db = Database::getInstance();
        $isMysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        $submitterNameExpr = $isMysql
            ? "CONCAT(u.first_name, ' ', u.last_name)"
            : "(u.first_name || ' ' || u.last_name)";
        $decisionPapers = $db->query("
            SELECT p.*, {$submitterNameExpr} AS submitter_name,
                   ct.name_th AS theme_th, ct.name_en AS theme_en,
                   COUNT(r.id) AS review_count,
                   ROUND(AVG(r.score_overall), 1) AS avg_score
            FROM papers p
            LEFT JOIN users u ON u.id = p.submitter_id
            LEFT JOIN conference_themes ct ON ct.id = p.theme_id
            LEFT JOIN review_assignments ra ON ra.paper_id = p.id AND ra.assignment_status = 'completed'
            LEFT JOIN reviews r ON r.assignment_id = ra.id
            WHERE p.status_code = 'under_review'
            GROUP BY p.id, u.first_name, u.last_name, ct.name_th, ct.name_en
            ORDER BY p.submitted_at ASC
        ")->fetchAll();
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        $decisionPapers = [];
    }
    $pageTitle  = $_lang==='th' ? 'ตัดสินผลบทคัดย่อ' : 'Final Decision';
    $activeMenu = 'final-decision';
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
        <p class="dash-breadcrumb"><?= $_lang==='th' ? 'เลือกบทคัดย่อที่ต้องการตัดสินผล' : 'Select a paper to make final decision' ?></p>
      </div>
    </div>
    <?= flashHtml() ?>
    <div class="table-card">
      <?php if (empty($decisionPapers)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-inbox fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= $_lang==='th' ? 'ไม่มีบทคัดย่อที่รอการตัดสินในขณะนี้' : 'No papers pending final decision.' ?></h5>
          <p style="font-size:.85rem;color:var(--gray-500);">
            <?= $_lang==='th' ? 'บทคัดย่อต้องมีสถานะ "กำลังพิจารณา" เพื่อทำการตัดสิน' : 'Papers must be in "Under Review" status to make a decision.' ?>
          </p>
          <a href="<?= $appUrl ?>/admin/papers.php" class="btn-primary-custom mt-3 d-inline-block">
            <i class="fas fa-file-alt me-2"></i><?= $_lang==='th' ? 'ดูบทคัดย่อทั้งหมด' : 'View All Papers' ?>
          </a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= t('paper.code') ?></th>
                <th><?= $_lang==='th' ? 'บทคัดย่อ' : 'Paper' ?></th>
                <th><?= $_lang==='th' ? 'ผู้ส่ง' : 'Submitter' ?></th>
                <th><?= $_lang==='th' ? 'หัวข้อ' : 'Theme' ?></th>
                <th><?= $_lang==='th' ? 'ผลประเมิน' : 'Reviews' ?></th>
                <th><?= $_lang==='th' ? 'คะแนนเฉลี่ย' : 'Avg Score' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($decisionPapers as $p): ?>
                <tr>
                  <td><code style="font-size:.78rem;color:var(--blue-mid);"><?= e($p['paper_code']) ?></code></td>
                  <td style="max-width:180px;">
                    <div style="font-weight:600;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= e($_lang==='th' ? $p['title_th'] : $p['title_en']) ?>
                    </div>
                  </td>
                  <td style="font-size:.82rem;"><?= e($p['submitter_name'] ?? '—') ?></td>
                  <td style="font-size:.78rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= e($_lang==='th' ? $p['theme_th'] : $p['theme_en']) ?>
                  </td>
                  <td style="text-align:center;">
                    <?php $rc = (int)$p['review_count']; ?>
                    <span style="font-weight:700;color:<?= $rc>0?'#198754':'var(--gray-400)' ?>;">
                      <?= $rc ?> <?= $_lang==='th'?'ผล':'review(s)' ?>
                    </span>
                  </td>
                  <td style="text-align:center;">
                    <?php if ($p['avg_score']): ?>
                      <span style="font-weight:800;color:var(--blue-dark);"><?= $p['avg_score'] ?>/10</span>
                    <?php else: ?>
                      <span style="color:var(--gray-400);">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?= $appUrl ?>/admin/final-decision.php?paper_id=<?= (int)$p['id'] ?>"
                       class="btn btn-sm btn-warning rounded-pill" style="font-size:.72rem;color:var(--blue-dark);">
                      <i class="fas fa-gavel me-1"></i><?= $_lang==='th' ? 'ตัดสิน' : 'Decide' ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
    <?php
    exit;
}
// ──────────────────────────────────────────────────────────────────

try {
    $db    = Database::getInstance();
    $pStmt = $db->prepare("
        SELECT p.*, (u.first_name || ' ' || u.last_name) AS submitter_name, u.email AS submitter_email,
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
        SELECT r.*, (u.first_name || ' ' || u.last_name) AS reviewer_name, ra.assignment_status AS assign_status
        FROM reviews r
        JOIN review_assignments ra ON ra.id = r.assignment_id
        JOIN users u ON u.id = ra.reviewer_id
        WHERE ra.paper_id = :pid AND ra.assignment_status = 'completed'
        ORDER BY r.reviewed_at DESC
    ");
    $revStmt->execute([':pid' => $paperId]);
    $reviews = $revStmt->fetchAll();

    // Average score
    $avgScore = empty($reviews) ? null : array_sum(array_column($reviews, 'score_overall')) / count($reviews);

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

            if ($editorNote) {
                auditLog("final_decision_{$decision}", 'papers', "Paper $pIdPost: $editorNote");
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด กรุณาลองใหม่' : 'An error occurred. Please try again.';
        }

        if (empty($errors)) {
            // Notify author (non-fatal — errors here don't block the decision)
            $paper_code  = $paper['paper_code'];
            $paper_title = $_lang === 'th' ? $paper['title_th'] : $paper['title_en'];
            try {
                switch ($decision) {
                    case 'accepted':
                        Notification::paperAccepted($paper['submitter_id'], $paper_code, $pIdPost);
                        Mail::sendAccepted($paper['submitter_email'], $paper['submitter_name'], $paper_code, $paper_title);
                        break;
                    case 'revision_required':
                        Notification::revisionRequired($paper['submitter_id'], $paper_code, $pIdPost);
                        Mail::sendReviewResult($paper['submitter_email'], $paper['submitter_name'], $paper_code, $paper_title, 'Revision Required');
                        break;
                    case 'rejected':
                        Notification::create(
                            $paper['submitter_id'], 'review_result',
                            'บทคัดย่อของท่านไม่ผ่านการพิจารณา',
                            'Paper Not Accepted',
                            "บทคัดย่อ $paper_code ไม่ผ่านการพิจารณา",
                            "Paper $paper_code was not accepted after review.",
                            $pIdPost, 'both'
                        );
                        Mail::sendReviewResult($paper['submitter_email'], $paper['submitter_name'], $paper_code, $paper_title, 'Not Accepted');
                        break;
                }
            } catch (\Throwable $e) {
                error_log('Notification/Mail error after decision: ' . $e->getMessage());
            }

            $decLabels = [
                'accepted'         => $_lang==='th' ? 'ยอมรับบทคัดย่อแล้ว'       : 'Paper accepted.',
                'rejected'         => $_lang==='th' ? 'ปฏิเสธบทคัดย่อแล้ว'        : 'Paper rejected.',
                'revision_required'=> $_lang==='th' ? 'ส่งคืนให้แก้ไขแล้ว'     : 'Paper sent back for revision.',
            ];
            flashSet('success', $decLabels[$decision] ?? 'Done.');
            redirect($appUrl . '/admin/paper-detail.php?id=' . $pIdPost);
        }
    }
}

$pageTitle  = $_lang==='th' ? 'ตัดสินผลบทคัดย่อ' : 'Final Decision';
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
            <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทคัดย่อ' : 'Paper' ?>
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
                  <div style="font-size:.78rem;color:var(--gray-500);"><?= humanDate($rv['reviewed_at'], $_lang) ?></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <div style="background:var(--blue-dark);color:#fff;border-radius:50%;width:46px;height:46px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800;">
                    <?= number_format($rv['score_overall'],1) ?>
                    <span style="font-size:.55rem;font-weight:400;">/10</span>
                  </div>
                  <?php if ($rv['recommendation']): ?>
                    <span class="badge" style="background:<?= $recColors[$rv['recommendation']]??'#6c757d' ?>;color:#fff;">
                      <?= $recLabels[$rv['recommendation']] ?? ucfirst($rv['recommendation']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              <?php if ($rv['comment_for_author']): ?>
                <div style="font-size:.85rem;line-height:1.7;padding:10px;background:var(--gray-100);border-radius:var(--radius);border-left:3px solid var(--blue-mid);">
                  <?= nl2br(e($rv['comment_for_author'])) ?>
                </div>
              <?php endif; ?>
              <?php if ($rv['comment_for_editor']): ?>
                <div class="mt-2 p-2 rounded" style="background:#fff3cd;border-left:3px solid var(--warning);font-size:.82rem;">
                  <strong style="font-size:.73rem;color:#856404;"><?= $_lang==='th'?'(ลับ - ถึงบรรณาธิการ):':'(Confidential - to editor):' ?></strong><br>
                  <?= nl2br(e($rv['comment_for_editor'])) ?>
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
                    'accepted'          => ['color'=>'#198754','icon'=>'fa-check-circle','th'=>'ยอมรับบทคัดย่อ','en'=>'Accept Paper'],
                    'revision_required' => ['color'=>'#fd7e14','icon'=>'fa-edit','th'=>'ส่งคืนให้แก้ไข','en'=>'Require Revision'],
                    'rejected'          => ['color'=>'#dc3545','icon'=>'fa-times-circle','th'=>'ปฏิเสธบทคัดย่อ','en'=>'Reject Paper'],
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
