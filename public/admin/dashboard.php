<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;

try {
    $db = Database::getInstance();

    // Paper stats
    $paperStats = $db->query("
        SELECT
            COUNT(*) AS total,
            COUNT(*) FILTER (WHERE status_code = 'submitted') AS new_submissions,
            COUNT(*) FILTER (WHERE status_code IN ('screening','under_review')) AS under_review,
            COUNT(*) FILTER (WHERE status_code = 'accepted') AS accepted,
            COUNT(*) FILTER (WHERE status_code = 'published') AS published,
            COUNT(*) FILTER (WHERE status_code = 'revision_required') AS revision_required,
            COUNT(*) FILTER (WHERE status_code = 'rejected') AS rejected
        FROM papers
    ")->fetch();

    // User stats
    $userStats = $db->query("
        SELECT
            COUNT(*) AS total,
            COUNT(*) FILTER (WHERE role = 'author') AS authors,
            COUNT(*) FILTER (WHERE role = 'reviewer') AS reviewers
        FROM users
    ")->fetch();

    // Pending review assignments
    $pendingReviews = (int)$db->query("SELECT COUNT(*) FROM review_assignments WHERE status IN ('pending','accepted')")->fetchColumn();

    // Recent papers (10)
    $recentPapers = $db->query("
        SELECT p.*, u.name AS submitter_name,
               ps.name_th, ps.name_en AS ps_name_en, ps.color_hex
        FROM papers p
        JOIN users u ON u.id = p.submitter_id
        JOIN paper_statuses ps ON ps.code = p.status_code
        ORDER BY p.submitted_at DESC
        LIMIT 10
    ")->fetchAll();

    // Papers by theme
    $byTheme = $db->query("
        SELECT ct.name_en, ct.name_th, COUNT(p.id) AS cnt
        FROM conference_themes ct
        LEFT JOIN papers p ON p.theme_id = ct.id
        GROUP BY ct.id, ct.name_en, ct.name_th
        ORDER BY cnt DESC
    ")->fetchAll();

    // Papers by status
    $byStatus = $db->query("
        SELECT ps.name_en, ps.name_th, ps.color_hex, COUNT(p.id) AS cnt
        FROM paper_statuses ps
        LEFT JOIN papers p ON p.status_code = ps.code
        GROUP BY ps.id, ps.name_en, ps.name_th, ps.color_hex
        ORDER BY ps.progress_step
    ")->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $paperStats = []; $userStats = []; $pendingReviews = 0;
    $recentPapers = []; $byTheme = []; $byStatus = [];
}

$pageTitle  = t('author.dashboard');
$activeMenu = 'dashboard';
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin <?= e($pageTitle) ?> — ICALGC 2026</title>
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
          <?= $_lang==='th' ? 'แดชบอร์ดผู้ดูแล' : 'Admin Dashboard' ?>
        </h1>
        <p class="dash-breadcrumb"><?= date('j F Y') ?></p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= $appUrl ?>/admin/papers.php" class="btn-primary-custom">
          <i class="fas fa-file-alt me-2"></i><?= $_lang==='th' ? 'จัดการบทความ' : 'Manage Papers' ?>
        </a>
      </div>
    </div>

    <?= flashHtml() ?>

    <!-- Alert: New Submissions -->
    <?php if ((int)($paperStats['new_submissions'] ?? 0) > 0): ?>
      <div class="alert d-flex align-items-center gap-3 mb-4" style="background:#d1ecf1;border-left:4px solid #17a2b8;border-radius:var(--radius);color:#0c5460;">
        <i class="fas fa-inbox fa-lg"></i>
        <div>
          <strong><?= $_lang==='th' ? 'บทความใหม่รอตรวจสอบ' : 'New Submissions Pending' ?></strong><br>
          <span style="font-size:.88rem;">
            <?= $_lang==='th'
              ? "มี {$paperStats['new_submissions']} บทความใหม่ที่รอการคัดกรอง"
              : "There are {$paperStats['new_submissions']} new paper(s) awaiting screening." ?>
          </span>
          <a href="<?= $appUrl ?>/admin/papers.php?status=submitted" class="ms-2 fw-bold" style="color:inherit;">
            <?= $_lang==='th' ? 'ดูบทความ' : 'View Papers' ?> →
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- Paper Stats -->
    <div class="row g-3 mb-4">
      <?php
      $paperCards = [
          ['icon'=>'fa-file-alt','val'=>$paperStats['total']??0,'label'=>$_lang==='th'?'บทความทั้งหมด':'Total Papers','class'=>''],
          ['icon'=>'fa-inbox','val'=>$paperStats['new_submissions']??0,'label'=>$_lang==='th'?'ส่งใหม่':'New Submissions','class'=>'gold'],
          ['icon'=>'fa-search','val'=>$paperStats['under_review']??0,'label'=>$_lang==='th'?'กำลังพิจารณา':'Under Review','class'=>''],
          ['icon'=>'fa-check-circle','val'=>$paperStats['accepted']??0,'label'=>$_lang==='th'?'ยอมรับแล้ว':'Accepted','class'=>'green'],
          ['icon'=>'fa-globe','val'=>$paperStats['published']??0,'label'=>$_lang==='th'?'เผยแพร่แล้ว':'Published','class'=>''],
          ['icon'=>'fa-edit','val'=>$paperStats['revision_required']??0,'label'=>$_lang==='th'?'ต้องแก้ไข':'Revision','class'=>''],
          ['icon'=>'fa-times-circle','val'=>$paperStats['rejected']??0,'label'=>$_lang==='th'?'ปฏิเสธ':'Rejected','class'=>''],
          ['icon'=>'fa-user-tie','val'=>$pendingReviews,'label'=>$_lang==='th'?'รอประเมิน':'Pending Reviews','class'=>'gold'],
      ];
      foreach ($paperCards as $card): ?>
        <div class="col-6 col-sm-4 col-xl-3">
          <div class="stat-card <?= $card['class'] ?>">
            <div class="stat-icon"><i class="fas <?= $card['icon'] ?>"></i></div>
            <div>
              <div class="stat-number"><?= number_format((int)$card['val']) ?></div>
              <div class="stat-label"><?= $card['label'] ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-4">
      <!-- Recent Papers -->
      <div class="col-lg-8">
        <div class="table-card">
          <div class="table-card-header">
            <span class="table-card-title"><i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทความล่าสุด' : 'Recent Papers' ?></span>
            <a href="<?= $appUrl ?>/admin/papers.php" class="btn-primary-custom" style="padding:8px 18px;font-size:.82rem;">
              <?= $_lang==='th' ? 'ดูทั้งหมด' : 'View All' ?>
            </a>
          </div>
          <?php if (empty($recentPapers)): ?>
            <div class="p-4 text-center" style="color:var(--gray-500);"><?= $_lang==='th' ? 'ยังไม่มีบทความ' : 'No papers yet' ?></div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-custom">
                <thead>
                  <tr>
                    <th><?= t('paper.code') ?></th>
                    <th><?= $_lang==='th' ? 'บทความ' : 'Paper' ?></th>
                    <th><?= $_lang==='th' ? 'ผู้ส่ง' : 'Submitter' ?></th>
                    <th><?= t('paper.status') ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentPapers as $p): ?>
                    <tr>
                      <td><code style="font-size:.78rem;color:var(--blue-mid);"><?= e($p['paper_code']) ?></code></td>
                      <td style="max-width:160px;">
                        <div style="font-size:.83rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                          <?= e($_lang==='th'?$p['title_th']:$p['title_en']) ?>
                        </div>
                        <div style="font-size:.74rem;color:var(--gray-500);"><?= humanDate($p['submitted_at'], $_lang) ?></div>
                      </td>
                      <td style="font-size:.83rem;"><?= e($p['submitter_name']) ?></td>
                      <td><?= statusBadge($p['status_code']) ?></td>
                      <td>
                        <a href="<?= $appUrl ?>/admin/paper-detail.php?id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;">
                          <i class="fas fa-eye"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right Column -->
      <div class="col-lg-4">

        <!-- By Status -->
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-chart-pie me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทความตามสถานะ' : 'Papers by Status' ?>
          </div>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($byStatus as $bs): ?>
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <span style="width:10px;height:10px;border-radius:50%;background:<?= e($bs['color_hex']) ?>;display:inline-block;flex-shrink:0;"></span>
                  <span style="font-size:.82rem;color:var(--gray-700);"><?= e($_lang==='th' ? $bs['name_th'] : $bs['name_en']) ?></span>
                </div>
                <span style="font-weight:700;font-size:.88rem;color:var(--blue-dark);"><?= (int)$bs['cnt'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- By Theme -->
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-tags me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทความตามหัวข้อ' : 'Papers by Theme' ?>
          </div>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($byTheme as $bt): ?>
              <div>
                <div class="d-flex justify-content-between mb-1" style="font-size:.8rem;">
                  <span style="color:var(--gray-700);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px;" title="<?= e($_lang==='th'?$bt['name_th']:$bt['name_en']) ?>">
                    <?= e($_lang==='th'?$bt['name_th']:$bt['name_en']) ?>
                  </span>
                  <span style="font-weight:700;color:var(--blue-dark);flex-shrink:0;margin-left:4px;"><?= (int)$bt['cnt'] ?></span>
                </div>
                <?php if ((int)($paperStats['total'] ?? 0) > 0): ?>
                  <div style="height:5px;background:var(--gray-200);border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:<?= round((int)$bt['cnt']/(int)$paperStats['total']*100) ?>%;background:var(--blue-mid);border-radius:99px;"></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- User Stats -->
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-users me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ผู้ใช้งาน' : 'Users' ?>
          </div>
          <div class="row g-2 text-center">
            <div class="col-4">
              <div style="font-size:1.4rem;font-weight:800;color:var(--blue-dark);"><?= number_format((int)($userStats['total']??0)) ?></div>
              <div style="font-size:.75rem;color:var(--gray-500);"><?= $_lang==='th'?'ทั้งหมด':'Total' ?></div>
            </div>
            <div class="col-4">
              <div style="font-size:1.4rem;font-weight:800;color:var(--blue-mid);"><?= number_format((int)($userStats['authors']??0)) ?></div>
              <div style="font-size:.75rem;color:var(--gray-500);"><?= $_lang==='th'?'ผู้แต่ง':'Authors' ?></div>
            </div>
            <div class="col-4">
              <div style="font-size:1.4rem;font-weight:800;color:var(--gold);"><?= number_format((int)($userStats['reviewers']??0)) ?></div>
              <div style="font-size:.75rem;color:var(--gray-500);"><?= $_lang==='th'?'ผู้ทรง':'Reviewers' ?></div>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <a href="<?= $appUrl ?>/admin/users.php" class="btn-outline-custom flex-fill text-center" style="font-size:.82rem;padding:8px;">
              <i class="fas fa-users me-1"></i><?= $_lang==='th'?'ผู้ใช้':'Users' ?>
            </a>
            <a href="<?= $appUrl ?>/admin/reviewers.php" class="btn-outline-custom flex-fill text-center" style="font-size:.82rem;padding:8px;">
              <i class="fas fa-user-tie me-1"></i><?= $_lang==='th'?'ผู้ทรง':'Reviewers' ?>
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
