<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('reviewer');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

try {
    $db = Database::getInstance();
    $isMysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';

    // Stats
    $sStmt = $db->prepare("
        SELECT
            COUNT(*) AS total_assigned,
            SUM(CASE WHEN ra.assignment_status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN ra.assignment_status = 'in_progress' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN ra.assignment_status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN ra.due_date < NOW() AND ra.assignment_status NOT IN ('completed','declined') THEN 1 ELSE 0 END) AS overdue
        FROM review_assignments ra
        WHERE ra.reviewer_id = :uid
    ");
    $sStmt->execute([':uid' => $uid]);
    $stats = $sStmt->fetch();

    // Pending assignments
    $submitterNameExpr = $isMysql
        ? "CONCAT(u.first_name, ' ', u.last_name)"
        : "(u.first_name || ' ' || u.last_name)";
    $pendingStmt = $db->prepare("
        SELECT ra.*, p.paper_code, p.title_th, p.title_en, p.status_code,
               ct.name_th AS theme_th, ct.name_en AS theme_en,
               {$submitterNameExpr} AS submitter_name
        FROM review_assignments ra
        JOIN papers p ON p.id = ra.paper_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        JOIN users u ON u.id = p.submitter_id
        WHERE ra.reviewer_id = :uid AND ra.assignment_status IN ('pending','in_progress')
        ORDER BY ra.due_date ASC
        LIMIT 10
    ");
    $pendingStmt->execute([':uid' => $uid]);
    $pendingAssignments = $pendingStmt->fetchAll();

    // Recent completed reviews
    $recentStmt = $db->prepare("
        SELECT ra.id AS assignment_id, p.paper_code, p.title_th, p.title_en,
               r.score_overall, r.recommendation, r.reviewed_at
        FROM review_assignments ra
        JOIN papers p ON p.id = ra.paper_id
        JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.reviewer_id = :uid
        ORDER BY r.reviewed_at DESC
        LIMIT 5
    ");
    $recentStmt->execute([':uid' => $uid]);
    $recentReviews = $recentStmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $stats = ['total_assigned'=>0,'pending'=>0,'accepted'=>0,'completed'=>0,'overdue'=>0];
    $pendingAssignments = []; $recentReviews = [];
}

$pageTitle  = t('author.dashboard');
$activeMenu = 'dashboard';
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
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_reviewer.php'; ?>

  <main class="dashboard-content">
    <!-- Header -->
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title">
          <?= $_lang==='th'?'สวัสดี, ':'Hello, ' ?><?= e($user['name']) ?> 👋
        </h1>
        <p class="dash-breadcrumb"><?= $_lang==='th' ? 'แดชบอร์ดผู้ทรงคุณวุฒิ' : 'Reviewer Dashboard' ?> — <?= date('j F Y') ?></p>
      </div>
      <a href="<?= $appUrl ?>/reviewer/assigned-papers.php" class="btn-primary-custom">
        <i class="fas fa-tasks me-2"></i><?= $_lang==='th' ? 'ดูบทคัดย่อที่ได้รับ' : 'View Assignments' ?>
      </a>
    </div>

    <?= flashHtml() ?>

    <!-- Stats -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['total_assigned'] ?></div>
            <div class="stat-label"><?= $_lang==='th' ? 'ทั้งหมด' : 'Total Assigned' ?></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card gold">
          <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['pending'] ?></div>
            <div class="stat-label"><?= $_lang==='th' ? 'รอตอบรับ' : 'Pending' ?></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card green">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['completed'] ?></div>
            <div class="stat-label"><?= $_lang==='th' ? 'ประเมินเสร็จแล้ว' : 'Completed' ?></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left-color:#dc3545;">
          <div class="stat-icon" style="background:#f8d7da;color:#dc3545;"><i class="fas fa-exclamation-circle"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['overdue'] ?></div>
            <div class="stat-label"><?= $_lang==='th' ? 'เกินกำหนด' : 'Overdue' ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Overdue Alert -->
    <?php if ((int)$stats['overdue'] > 0): ?>
      <div class="alert d-flex align-items-center gap-3 mb-4" style="background:#f8d7da;border-left:4px solid #dc3545;border-radius:var(--radius);color:#842029;">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <div>
          <strong><?= $_lang==='th' ? 'บทคัดย่อเกินกำหนดประเมิน' : 'Overdue Reviews' ?></strong><br>
          <span style="font-size:.88rem;">
            <?= $_lang==='th'
              ? "คุณมี {$stats['overdue']} บทคัดย่อที่เกินกำหนดประเมิน กรุณาดำเนินการโดยด่วน"
              : "You have {$stats['overdue']} overdue review(s). Please complete them as soon as possible." ?>
          </span>
        </div>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Pending Assignments -->
      <div class="col-lg-7">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title">
              <i class="fas fa-tasks me-2" style="color:var(--gold);"></i>
              <?= $_lang==='th' ? 'บทคัดย่อที่รอประเมิน' : 'Pending Assignments' ?>
            </span>
            <a href="<?= $appUrl ?>/reviewer/assigned-papers.php" class="btn-primary-custom" style="padding:8px 18px;font-size:.82rem;">
              <?= $_lang==='th' ? 'ดูทั้งหมด' : 'View All' ?>
            </a>
          </div>

          <?php if (empty($pendingAssignments)): ?>
            <div class="p-5 text-center">
              <i class="fas fa-check-circle fa-3x mb-3" style="color:var(--gray-200);"></i>
              <h5 style="color:var(--gray-500);font-size:1rem;">
                <?= $_lang==='th' ? 'ไม่มีบทคัดย่อที่รอประเมิน' : 'No pending assignments' ?>
              </h5>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th><?= t('paper.code') ?></th>
                    <th><?= $_lang==='th' ? 'บทคัดย่อ' : 'Paper' ?></th>
                    <th><?= $_lang==='th' ? 'กำหนด' : 'Due' ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pendingAssignments as $ra):
                    $isOverdue = $ra['due_date'] && strtotime($ra['due_date']) < time() && $ra['assignment_status'] !== 'completed';
                  ?>
                    <tr>
                      <td><code style="font-size:.8rem;color:var(--blue-mid);"><?= e($ra['paper_code']) ?></code></td>
                      <td style="max-width:180px;">
                        <div style="font-weight:600;font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                          <?= e($_lang==='th' ? $ra['title_th'] : $ra['title_en']) ?>
                        </div>
                        <div style="font-size:.75rem;color:var(--gray-500);"><?= e($_lang==='th' ? $ra['theme_th'] : $ra['theme_en']) ?></div>
                      </td>
                      <td style="font-size:.82rem;white-space:nowrap;">
                        <?php if ($ra['due_date']): ?>
                          <span style="color:<?= $isOverdue ? '#dc3545' : 'var(--gray-700)' ?>;font-weight:<?= $isOverdue ? '700' : '400' ?>;">
                            <?= $isOverdue ? '<i class="fas fa-exclamation-triangle me-1"></i>' : '' ?>
                            <?= humanDate($ra['due_date'], $_lang) ?>
                          </span>
                        <?php else: ?>
                          <span style="color:var(--gray-400);">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php
                          $badgeMap = [
                            'pending'     => ['bg'=>'#ffc107','color'=>'#000','th'=>'รอตอบรับ',    'en'=>'Pending'],
                            'in_progress' => ['bg'=>'#0d6efd','color'=>'#fff','th'=>'กำลังประเมิน','en'=>'In Progress'],
                            'completed'   => ['bg'=>'#198754','color'=>'#fff','th'=>'เสร็จแล้ว',   'en'=>'Completed'],
                            'declined'    => ['bg'=>'#bd3838','color'=>'#fff','th'=>'ปฏิเสธ',      'en'=>'Declined'],
                          ];
                          $b = $badgeMap[$ra['assignment_status']] ?? ['bg'=>'#6c757d','color'=>'#fff','th'=>$ra['assignment_status'],'en'=>$ra['assignment_status']];
                        ?>
                        <span style="font-size:.72rem;padding:3px 10px;border-radius:99px;background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;font-weight:600;white-space:nowrap;">
                          <?= $_lang==='th' ? $b['th'] : $b['en'] ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Completed -->
      <div class="col-lg-5">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title">
              <i class="fas fa-check-double me-2" style="color:var(--gold);"></i>
              <?= $_lang==='th' ? 'ประเมินล่าสุด' : 'Recently Completed' ?>
            </span>
          </div>
          <?php if (empty($recentReviews)): ?>
            <div class="p-4 text-center" style="color:var(--gray-500);">
              <?= $_lang==='th' ? 'ยังไม่มีการประเมิน' : 'No completed reviews yet' ?>
            </div>
          <?php else: ?>
            <?php foreach ($recentReviews as $rr):
              $recColors = ['accept'=>'#198754','minor_revision'=>'#fd7e14','major_revision'=>'#dc3545','reject'=>'#6c757d'];
              $recColor  = $recColors[$rr['recommendation']] ?? '#6c757d';
            ?>
              <div class="p-3" style="border-bottom:1px solid var(--gray-200);">
                <div class="d-flex justify-content-between align-items-start">
                  <div style="flex:1;min-width:0;">
                    <code style="font-size:.78rem;color:var(--blue-mid);"><?= e($rr['paper_code']) ?></code>
                    <div style="font-size:.83rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--blue-dark);">
                      <?= e($_lang==='th' ? $rr['title_th'] : $rr['title_en']) ?>
                    </div>
                    <div style="font-size:.76rem;color:var(--gray-500);"><?= humanDate($rr['reviewed_at'], $_lang) ?></div>
                  </div>
                  <div class="text-end ms-2 flex-shrink-0">
                    <?php if ($rr['score_overall']): ?>
                      <div style="font-weight:800;color:var(--blue-dark);font-size:1rem;"><?= number_format($rr['score_overall'],1) ?><span style="font-size:.65rem;font-weight:400;">/10</span></div>
                    <?php endif; ?>
                    <?php if ($rr['recommendation']): ?>
                      <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:<?= $recColor ?>;color:#fff;">
                        <?= ucfirst(str_replace('_', ' ', $rr['recommendation'])) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
            <div class="p-3 text-end" style="border-top:1px solid var(--gray-200);">
              <a href="<?= $appUrl ?>/reviewer/history.php" style="font-size:.85rem;color:var(--blue-mid);">
                <?= $_lang==='th' ? 'ดูทั้งหมด' : 'View All' ?> <i class="fas fa-arrow-right ms-1"></i>
              </a>
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
