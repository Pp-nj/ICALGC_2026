<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('author');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

// Filters
$statusFilter = sanitize(get('status'));
$page         = max(1, intGet('page', 1));
$perPage      = 10;

$where  = ['p.submitter_id = :uid'];
$params = [':uid' => $uid];

if ($statusFilter) {
    $where[]           = "p.status_code = :sc";
    $params[':sc']     = $statusFilter;
}

$whereStr = implode(' AND ', $where);

try {
    $db   = Database::getInstance();
    $total = (int)$db->prepare("SELECT COUNT(*) FROM papers p WHERE {$whereStr}")->execute($params) ? null : 0;

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM papers p WHERE {$whereStr}");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $pg = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT p.*, ct.name_th AS theme_th, ct.name_en AS theme_en
        FROM papers p
        JOIN conference_themes ct ON ct.id = p.theme_id
        WHERE {$whereStr}
        ORDER BY p.submitted_at DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $papers = $stmt->fetchAll();

    // Statuses for filter
    $statuses = $db->query("SELECT * FROM paper_statuses ORDER BY progress_step")->fetchAll();

} catch (\Throwable $e) {
    $papers = []; $total = 0; $pg = paginate(0, $perPage, 1); $statuses = [];
    error_log($e->getMessage());
}

$pageTitle  = t('author.my_papers');
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
        <h1 class="dash-title"><i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb"><?= $total ?> <?= $_lang==='th'?'บทความ':'paper(s)' ?></p>
      </div>
      <a href="<?= $appUrl ?>/author/submit.php" class="btn-primary-custom">
        <i class="fas fa-file-upload me-2"></i><?= t('author.submit_paper') ?>
      </a>
    </div>

    <?= flashHtml() ?>

    <!-- Status Filter Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a href="?" class="btn btn-sm rounded-pill fw-bold <?= !$statusFilter?'btn-warning':'btn-outline-secondary' ?>"
         style="<?= !$statusFilter?'background:var(--gold);color:var(--blue-dark);border-color:var(--gold);':'' ?>">
        <?= t('common.all') ?>
      </a>
      <?php foreach ($statuses as $s):
        $name = $_lang==='th' ? $s['name_th'] : $s['name_en'];
      ?>
        <a href="?status=<?= urlencode($s['code']) ?>"
           class="btn btn-sm rounded-pill fw-bold"
           style="background:<?= $statusFilter===$s['code']?$s['color_hex']:'transparent' ?>;
                  color:<?= $statusFilter===$s['code']?'#fff':'var(--gray-700)' ?>;
                  border:2px solid <?= $s['color_hex'] ?>;">
          <?= e($name) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Papers Table -->
    <div class="table-card">
      <div class="table-card-header">
        <span class="table-card-title"><i class="fas fa-list me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></span>
      </div>

      <?php if (empty($papers)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-folder-open fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5>
          <a href="<?= $appUrl ?>/author/submit.php" class="btn-primary-custom mt-3">
            <i class="fas fa-file-upload me-2"></i><?= t('author.submit_paper') ?>
          </a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= t('paper.code') ?></th>
                <th><?= $_lang==='th'?'ชื่อบทความ':'Paper Title' ?></th>
                <th><?= $_lang==='th'?'หัวข้อ':'Theme' ?></th>
                <th><?= t('paper.submitted_date') ?></th>
                <th><?= t('paper.status') ?></th>
                <th><?= t('paper.actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($papers as $p): ?>
                <tr>
                  <td>
                    <code style="font-size:.8rem;color:var(--blue-mid);"><?= e($p['paper_code']) ?></code>
                  </td>
                  <td style="max-width:220px;">
                    <div style="font-weight:600;font-size:.88rem;">
                      <?= e($_lang==='th'?$p['title_th']:$p['title_en']) ?>
                    </div>
                    <?php if ($_lang==='th'): ?>
                      <div style="font-size:.76rem;color:var(--gray-500);"><?= e($p['title_en']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;max-width:160px;">
                    <?= e($_lang==='th'?$p['theme_th']:$p['theme_en']) ?>
                  </td>
                  <td style="font-size:.82rem;white-space:nowrap;">
                    <?= humanDate($p['submitted_at']) ?>
                  </td>
                  <td><?= statusBadge($p['status_code']) ?></td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <a href="<?= $appUrl ?>/author/paper-detail.php?id=<?= (int)$p['id'] ?>"
                         class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.75rem;">
                        <i class="fas fa-eye"></i>
                      </a>
                      <?php if (in_array($p['status_code'], ['revision_required'])): ?>
                        <a href="<?= $appUrl ?>/author/revise.php?id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-warning rounded-pill" style="font-size:.75rem;color:#fff;">
                          <i class="fas fa-edit"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($p['status_code'] === 'published'): ?>
                        <a href="<?= $appUrl ?>/download.php?paper_id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-success rounded-pill" style="font-size:.75rem;">
                          <i class="fas fa-download"></i>
                        </a>
                      <?php endif; ?>
                    </div>
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

    <!-- Progress Legend -->
    <div class="content-card mt-4">
      <div class="content-card-title"><i class="fas fa-info-circle me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'คำอธิบายสถานะ':'Status Legend' ?></div>
      <div class="d-flex flex-wrap gap-3">
        <?php foreach ($statuses as $s): ?>
          <div class="d-flex align-items-center gap-2">
            <span class="status-badge" style="background:<?= e($s['color_hex']) ?>;color:#fff;">
              <?= e($_lang==='th'?$s['name_th']:$s['name_en']) ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
