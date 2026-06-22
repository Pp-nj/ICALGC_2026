<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

try {
    $db = Database::getInstance();

    // Paper stats by status
    $byStatus = $db->query("
        SELECT ps.name_th, ps.name_en, ps.color_hex, COUNT(p.id) AS cnt
        FROM paper_statuses ps
        LEFT JOIN papers p ON p.status_code = ps.code
        GROUP BY ps.id, ps.name_th, ps.name_en, ps.color_hex
        ORDER BY ps.progress_step
    ")->fetchAll();

    // Papers by theme
    $byTheme = $db->query("
        SELECT ct.name_th, ct.name_en, COUNT(p.id) AS cnt,
               COUNT(CASE WHEN p.status_code = 'published' THEN 1 END) AS published
        FROM conference_themes ct
        LEFT JOIN papers p ON p.theme_id = ct.id
        GROUP BY ct.id, ct.name_th, ct.name_en
        ORDER BY cnt DESC
    ")->fetchAll();

    // User registrations by month
    $byMonth = $db->query("
        SELECT TO_CHAR(created_at, 'YYYY-MM') AS month, COUNT(*) AS cnt
        FROM users WHERE role = 'author'
        GROUP BY month ORDER BY month DESC LIMIT 12
    ")->fetchAll();

    // Submissions by month
    $submByMonth = $db->query("
        SELECT TO_CHAR(submitted_at, 'YYYY-MM') AS month, COUNT(*) AS cnt
        FROM papers
        GROUP BY month ORDER BY month DESC LIMIT 12
    ")->fetchAll();

    // Countries
    $byCountry = $db->query("
        SELECT country, COUNT(*) AS cnt FROM users
        WHERE role = 'author' AND country IS NOT NULL AND country != ''
        GROUP BY country ORDER BY cnt DESC LIMIT 15
    ")->fetchAll();

    // Overall totals
    $totals = $db->query("
        SELECT
            (SELECT COUNT(*) FROM papers) AS total_papers,
            (SELECT COUNT(*) FROM papers WHERE status_code = 'published') AS published,
            (SELECT COUNT(*) FROM users WHERE role = 'author') AS authors,
            (SELECT COUNT(*) FROM users WHERE role = 'reviewer') AS reviewers,
            (SELECT COALESCE(SUM(download_count),0) FROM publications) AS total_downloads,
            (SELECT COALESCE(SUM(view_count),0) FROM publications) AS total_views,
            (SELECT COUNT(DISTINCT country) FROM users WHERE country IS NOT NULL AND country != '') AS countries,
            (SELECT ROUND(AVG(overall_score)::numeric,2) FROM reviews) AS avg_review_score
    ")->fetch();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $byStatus = []; $byTheme = []; $byMonth = []; $submByMonth = [];
    $byCountry = []; $totals = [];
}

$pageTitle  = $_lang==='th' ? 'รายงานสถิติ' : 'Statistics Report';
$activeMenu = 'reports';
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
        <h1 class="dash-title"><i class="fas fa-chart-bar me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb"><?= $_lang==='th' ? 'ข้อมูล ณ วันที่' : 'Data as of' ?> <?= date('j F Y') ?></p>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
      <?php
      $kpis = [
        ['icon'=>'fa-file-alt','val'=>$totals['total_papers']??0,'label'=>$_lang==='th'?'บทความทั้งหมด':'Total Papers','color'=>''],
        ['icon'=>'fa-globe','val'=>$totals['published']??0,'label'=>$_lang==='th'?'เผยแพร่แล้ว':'Published','color'=>'green'],
        ['icon'=>'fa-user-edit','val'=>$totals['authors']??0,'label'=>$_lang==='th'?'ผู้แต่ง':'Authors','color'=>''],
        ['icon'=>'fa-user-tie','val'=>$totals['reviewers']??0,'label'=>$_lang==='th'?'ผู้ทรงคุณวุฒิ':'Reviewers','color'=>'gold'],
        ['icon'=>'fa-download','val'=>$totals['total_downloads']??0,'label'=>$_lang==='th'?'ดาวน์โหลดรวม':'Total Downloads','color'=>''],
        ['icon'=>'fa-eye','val'=>$totals['total_views']??0,'label'=>$_lang==='th'?'เข้าชมรวม':'Total Views','color'=>''],
        ['icon'=>'fa-globe-asia','val'=>$totals['countries']??0,'label'=>$_lang==='th'?'ประเทศ':'Countries','color'=>'green'],
        ['icon'=>'fa-star','val'=>number_format($totals['avg_review_score']??0,1),'label'=>$_lang==='th'?'คะแนนเฉลี่ย':'Avg Review Score','color'=>'gold'],
      ];
      foreach ($kpis as $kpi): ?>
        <div class="col-6 col-sm-4 col-xl-3">
          <div class="stat-card <?= $kpi['color'] ?>">
            <div class="stat-icon"><i class="fas <?= $kpi['icon'] ?>"></i></div>
            <div>
              <div class="stat-number"><?= $kpi['val'] ?></div>
              <div class="stat-label"><?= $kpi['label'] ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-4">

      <!-- By Status -->
      <div class="col-lg-6">
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-chart-donut me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทความตามสถานะ' : 'Papers by Status' ?></div>
          <?php
          $maxStatus = max(array_column($byStatus, 'cnt') ?: [1]);
          foreach ($byStatus as $bs): ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1" style="font-size:.83rem;">
                <span style="color:var(--gray-700);"><?= e($_lang==='th'?$bs['name_th']:$bs['name_en']) ?></span>
                <span style="font-weight:700;color:var(--blue-dark);"><?= (int)$bs['cnt'] ?></span>
              </div>
              <div style="height:8px;background:var(--gray-200);border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:<?= $maxStatus>0?round((int)$bs['cnt']/$maxStatus*100):0 ?>%;background:<?= e($bs['color_hex']) ?>;border-radius:99px;transition:.5s;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- By Theme -->
      <div class="col-lg-6">
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-tags me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'บทความตามหัวข้อ' : 'Papers by Theme' ?></div>
          <div class="table-responsive">
            <table class="table-custom">
              <thead>
                <tr>
                  <th><?= $_lang==='th'?'หัวข้อ':'Theme' ?></th>
                  <th style="text-align:right;"><?= $_lang==='th'?'ทั้งหมด':'Total' ?></th>
                  <th style="text-align:right;"><?= $_lang==='th'?'เผยแพร่':'Published' ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($byTheme as $bt): ?>
                  <tr>
                    <td style="font-size:.83rem;"><?= e($_lang==='th'?$bt['name_th']:$bt['name_en']) ?></td>
                    <td style="text-align:right;font-weight:700;color:var(--blue-dark);"><?= (int)$bt['cnt'] ?></td>
                    <td style="text-align:right;font-weight:700;color:#198754;"><?= (int)$bt['published'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Submissions by Month -->
      <div class="col-lg-6">
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-calendar-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'การส่งบทความรายเดือน' : 'Submissions by Month' ?></div>
          <?php if (empty($submByMonth)): ?>
            <div class="p-3 text-center" style="color:var(--gray-500);font-size:.88rem;"><?= t('common.no_data') ?></div>
          <?php else:
            $maxSubm = max(array_column($submByMonth, 'cnt') ?: [1]);
            foreach (array_reverse($submByMonth) as $sm): ?>
              <div class="mb-2 d-flex align-items-center gap-3">
                <div style="width:70px;font-size:.78rem;color:var(--gray-600);flex-shrink:0;"><?= $sm['month'] ?></div>
                <div style="flex:1;height:18px;background:var(--gray-200);border-radius:99px;overflow:hidden;">
                  <div style="height:100%;width:<?= $maxSubm>0?round((int)$sm['cnt']/$maxSubm*100):0 ?>%;background:var(--blue-mid);border-radius:99px;"></div>
                </div>
                <div style="width:30px;font-weight:700;font-size:.83rem;color:var(--blue-dark);text-align:right;"><?= (int)$sm['cnt'] ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Countries -->
      <div class="col-lg-6">
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-globe-asia me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ประเทศผู้เข้าร่วม' : 'Participant Countries' ?></div>
          <?php if (empty($byCountry)): ?>
            <div class="p-3 text-center" style="color:var(--gray-500);font-size:.88rem;"><?= t('common.no_data') ?></div>
          <?php else:
            $maxCtry = max(array_column($byCountry, 'cnt') ?: [1]);
            foreach ($byCountry as $ctry): ?>
              <div class="mb-2 d-flex align-items-center gap-3">
                <div style="width:100px;font-size:.8rem;color:var(--gray-700);flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($ctry['country']) ?>">
                  <?= e($ctry['country']) ?>
                </div>
                <div style="flex:1;height:14px;background:var(--gray-200);border-radius:99px;overflow:hidden;">
                  <div style="height:100%;width:<?= $maxCtry>0?round((int)$ctry['cnt']/$maxCtry*100):0 ?>%;background:var(--gold);border-radius:99px;"></div>
                </div>
                <div style="width:25px;font-weight:700;font-size:.8rem;color:var(--blue-dark);text-align:right;"><?= (int)$ctry['cnt'] ?></div>
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
