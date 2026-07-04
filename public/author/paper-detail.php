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

    $stmt = $db->prepare("
        SELECT p.*, ct.name_th AS theme_th, ct.name_en AS theme_en,
               ps.name_th AS status_th, ps.name_en AS status_en,
               ps.color_hex, ps.progress_step, ps.description_th, ps.description_en
        FROM papers p
        JOIN conference_themes ct ON ct.id = p.theme_id
        JOIN paper_statuses ps ON ps.code = p.status_code
        WHERE p.id = :id AND p.submitter_id = :uid
    ");
    $stmt->execute([':id' => $paperId, ':uid' => $uid]);
    $paper = $stmt->fetch();

    if (!$paper) { redirect($appUrl . '/author/my-papers.php'); }

    // Co-authors
    $coStmt = $db->prepare("SELECT * FROM paper_co_authors WHERE paper_id = :pid ORDER BY sort_order");
    $coStmt->execute([':pid' => $paperId]);
    $coAuthors = $coStmt->fetchAll();

    // Paper files
    $fileStmt = $db->prepare("
        SELECT * FROM paper_files WHERE paper_id = :pid ORDER BY uploaded_at DESC
    ");
    $fileStmt->execute([':pid' => $paperId]);
    $files = $fileStmt->fetchAll();

    // Review assignments + reviews
    $revStmt = $db->prepare("
        SELECT ra.id AS assignment_id, ra.assignment_status AS assign_status,
               ra.assigned_at, ra.due_date,
               r.id AS review_id, r.recommendation, r.score_overall,
               r.score_originality, r.score_relevance, r.score_methodology,
               r.score_writing, r.score_contribution,
               r.comment_for_author, r.reviewed_at
        FROM review_assignments ra
        LEFT JOIN reviews r ON r.assignment_id = ra.id
        WHERE ra.paper_id = :pid
        ORDER BY ra.assigned_at DESC
    ");
    $revStmt->execute([':pid' => $paperId]);
    $reviews = $revStmt->fetchAll();

    // All statuses for progress track
    $allStatuses = $db->query("SELECT * FROM paper_statuses ORDER BY progress_step")->fetchAll();

    // Publication info
    $pubStmt = $db->prepare("SELECT * FROM publications WHERE paper_id = :pid LIMIT 1");
    $pubStmt->execute([':pid' => $paperId]);
    $publication = $pubStmt->fetch();

    // Certificate
    $certStmt = $db->prepare("SELECT * FROM certificates WHERE paper_id = :pid AND user_id = :uid ORDER BY generated_at DESC LIMIT 1");
    $certStmt->execute([':pid' => $paperId, ':uid' => $uid]);
    $certificate = $certStmt->fetch();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/author/my-papers.php');
}

$pageTitle  = $_lang === 'th' ? 'รายละเอียดบทคัดย่อ' : 'Paper Detail';
$activeMenu = 'my-papers';

$recommendationLabel = function($rec) use ($_lang) {
    $map = [
        'accept'          => $_lang==='th' ? 'ยอมรับ'              : 'Accept',
        'minor_revision'  => $_lang==='th' ? 'แก้ไขเล็กน้อย'       : 'Minor Revision',
        'major_revision'  => $_lang==='th' ? 'แก้ไขหลัก'           : 'Major Revision',
        'reject'          => $_lang==='th' ? 'ปฏิเสธ'              : 'Reject',
    ];
    return $map[$rec] ?? ucfirst(str_replace('_', ' ', $rec));
};
$recommendationColor = function($rec) {
    $map = [
        'accept'         => '#198754',
        'minor_revision' => '#fd7e14',
        'major_revision' => '#dc3545',
        'reject'         => '#6c757d',
    ];
    return $map[$rec] ?? '#6c757d';
};
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
          <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
        </h1>
        <p class="dash-breadcrumb">
          <a href="<?= $appUrl ?>/author/my-papers.php" style="color:var(--blue-mid);"><?= t('author.my_papers') ?></a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <code style="color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code>
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <?php if ($paper['status_code'] === 'revision_required'): ?>
          <a href="<?= $appUrl ?>/author/revise.php?id=<?= $paperId ?>" class="btn-primary-custom">
            <i class="fas fa-edit me-2"></i><?= $_lang==='th' ? 'ส่งบทคัดย่อแก้ไข' : 'Submit Revision' ?>
          </a>
        <?php endif; ?>
        <?php if ($paper['status_code'] === 'published'): ?>
          <a href="<?= $appUrl ?>/download.php?paper_id=<?= $paperId ?>" class="btn btn-success rounded-pill px-4">
            <i class="fas fa-download me-2"></i><?= $_lang==='th' ? 'ดาวน์โหลด' : 'Download' ?>
          </a>
        <?php endif; ?>
        <?php if ($paper['status_code'] === 'accepted' || $paper['status_code'] === 'published'): ?>
          <a href="<?= $appUrl ?>/author/certificates.php" class="btn-outline-custom">
            <i class="fas fa-certificate me-2"></i><?= t('author.certificates') ?>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <?= flashHtml() ?>

    <!-- Progress Track -->
    <div class="content-card mb-4">
      <div class="content-card-title">
        <i class="fas fa-tasks me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'สถานะการดำเนินการ' : 'Submission Progress' ?>
      </div>
      <div class="progress-track">
        <?php foreach ($allStatuses as $i => $s):
          $currentStep  = (int)$paper['progress_step'];
          $thisStep     = (int)$s['progress_step'];
          $isDone       = $thisStep < $currentStep;
          $isCurrent    = $thisStep === $currentStep;
          $isRejected   = $paper['status_code'] === 'rejected';
        ?>
          <div class="progress-step <?= $isDone ? 'done' : ($isCurrent ? 'active' : '') ?>">
            <div class="progress-circle"
                 style="<?= $isCurrent ? "background:{$s['color_hex']};color:#fff;border-color:{$s['color_hex']};" : ($isDone ? 'background:#198754;color:#fff;border-color:#198754;' : '') ?>">
              <?php if ($isDone): ?>
                <i class="fas fa-check"></i>
              <?php elseif ($isCurrent && $isRejected && $s['code'] === 'rejected'): ?>
                <i class="fas fa-times"></i>
              <?php else: ?>
                <?= $thisStep ?>
              <?php endif; ?>
            </div>
            <div class="progress-label" style="<?= $isCurrent ? "color:{$s['color_hex']};font-weight:700;" : '' ?>">
              <?= e($_lang==='th' ? $s['name_th'] : $s['name_en']) ?>
            </div>
          </div>
          <?php if ($i < count($allStatuses)-1): ?>
            <div class="progress-connector <?= $isDone ? 'done' : '' ?>"></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- Current status description -->
      <div class="mt-3 p-3 rounded" style="background:<?= e($paper['color_hex']) ?>18;border-left:4px solid <?= e($paper['color_hex']) ?>;">
        <strong style="color:<?= e($paper['color_hex']) ?>;">
          <?= e($_lang==='th' ? $paper['status_th'] : $paper['status_en']) ?>
        </strong>
        <?php $desc = $_lang==='th' ? $paper['description_th'] : $paper['description_en']; ?>
        <?php if ($desc): ?>
          <span style="color:var(--gray-700);font-size:.88rem;margin-left:8px;"><?= e($desc) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="row g-4">
      <!-- Main Content -->
      <div class="col-lg-8">

        <!-- Paper Info -->
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-info-circle me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลบทคัดย่อ' : 'Paper Information' ?>
          </div>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold" style="color:var(--blue-dark);font-size:.82rem;"><?= $_lang==='th' ? 'ชื่อบทคัดย่อ (ภาษาไทย)' : 'Title (Thai)' ?></label>
              <div style="font-size:.95rem;padding:10px 14px;background:var(--gray-100);border-radius:var(--radius);"><?= e($paper['title_th']) ?></div>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold" style="color:var(--blue-dark);font-size:.82rem;"><?= $_lang==='th' ? 'ชื่อบทคัดย่อ (ภาษาอังกฤษ)' : 'Title (English)' ?></label>
              <div style="font-size:.95rem;padding:10px 14px;background:var(--gray-100);border-radius:var(--radius);"><?= e($paper['title_en']) ?></div>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold" style="color:var(--blue-dark);font-size:.82rem;"><?= $_lang==='th' ? 'บทคัดย่อ (ภาษาไทย)' : 'Abstract (Thai)' ?></label>
              <div style="font-size:.88rem;line-height:1.8;padding:12px 14px;background:var(--gray-100);border-radius:var(--radius);"><?= nl2br(e($paper['abstract_th'])) ?></div>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold" style="color:var(--blue-dark);font-size:.82rem;"><?= $_lang==='th' ? 'บทคัดย่อ (ภาษาอังกฤษ)' : 'Abstract (English)' ?></label>
              <div style="font-size:.88rem;line-height:1.8;padding:12px 14px;background:var(--gray-100);border-radius:var(--radius);"><?= nl2br(e($paper['abstract_en'])) ?></div>
            </div>
            <?php if ($paper['keywords']): ?>
            <div class="col-12">
              <label class="form-label fw-bold" style="color:var(--blue-dark);font-size:.82rem;"><?= t('paper.keywords') ?></label>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach (explode(',', $paper['keywords']) as $kw): ?>
                  <span class="badge rounded-pill" style="background:var(--blue-dark);color:#fff;font-weight:500;font-size:.8rem;padding:6px 14px;"><?= e(trim($kw)) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Co-Authors -->
        <?php if (!empty($coAuthors)): ?>
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-users me-2" style="color:var(--gold);"></i><?= t('paper.co_authors') ?>
          </div>
          <div class="table-responsive">
            <table class="table-custom">
              <thead>
                <tr>
                  <th>#</th>
                  <th><?= $_lang==='th' ? 'ชื่อ-นามสกุล' : 'Name' ?></th>
                  <th><?= $_lang==='th' ? 'อีเมล' : 'Email' ?></th>
                  <th><?= $_lang==='th' ? 'สังกัด' : 'Affiliation' ?></th>
                  <th><?= $_lang==='th' ? 'ประเทศ' : 'Country' ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($coAuthors as $i => $ca): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td style="font-weight:600;"><?= e($ca['full_name']) ?></td>
                    <td style="font-size:.85rem;"><?= e($ca['email']) ?></td>
                    <td style="font-size:.85rem;"><?= e($ca['institution']) ?></td>
                    <td style="font-size:.85rem;"><?= e($ca['country']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <!-- Uploaded Files -->
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-paperclip me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ไฟล์บทคัดย่อ' : 'Submitted Files' ?>
          </div>
          <?php if (empty($files)): ?>
            <div class="p-3 text-center" style="color:var(--gray-500);font-size:.88rem;">
              <?= $_lang==='th' ? 'ยังไม่มีไฟล์' : 'No files uploaded' ?>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($files as $f): ?>
                <div class="d-flex align-items-center justify-content-between p-3 rounded" style="background:var(--gray-100);border:1px solid var(--gray-200);">
                  <div class="d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;background:var(--blue-dark);color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                      <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                      <div style="font-weight:600;font-size:.88rem;color:var(--blue-dark);"><?= e($f['original_name']) ?></div>
                      <div style="font-size:.76rem;color:var(--gray-500);">
                        <?= strtoupper($f['file_type']) ?> &bull; <?= formatFileSize($f['file_size']) ?>
                        &bull; <?= humanDate($f['uploaded_at'], $_lang) ?>
                        <?php if ($f['file_category'] === 'revision'): ?>
                          <span class="badge ms-2" style="background:#fd7e14;color:#fff;font-size:.7rem;"><?= $_lang==='th' ? 'แก้ไข' : 'Revision' ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <a href="<?= $appUrl ?>/download.php?file_id=<?= (int)$f['id'] ?>"
                     class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.78rem;">
                    <i class="fas fa-download me-1"></i><?= $_lang==='th' ? 'ดาวน์โหลด' : 'Download' ?>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Review Results -->
        <?php if (!empty($reviews)): ?>
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-star-half-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ผลการประเมิน' : 'Review Results' ?>
          </div>
          <?php foreach ($reviews as $ri => $rev): ?>
            <?php if (!$rev['review_id']) continue; ?>
            <div class="p-4 rounded mb-3" style="border:1px solid var(--gray-200);background:var(--gray-100);">
              <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div>
                  <span style="font-weight:700;color:var(--blue-dark);">
                    <?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิคนที่' : 'Reviewer' ?> <?= $ri+1 ?>
                  </span>
                  <div style="font-size:.8rem;color:var(--gray-500);"><?= humanDate($rev['reviewed_at'], $_lang) ?></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <?php if ($rev['score_overall']): ?>
                    <div class="text-center" style="background:var(--blue-dark);color:#fff;border-radius:50%;width:54px;height:54px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;">
                      <?= number_format($rev['score_overall'], 1) ?>
                      <span style="font-size:.6rem;font-weight:400;">/10</span>
                    </div>
                  <?php endif; ?>
                  <?php if ($rev['recommendation']): ?>
                    <span class="badge rounded-pill px-3 py-2" style="background:<?= $recommendationColor($rev['recommendation']) ?>;color:#fff;font-size:.8rem;">
                      <?= $recommendationLabel($rev['recommendation']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <?php if ($rev['score_originality']): ?>
                <div class="mb-3">
                  <div style="font-size:.82rem;font-weight:700;color:var(--blue-dark);margin-bottom:8px;"><?= $_lang==='th' ? 'คะแนนแต่ละเกณฑ์' : 'Criterion Scores' ?></div>
                  <div class="row g-2">
                    <?php
                    $criteria = $_lang==='th' ? [
                        'score_originality'  => 'ความเป็นต้นฉบับ',
                        'score_relevance'    => 'ความเกี่ยวข้องกับหัวข้อ',
                        'score_methodology'  => 'ความเหมาะสมของวิธีวิจัย',
                        'score_writing'      => 'การเขียนและภาษา',
                        'score_contribution' => 'คุณค่าทางวิชาการ',
                    ] : [
                        'score_originality'  => 'Originality',
                        'score_relevance'    => 'Relevance to Theme',
                        'score_methodology'  => 'Research Methodology',
                        'score_writing'      => 'Writing & Language',
                        'score_contribution' => 'Academic Contribution',
                    ];
                    foreach ($criteria as $field => $label):
                        $score = (int)($rev[$field] ?? 0);
                        $pct   = $score * 10;
                    ?>
                      <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.78rem;">
                          <span style="color:var(--gray-700);"><?= $label ?></span>
                          <span style="font-weight:700;color:var(--blue-dark);"><?= $score ?>/10</span>
                        </div>
                        <div style="height:6px;background:var(--gray-200);border-radius:99px;overflow:hidden;">
                          <div style="height:100%;width:<?= $pct ?>%;background:var(--blue-mid);border-radius:99px;transition:.3s;"></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($rev['comment_for_author']): ?>
                <div>
                  <div style="font-size:.82rem;font-weight:700;color:var(--blue-dark);margin-bottom:6px;">
                    <i class="fas fa-comment-alt me-1"></i><?= $_lang==='th' ? 'ความเห็นถึงผู้แต่ง' : 'Comments to Author' ?>
                  </div>
                  <div style="font-size:.88rem;line-height:1.7;padding:12px;background:#fff;border-radius:var(--radius);border-left:3px solid var(--blue-mid);">
                    <?= nl2br(e($rev['comment_for_author'])) ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Publication Info -->
        <?php if ($publication && $paper['status_code'] === 'published'): ?>
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-globe me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลการเผยแพร่' : 'Publication Info' ?>
          </div>
          <div class="row g-3">
            <?php if ($publication['doi']): ?>
            <div class="col-sm-6">
              <div style="font-size:.8rem;color:var(--gray-500);">DOI</div>
              <div style="font-weight:600;font-size:.9rem;color:var(--blue-mid);"><?= e($publication['doi']) ?></div>
            </div>
            <?php endif; ?>
            <div class="col-sm-6">
              <div style="font-size:.8rem;color:var(--gray-500);"><?= $_lang==='th' ? 'วันที่เผยแพร่' : 'Published Date' ?></div>
              <div style="font-weight:600;font-size:.9rem;"><?= humanDate($publication['published_at'], $_lang) ?></div>
            </div>
            <div class="col-sm-6">
              <div style="font-size:.8rem;color:var(--gray-500);"><?= $_lang==='th' ? 'ยอดดาวน์โหลด' : 'Downloads' ?></div>
              <div style="font-weight:700;font-size:1.1rem;color:var(--blue-dark);"><?= number_format($publication['download_count']) ?></div>
            </div>
            <div class="col-sm-6">
              <div style="font-size:.8rem;color:var(--gray-500);"><?= $_lang==='th' ? 'ยอดเข้าชม' : 'Views' ?></div>
              <div style="font-weight:700;font-size:1.1rem;color:var(--blue-dark);"><?= number_format($publication['view_count']) ?></div>
            </div>
          </div>
          <div class="mt-3">
            <a href="<?= $appUrl ?>/publication-detail.php?id=<?= (int)$publication['id'] ?>"
               class="btn-outline-custom" target="_blank">
              <i class="fas fa-external-link-alt me-2"></i><?= $_lang==='th' ? 'ดูในหน้าสาธารณะ' : 'View Public Page' ?>
            </a>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">

        <!-- Metadata Card -->
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-tag me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลสรุป' : 'Summary' ?>
          </div>
          <div class="d-flex flex-column gap-3" style="font-size:.88rem;">
            <div>
              <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;"><?= t('paper.code') ?></div>
              <code style="font-size:.9rem;color:var(--blue-mid);"><?= e($paper['paper_code']) ?></code>
            </div>
            <div>
              <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;"><?= t('paper.status') ?></div>
              <?= statusBadge($paper['status_code']) ?>
            </div>
            <div>
              <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;"><?= $_lang==='th' ? 'หัวข้อ' : 'Theme' ?></div>
              <div style="font-weight:600;color:var(--blue-dark);"><?= e($_lang==='th' ? $paper['theme_th'] : $paper['theme_en']) ?></div>
            </div>
            <div>
              <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;"><?= t('paper.submitted_date') ?></div>
              <div><?= humanDate($paper['submitted_at'], $_lang) ?></div>
            </div>
            <?php if ($paper['reviewed_at'] ?? null): ?>
            <div>
              <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;"><?= $_lang==='th' ? 'วันที่ประเมินล่าสุด' : 'Last Reviewed' ?></div>
              <div><?= humanDate($paper['reviewed_at'], $_lang) ?></div>
            </div>
            <?php endif; ?>
            <div>
              <div style="font-size:.75rem;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;"><?= $_lang==='th' ? 'จำนวนผู้ร่วมวิจัย' : 'Co-Authors' ?></div>
              <div><?= count($coAuthors) ?> <?= $_lang==='th' ? 'คน' : 'person(s)' ?></div>
            </div>
          </div>
        </div>

        <!-- Actions Card -->
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-bolt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'การดำเนินการ' : 'Actions' ?>
          </div>
          <div class="d-flex flex-column gap-2">
            <?php if ($paper['status_code'] === 'revision_required'): ?>
              <a href="<?= $appUrl ?>/author/revise.php?id=<?= $paperId ?>" class="btn-primary-custom text-center">
                <i class="fas fa-edit me-2"></i><?= $_lang==='th' ? 'ส่งบทคัดย่อแก้ไข' : 'Submit Revision' ?>
              </a>
            <?php endif; ?>
            <?php if ($paper['status_code'] === 'published'): ?>
              <a href="<?= $appUrl ?>/download.php?paper_id=<?= $paperId ?>" class="btn btn-success rounded-pill text-center py-2 text-decoration-none">
                <i class="fas fa-download me-2"></i><?= $_lang==='th' ? 'ดาวน์โหลด PDF' : 'Download PDF' ?>
              </a>
            <?php endif; ?>
            <a href="<?= $appUrl ?>/author/my-papers.php" class="btn-outline-custom text-center">
              <i class="fas fa-list me-2"></i><?= t('author.my_papers') ?>
            </a>
            <a href="<?= $appUrl ?>/author/submit.php" class="btn-outline-custom text-center">
              <i class="fas fa-file-upload me-2"></i><?= t('author.submit_paper') ?>
            </a>
          </div>
        </div>

        <!-- Certificate -->
        <?php if (in_array($paper['status_code'], ['accepted', 'published'])): ?>
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-certificate me-2" style="color:var(--gold);"></i><?= t('author.certificates') ?>
          </div>
          <p style="font-size:.85rem;color:var(--gray-600);">
            <?= $_lang==='th'
              ? 'ดาวน์โหลดหนังสือรับรองการนำเสนอบทคัดย่อ'
              : 'Download your presentation/acceptance certificate.' ?>
          </p>
          <a href="<?= $appUrl ?>/author/certificates.php?paper_id=<?= $paperId ?>"
             class="btn-gold text-center d-block" style="font-size:.88rem;">
            <i class="fas fa-download me-2"></i><?= $_lang==='th' ? 'ดาวน์โหลดใบรับรอง' : 'Get Certificate' ?>
          </a>
        </div>
        <?php endif; ?>

      </div>
    </div><!-- /.row -->
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
