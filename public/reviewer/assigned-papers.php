<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('reviewer');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

// Accept or decline assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));
    $action = post('action');
    $aid    = intPost('assignment_id');

    if (in_array($action, ['accept', 'decline']) && $aid) {
        try {
            $db = Database::getInstance();
            $newStatus = $action === 'accept' ? 'accepted' : 'declined';
            $db->prepare("UPDATE review_assignments SET status = :st WHERE id = :aid AND reviewer_id = :uid")
               ->execute([':st' => $newStatus, ':aid' => $aid, ':uid' => $uid]);
            $msg = $action === 'accept'
                ? ($_lang==='th' ? 'รับงานประเมินแล้ว' : 'Assignment accepted.')
                : ($_lang==='th' ? 'ปฏิเสธงานประเมินแล้ว' : 'Assignment declined.');
            flashSet('success', $msg);
        } catch (\Throwable $e) { error_log($e->getMessage()); }
    }
    redirect($appUrl . '/reviewer/assigned-papers.php');
}

$statusFilter = sanitize(get('status'));
$page         = max(1, intGet('page', 1));
$perPage      = 10;

$where  = ['ra.reviewer_id = :uid'];
$params = [':uid' => $uid];

if ($statusFilter) {
    $where[]       = "ra.status = :st";
    $params[':st'] = $statusFilter;
}
$whereStr = implode(' AND ', $where);

try {
    $db = Database::getInstance();

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM review_assignments ra WHERE {$whereStr}");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $pg = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT ra.*, p.paper_code, p.title_th, p.title_en, p.abstract_th, p.abstract_en,
               p.status_code AS paper_status,
               ct.name_th AS theme_th, ct.name_en AS theme_en,
               r.id AS review_id, r.overall_score, r.recommendation
        FROM review_assignments ra
        JOIN papers p ON p.id = ra.paper_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        LEFT JOIN reviews r ON r.assignment_id = ra.id
        WHERE {$whereStr}
        ORDER BY ra.due_date ASC NULLS LAST, ra.assigned_at DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $assignments = $stmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $assignments = []; $total = 0; $pg = paginate(0, $perPage, 1);
}

$pageTitle  = $_lang==='th' ? 'บทความที่ได้รับมอบหมาย' : 'Assigned Papers';
$activeMenu = 'assigned';

