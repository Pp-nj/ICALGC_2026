<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('author');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;

// Fetch stats
try {
    $db  = Database::getInstance();
    $uid = $user['id'];

    $statsQuery = $db->prepare("
        SELECT
            COUNT(*) AS total,
            COUNT(*) FILTER (WHERE status_code IN ('under_review','screening')) AS under_review,
            COUNT(*) FILTER (WHERE status_code = 'accepted') AS accepted,
            COUNT(*) FILTER (WHERE status_code = 'published') AS published,
            COUNT(*) FILTER (WHERE status_code = 'revision_required') AS revisions
        FROM papers WHERE submitter_id = :uid
    ");
    $statsQuery->execute([':uid' => $uid]);
    $stats = $statsQuery->fetch();

    // Recent papers
    $recentStmt = $db->prepare("
        SELECT p.*, ct.name_th AS theme_th, ct.name_en AS theme_en
        FROM papers p
        JOIN conference_themes ct ON ct.id = p.theme_id
        WHERE p.submitter_id = :uid
        ORDER BY p.submitted_at DESC
        LIMIT 5
    ");
    $recentStmt->execute([':uid' => $uid]);
    $recentPapers = $recentStmt->fetchAll();

    // Recent notifications
    $notifStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
    $notifStmt->execute([':uid' => $uid]);
    $notifications = $notifStmt->fetchAll();

} catch (\Throwable $e) {
    $stats = ['total'=>0,'under_review'=>0,'accepted'=>0,'published'=>0,'revisions'=>0];
    $recentPapers = []; $notifications = [];
}

$pageTitle = t('author.dashboard');
$activeMenu = 'dashboard';
$bodyClass  = '';
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
    <!-- Header -->
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title">
          <?= $_lang==='th'?'สวัสดี, ':'Hello, ' ?><?= e($user['name']) ?> 👋
        </h1>
        <p class="dash-breadcrumb"><?= e($pageTitle) ?> — <?= date('j F Y') ?></p>
      </div>
      <div class="d-flex gap-2">
        <a href="?lang=th" class="btn btn-sm rounded-pill fw-bold <?= $_lang==='th'?'btn-warning':'btn-outline-secondary' ?>">TH</a>
        <a href="?lang=en" class="btn btn-sm rounded-pill fw-bold <?= $_lang==='en'?'btn-warning':'btn-outline-secondary' ?>">EN</a>
      </div>
    </div>

    <?= flashHtml() ?>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['total'] ?></div>
            <div class="stat-label"><?= t('author.total_papers') ?></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card gold">
          <div class="stat-icon"><i class="fas fa-search"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['under_review'] ?></div>
            <div class="stat-label"><?= t('author.under_review') ?></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card green">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['accepted'] ?></div>
            <div class="stat-label"><?= t('author.accepted') ?></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left-color:#0f5132;">
          <div class="stat-icon" style="background:#d1e7dd;color:#0f5132;"><i class="fas fa-globe"></i></div>
          <div>
            <div class="stat-number"><?= (int)$stats['published'] ?></div>
            <div class="stat-label"><?= t('author.published') ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Revision Reminder -->
    <?php if ((int)$stats['revisions'] > 0): ?>
      <div class="alert d-flex align-items-center gap-3 mb-4" style="background:#fff3cd;border-left:4px solid var(--warning);border-radius:var(--radius);color:#856404;">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <div>
          <strong><?= $_lang==='th'?'บทความต้องการแก้ไข':'Papers Require Revision' ?></strong><br>
          <span style="font-size:.88rem;">
            <?= $_lang==='th'
              ? "คุณมี {$stats['revisions']} บทความที่ต้องแก้ไข กรุณาตรวจสอบความเห็นของผู้ทรงคุณวุฒิ"
              : "You have {$stats['revisions']} paper(s) requiring revision. Please review the comments." ?>
          </span>
          <a href="<?= $appUrl ?>/author/my-papers.php" class="ms-2 fw-bold" style="color:inherit;">
            <?= $_lang==='th'?'ดูบทความ':'View Papers' ?> →
          </a>
        </div>
      </div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- Recent Papers -->
      <div class="col-lg-7">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title"><i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= t('author.my_papers') ?></span>
            <a href="<?= $appUrl ?>/author/submit.php" class="btn-primary-custom" style="padding:8px 18px;font-size:.82rem;">
              <i class="fas fa-plus me-1"></i><?= $_lang==='th'?'ส่งใหม่':'New Submission' ?>
            </a>
          </div>

          <?php if (empty($recentPapers)): ?>
            <div class="p-5 text-center">
              <i class="fas fa-file-circle-plus fa-3x mb-3" style="color:var(--gray-200);"></i>
              <h5 style="color:var(--gray-500);font-size:1rem;"><?= $_lang==='th'?'ยังไม่มีบทความ':'No papers submitted yet' ?></h5>
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
                    <th><?= $_lang==='th'?'ชื่อบทความ':'Title' ?></th>
                    <th><?= t('paper.status') ?></th>
                    <th><?= t('paper.actions') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentPapers as $p): ?>
                    <tr>
                      <td><code style="font-size:.8rem;color:var(--blue-mid);"><?= e($p['paper_code']) ?></code></td>
                      <td style="max-width:200px;">
                        <div style="font-weight:600;font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                          <?= e($_lang==='th'?$p['title_th']:$p['title_en']) ?>
                        </div>
                        <div style="font-size:.76rem;color:var(--gray-500);"><?= humanDate($p['submitted_at']) ?></div>
                      </td>
                      <td><?= statusBadge($p['status_code']) ?></td>
                      <td>
                        <a href="<?= $appUrl ?>/author/paper-detail.php?id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.75rem;">
                          <i class="fas fa-eye me-1"></i><?= t('paper.view_details') ?>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="p-3 text-end" style="border-top:1px solid var(--gray-200);">
              <a href="<?= $appUrl ?>/author/my-papers.php" style="font-size:.85rem;color:var(--blue-mid);">
                <?= $_lang==='th'?'ดูทั้งหมด':'View All' ?> <i class="fas fa-arrow-right ms-1"></i>
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Notifications -->
      <div class="col-lg-5">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title"><i class="fas fa-bell me-2" style="color:var(--gold);"></i><?= t('notif.title') ?></span>
          </div>
          <?php if (empty($notifications)): ?>
            <div class="p-4 text-center" style="color:var(--gray-500);">
              <i class="far fa-bell-slash fa-2x mb-2 d-block"></i>
              <?= t('notif.no_notif') ?>
            </div>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
              <div class="notif-item <?= !$n['is_read']?'unread':'' ?>">
                <div class="notif-item-title"><?= e($_lang==='th'?$n['title_th']:$n['title_en']) ?></div>
                <div class="notif-item-msg"><?= e($_lang==='th'?$n['message_th']:$n['message_en']) ?></div>
                <div class="notif-item-time"><?= humanDate($n['created_at']) ?></div>
              </div>
            <?php endforeach; ?>
            <div class="p-3 text-center" style="border-top:1px solid var(--gray-200);">
              <a href="<?= $appUrl ?>/author/notifications.php" style="font-size:.85rem;color:var(--blue-mid);">
                <?= t('notif.view_all') ?> <i class="fas fa-arrow-right ms-1"></i>
              </a>
            </div>
          <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="content-card mt-4">
          <div class="content-card-title"><i class="fas fa-bolt me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'การดำเนินการด่วน':'Quick Actions' ?></div>
          <div class="d-flex flex-column gap-2">
            <a href="<?= $appUrl ?>/author/submit.php" class="btn-primary-custom text-center">
              <i class="fas fa-file-upload me-2"></i><?= t('author.submit_paper') ?>
            </a>
            <a href="<?= $appUrl ?>/author/certificates.php" class="btn-outline-custom text-center">
              <i class="fas fa-certificate me-2"></i><?= t('author.certificates') ?>
            </a>
            <a href="<?= $appUrl ?>/author/profile.php" class="btn-outline-custom text-center">
              <i class="fas fa-user-edit me-2"></i><?= $_lang==='th'?'แก้ไขข้อมูลส่วนตัว':'Edit Profile' ?>
            </a>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
