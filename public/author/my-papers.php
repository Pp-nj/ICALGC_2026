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

    <!-- Horizontal Stepper: Submission Pipeline -->
    <div class="content-card mb-4" style="padding:20px 28px;">
      <div class="content-card-title mb-3">
        <i class="fas fa-route me-2" style="color:var(--gold);"></i>
        <?= $_lang==='th' ? 'ขั้นตอนการดำเนินการบทความ' : 'Submission Pipeline' ?>
      </div>
      <?php
        $rejectedStatus = null;
        $mainStatuses   = [];
        foreach ($statuses as $s) {
          if ($s['code'] === 'rejected') { $rejectedStatus = $s; }
          else { $mainStatuses[] = $s; }
        }
      ?>
      <?php $n = count($mainStatuses); ?>
      <div class="progress-track pipeline-track" style="position:relative;margin:0;padding:8px 0 70px;">
        <?php if ($n > 1): ?>
          <div style="position:absolute;top:25px;left:<?= (0.5 / $n * 100) ?>%;right:<?= (0.5 / $n * 100) ?>%;height:2px;background:var(--gray-200);z-index:0;"></div>
        <?php endif; ?>
        <?php foreach ($mainStatuses as $i => $s):
          $sName = $_lang==='th' ? $s['name_th'] : $s['name_en'];
        ?>
          <div class="progress-step" style="min-width:70px;position:relative;z-index:1;">
            <div class="progress-circle"
                 style="background:<?= e($s['color_hex']) ?>;border-color:<?= e($s['color_hex']) ?>;color:#fff;width:34px;height:34px;font-size:.75rem;">
              <?= (int)$s['progress_step'] ?>
            </div>
            <div class="progress-label" style="color:var(--gray-700);font-size:.68rem;margin-top:6px;">
              <?= e($sName) ?>
            </div>

            <?php if ($s['code'] === 'accepted' && $rejectedStatus): ?>
              <div style="position:absolute;top:56px;left:50%;width:2px;height:16px;background:var(--gray-200);"></div>
              <div style="position:absolute;top:72px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;min-width:70px;">
                <div class="progress-circle"
                     style="background:<?= e($rejectedStatus['color_hex']) ?>22;border-color:<?= e($rejectedStatus['color_hex']) ?>;color:<?= e($rejectedStatus['color_hex']) ?>;width:34px;height:34px;font-size:.75rem;">
                  <?= (int)$rejectedStatus['progress_step'] ?>
                </div>
                <div class="progress-label" style="color:var(--gray-700);font-size:.68rem;margin-top:6px;white-space:nowrap;">
                  <?= e($_lang==='th' ? $rejectedStatus['name_th'] : $rejectedStatus['name_en']) ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Status Filter Dropdown -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
      <label class="fw-bold" style="font-size:.85rem;color:var(--blue-dark);white-space:nowrap;">
        <i class="fas fa-filter me-1" style="color:var(--gold);"></i>
        <?= $_lang==='th' ? 'กรองตามสถานะ' : 'Filter by Status' ?>
      </label>
      <div class="dropdown">
        <?php
          $selectedLabel = $_lang==='th' ? 'ทั้งหมด' : 'All';
          $selectedColor = 'var(--blue-dark)';
          foreach ($statuses as $s) {
            if ($statusFilter === $s['code']) {
              $selectedLabel = $_lang==='th' ? $s['name_th'] : $s['name_en'];
              $selectedColor = $s['color_hex'];
            }
          }
        ?>
        <button class="btn btn-sm dropdown-toggle fw-bold rounded-pill px-4"
                type="button" data-bs-toggle="dropdown" aria-expanded="false"
                style="border:2px solid <?= $selectedColor ?>;color:<?= $selectedColor ?>;background:#fff;min-width:160px;text-align:left;">
          <?= e($selectedLabel) ?>
        </button>
        <ul class="dropdown-menu shadow-sm rounded" style="min-width:200px;">
          <li>
            <a class="dropdown-item fw-bold <?= !$statusFilter ? 'active' : '' ?>" href="?"
               style="font-size:.88rem;">
              <i class="fas fa-layer-group me-2" style="color:var(--blue-mid);"></i>
              <?= $_lang==='th' ? 'ทั้งหมด' : 'All Statuses' ?>
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <?php foreach ($statuses as $s):
            $sName = $_lang==='th' ? $s['name_th'] : $s['name_en'];
          ?>
            <li>
              <a class="dropdown-item <?= $statusFilter===$s['code'] ? 'active' : '' ?>"
                 href="?status=<?= urlencode($s['code']) ?>"
                 style="font-size:.88rem;">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= e($s['color_hex']) ?>;margin-right:8px;"></span>
                <?= e($sName) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
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
              <?php foreach ($papers as $p):
                $detailUrl   = $appUrl . '/author/paper-detail.php?id='  . (int)$p['id'];
                $historyUrl  = $appUrl . '/author/paper-history.php?id=' . (int)$p['id'];
              ?>
                <tr style="cursor:pointer;" onclick="window.location='<?= $historyUrl ?>'">
                  <td onclick="event.stopPropagation()">
                    <a href="<?= $historyUrl ?>" style="text-decoration:none;">
                      <code style="font-size:.8rem;color:var(--blue-mid);"><?= e($p['paper_code']) ?></code>
                    </a>
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
                  <td onclick="event.stopPropagation()">
                    <div class="d-flex gap-1 flex-wrap">
                      <a href="<?= $historyUrl ?>"
                         class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.75rem;"
                         title="<?= $_lang==='th' ? 'ประวัติการส่ง' : 'Submission History' ?>">
                        <i class="fas fa-history me-1"></i><?= $_lang==='th' ? 'ประวัติ' : 'History' ?>
                      </a>
                      <?php if (in_array($p['status_code'], ['revision_required'])): ?>
                        <a href="<?= $appUrl ?>/author/revise.php?id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-warning rounded-pill" style="font-size:.75rem;color:#fff;"
                           title="<?= $_lang==='th' ? 'ส่งบทความแก้ไข' : 'Submit Revision' ?>">
                          <i class="fas fa-edit"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($p['status_code'] === 'published'): ?>
                        <a href="<?= $appUrl ?>/download.php?paper_id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-success rounded-pill" style="font-size:.75rem;"
                           title="<?= $_lang==='th' ? 'ดาวน์โหลด' : 'Download' ?>">
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


  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
