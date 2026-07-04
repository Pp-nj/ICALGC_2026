<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'mark_read') {
    Auth::verifyCsrf(post('csrf_token'));
    $nid = intPost('notif_id');
    if ($nid) {
        try {
            $db = Database::getInstance();
            $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = :nid AND user_id = :uid")
               ->execute([':nid' => $nid, ':uid' => $uid]);
        } catch (\Throwable $e) { error_log($e->getMessage()); }
    }
    redirect($appUrl . '/admin/notifications.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'mark_all') {
    Auth::verifyCsrf(post('csrf_token'));
    try {
        $db = Database::getInstance();
        $db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = :uid")
           ->execute([':uid' => $uid]);
    } catch (\Throwable $e) { error_log($e->getMessage()); }
    flashSet('success', $_lang==='th' ? 'อ่านทั้งหมดแล้ว' : 'All notifications marked as read.');
    redirect($appUrl . '/admin/notifications.php');
}

if (intGet('mark_all_read') === 1) {
    try {
        $db = Database::getInstance();
        $db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = :uid")
           ->execute([':uid' => $uid]);
    } catch (\Throwable $e) { error_log($e->getMessage()); }
    flashSet('success', $_lang==='th' ? 'อ่านทั้งหมดแล้ว' : 'All notifications marked as read.');
    redirect($appUrl . '/admin/notifications.php');
}

$page    = max(1, intGet('page', 1));
$perPage = 15;

try {
    $db = Database::getInstance();

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid");
    $cntStmt->execute([':uid' => $uid]);
    $total = (int)$cntStmt->fetchColumn();
    $pg    = paginate($total, $perPage, $page);

    $nStmt = $db->prepare("
        SELECT * FROM notifications
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT :lim OFFSET :off
    ");
    $nStmt->bindValue(':uid', $uid, \PDO::PARAM_INT);
    $nStmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $nStmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $nStmt->execute();
    $notifications = $nStmt->fetchAll();

    $ucStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = FALSE");
    $ucStmt->execute([':uid' => $uid]);
    $unreadCount = (int)$ucStmt->fetchColumn();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $notifications = []; $total = 0; $pg = paginate(0, $perPage, 1); $unreadCount = 0;
}

$pageTitle  = $_lang==='th' ? 'การแจ้งเตือน' : 'Notifications';
$activeMenu = 'notifications';

$typeIcons = [
    'paper_submitted'    => ['fas fa-file-upload',  '#0057b7'],
    'review_assigned'    => ['fas fa-search',        '#6f42c1'],
    'review_result'      => ['fas fa-star',          '#fd7e14'],
    'paper_accepted'     => ['fas fa-check-circle',  '#198754'],
    'paper_published'    => ['fas fa-globe',         '#0f5132'],
    'revision_required'  => ['fas fa-edit',          '#dc3545'],
    'revision_submitted' => ['fas fa-paper-plane',   '#17a2b8'],
    'system'             => ['fas fa-bell',          '#6c757d'],
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
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_admin.php'; ?>

  <main class="dashboard-content">
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title">
          <i class="fas fa-bell me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
          <?php if ($unreadCount > 0): ?>
            <span class="badge rounded-pill ms-2" style="background:var(--gold);color:var(--blue-dark);font-size:.75rem;">
              <?= $unreadCount ?>
            </span>
          <?php endif; ?>
        </h1>
        <p class="dash-breadcrumb"><?= $total ?> <?= $_lang==='th' ? 'การแจ้งเตือน' : 'notification(s)' ?></p>
      </div>
      <?php if ($unreadCount > 0): ?>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="action" value="mark_all">
          <button type="submit" class="btn-outline-custom">
            <i class="fas fa-check-double me-2"></i><?= t('notif.mark_all') ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <?= flashHtml() ?>

    <div class="table-card">
      <?php if (empty($notifications)): ?>
        <div class="p-5 text-center">
          <i class="far fa-bell-slash fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= t('notif.no_notif') ?></h5>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $n):
          [$icon, $color] = $typeIcons[$n['type']] ?? ['fas fa-bell', '#6c757d'];
          $isUnread = !$n['is_read'];
        ?>
          <div class="notif-item <?= $isUnread ? 'unread' : '' ?> d-flex align-items-start gap-3"
               style="padding:16px 20px;<?= $isUnread ? 'background:#f0f4ff;' : '' ?>">
            <div style="width:40px;height:40px;background:<?= $color ?>22;color:<?= $color ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
              <i class="<?= $icon ?>" style="font-size:.9rem;"></i>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div>
                  <div style="font-weight:<?= $isUnread ? '700' : '600' ?>;font-size:.9rem;color:var(--blue-dark);">
                    <?= e($_lang==='th' ? $n['title_th'] : $n['title_en']) ?>
                    <?php if ($isUnread): ?>
                      <span class="badge rounded-pill ms-2" style="background:var(--gold);color:var(--blue-dark);font-size:.65rem;">NEW</span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:.85rem;color:var(--gray-600);margin-top:3px;line-height:1.6;">
                    <?= e($_lang==='th' ? $n['message_th'] : $n['message_en']) ?>
                  </div>
                </div>
                <div class="text-end flex-shrink-0">
                  <div style="font-size:.75rem;color:var(--gray-400);white-space:nowrap;"><?= humanDate($n['created_at'], $_lang) ?></div>
                  <?php if ($isUnread): ?>
                    <form method="POST" class="mt-1">
                      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                      <input type="hidden" name="action" value="mark_read">
                      <input type="hidden" name="notif_id" value="<?= (int)$n['id'] ?>">
                      <button type="submit" class="btn btn-link p-0" style="font-size:.75rem;color:var(--blue-mid);">
                        <i class="fas fa-check me-1"></i><?= t('notif.mark_read') ?>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
              <?php if (!empty($n['related_paper_id'])): ?>
                <div class="mt-2">
                  <a href="<?= $appUrl ?>/admin/paper-detail.php?id=<?= (int)$n['related_paper_id'] ?>"
                     style="font-size:.78rem;color:var(--blue-mid);">
                    <i class="fas fa-arrow-right me-1"></i><?= t('paper.view_details') ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

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
