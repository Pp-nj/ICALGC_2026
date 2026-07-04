<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('reviewer');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

$page    = max(1, intGet('page', 1));
$perPage = 10;

try {
    $db = Database::getInstance();

    $cntStmt = $db->prepare("
        SELECT COUNT(*) FROM review_assignments ra
        JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.reviewer_id = :uid AND ra.assignment_status = 'completed'
    ");
    $cntStmt->execute([':uid' => $uid]);
    $total = (int)$cntStmt->fetchColumn();

    $pg = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT ra.id AS assignment_id, ra.assigned_at, ra.due_date,
               p.paper_code, p.title_th, p.title_en,
               ct.name_th AS theme_th, ct.name_en AS theme_en,
               r.score_overall, r.recommendation, r.reviewed_at,
               r.score_relevance, r.score_methodology, r.score_originality,
               r.score_contribution, r.score_writing
        FROM review_assignments ra
        JOIN papers p ON p.id = ra.paper_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.reviewer_id = :uid AND ra.assignment_status = 'completed'
        ORDER BY r.reviewed_at DESC
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':uid', $uid, \PDO::PARAM_INT);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll();

    // Summary stats
    $sStmt = $db->prepare("
        SELECT
            COUNT(*) AS total,
            ROUND(AVG(r.score_overall), 2) AS avg_score,
            SUM(CASE WHEN r.recommendation = 'accept' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN r.recommendation IN ('minor_revision','major_revision') THEN 1 ELSE 0 END) AS revised,
            SUM(CASE WHEN r.recommendation = 'reject' THEN 1 ELSE 0 END) AS rejected
        FROM review_assignments ra
        JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.reviewer_id = :uid AND ra.assignment_status = 'completed'
    ");
    $sStmt->execute([':uid' => $uid]);
    $summary = $sStmt->fetch();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $reviews = []; $total = 0; $pg = paginate(0, $perPage, 1); $summary = [];
}

$pageTitle  = $_lang==='th' ? 'ประวัติการประเมิน' : 'Review History';
$activeMenu = 'history';

$recColors = ['accept'=>'#198754','minor_revision'=>'#fd7e14','major_revision'=>'#dc3545','reject'=>'#6c757d'];
$recLabels = ['accept'=>['th'=>'ยอมรับ','en'=>'Accept'],'minor_revision'=>['th'=>'แก้ไขเล็กน้อย','en'=>'Minor Revision'],'major_revision'=>['th'=>'แก้ไขหลัก','en'=>'Major Revision'],'reject'=>['th'=>'ปฏิเสธ','en'=>'Reject']];
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
        <i class="fas fa-history me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
      </h1>
      <p class="dash-breadcrumb"><?= $total ?> <?= $_lang==='th' ? 'บทความที่ประเมินแล้ว' : 'completed review(s)' ?></p>
    </div>

    <!-- Summary Stats -->
    <?php if ($total > 0): ?>
    <div class="row g-3 mb-4">
      <div class="col-sm-3">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
          <div><div class="stat-number"><?= (int)$summary['total'] ?></div><div class="stat-label"><?= $_lang==='th' ? 'ทั้งหมด' : 'Total' ?></div></div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="stat-card gold">
          <div class="stat-icon"><i class="fas fa-star"></i></div>
          <div><div class="stat-number"><?= number_format($summary['avg_score'], 1) ?></div><div class="stat-label"><?= $_lang==='th' ? 'คะแนนเฉลี่ย' : 'Avg Score' ?></div></div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="stat-card green">
          <div class="stat-icon"><i class="fas fa-check"></i></div>
          <div><div class="stat-number"><?= (int)$summary['accepted'] ?></div><div class="stat-label"><?= $_lang==='th' ? 'ยอมรับ' : 'Accepted' ?></div></div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="stat-card" style="border-left-color:#dc3545;">
          <div class="stat-icon" style="background:#f8d7da;color:#dc3545;"><i class="fas fa-times"></i></div>
          <div><div class="stat-number"><?= (int)$summary['rejected'] ?></div><div class="stat-label"><?= $_lang==='th' ? 'ปฏิเสธ' : 'Rejected' ?></div></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Reviews Table -->
    <div class="table-card">
      <?php if (empty($reviews)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-history fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= $_lang==='th' ? 'ยังไม่มีประวัติการประเมิน' : 'No review history yet' ?></h5>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= t('paper.code') ?></th>
                <th><?= $_lang==='th' ? 'บทความ' : 'Paper' ?></th>
                <th><?= $_lang==='th' ? 'คะแนน' : 'Score' ?></th>
                <th><?= $_lang==='th' ? 'ข้อเสนอแนะ' : 'Recommendation' ?></th>
                <th><?= $_lang==='th' ? 'วันที่ส่ง' : 'Submitted' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reviews as $rv): ?>
                <tr>
                  <td><code style="font-size:.8rem;color:var(--blue-mid);"><?= e($rv['paper_code']) ?></code></td>
                  <td style="max-width:200px;">
                    <div style="font-weight:600;font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= e($_lang==='th' ? $rv['title_th'] : $rv['title_en']) ?>
                    </div>
                    <div style="font-size:.75rem;color:var(--gray-500);"><?= e($_lang==='th' ? $rv['theme_th'] : $rv['theme_en']) ?></div>
                  </td>
                  <td>
                    <div class="text-center" style="background:var(--blue-dark);color:#fff;border-radius:50%;width:42px;height:42px;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;">
                      <?= number_format($rv['score_overall'], 1) ?>
                    </div>
                  </td>
                  <td>
                    <?php if ($rv['recommendation']): ?>
                      <span class="badge rounded-pill" style="background:<?= $recColors[$rv['recommendation']] ?? '#6c757d' ?>;color:#fff;font-size:.75rem;">
                        <?= $_lang==='th' ? ($recLabels[$rv['recommendation']]['th'] ?? '') : ($recLabels[$rv['recommendation']]['en'] ?? '') ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;"><?= humanDate($rv['reviewed_at'], $_lang) ?></td>
                  <td>
                    <a href="<?= $appUrl ?>/reviewer/review.php?assignment_id=<?= (int)$rv['assignment_id'] ?>"
                       class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.75rem;">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="p-3 d-flex justify-content-between align-items-center" style="border-top:1px solid var(--gray-200);">
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
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
