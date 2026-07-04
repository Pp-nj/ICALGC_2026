<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('author');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

// ── Certificate type meta ────────────────────────────────
$certTypeMeta = [
    'acceptance'   => ['th' => 'ใบตอบรับบทคัดย่อ',        'en' => 'Certificate of Acceptance',   'icon' => 'fa-file-contract',      'color' => '#0057b7'],
    'presentation' => ['th' => 'ใบรับรองการนำเสนอ',     'en' => 'Certificate of Presentation', 'icon' => 'fa-chalkboard-teacher', 'color' => '#198754'],
    'attendance'   => ['th' => 'ใบรับรองการเข้าร่วม',   'en' => 'Certificate of Attendance',   'icon' => 'fa-users',              'color' => '#6f42c1'],
    'reviewer'     => ['th' => 'ใบรับรองผู้ทรงคุณวุฒิ', 'en' => 'Certificate of Reviewer',     'icon' => 'fa-user-tie',           'color' => '#a07c10'],
];

try {
    $db = Database::getInstance();

    // All certificates issued to this user
    $certStmt = $db->prepare("
        SELECT c.*,
               p.paper_code,
               p.title_en  AS paper_title_en,
               p.title_th  AS paper_title_th,
               p.status_code AS paper_status
        FROM certificates c
        LEFT JOIN papers p ON p.id = c.paper_id
        WHERE c.user_id = :uid
        ORDER BY c.generated_at DESC
    ");
    $certStmt->execute([':uid' => $uid]);
    $myCerts = $certStmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $myCerts = [];
}

// Group certs by type for the summary cards
$certsByType = [];
foreach ($myCerts as $c) {
    $certsByType[$c['cert_type']][] = $c;
}

$pageTitle  = t('author.certificates');
$activeMenu = 'certificates';
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
  <style>
    /* ── Certificate Card ── */
    .cert-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-sm);
      border: 1.5px solid var(--gray-200);
      overflow: hidden;
      transition: var(--transition);
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .cert-card:hover {
      box-shadow: var(--shadow-md);
      border-color: var(--gold);
      transform: translateY(-3px);
    }
    .cert-card-header {
      padding: 20px 20px 16px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }
    .cert-card-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: #fff;
      flex-shrink: 0;
    }
    .cert-card-body { padding: 0 20px 16px; flex: 1; }
    .cert-card-footer {
      padding: 14px 20px;
      background: var(--gray-100);
      border-top: 1px solid var(--gray-200);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      flex-wrap: wrap;
    }

    /* ── Empty state within card ── */
    .cert-pending {
      background: var(--gray-100);
      border-radius: var(--radius);
      padding: 16px;
      text-align: center;
    }

    /* ── History table responsive ── */
    @media (max-width: 575px) {
      .dashboard-content { padding: 14px; }
      .cert-card-footer { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>

<div class="dashboard-wrap">
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_author.php'; ?>

  <main class="dashboard-content">

    <!-- Header -->
    <div class="dash-header mb-4">
      <h1 class="dash-title">
        <i class="fas fa-certificate me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
      </h1>
      <p class="dash-breadcrumb">
        <?= $_lang === 'th'
          ? 'ใบรับรองที่ออกให้แก่ท่านโดยคณะผู้จัดงาน'
          : 'Certificates issued to you by the conference organizer' ?>
      </p>
    </div>

    <?= flashHtml() ?>

    <?php if (empty($myCerts)): ?>
      <!-- ── Empty State ── -->
      <div class="content-card text-center p-5">
        <div style="width:80px;height:80px;background:var(--gray-100);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
          <i class="fas fa-certificate fa-2x" style="color:var(--gray-400);"></i>
        </div>
        <h5 style="color:var(--gray-700);font-weight:700;">
          <?= $_lang === 'th' ? 'ยังไม่มีใบรับรอง' : 'No certificates yet' ?>
        </h5>
        <p style="color:var(--gray-500);font-size:.9rem;max-width:420px;margin:8px auto 0;">
          <?= $_lang === 'th'
            ? 'ใบรับรองจะแสดงที่นี่เมื่อคณะผู้จัดงานออกให้แก่ท่าน กรุณาตรวจสอบอีกครั้งในภายหลัง'
            : 'Your certificates will appear here once issued by the conference organizer. Please check back later.' ?>
        </p>
      </div>

    <?php else: ?>

      <!-- ── Certificate Cards Grid ── -->
      <div class="row g-3 mb-4">
        <?php foreach ($myCerts as $c):
          $m = $certTypeMeta[$c['cert_type']] ?? [
            'th'    => ucfirst($c['cert_type']),
            'en'    => ucfirst($c['cert_type']),
            'icon'  => 'fa-certificate',
            'color' => '#6c757d',
          ];
          $title    = $_lang === 'th' ? $m['th'] : $m['en'];
          $hasFile  = !empty($c['pdf_path']);
          $paperTitle = $_lang === 'th' ? ($c['paper_title_th'] ?: $c['paper_title_en']) : ($c['paper_title_en'] ?: $c['paper_title_th']);
        ?>
          <div class="col-12 col-sm-6 col-xl-4">
            <div class="cert-card">
              <div class="cert-card-header">
                <div class="cert-card-icon" style="background:<?= $m['color'] ?>;">
                  <i class="fas <?= $m['icon'] ?>"></i>
                </div>
                <div>
                  <div style="font-weight:700;color:var(--blue-dark);font-size:.95rem;line-height:1.3;">
                    <?= e($title) ?>
                  </div>
                  <div style="font-size:.75rem;color:var(--gray-500);margin-top:3px;">
                    <i class="fas fa-clock me-1"></i><?= humanDate($c['generated_at'], $_lang) ?>
                  </div>
                </div>
              </div>

              <div class="cert-card-body">
                <!-- Recipient -->
                <div style="font-size:.82rem;color:var(--gray-700);">
                  <i class="fas fa-user me-1" style="color:var(--blue-mid);"></i>
                  <?= e($c['recipient_name']) ?>
                </div>

                <?php if ($c['paper_code']): ?>
                  <div class="mt-2" style="font-size:.8rem;">
                    <code style="color:var(--blue-mid);"><?= e($c['paper_code']) ?></code>
                    <?php if ($paperTitle): ?>
                      <div style="color:var(--gray-500);margin-top:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= e($paperTitle) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>

              <div class="cert-card-footer">
                <?php if ($hasFile): ?>
                  <span style="font-size:.75rem;color:var(--success);font-weight:600;">
                    <i class="fas fa-check-circle me-1"></i>
                    <?= $_lang === 'th' ? 'พร้อมดาวน์โหลด' : 'Ready to download' ?>
                  </span>
                  <a href="<?= $appUrl ?>/download.php?cert_id=<?= (int)$c['id'] ?>"
                     class="btn btn-success btn-sm rounded-pill"
                     style="font-size:.78rem;"
                     target="_blank">
                    <i class="fas fa-download me-1"></i>
                    <?= $_lang === 'th' ? 'ดาวน์โหลด' : 'Download' ?>
                  </a>
                <?php else: ?>
                  <span style="font-size:.75rem;color:var(--gray-500);">
                    <i class="fas fa-hourglass-half me-1"></i>
                    <?= $_lang === 'th' ? 'ไฟล์ยังไม่พร้อม' : 'File not yet available' ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ── History Table (full list) ── -->
      <div class="table-card">
        <div class="table-card-header">
          <span class="table-card-title">
            <i class="fas fa-history me-2" style="color:var(--gold);"></i>
            <?= $_lang === 'th' ? 'ประวัติใบรับรองทั้งหมด' : 'All Certificates' ?>
          </span>
          <span style="font-size:.82rem;color:var(--gray-500);">
            <?= count($myCerts) ?> <?= $_lang === 'th' ? 'รายการ' : 'item(s)' ?>
          </span>
        </div>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= $_lang === 'th' ? 'ประเภท' : 'Type' ?></th>
                <th><?= $_lang === 'th' ? 'บทคัดย่อ' : 'Paper' ?></th>
                <th><?= $_lang === 'th' ? 'วันที่ออก' : 'Issued' ?></th>
                <th style="width:80px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myCerts as $c):
                $m = $certTypeMeta[$c['cert_type']] ?? ['th' => ucfirst($c['cert_type']), 'en' => ucfirst($c['cert_type']), 'icon' => 'fa-certificate', 'color' => '#6c757d'];
                $hasFile = !empty($c['pdf_path']);
              ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                      <span style="width:28px;height:28px;border-radius:6px;background:<?= $m['color'] ?>;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;flex-shrink:0;">
                        <i class="fas <?= $m['icon'] ?>"></i>
                      </span>
                      <span style="font-size:.85rem;font-weight:600;">
                        <?= $_lang === 'th' ? $m['th'] : $m['en'] ?>
                      </span>
                    </div>
                  </td>
                  <td style="font-size:.82rem;">
                    <?php if ($c['paper_code']): ?>
                      <code style="color:var(--blue-mid);font-size:.78rem;"><?= e($c['paper_code']) ?></code>
                    <?php else: ?>
                      <span style="color:var(--gray-400);">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;white-space:nowrap;"><?= humanDate($c['generated_at'], $_lang) ?></td>
                  <td>
                    <?php if ($hasFile): ?>
                      <a href="<?= $appUrl ?>/download.php?cert_id=<?= (int)$c['id'] ?>"
                         class="btn btn-sm btn-outline-success rounded-pill"
                         style="font-size:.72rem;"
                         target="_blank">
                        <i class="fas fa-download"></i>
                      </a>
                    <?php else: ?>
                      <span class="badge bg-secondary" style="font-size:.7rem;">
                        <?= $_lang === 'th' ? 'รอไฟล์' : 'Pending' ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