$statuses = [
    'pending'   => ['label_th' => 'รอตอบรับ',      'label_en' => 'Pending',   'color' => '#fd7e14'],
    'accepted'  => ['label_th' => 'รับงานแล้ว',    'label_en' => 'Accepted',  'color' => '#0057b7'],
    'completed' => ['label_th' => 'เสร็จสิ้น',      'label_en' => 'Completed', 'color' => '#198754'],
    'declined'  => ['label_th' => 'ปฏิเสธ',         'label_en' => 'Declined',  'color' => '#6c757d'],
];
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
    <div class="dash-header">
      <h1 class="dash-title">
        <i class="fas fa-tasks me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
      </h1>
      <p class="dash-breadcrumb"><?= $total ?> <?= $_lang==='th' ? 'บทความ' : 'assignment(s)' ?></p>
    </div>

    <?= flashHtml() ?>

    <!-- Status Filter -->
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a href="?" class="btn btn-sm rounded-pill fw-bold <?= !$statusFilter?'btn-warning':'btn-outline-secondary' ?>"
         style="<?= !$statusFilter?'background:var(--gold);color:var(--blue-dark);border-color:var(--gold);':'' ?>">
        <?= t('common.all') ?>
      </a>
      <?php foreach ($statuses as $sCode => $sInfo): ?>
        <a href="?status=<?= $sCode ?>"
           class="btn btn-sm rounded-pill fw-bold"
           style="background:<?= $statusFilter===$sCode?$sInfo['color']:'transparent' ?>;
                  color:<?= $statusFilter===$sCode?'#fff':'var(--gray-700)' ?>;
                  border:2px solid <?= $sInfo['color'] ?>;">
          <?= $_lang==='th' ? $sInfo['label_th'] : $sInfo['label_en'] ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Assignments List -->
    <?php if (empty($assignments)): ?>
      <div class="content-card p-5 text-center">
        <i class="fas fa-inbox fa-3x mb-3" style="color:var(--gray-200);"></i>
        <h5 style="color:var(--gray-500);"><?= $_lang==='th' ? 'ไม่มีบทความที่ตรงกัน' : 'No assignments found' ?></h5>
      </div>
    <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($assignments as $ra):
          $isOverdue  = $ra['due_date'] && strtotime($ra['due_date']) < time() && $ra['status'] !== 'completed';
          $sInfo      = $statuses[$ra['status']] ?? ['label_th'=>$ra['status'],'label_en'=>$ra['status'],'color'=>'#6c757d'];
        ?>
          <div class="content-card" style="<?= $isOverdue ? 'border-left:4px solid #dc3545;' : '' ?>">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
              <div>
                <code style="font-size:.85rem;color:var(--blue-mid);"><?= e($ra['paper_code']) ?></code>
                <span class="ms-2 badge rounded-pill" style="background:<?= $sInfo['color'] ?>;color:#fff;font-size:.72rem;">
                  <?= $_lang==='th' ? $sInfo['label_th'] : $sInfo['label_en'] ?>
                </span>
                <?php if ($isOverdue): ?>
                  <span class="ms-1 badge rounded-pill" style="background:#dc3545;color:#fff;font-size:.72rem;">
                    <i class="fas fa-exclamation-triangle me-1"></i><?= $_lang==='th' ? 'เกินกำหนด' : 'Overdue' ?>
                  </span>
                <?php endif; ?>
              </div>
              <div class="text-end" style="font-size:.8rem;color:var(--gray-500);">
                <?= $_lang==='th' ? 'มอบหมาย' : 'Assigned' ?>: <?= humanDate($ra['assigned_at'], $_lang) ?>
                <?php if ($ra['due_date']): ?>
                  <br><?= $_lang==='th' ? 'กำหนด' : 'Due' ?>:
                  <span style="color:<?= $isOverdue ? '#dc3545' : 'inherit' ?>;font-weight:<?= $isOverdue ? '700' : '400' ?>;">
                    <?= humanDate($ra['due_date'], $_lang) ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <h5 style="font-size:1rem;font-weight:700;color:var(--blue-dark);margin-bottom:4px;">
              <?= e($_lang==='th' ? $ra['title_th'] : $ra['title_en']) ?>
            </h5>
            <div style="font-size:.82rem;color:var(--gray-500);margin-bottom:10px;">
              <?= e($_lang==='th' ? $ra['theme_th'] : $ra['theme_en']) ?>
            </div>
            <div style="font-size:.86rem;color:var(--gray-700);line-height:1.7;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
              <?= e($_lang==='th' ? $ra['abstract_th'] : $ra['abstract_en']) ?>
            </div>

            <div class="d-flex gap-2 flex-wrap align-items-center">
              <?php if ($ra['status'] === 'pending'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                  <input type="hidden" name="action" value="accept">
                  <input type="hidden" name="assignment_id" value="<?= (int)$ra['id'] ?>">
                  <button type="submit" class="btn-primary-custom" style="font-size:.82rem;padding:8px 18px;">
                    <i class="fas fa-check me-1"></i><?= $_lang==='th' ? 'รับงาน' : 'Accept' ?>
                  </button>
                </form>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                  <input type="hidden" name="action" value="decline">
                  <input type="hidden" name="assignment_id" value="<?= (int)$ra['id'] ?>">
                  <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill"
                          data-confirm="<?= $_lang==='th' ? 'ยืนยันการปฏิเสธงานนี้?' : 'Confirm declining this assignment?' ?>">
                    <i class="fas fa-times me-1"></i><?= $_lang==='th' ? 'ปฏิเสธ' : 'Decline' ?>
                  </button>
                </form>
              <?php elseif ($ra['status'] === 'accepted'): ?>
                <a href="<?= $appUrl ?>/reviewer/review.php?assignment_id=<?= (int)$ra['id'] ?>"
                   class="btn-primary-custom" style="font-size:.82rem;padding:8px 18px;">
                  <i class="fas fa-star me-1"></i><?= $_lang==='th' ? 'ประเมินบทความ' : 'Write Review' ?>
                </a>
              <?php elseif ($ra['status'] === 'completed'): ?>
                <a href="<?= $appUrl ?>/reviewer/review.php?assignment_id=<?= (int)$ra['id'] ?>"
                   class="btn-outline-custom" style="font-size:.82rem;">
                  <i class="fas fa-eye me-1"></i><?= $_lang==='th' ? 'ดูผลประเมิน' : 'View Review' ?>
                </a>
                <?php if ($ra['overall_score']): ?>
                  <span style="font-size:.85rem;font-weight:700;color:var(--blue-dark);">
                    <?= number_format($ra['overall_score'],1) ?>/10
                  </span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pg['total_pages'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
          <span style="font-size:.85rem;color:var(--gray-500);">
            <?= t('common.page') ?> <?= $pg['page'] ?> <?= t('common.of') ?> <?= $pg['total_pages'] ?>
          </span>
          <div class="d-flex gap-2">
            <?php if ($pg['has_prev']): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$pg['page']-1])) ?>"
                 class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="fas fa-chevron-left"></i>
              </a>
            <?php endif; ?>
            <?php if ($pg['has_next']): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$pg['page']+1])) ?>"
                 class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
