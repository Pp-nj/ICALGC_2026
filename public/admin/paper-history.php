?<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$paperId = intGet('id');
if (!$paperId) { redirect($appUrl . '/admin/papers.php'); }

try {
    $db = Database::getInstance();

    $stmt = $db->prepare("
        SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS submitter_name, u.email AS submitter_email,
               ct.name_th AS theme_th, ct.name_en AS theme_en,
               ps.name_th AS status_th, ps.name_en AS status_en, ps.color_hex, ps.progress_step, ps.description
        FROM papers p
        JOIN users u ON u.id = p.submitter_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        JOIN paper_statuses ps ON ps.code = p.status_code
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $paperId]);
    $paper = $stmt->fetch();
    if (!$paper) { redirect($appUrl . '/admin/papers.php'); }

    $allStatuses = $db->query("SELECT * FROM paper_statuses ORDER BY progress_step")->fetchAll();

    // Paper files with uploader name
    $fileStmt = $db->prepare("
        SELECT f.*, CONCAT(u.first_name, ' ', u.last_name) AS uploader_name
        FROM paper_files f
        JOIN users u ON u.id = f.uploaded_by
        WHERE f.paper_id = :pid ORDER BY f.uploaded_at ASC
    ");
    $fileStmt->execute([':pid' => $paperId]);
    $files = $fileStmt->fetchAll();

    // Co-authors
    $caStmt = $db->prepare("SELECT * FROM paper_co_authors WHERE paper_id = :pid ORDER BY sort_order");
    $caStmt->execute([':pid' => $paperId]);
    $coAuthors = $caStmt->fetchAll();

    // Review assignments with reviewer + assigner + full review detail
    $raStmt = $db->prepare("
        SELECT ra.*, CONCAT(rv.first_name, ' ', rv.last_name) AS reviewer_name, rv.email AS reviewer_email,
               CONCAT(ab.first_name, ' ', ab.last_name) AS assigner_name,
               r.id AS review_id, r.recommendation, r.score_originality, r.score_relevance,
               r.score_methodology, r.score_writing, r.score_contribution, r.score_overall,
               r.final_score, r.comment_for_author, r.comment_for_editor, r.reviewed_at, r.created_at AS review_created_at
        FROM review_assignments ra
        JOIN users rv ON rv.id = ra.reviewer_id
        JOIN users ab ON ab.id = ra.assigned_by
        LEFT JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.paper_id = :pid
        ORDER BY ra.assigned_at ASC
    ");
    $raStmt->execute([':pid' => $paperId]);
    $assignments = $raStmt->fetchAll();

    // All notifications tied to this paper (any recipient)
    $notifStmt = $db->prepare("
        SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) AS recipient_name, u.role AS recipient_role
        FROM notifications n
        LEFT JOIN users u ON u.id = n.user_id
        WHERE n.related_paper_id = :pid
        ORDER BY n.created_at ASC
    ");
    $notifStmt->execute([':pid' => $paperId]);
    $notifications = $notifStmt->fetchAll();

    // Publication info
    $pubStmt = $db->prepare("
        SELECT pub.*, CONCAT(u.first_name, ' ', u.last_name) AS publisher_name
        FROM publications pub
        JOIN users u ON u.id = pub.published_by
        WHERE pub.paper_id = :pid LIMIT 1
    ");
    $pubStmt->execute([':pid' => $paperId]);
    $publication = $pubStmt->fetch();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/admin/papers.php');
}

// ── Build detailed timeline events ─────────────────────────────
$events = [];

// 1. Submitted
$events[] = [
    'at'    => $paper['submitted_at'],
    'icon'  => 'fa-paper-plane',
    'color' => '#0d6efd',
    'title' => $_lang === 'th' ? 'ส่งบทคัดย่อเข้าระบบ' : 'Paper Submitted',
    'desc'  => ($_lang === 'th' ? 'รหัส: ' : 'Code: ') . e($paper['paper_code']) . ' &bull; ' .
               ($_lang === 'th' ? 'โดย ' : 'by ') . e($paper['submitter_name']) . ' (' . e($paper['submitter_email']) . ')',
];

// 2. Co-authors added (grouped, no individual timestamp available)
if (!empty($coAuthors)) {
    $names = array_map(fn($c) => e($c['full_name']) . ($c['is_corresponding'] ? ' <span class="badge rounded-pill" style="background:var(--gold);color:#1a1a1a;font-size:.62rem;">' . ($_lang==='th'?'ผู้ประสานงาน':'Corresponding') . '</span>' : ''), $coAuthors);
    $events[] = [
        'at'    => $paper['submitted_at'],
        'icon'  => 'fa-users',
        'color' => '#20c997',
        'title' => $_lang === 'th' ? 'เพิ่มผู้ร่วมวิจัย' : 'Co-Authors Added',
        'desc'  => implode(', ', $names),
    ];
}

// 3. File uploads
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
        'title' => ($fileCatLabel[$f['file_category']] ?? ($_lang === 'th' ? 'อัพโหลดไฟล์' : 'File Uploaded')) . ' (v' . (int)$f['version_number'] . ')',
        'desc'  => e($f['original_name']) . ' &bull; ' . strtoupper($f['file_type']) . ' &bull; ' . formatFileSize($f['file_size']) .
                   '<br><span style="color:var(--gray-500);">' . ($_lang === 'th' ? 'อัพโหลดโดย ' : 'Uploaded by ') . e($f['uploader_name']) . '</span>',
    ];
}

