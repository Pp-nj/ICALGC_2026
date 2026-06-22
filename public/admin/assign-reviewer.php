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
if (!$paperId) { redirect($appUrl . '/admin/papers.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));

    $reviewerId = intPost('reviewer_id');
    $dueDate    = post('due_date');

    if (!$reviewerId) $errors[] = $_lang==='th' ? 'กรุณาเลือกผู้ทรงคุณวุฒิ' : 'Please select a reviewer.';
    if (!$dueDate)    $errors[] = $_lang==='th' ? 'กรุณากำหนดวันส่งผลประเมิน' : 'Please set a due date.';

    if (empty($errors)) {
        try {
            $db = Database::getInstance();

            // Check not already assigned
            $chk = $db->prepare("SELECT id FROM review_assignments WHERE paper_id = :pid AND reviewer_id = :rid AND status != 'declined'");
            $chk->execute([':pid' => $paperId, ':rid' => $reviewerId]);
            if ($chk->fetch()) {
                $errors[] = $_lang==='th' ? 'ผู้ทรงคุณวุฒิคนนี้ถูกมอบหมายแล้ว' : 'This reviewer is already assigned.';
            } else {
                $ins = $db->prepare("INSERT INTO review_assignments (paper_id, reviewer_id, due_date, status) VALUES (:pid, :rid, :dd, 'pending')");
                $ins->execute([':pid' => $paperId, ':rid' => $reviewerId, ':dd' => $dueDate]);

                // Update paper status to under_review if it was submitted/screening
                $db->prepare("UPDATE papers SET status_code = 'under_review', updated_at = NOW() WHERE id = :pid AND status_code IN ('submitted','screening')")
                   ->execute([':pid' => $paperId]);

                // Notify reviewer
                $rvStmt = $db->prepare("SELECT * FROM users WHERE id = :rid");
                $rvStmt->execute([':rid' => $reviewerId]);
                $reviewer = $rvStmt->fetch();

                $pStmt = $db->prepare("SELECT * FROM papers WHERE id = :pid");
                $pStmt->execute([':pid' => $paperId]);
                $paper = $pStmt->fetch();

                Notification::reviewAssigned($reviewerId, $paperId, $paper['paper_code']);
                Mail::sendReviewAssignment($reviewer['email'], $reviewer['name'], $paper['paper_code'], $dueDate);

                auditLog('assign_reviewer', 'papers', "Paper $paperId → Reviewer $reviewerId");
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
        LEFT JOIN review_assignments ra ON ra.reviewer_id = u.id AND ra.status IN ('pending','accepted')
        WHERE u.role = 'reviewer' AND u.is_suspended = FALSE
          AND u.id NOT IN (
            SELECT reviewer_id FROM review_assignments
            WHERE paper_id = :pid AND status != 'declined'
          )
        GROUP BY u.id
        ORDER BY active_assignments ASC, u.name ASC
    ");
    $rvStmt->execute([':pid' => $paperId]);
    $reviewers = $rvStmt->fetchAll();

    // Current assignments
    $curStmt = $db->prepare("
        SELECT ra.*, u.name AS reviewer_name, u.email AS reviewer_email,
               r.overall_score, r.recommendation
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
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'Reviewer' ?> <span class="text-danger">*</span>
                </label>
                <?php if (empty($reviewers)): ?>
                  <div class="alert alert-warning"><?= $_lang==='th' ? 'ไม่มีผู้ทรงคุณวุฒิที่พร้อมรับงาน' : 'No available reviewers.' ?></div>
                <?php else: ?>
                  <select name="reviewer_id" class="form-select" required>
                    <option value=""><?= $_lang==='th' ? '-- เลือกผู้ทรงคุณวุฒิ --' : '-- Select Reviewer --' ?></option>
                    <?php foreach ($reviewers as $rv): ?>
                      <option value="<?= $rv['id'] ?>" <?= intPost('reviewer_id')===$rv['id']?'selected':'' ?>>
                        <?= e($rv['name']) ?> (<?= e($rv['affiliation'] ?? $rv['email']) ?>)
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
                  <span class="badge" style="background:<?= $statusColors[$ra['status']]??'#6c757d' ?>;color:#fff;font-size:.7rem;">
                    <?= $_lang==='th' ? ($statusLabels[$ra['status']]['th']??$ra['status']) : ($statusLabels[$ra['status']]['en']??$ra['status']) ?>
                  </span>
                </div>
                <?php if ($ra['overall_score']): ?>
                  <div class="mt-2 d-flex gap-2 align-items-center">
                    <span style="font-weight:800;color:var(--blue-dark);"><?= number_format($ra['overall_score'],1) ?>/10</span>
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
</body>
</html>
