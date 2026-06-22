<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$statusFilter = sanitize(get('status'));
$themeFilter  = intGet('theme_id');
$search       = sanitize(get('q'));
$page         = max(1, intGet('page', 1));
$perPage      = 15;

$where  = ['1=1'];
$params = [];

if ($statusFilter) {
    $where[]       = "p.status_code = :sc";
    $params[':sc'] = $statusFilter;
}
if ($themeFilter) {
    $where[]        = "p.theme_id = :tid";
    $params[':tid'] = $themeFilter;
}
if ($search) {
    $where[]       = "(p.paper_code ILIKE :q OR p.title_th ILIKE :q OR p.title_en ILIKE :q OR u.name ILIKE :q)";
    $params[':q']  = "%{$search}%";
}
$whereStr = implode(' AND ', $where);

try {
    $db = Database::getInstance();

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM papers p JOIN users u ON u.id = p.submitter_id WHERE {$whereStr}");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $pg = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT p.*, u.name AS submitter_name, u.affiliation,
               ct.name_th AS theme_th, ct.name_en AS theme_en,
               ps.color_hex,
               (SELECT COUNT(*) FROM review_assignments ra WHERE ra.paper_id = p.id AND ra.status = 'completed') AS review_count
        FROM papers p
        JOIN users u ON u.id = p.submitter_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        JOIN paper_statuses ps ON ps.code = p.status_code
        WHERE {$whereStr}
        ORDER BY p.submitted_at DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $papers = $stmt->fetchAll();

    $statuses = $db->query("SELECT * FROM paper_statuses ORDER BY progress_step")->fetchAll();
    $themes   = $db->query("SELECT * FROM conference_themes ORDER BY id")->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $papers = []; $total = 0; $pg = paginate(0,$perPage,1); $statuses = []; $themes = [];
}

$pageTitle  = $_lang==='th' ? 'จัดการบทความ' : 'Manage Papers';
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
    <div class="dash-header">
      <h1 class="dash-title"><i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
      <p class="dash-breadcrumb"><?= $total ?> <?= $_lang==='th' ? 'บทความ' : 'paper(s)' ?></p>
    </div>

    <?= flashHtml() ?>

    <!-- Search & Filters -->
    <form method="GET" class="content-card mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-bold" style="font-size:.82rem;"><?= $_lang==='th' ? 'ค้นหา' : 'Search' ?></label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" name="q" class="form-control" placeholder="<?= $_lang==='th' ? 'รหัส, ชื่อบทความ, ผู้แต่ง...' : 'Code, title, author...' ?>"
                   value="<?= e($search) ?>">
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold" style="font-size:.82rem;"><?= t('paper.status') ?></label>
          <select name="status" class="form-select">
            <option value=""><?= $_lang==='th' ? 'ทุกสถานะ' : 'All Statuses' ?></option>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s['code'] ?>" <?= $statusFilter===$s['code']?'selected':'' ?>>
                <?= e($_lang==='th'?$s['name_th']:$s['name_en']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold" style="font-size:.82rem;"><?= $_lang==='th' ? 'หัวข้อ' : 'Theme' ?></label>
          <select name="theme_id" class="form-select">
            <option value=""><?= $_lang==='th' ? 'ทุกหัวข้อ' : 'All Themes' ?></option>
            <?php foreach ($themes as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $themeFilter===$t['id']?'selected':'' ?>>
                <?= e($_lang==='th'?$t['name_th']:$t['name_en']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn-primary-custom flex-fill" style="font-size:.85rem;padding:10px;">
            <i class="fas fa-search"></i>
          </button>
          <a href="?" class="btn btn-outline-secondary rounded-pill" style="padding:10px 14px;">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </div>
    </form>

    <!-- Status Filter Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a href="?<?= http_build_query(array_diff_key($_GET, ['status'=>'','page'=>''])) ?>"
         class="btn btn-sm rounded-pill fw-bold <?= !$statusFilter?'btn-warning':'btn-outline-secondary' ?>"
         style="<?= !$statusFilter?'background:var(--gold);color:var(--blue-dark);border-color:var(--gold);':'' ?>">
        <?= t('common.all') ?>
      </a>
      <?php foreach ($statuses as $s): ?>
        <a href="?status=<?= urlencode($s['code']) ?><?= $themeFilter?"&theme_id=$themeFilter":'' ?><?= $search?"&q=".urlencode($search):'' ?>"
           class="btn btn-sm rounded-pill fw-bold"
           style="background:<?= $statusFilter===$s['code']?$s['color_hex']:'transparent' ?>;
                  color:<?= $statusFilter===$s['code']?'#fff':'var(--gray-700)' ?>;
                  border:2px solid <?= $s['color_hex'] ?>;">
          <?= e($_lang==='th'?$s['name_th']:$s['name_en']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Papers Table -->
    <div class="table-card">
      <?php if (empty($papers)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-search fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5>
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
                <th><?= $_lang==='th' ? 'วันที่' : 'Date' ?></th>
                <th><?= t('paper.status') ?></th>
                <th><?= $_lang==='th' ? 'ประเมิน' : 'Reviews' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($papers as $p): ?>
                <tr>
                  <td><code style="font-size:.78rem;color:var(--blue-mid);"><?= e($p['paper_code']) ?></code></td>
                  <td style="max-width:180px;">
                    <div style="font-weight:600;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= e($_lang==='th'?$p['title_th']:$p['title_en']) ?>
                    </div>
                  </td>
                  <td style="font-size:.82rem;"><?= e($p['submitter_name']) ?></td>
                  <td style="font-size:.78rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= e($_lang==='th'?$p['theme_th']:$p['theme_en']) ?>
                  </td>
                  <td style="font-size:.78rem;white-space:nowrap;"><?= humanDate($p['submitted_at'], $_lang) ?></td>
                  <td><?= statusBadge($p['status_code']) ?></td>
                  <td style="text-align:center;">
                    <span style="font-weight:700;font-size:.85rem;color:<?= (int)$p['review_count']>0?'#198754':'var(--gray-400)' ?>;">
                      <?= (int)$p['review_count'] ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="<?= $appUrl ?>/admin/paper-detail.php?id=<?= (int)$p['id'] ?>"
                         class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;">
                        <i class="fas fa-eye"></i>
                      </a>
                      <?php if (in_array($p['status_code'], ['submitted','screening'])): ?>
                        <a href="<?= $appUrl ?>/admin/assign-reviewer.php?paper_id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.72rem;">
                          <i class="fas fa-user-plus"></i>
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
                <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']-1])) ?>"
                   class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-chevron-left"></i></a>
              <?php endif; ?>
              <?php if ($pg['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']+1])) ?>"
                   class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-chevron-right"></i></a>
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