// 4. Review assignments — full detail per reviewer
$assignStatusLabel = [
    'pending'     => ['th' => 'รอตอบรับ',     'en' => 'Pending',     'color' => '#fd7e14'],
    'in_progress' => ['th' => 'กำลังประเมิน', 'en' => 'In Progress', 'color' => '#0057b7'],
    'completed'   => ['th' => 'เสร็จสิ้น',    'en' => 'Completed',   'color' => '#198754'],
    'declined'    => ['th' => 'ปฏิเสธ',       'en' => 'Declined',    'color' => '#6c757d'],
];
$recMap = [
    'accept'         => ['th' => 'ยอมรับ',        'en' => 'Accept',         'color' => '#198754'],
    'minor_revision' => ['th' => 'แก้ไขเล็กน้อย', 'en' => 'Minor Revision', 'color' => '#fd7e14'],
    'major_revision' => ['th' => 'แก้ไขหลัก',     'en' => 'Major Revision', 'color' => '#dc3545'],
    'reject'         => ['th' => 'ปฏิเสธ',        'en' => 'Reject',         'color' => '#6c757d'],
];
foreach ($assignments as $i => $ra) {
    $sLabel = $assignStatusLabel[$ra['assignment_status']] ?? null;
    $events[] = [
        'at'    => $ra['assigned_at'],
        'icon'  => 'fa-user-check',
        'color' => '#6c757d',
        'title' => ($_lang === 'th' ? 'มอบหมายให้ผู้ทรงคุณวุฒิ #' : 'Assigned to Reviewer #') . ($i + 1) . ': ' . e($ra['reviewer_name']),
        'desc'  => ($_lang === 'th' ? 'อีเมล: ' : 'Email: ') . e($ra['reviewer_email']) .
                   ($ra['due_date'] ? '<br>' . ($_lang === 'th' ? 'กำหนดส่งผล: ' : 'Due date: ') . humanDate($ra['due_date'], $_lang) : '') .
                   '<br>' . ($_lang === 'th' ? 'มอบหมายโดย: ' : 'Assigned by: ') . e($ra['assigner_name']) .
                   ($sLabel ? '<br><span class="badge rounded-pill mt-1" style="background:' . $sLabel['color'] . ';color:#fff;font-size:.68rem;">' . e($sLabel[$_lang]) . '</span>' : ''),
    ];

    if ($ra['review_id'] && $ra['reviewed_at']) {
        $rec = $recMap[$ra['recommendation']] ?? null;
        $scoreRows = [
            ['label' => $_lang === 'th' ? 'ความริเริ่ม' : 'Originality',   'val' => $ra['score_originality'],  'max' => 25],
            ['label' => $_lang === 'th' ? 'ความเกี่ยวข้อง' : 'Relevance', 'val' => $ra['score_relevance'],    'max' => 20],
            ['label' => $_lang === 'th' ? 'ระเบียบวิธี' : 'Methodology',  'val' => $ra['score_methodology'],  'max' => 20],
            ['label' => $_lang === 'th' ? 'การเขียน' : 'Writing',         'val' => $ra['score_writing'],      'max' => 10],
            ['label' => $_lang === 'th' ? 'คุณูปการ' : 'Contribution',   'val' => $ra['score_contribution'], 'max' => 25],
        ];
        $scoreHtml = '<div class="d-flex flex-wrap gap-2 mt-2">';
        foreach ($scoreRows as $sr) {
            if ($sr['val'] === null) continue;
            $scoreHtml .= '<span class="badge rounded-pill" style="background:var(--gray-200);color:var(--blue-dark);font-size:.68rem;">' . e($sr['label']) . ': ' . number_format((float)$sr['val'],1) . '/' . $sr['max'] . '</span>';
        }
        $scoreHtml .= '</div>';

        $desc = ($rec ? '<span class="badge rounded-pill" style="background:' . $rec['color'] . ';color:#fff;font-size:.75rem;">' . e($rec[$_lang]) . '</span>' : '') .
                ($ra['score_overall'] !== null ? ' &nbsp;<strong>' . ($_lang === 'th' ? 'คะแนนรวม: ' : 'Overall: ') . number_format((float)$ra['score_overall'],1) . '/100</strong>' : '') .
                $scoreHtml;

        if ($ra['comment_for_author']) {
            $desc .= '<div class="mt-2 p-2 rounded" style="background:#fff;border-left:3px solid var(--blue-mid);font-size:.82rem;">' .
                     '<strong style="font-size:.72rem;color:var(--blue-mid);">' . ($_lang === 'th' ? 'ความเห็นถึงผู้แต่ง:' : 'Comment to author:') . '</strong><br>' .
                     nl2br(e($ra['comment_for_author'])) . '</div>';
        }
        if ($ra['comment_for_editor']) {
            $desc .= '<div class="mt-2 p-2 rounded" style="background:#fff;border-left:3px solid var(--gold);font-size:.82rem;">' .
                     '<strong style="font-size:.72rem;color:var(--gold);">' . ($_lang === 'th' ? 'ความเห็นถึงบรรณาธิการ (ลับ):' : 'Confidential comment to editor:') . '</strong><br>' .
                     nl2br(e($ra['comment_for_editor'])) . '</div>';
        }

        $events[] = [
            'at'    => $ra['reviewed_at'],
            'icon'  => 'fa-star-half-alt',
            'color' => $rec ? $rec['color'] : '#ffc107',
            'title' => ($_lang === 'th' ? 'ผู้ทรงคุณวุฒิ #' : 'Reviewer #') . ($i + 1) . ' (' . e($ra['reviewer_name']) . ') ' . ($_lang === 'th' ? 'ส่งผลการประเมิน' : 'submitted a review'),
            'desc'  => $desc,
        ];
    }
}

