<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('author');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

$paperId = intGet('id');
if (!$paperId) { redirect($appUrl . '/author/my-papers.php'); }

try {
    $db = Database::getInstance();

    // Main paper + status info
    $stmt = $db->prepare("
        SELECT p.*, ct.name_th AS theme_th, ct.name_en AS theme_en,
               ps.name_th AS status_th, ps.name_en AS status_en,
               ps.color_hex, ps.progress_step, ps.description
        FROM papers p
        JOIN conference_themes ct ON ct.id = p.theme_id
        JOIN paper_statuses ps ON ps.code = p.status_code
        WHERE p.id = :id AND p.submitter_id = :uid
    ");
    $stmt->execute([':id' => $paperId, ':uid' => $uid]);
    $paper = $stmt->fetch();
    if (!$paper) { redirect($appUrl . '/author/my-papers.php'); }

    // All statuses for stepper
    $allStatuses = $db->query("SELECT * FROM paper_statuses ORDER BY progress_step")->fetchAll();

    // Paper files (for timeline events)
    $fileStmt = $db->prepare("
        SELECT id, original_name, file_category, file_type, file_size, uploaded_at
        FROM paper_files WHERE paper_id = :pid ORDER BY uploaded_at ASC
    ");
    $fileStmt->execute([':pid' => $paperId]);
    $files = $fileStmt->fetchAll();

    // Review assignments
    $raStmt = $db->prepare("
        SELECT ra.id, ra.assigned_at, ra.due_date, ra.assignment_status,
               r.id AS review_id, r.recommendation, r.reviewed_at, r.score_overall, r.comment_for_author
        FROM review_assignments ra
        LEFT JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.paper_id = :pid
        ORDER BY ra.assigned_at ASC
    ");
    $raStmt->execute([':pid' => $paperId]);
    $assignments = $raStmt->fetchAll();

    // Notifications related to this paper
    $notifStmt = $db->prepare("
        SELECT title_th, title_en, message_th, message_en, created_at
        FROM notifications
        WHERE related_paper_id = :pid AND user_id = :uid
        ORDER BY created_at ASC
    ");
    $notifStmt->execute([':pid' => $paperId, ':uid' => $uid]);
    $notifications = $notifStmt->fetchAll();

    // Publication info
    $pubStmt = $db->prepare("SELECT * FROM publications WHERE paper_id = :pid LIMIT 1");
    $pubStmt->execute([':pid' => $paperId]);
    $publication = $pubStmt->fetch();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/author/my-papers.php');
}

// ── Build Timeline Events ──────────────────────────────────────
$events = [];

// 1. Submitted
$events[] = [
    'at'    => $paper['submitted_at'],
    'icon'  => 'fa-paper-plane',
    'color' => '#0d6efd',
    'title' => $_lang === 'th' ? 'ส่งบทคัดย่อเข้าระบบ' : 'Paper Submitted',
    'desc'  => e($paper['paper_code']),
];

// 2. File uploads
$fileCatLabel = [
    'submission'   => $_lang === 'th' ? 'ไฟล์บทคัดย่อต้นฉบับ' : 'Original Submission File',
    'revision'     => $_lang === 'th' ? 'ไฟล์บทคัดย่อแก้ไข'  : 'Revised File',
    'camera_ready' => $_lang === 'th' ? 'ไฟล์ Camera Ready' : 'Camera Ready File',
];
foreach ($files as $f) {
    $events[] = [
        'at'    => $f['uploaded_at'],
        'icon'  => 'fa-file-upload',
        'color' => '#6f42c1',
        'title' => $fileCatLabel[$f['file_category']] ?? ($_lang === 'th' ? 'อัพโหลดไฟล์' : 'File Uploaded'),
        'desc'  => e($f['original_name']) . ' (' . strtoupper($f['file_type']) . ')',
    ];
}

// 3. Review assignments
$completedReviewsCount = 0;
$lastReviewedAt = null;
foreach ($assignments as $i => $ra) {
    $events[] = [
        'at'    => $ra['assigned_at'],
        'icon'  => 'fa-user-check',
        'color' => '#6c757d',
        'title' => ($_lang === 'th' ? 'ส่งให้ผู้ทรงคุณวุฒิพิจารณา #' : 'Sent to Reviewer #') . ($i + 1),
        'desc'  => $ra['due_date'] ? ($_lang === 'th' ? 'กำหนดส่งผล: ' : 'Due: ') . humanDate($ra['due_date'], $_lang) : '',
    ];
    if ($ra['review_id'] && $ra['reviewed_at']) {
        $completedReviewsCount++;
        if (!$lastReviewedAt || strtotime($ra['reviewed_at']) > strtotime($lastReviewedAt)) {
            $lastReviewedAt = $ra['reviewed_at'];
        }
    }
}
// Only show that reviewing is complete once at least 2 reviewers have submitted their evaluations
if ($completedReviewsCount >= 2) {
    $events[] = [
        'at'    => $lastReviewedAt,
        'icon'  => 'fa-star-half-alt',
        'color' => '#198754',
        'title' => $_lang === 'th' ? 'ผู้ทรงคุณวุฒิประเมินเสร็จสิ้น' : 'Reviewers Completed Evaluation',
        'desc'  => '',
    ];
}

// 4. Notifications (status changes)
foreach ($notifications as $n) {
    $events[] = [
        'at'    => $n['created_at'],
        'icon'  => 'fa-bell',
        'color' => '#e7ba26',
        'title' => e($_lang === 'th' ? $n['title_th'] : $n['title_en']),
        'desc'  => e($_lang === 'th' ? $n['message_th'] : $n['message_en']),
    ];
}

// 5. Published
if ($publication) {
    $events[] = [
        'at'    => $publication['published_at'],
        'icon'  => 'fa-globe',
        'color' => '#0f5132',
        'title' => $_lang === 'th' ? 'เผยแพร่บทคัดย่อแล้ว' : 'Paper Published',
        'desc'  => $publication['doi'] ? 'DOI: ' . e($publication['doi']) : '',
    ];
}

// Sort all events by date ascending
usort($events, fn($a, $b) => strtotime($a['at']) <=> strtotime($b['at']));

$pageTitle  = $_lang === 'th' ? 'ประวัติการส่งบทคัดย่อ' : 'Submission History';
$activeMenu = 'my-papers';
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — <?= e($paper['paper_code']) ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
  <style>
    /* ── Timeline ───────────────────────────────────────── */
    .timeline { position: relative; padding-left: 40px; }
    .timeline::before {
      content: '';
      position: absolute;
      left: 16px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: linear-gradient(to bottom, var(--blue-mid), var(--gray-200));
    }
    .tl-item {
      position: relative;
      margin-bottom: 28px;
      animation: fadeInUp .4s ease both;
    }
    .tl-item:last-child { margin-bottom: 0; }
    .tl-dot {
      position: absolute;
      left: -32px;
      top: 4px;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .75rem;
      color: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,.15);
      z-index: 2;
      flex-shrink: 0;
    }
    .tl-card {
      background: var(--white);
      border-radius: var(--radius);
      border: 1px solid var(--gray-200);
      padding: 14px 18px;
      box-shadow: var(--shadow-sm);
      transition: box-shadow .2s;
    }
    .tl-card:hover { box-shadow: var(--shadow-md); }
    .tl-time {
      font-size: .72rem;
      color: var(--gray-500);
      white-space: nowrap;
    }
    .tl-title {
      font-weight: 700;
      font-size: .9rem;
      color: var(--blue-dark);
      margin: 2px 0 4px;
    }
    .tl-desc {
      font-size: .82rem;
      color: var(--gray-700);
      line-height: 1.5;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .tl-item:nth-child(1)  { animation-delay: .05s; }
    .tl-item:nth-child(2)  { animation-delay: .10s; }
    .tl-item:nth-child(3)  { animation-delay: .15s; }
    .tl-item:nth-child(4)  { animation-delay: .20s; }
    .tl-item:nth-child(5)  { animation-delay: .25s; }
    .tl-item:nth-child(6)  { animation-delay: .30s; }
    .tl-item:nth-child(7)  { animation-delay: .35s; }
    .tl-item:nth-child(8)  { animation-delay: .40s; }
    .tl-item:nth-child(9)  { animation-delay: .45s; }
    .tl-item:nth-child(10) { animation-delay: .50s; }
  </style>
</head>
<body>

<div class="dashboard-wrap">
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_author.php'; ?>

  <main class="dashboard-content">

    <!-- Header -->
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title">
          <i class="fas fa-history me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
        </h1>
        <p class="dash-breadcrumb">
          <a href="<?= $appUrl ?>/author/my-papers.php" style="color:var(--blue-mid);"><?= t('author.my_papers') ?></a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <code style="color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <?= e($pageTitle) ?>
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= $appUrl ?>/author/paper-detail.php?id=<?= $paperId ?>"
           class="btn-outline-custom">
          <i class="fas fa-file-alt me-2"></i><?= $_lang === 'th' ? 'รายละเอียดบทคัดย่อ' : 'Paper Detail' ?>
        </a>
        <?php if ($paper['status_code'] === 'revision_required'): ?>
          <a href="<?= $appUrl ?>/author/revise.php?id=<?= $paperId ?>" class="btn-primary-custom">
            <i class="fas fa-edit me-2"></i><?= $_lang === 'th' ? 'ส่งบทคัดย่อแก้ไข' : 'Submit Revision' ?>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Paper Title Card ─────────────────────────────────── -->
    <div class="content-card mb-4" style="border-left:5px solid <?= e($paper['color_hex']) ?>;">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">
            <?= t('paper.code') ?>
          </div>
          <code style="font-size:1rem;color:var(--blue-mid);font-weight:700;"><?= e($paper['paper_code']) ?></code>
          <div style="font-weight:700;font-size:1rem;color:var(--blue-dark);margin-top:6px;">
            <?= e($_lang === 'th' ? $paper['title_th'] : $paper['title_en']) ?>
          </div>
          <?php if ($_lang === 'th'): ?>
            <div style="font-size:.82rem;color:var(--gray-500);margin-top:2px;"><?= e($paper['title_en']) ?></div>
          <?php endif; ?>
        </div>
        <div class="text-end flex-shrink-0">
          <?= statusBadge($paper['status_code']) ?>
          <div style="font-size:.75rem;color:var(--gray-500);margin-top:6px;">
            <?= $_lang === 'th' ? 'อัพเดตล่าสุด' : 'Last updated' ?>: <?= humanDate($paper['updated_at'], $_lang) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Horizontal Stepper ──────────────────────────────── -->
    <div class="content-card mb-4">
      <div class="content-card-title mb-4">
        <i class="fas fa-route me-2" style="color:var(--gold);"></i>
        <?= $_lang === 'th' ? 'สถานะการดำเนินการ' : 'Submission Progress' ?>
      </div>

      <?php
        $currentStep    = (int)$paper['progress_step'];
        $isRejected     = $paper['status_code'] === 'rejected';
        $rejectedStatus = null;
        $mainStatuses   = [];
        foreach ($allStatuses as $s) {
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
          $thisStep = (int)$s['progress_step'];

          // Once the paper is rejected, nothing after "accepted" was actually reached
          $isDone    = !$isRejected && $thisStep < $currentStep;
          $isCurrent = !$isRejected && $paper['status_code'] === $s['code'];
          $sName     = $_lang === 'th' ? $s['name_th'] : $s['name_en'];
        ?>
          <div class="progress-step" style="min-width:70px;position:relative;z-index:1;">
            <div class="progress-circle"
                 style="background:<?= $isDone || $isCurrent ? e($s['color_hex']) : 'var(--gray-200)' ?>;border-color:<?= $isDone || $isCurrent ? e($s['color_hex']) : 'var(--gray-200)' ?>;color:<?= $isDone || $isCurrent ? '#fff' : 'var(--gray-500)' ?>;width:34px;height:34px;font-size:.75rem;<?= $isCurrent ? "box-shadow:0 0 0 4px {$s['color_hex']}33;" : '' ?>">
              <?php if ($isDone): ?>
                <i class="fas fa-check"></i>
              <?php else: ?>
                <?= $thisStep ?>
              <?php endif; ?>
            </div>
            <div class="progress-label" style="color:<?= $isDone || $isCurrent ? e($s['color_hex']) : 'var(--gray-700)' ?>;font-size:.68rem;margin-top:6px;<?= $isCurrent ? 'font-weight:700;' : '' ?>">
              <?= e($sName) ?>
              <?php if ($isCurrent): ?>
                <div style="margin-top:4px;">
                  <span style="display:inline-block;background:<?= e($s['color_hex']) ?>;color:#fff;border-radius:99px;padding:2px 8px;font-size:.6rem;font-weight:700;white-space:nowrap;">
                    <?= $_lang === 'th' ? 'ปัจจุบัน' : 'Current' ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($s['code'] === 'accepted' && $rejectedStatus): ?>
              <div style="position:absolute;top:56px;left:50%;width:2px;height:16px;background:var(--gray-200);"></div>
              <div style="position:absolute;top:72px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;min-width:70px;<?= $isRejected ? '' : 'opacity:.55;' ?>">
                <div class="progress-circle"
                     style="background:<?= $isRejected ? e($rejectedStatus['color_hex']) : e($rejectedStatus['color_hex']).'22' ?>;border-color:<?= e($rejectedStatus['color_hex']) ?>;color:<?= $isRejected ? '#fff' : e($rejectedStatus['color_hex']) ?>;width:34px;height:34px;font-size:.75rem;">
                  <?php if ($isRejected): ?>
                    <i class="fas fa-times"></i>
                  <?php else: ?>
                    <?= (int)$rejectedStatus['progress_step'] ?>
                  <?php endif; ?>
                </div>
                <div class="progress-label" style="color:<?= $isRejected ? e($rejectedStatus['color_hex']) : 'var(--gray-700)' ?>;font-size:.68rem;margin-top:6px;white-space:nowrap;<?= $isRejected ? 'font-weight:700;' : '' ?>">
                  <?= e($_lang === 'th' ? $rejectedStatus['name_th'] : $rejectedStatus['name_en']) ?>
                  <?php if ($isRejected): ?>
                    <div style="margin-top:4px;">
                      <span style="display:inline-block;background:<?= e($rejectedStatus['color_hex']) ?>;color:#fff;border-radius:99px;padding:2px 8px;font-size:.6rem;font-weight:700;white-space:nowrap;">
                        <?= $_lang === 'th' ? 'ปัจจุบัน' : 'Current' ?>
                      </span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Current status description -->
      <?php $desc = $paper['description'] ?? ''; ?>
      <?php if ($desc): ?>
        <div class="mt-2 p-3 rounded" style="background:<?= e($paper['color_hex']) ?>18;border-left:4px solid <?= e($paper['color_hex']) ?>;">
          <strong style="color:<?= e($paper['color_hex']) ?>;">
            <?= e($_lang === 'th' ? $paper['status_th'] : $paper['status_en']) ?>
          </strong>
          <span style="color:var(--gray-700);font-size:.87rem;margin-left:8px;"><?= e($desc) ?></span>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── Submission Timeline ─────────────────────────────── -->
    <div class="content-card">
      <div class="content-card-title mb-4">
        <i class="fas fa-stream me-2" style="color:var(--gold);"></i>
        <?= $_lang === 'th' ? 'ประวัติการส่งบทคัดย่อ' : 'Submission History' ?>
        <span class="ms-2 badge rounded-pill"
              style="background:var(--blue-dark);color:#fff;font-size:.72rem;font-weight:600;vertical-align:middle;">
          <?= count($events) ?>
        </span>
      </div>

      <?php if (empty($events)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-clock fa-3x mb-3" style="color:var(--gray-200);"></i>
          <p style="color:var(--gray-500);font-size:.88rem;">
            <?= $_lang === 'th' ? 'ยังไม่มีประวัติการดำเนินการ' : 'No history available yet.' ?>
          </p>
        </div>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($events as $ev): ?>
            <div class="tl-item">
              <div class="tl-dot" style="background:<?= $ev['color'] ?>;">
                <i class="fas <?= $ev['icon'] ?>" style="font-size:.72rem;"></i>
              </div>
              <div class="tl-card">
                <div class="tl-time">
                  <i class="far fa-clock me-1"></i>
                  <?= humanDate($ev['at'], $_lang) ?>
                </div>
                <div class="tl-title"><?= $ev['title'] ?></div>
                <?php if ($ev['desc']): ?>
                  <div class="tl-desc"><?= $ev['desc'] ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
