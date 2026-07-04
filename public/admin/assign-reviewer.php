<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notification;
use App\Core\Mail;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$paperId = intGet('paper_id');

$errors = [];

// ── No paper_id: show list of papers needing reviewer ────────────
if (!$paperId) {
    try {
        $db = Database::getInstance();
        $pendingPapers = $db->query("
            SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS submitter_name,
                   ct.name_th AS theme_th, ct.name_en AS theme_en,
                   (SELECT COUNT(*) FROM review_assignments ra WHERE ra.paper_id = p.id AND ra.assignment_status != 'declined') AS assigned_count
            FROM papers p
            LEFT JOIN users u ON u.id = p.submitter_id
            LEFT JOIN conference_themes ct ON ct.id = p.theme_id
            WHERE p.status_code IN ('submitted','under_review')
            ORDER BY p.submitted_at ASC
        ")->fetchAll();
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        $pendingPapers = [];
    }
    $pageTitle  = $_lang==='th' ? 'มอบหมายผู้ทรงคุณวุฒิ' : 'Assign Reviewer';
    $activeMenu = 'assign-reviewer';
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
        <h1 class="dash-title"><i class="fas fa-user-check me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb"><?= $_lang==='th' ? 'เลือกบทความที่ต้องการมอบหมายผู้ทรงคุณวุฒิ' : 'Select a paper to assign a reviewer' ?></p>
      </div>
    </div>
    <?= flashHtml() ?>
    <div class="table-card">
      <?php if (empty($pendingPapers)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-check-circle fa-3x mb-3" style="color:#198754;"></i>
          <h5 style="color:var(--gray-500);"><?= $_lang==='th' ? 'ไม่มีบทความที่รอมอบหมายในขณะนี้' : 'No papers pending reviewer assignment.' ?></h5>
          <a href="<?= $appUrl ?>/admin/papers.php" class="btn-primary-custom mt-3 d-inline-block">
            <i class="fas fa-file-alt me-2"></i><?= $_lang==='th' ? 'ดูบทความทั้งหมด' : 'View All Papers' ?>
          </a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= t('paper.code') ?></th>
                <th><?= $_lang==='th' ? 'บทความ' : 'Paper' ?></th>
                <th><?= $_lang==='th' ? 'ผู้ส่ง' : 'Submitter' ?></th>
                <th><?= $_lang==='th' ? 'หัวข้อ' : 'Theme' ?></th>
                <th><?= t('paper.status') ?></th>
                <th><?= $_lang==='th' ? 'ผู้ทรงที่มอบหมาย' : 'Assigned' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingPapers as $p): ?>
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
                  <td><?= statusBadge($p['status_code']) ?></td>
                  <td style="text-align:center;">
                    <span style="font-weight:700;color:<?= (int)$p['assigned_count']>0?'#198754':'var(--gray-400)' ?>;">
                      <?= (int)$p['assigned_count'] ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= $appUrl ?>/admin/assign-reviewer.php?paper_id=<?= (int)$p['id'] ?>"
                       class="btn btn-sm btn-primary rounded-pill" style="font-size:.72rem;">
                      <i class="fas fa-user-plus me-1"></i><?= $_lang==='th' ? 'มอบหมาย' : 'Assign' ?>
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));

    $reviewerIds = array_filter(array_map('intval', (array)($_POST['reviewer_id'] ?? [])));
    $dueDate     = post('due_date');

    if (count($reviewerIds) < 2) {
        $errors[] = $_lang==='th' ? 'กรุณาเลือกผู้ทรงคุณวุฒิทั้ง 2 คน' : 'Please select both reviewers.';
    } elseif (count(array_unique($reviewerIds)) < count($reviewerIds)) {
        $errors[] = $_lang==='th' ? 'ไม่สามารถเลือกผู้ทรงคุณวุฒิคนเดิมซ้ำได้' : 'Please select different reviewers.';
    }
    if (!$dueDate) $errors[] = $_lang==='th' ? 'กรุณากำหนดวันส่งผลประเมิน' : 'Please set a due date.';

    if (empty($errors)) {
        try {
            $db = Database::getInstance();

            $pStmt = $db->prepare("SELECT * FROM papers WHERE id = :pid");
            $pStmt->execute([':pid' => $paperId]);
            $paper = $pStmt->fetch();

            $chk = $db->prepare("SELECT id FROM review_assignments WHERE paper_id = :pid AND reviewer_id = :rid AND assignment_status != 'declined'");
            $ins = $db->prepare("INSERT INTO review_assignments (paper_id, reviewer_id, assigned_by, due_date, assignment_status) VALUES (:pid, :rid, :aby, :dd, 'pending')");
            $rvStmt = $db->prepare("SELECT * FROM users WHERE id = :rid");

            foreach ($reviewerIds as $reviewerId) {
                $chk->execute([':pid' => $paperId, ':rid' => $reviewerId]);
                $alreadyAssigned = $chk->fetch();
                $chk->closeCursor();
                if ($alreadyAssigned) {
                    $errors[] = $_lang==='th' ? 'ผู้ทรงคุณวุฒิบางคนถูกมอบหมายแล้ว' : 'One or more reviewers are already assigned.';
                    break;
                }
            }

            if (empty($errors)) {
                foreach ($reviewerIds as $reviewerId) {
                    $ins->execute([':pid' => $paperId, ':rid' => $reviewerId, ':aby' => Auth::user()['id'], ':dd' => $dueDate]);

                    $rvStmt->execute([':rid' => $reviewerId]);
                    $reviewer = $rvStmt->fetch();
                    $rvStmt->closeCursor();

                    Notification::reviewAssigned($reviewerId, $paper['paper_code'], $_lang==='th' ? ($paper['title_th'] ?? $paper['title_en']) : ($paper['title_en'] ?? $paper['title_th']), $paperId);
                    Mail::sendReviewAssignment($reviewer['email'], $reviewer['first_name'] . ' ' . $reviewer['last_name'], $paper['paper_code'], $_lang==='th' ? ($paper['title_th'] ?? $paper['title_en']) : ($paper['title_en'] ?? $paper['title_th']), $dueDate);
                    auditLog('assign_reviewer', 'papers', "Paper $paperId → Reviewer $reviewerId");
                }

                $updStmt = $db->prepare("UPDATE papers SET status_code = 'under_review', updated_at = NOW() WHERE id = :pid AND status_code = 'submitted'");
                $updStmt->execute([':pid' => $paperId]);
                $statusChanged = $updStmt->rowCount() > 0;

                // Count total active assignments now
                $cntStmt2 = $db->prepare("SELECT COUNT(*) FROM review_assignments WHERE paper_id = :pid AND assignment_status != 'declined'");
                $cntStmt2->execute([':pid' => $paperId]);
                $totalAssigned = (int)$cntStmt2->fetchColumn();

                // Notify author when paper has >= 2 reviewers assigned
                if ($totalAssigned >= 2) {
                    Notification::underReview((int)$paper['submitter_id'], $paper['paper_code'], (int)$paperId);
                    // Also ensure status is under_review even if it was already set
                    if (!$statusChanged) {
                        $db->prepare("UPDATE papers SET status_code = 'under_review', updated_at = NOW() WHERE id = :pid AND status_code NOT IN ('accepted','published','rejected')")
                           ->execute([':pid' => $paperId]);
                    }
                }

                flashSet('success', $_lang==='th' ? 'มอบหมายผู้ทรงคุณวุฒิเรียบร้อย' : 'Reviewer assigned successfully.');
                redirect($appUrl . '/admin/paper-detail.php?id=' . $paperId);
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

try {
    $db    = Database::getInstance();
    $pStmt = $db->prepare("
        SELECT p.*, ct.name_th AS theme_th, ct.name_en AS theme_en
        FROM papers p JOIN conference_themes ct ON ct.id = p.theme_id
        WHERE p.id = :id
    ");
    $pStmt->execute([':id' => $paperId]);
    $paper = $pStmt->fetch();
    if (!$paper) { redirect($appUrl . '/admin/papers.php'); }

    // Available reviewers (not already assigned active to this paper)
    $rvStmt = $db->prepare("
        SELECT u.*, COUNT(ra.id) AS active_assignments
        FROM users u
        LEFT JOIN review_assignments ra ON ra.reviewer_id = u.id AND ra.assignment_status IN ('pending','in_progress')
        WHERE u.role = 'reviewer' AND u.account_status = 'active'
          AND u.id NOT IN (
            SELECT reviewer_id FROM review_assignments
            WHERE paper_id = :pid AND assignment_status != 'declined'
          )
        GROUP BY u.id
        ORDER BY active_assignments ASC, u.first_name ASC, u.last_name ASC
    ");
    $rvStmt->execute([':pid' => $paperId]);
    $reviewers = $rvStmt->fetchAll();

    // Current assignments
    $curStmt = $db->prepare("
        SELECT ra.*, CONCAT(u.first_name, ' ', u.last_name) AS reviewer_name, u.email AS reviewer_email,
               r.score_overall, r.recommendation
        FROM review_assignments ra
        JOIN users u ON u.id = ra.reviewer_id
        LEFT JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.paper_id = :pid
        ORDER BY ra.assigned_at DESC
    ");
    $curStmt->execute([':pid' => $paperId]);
    $currentAssignments = $curStmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/admin/papers.php');
}

$pageTitle  = $_lang==='th' ? 'มอบหมายผู้ทรงคุณวุฒิ' : 'Assign Reviewer';
$activeMenu = 'assign-reviewer';
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
        <h1 class="dash-title"><i class="fas fa-user-check me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
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
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Assignment Form -->
      <div class="col-lg-7">
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทความ' : 'Paper' ?>
          </div>
          <code style="color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code>
          <h5 style="font-weight:700;color:var(--blue-dark);margin:8px 0 4px;font-size:.95rem;"><?= e($_lang==='th'?$paper['title_th']:$paper['title_en']) ?></h5>
          <div style="font-size:.82rem;color:var(--gray-500);"><?= e($_lang==='th'?$paper['theme_th']:$paper['theme_en']) ?></div>
          <div class="mt-2"><?= statusBadge($paper['status_code']) ?></div>
        </div>

        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-user-plus me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'เลือกผู้ทรงคุณวุฒิ' : 'Select Reviewer' ?>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label fw-bold mb-4 d-block" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'Reviewer' ?> <span class="text-danger">*</span>
                </label>
                <?php if (empty($reviewers)): ?>
                  <div class="alert alert-warning"><?= $_lang==='th' ? 'ไม่มีผู้ทรงคุณวุฒิที่พร้อมรับงาน' : 'No available reviewers.' ?></div>
                <?php else: ?>
                  <label class="form-label" style="font-size:.82rem;color:var(--gray-500);"><?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิคนที่ 1' : 'Reviewer 1' ?></label>
                  <select name="reviewer_id[]" id="reviewer_1" class="form-select mb-3 reviewer-select" required>
                    <option value=""><?= $_lang==='th' ? '-- เลือกผู้ทรงคุณวุฒิ --' : '-- Select Reviewer --' ?></option>
                    <?php foreach ($reviewers as $rv): ?>
                      <option value="<?= $rv['id'] ?>">
                        <?= e($rv['first_name'] . ' ' . $rv['last_name']) ?> (<?= e($rv['affiliation'] ?? $rv['email']) ?>)
                        — <?= $_lang==='th' ? 'งานปัจจุบัน:' : 'Active:' ?> <?= (int)$rv['active_assignments'] ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <label class="form-label" style="font-size:.82rem;color:var(--gray-500);"><?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิคนที่ 2' : 'Reviewer 2' ?></label>
                  <select name="reviewer_id[]" id="reviewer_2" class="form-select mb-4 reviewer-select" required>
                    <option value=""><?= $_lang==='th' ? '-- เลือกผู้ทรงคุณวุฒิ --' : '-- Select Reviewer --' ?></option>
                    <?php foreach ($reviewers as $rv): ?>
                      <option value="<?= $rv['id'] ?>">
                        <?= e($rv['first_name'] . ' ' . $rv['last_name']) ?> (<?= e($rv['affiliation'] ?? $rv['email']) ?>)
                        — <?= $_lang==='th' ? 'งานปัจจุบัน:' : 'Active:' ?> <?= (int)$rv['active_assignments'] ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php endif; ?>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'กำหนดส่งผลประเมิน' : 'Due Date' ?> <span class="text-danger">*</span>
                </label>
                <input type="date" name="due_date" class="form-control"
                       min="<?= date('Y-m-d', strtotime('+3 days')) ?>"
                       value="<?= e(post('due_date', date('Y-m-d', strtotime('+21 days')))) ?>" required>
              </div>
              <button type="submit" class="btn-primary-custom" <?= empty($reviewers)?'disabled':'' ?>>
                <i class="fas fa-paper-plane me-2"></i><?= $_lang==='th' ? 'มอบหมาย' : 'Assign' ?>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Current Assignments -->
      <div class="col-lg-5">
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-list me-2" style="color:var(--gold);"></i>
            <?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิปัจจุบัน' : 'Current Assignments' ?>
            <span class="badge ms-2" style="background:var(--blue-mid);color:#fff;font-size:.72rem;"><?= count($currentAssignments) ?></span>
          </div>
          <?php if (empty($currentAssignments)): ?>
            <div class="p-3 text-center" style="color:var(--gray-500);font-size:.88rem;">
              <?= $_lang==='th' ? 'ยังไม่มีการมอบหมาย' : 'None assigned yet' ?>
            </div>
          <?php else: ?>
            <?php
            $statusColors = ['pending'=>'#fd7e14','accepted'=>'#0057b7','completed'=>'#198754','declined'=>'#6c757d'];
            $statusLabels = ['pending'=>['th'=>'รอตอบรับ','en'=>'Pending'],'accepted'=>['th'=>'รับแล้ว','en'=>'Accepted'],'completed'=>['th'=>'เสร็จสิ้น','en'=>'Completed'],'declined'=>['th'=>'ปฏิเสธ','en'=>'Declined']];
            foreach ($currentAssignments as $ra): ?>
              <div class="p-3 mb-2 rounded" style="background:var(--gray-100);border:1px solid var(--gray-200);">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div style="font-weight:700;font-size:.88rem;"><?= e($ra['reviewer_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--gray-500);"><?= e($ra['reviewer_email']) ?></div>
                    <?php if ($ra['due_date']): ?>
                      <div style="font-size:.75rem;color:var(--gray-500);"><?= $_lang==='th'?'กำหนด:':'Due:' ?> <?= humanDate($ra['due_date'], $_lang) ?></div>
                    <?php endif; ?>
                  </div>
                  <span class="badge" style="background:<?= $statusColors[$ra['assignment_status']]??'#6c757d' ?>;color:#fff;font-size:.7rem;">
                    <?= $_lang==='th' ? ($statusLabels[$ra['assignment_status']]['th']??$ra['assignment_status']) : ($statusLabels[$ra['assignment_status']]['en']??$ra['assignment_status']) ?>
                  </span>
                </div>
                <?php if ($ra['score_overall']): ?>
                  <div class="mt-2 d-flex gap-2 align-items-center">
                    <span style="font-weight:800;color:var(--blue-dark);"><?= number_format($ra['score_overall'],1) ?>/10</span>
                    <?php if ($ra['recommendation']): ?>
                      <span style="font-size:.72rem;padding:2px 8px;background:#198754;color:#fff;border-radius:99px;">
                        <?= ucfirst(str_replace('_',' ',$ra['recommendation'])) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
(function () {
  const selects = document.querySelectorAll('.reviewer-select');
  if (selects.length < 2) return;

  function syncDisabled() {
    selects.forEach(function (sel, i) {
      const otherSel = selects[1 - i];
      const otherVal = otherSel.value;
      Array.from(sel.options).forEach(function (opt) {
        opt.disabled = opt.value !== '' && opt.value === otherVal;
      });
    });
  }

  selects.forEach(function (sel) {
    sel.addEventListener('change', syncDisabled);
  });

  syncDisabled();
})();
</script>
</body>
</html>