// 5. Notifications (status changes / emails sent)
foreach ($notifications as $n) {
    $recipient = $n['recipient_name'] ? e($n['recipient_name']) . ($n['recipient_role'] ? ' (' . e($n['recipient_role']) . ')' : '') : ($_lang === 'th' ? 'ไม่ระบุผู้รับ' : 'Unknown recipient');
    $events[] = [
        'at'    => $n['created_at'],
        'icon'  => 'fa-bell',
        'color' => '#e7ba26',
        'title' => e($_lang === 'th' ? $n['title_th'] : $n['title_en']),
        'desc'  => e($_lang === 'th' ? $n['message_th'] : $n['message_en']) .
                   '<br><span style="color:var(--gray-500);">' . ($_lang === 'th' ? 'ผู้รับ: ' : 'To: ') . $recipient .
                   ' &bull; ' . ($_lang === 'th' ? 'ช่องทาง: ' : 'Channel: ') . e($n['channel']) . '</span>',
    ];
}

// 6. Admin note (if any)
if (!empty($paper['admin_note'])) {
    $events[] = [
        'at'    => $paper['updated_at'],
        'icon'  => 'fa-sticky-note',
        'color' => '#495057',
        'title' => $_lang === 'th' ? 'บันทึกของผู้ดูแลระบบ' : 'Admin Note',
        'desc'  => nl2br(e($paper['admin_note'])),
    ];
}

// 7. Published
if ($publication) {
    $events[] = [
        'at'    => $publication['published_at'],
        'icon'  => 'fa-globe',
        'color' => '#0f5132',
        'title' => $_lang === 'th' ? 'เผยแพร่บทคัดย่อแล้ว' : 'Paper Published',
        'desc'  => ($publication['doi'] ? 'DOI: ' . e($publication['doi']) . '<br>' : '') .
                   ($_lang === 'th' ? 'เผยแพร่โดย ' : 'Published by ') . e($publication['publisher_name']),
    ];
}

// Sort all events chronologically
usort($events, fn($a, $b) => strtotime($a['at']) <=> strtotime($b['at']));

$pageTitle  = $_lang === 'th' ? 'ประวัติการส่งบทคัดย่อ (ละเอียด)' : 'Submission History (Detailed)';
$activeMenu = 'papers';
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
  </style>
</head>
<body>

<div class="dashboard-wrap">
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_admin.php'; ?>

  <main class="dashboard-content">

    <!-- Header -->
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title">
          <i class="fas fa-history me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
        </h1>
        <p class="dash-breadcrumb">
          <a href="<?= $appUrl ?>/admin/papers.php" style="color:var(--blue-mid);"><?= $_lang==='th' ? 'จัดการบทคัดย่อ' : 'Manage Papers' ?></a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <a href="<?= $appUrl ?>/admin/paper-detail.php?id=<?= $paperId ?>" style="color:var(--blue-mid);"><code><?= e($paper['paper_code']) ?></code></a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <?= $_lang==='th' ? 'ประวัติ' : 'History' ?>
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= $appUrl ?>/admin/paper-detail.php?id=<?= $paperId ?>" class="btn-outline-custom">
          <i class="fas fa-file-alt me-2"></i><?= $_lang==='th' ? 'รายละเอียดบทคัดย่อ' : 'Paper Detail' ?>
        </a>
        <a href="<?= $appUrl ?>/admin/final-decision.php?paper_id=<?= $paperId ?>" class="btn-primary-custom">
          <i class="fas fa-gavel me-2"></i><?= $_lang==='th' ? 'ตัดสินผล' : 'Final Decision' ?>
        </a>
      </div>
    </div>

    <!-- Paper Title Card -->
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
          <div style="font-size:.8rem;color:var(--gray-500);margin-top:4px;">
            <?= $_lang === 'th' ? 'ผู้ส่ง' : 'Submitter' ?>: <?= e($paper['submitter_name']) ?> (<?= e($paper['submitter_email']) ?>)
          </div>
        </div>
        <div class="text-end flex-shrink-0">
          <?= statusBadge($paper['status_code']) ?>
          <div style="font-size:.75rem;color:var(--gray-500);margin-top:6px;">
            <?= $_lang === 'th' ? 'อัพเดตล่าสุด' : 'Last updated' ?>: <?= humanDate($paper['updated_at'], $_lang) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Submission Timeline -->
    <div class="content-card">
      <div class="content-card-title mb-4">
        <i class="fas fa-stream me-2" style="color:var(--gold);"></i>
        <?= $_lang === 'th' ? 'ประวัติการดำเนินการทั้งหมด' : 'Full Processing History' ?>
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
