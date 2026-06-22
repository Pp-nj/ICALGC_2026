<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notification;

Auth::require('reviewer');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

$assignmentId = intGet('assignment_id');
if (!$assignmentId) { redirect($appUrl . '/reviewer/assigned-papers.php'); }

try {
    $db = Database::getInstance();

    $stmt = $db->prepare("
        SELECT ra.*, p.id AS paper_id, p.paper_code, p.title_th, p.title_en,
               p.abstract_th, p.abstract_en, p.keywords, p.submitter_id,
               ct.name_th AS theme_th, ct.name_en AS theme_en
        FROM review_assignments ra
        JOIN papers p ON p.id = ra.paper_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        WHERE ra.id = :aid AND ra.reviewer_id = :uid
    ");
    $stmt->execute([':aid' => $assignmentId, ':uid' => $uid]);
    $assignment = $stmt->fetch();

    if (!$assignment) { redirect($appUrl . '/reviewer/assigned-papers.php'); }

    // Existing review
    $rStmt = $db->prepare("SELECT * FROM reviews WHERE assignment_id = :aid");
    $rStmt->execute([':aid' => $assignmentId]);
    $existingReview = $rStmt->fetch();

    // Paper files
    $fStmt = $db->prepare("SELECT * FROM paper_files WHERE paper_id = :pid ORDER BY uploaded_at DESC LIMIT 1");
    $fStmt->execute([':pid' => $assignment['paper_id']]);
    $latestFile = $fStmt->fetch();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/reviewer/assigned-papers.php');
}

$readOnly = ($assignment['status'] === 'completed' || $assignment['status'] === 'declined');
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$readOnly) {
    Auth::verifyCsrf(post('csrf_token'));

    $scores = [];
    for ($i = 1; $i <= 6; $i++) {
        $scores["criterion_{$i}_score"] = intPost("criterion_{$i}_score");
        if ($scores["criterion_{$i}_score"] < 1 || $scores["criterion_{$i}_score"] > 10)
            $errors[] = ($_lang==='th' ? "เกณฑ์ที่ $i ต้องมีคะแนน 1-10" : "Criterion $i score must be 1-10.");
    }

    $commentsAuthor = trim(post('comments_to_author'));
    $commentsEditor = trim(post('comments_to_editor'));
    $recommendation = post('recommendation');
    $isDraft        = post('save_draft') === '1';

    $allowedRec = ['accept', 'minor_revision', 'major_revision', 'reject'];
    if (!$isDraft && !in_array($recommendation, $allowedRec))
        $errors[] = $_lang==='th' ? 'กรุณาเลือกข้อเสนอแนะ' : 'Please select a recommendation.';
    if (!$isDraft && !$commentsAuthor)
        $errors[] = $_lang==='th' ? 'กรุณากรอกความเห็นถึงผู้แต่ง' : 'Comments to author are required.';

    if (empty($errors)) {
        try {
            $overallScore = array_sum(array_values($scores)) / 6;

            if ($existingReview) {
                $upd = $db->prepare("
                    UPDATE reviews SET
                        criterion_1_score = :c1, criterion_2_score = :c2,
                        criterion_3_score = :c3, criterion_4_score = :c4,
                        criterion_5_score = :c5, criterion_6_score = :c6,
                        overall_score = :os, recommendation = :rec,
                        comments_to_author = :ca, comments_to_editor = :ce,
                        is_draft = :dr, submitted_at = NOW()
                    WHERE id = :rid
                ");
                $upd->execute(array_merge($scores, [
                    ':os' => $overallScore, ':rec' => $recommendation,
                    ':ca' => $commentsAuthor, ':ce' => $commentsEditor,
                    ':dr' => $isDraft ? 't' : 'f',
                    ':rid' => $existingReview['id'],
                ]));
            } else {
                $ins = $db->prepare("
                    INSERT INTO reviews
                        (assignment_id, criterion_1_score, criterion_2_score,
                         criterion_3_score, criterion_4_score, criterion_5_score,
                         criterion_6_score, overall_score, recommendation,
                         comments_to_author, comments_to_editor, is_draft)
                    VALUES
                        (:aid, :c1, :c2, :c3, :c4, :c5, :c6, :os, :rec, :ca, :ce, :dr)
                ");
                $ins->execute(array_merge($scores, [
                    ':aid' => $assignmentId, ':os' => $overallScore,
                    ':rec' => $recommendation, ':ca' => $commentsAuthor,
                    ':ce'  => $commentsEditor, ':dr' => $isDraft ? 't' : 'f',
                ]));
            }

            if (!$isDraft) {
                // Mark assignment as completed
                $db->prepare("UPDATE review_assignments SET status = 'completed' WHERE id = :aid")
                   ->execute([':aid' => $assignmentId]);

                // Notify admin
                Notification::create(
                    null, 'review_result',
                    'มีผลประเมินบทความใหม่',
                    'New Review Submitted',
                    "ผู้ทรงคุณวุฒิส่งผลประเมินบทความ {$assignment['paper_code']} แล้ว",
                    "Reviewer has submitted review for {$assignment['paper_code']}.",
                    $assignment['paper_id'], 'system'
                );
            }

            $msg = $isDraft
                ? ($_lang==='th' ? 'บันทึกฉบับร่างแล้ว' : 'Draft saved.')
                : ($_lang==='th' ? 'ส่งผลประเมินเรียบร้อย' : 'Review submitted successfully.');
            flashSet('success', $msg);
            redirect($appUrl . '/reviewer/review.php?assignment_id=' . $assignmentId);

        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

$pageTitle  = $_lang==='th' ? 'ประเมินบทความ' : 'Review Paper';
$activeMenu = 'assigned';

$criteria = $_lang==='th' ? [
    ['key'=>'criterion_1_score', 'label'=>'ความเกี่ยวข้องกับหัวข้อประชุม', 'hint'=>'บทความสอดคล้องกับขอบเขตและหัวข้อของการประชุมมากเพียงใด'],
    ['key'=>'criterion_2_score', 'label'=>'ความชัดเจนของวัตถุประสงค์และคำถามวิจัย', 'hint'=>'วัตถุประสงค์และคำถามวิจัยชัดเจนและมีความเป็นไปได้'],
    ['key'=>'criterion_3_score', 'label'=>'ความเหมาะสมของวิธีการวิจัย', 'hint'=>'วิธีการวิจัยเหมาะสมและมีความน่าเชื่อถือ'],
    ['key'=>'criterion_4_score', 'label'=>'คุณภาพของบทคัดย่อ', 'hint'=>'บทคัดย่อสรุปได้ครบถ้วน ชัดเจน และน่าสนใจ'],
    ['key'=>'criterion_5_score', 'label'=>'ความเป็นต้นฉบับและคุณค่าทางวิชาการ', 'hint'=>'งานวิจัยมีความใหม่และสร้างคุณค่าให้วงการวิชาการ'],
    ['key'=>'criterion_6_score', 'label'=>'คุณภาพการเขียนและการใช้ภาษา', 'hint'=>'การเขียนชัดเจน ถูกต้องตามหลักภาษา และเป็นมืออาชีพ'],
] : [
    ['key'=>'criterion_1_score', 'label'=>'Relevance to Conference Theme', 'hint'=>'How well does the paper align with the scope and themes of the conference?'],
    ['key'=>'criterion_2_score', 'label'=>'Clarity of Objectives & Research Questions', 'hint'=>'Are the objectives and research questions clear and achievable?'],
    ['key'=>'criterion_3_score', 'label'=>'Appropriateness of Research Methodology', 'hint'=>'Is the research methodology appropriate and credible?'],
    ['key'=>'criterion_4_score', 'label'=>'Quality of the Abstract', 'hint'=>'Does the abstract accurately and concisely summarize the work?'],
    ['key'=>'criterion_5_score', 'label'=>'Originality & Academic Contribution', 'hint'=>'Is the work original and does it add value to the field?'],
    ['key'=>'criterion_6_score', 'label'=>'Writing Quality & Language', 'hint'=>'Is the writing clear, grammatically correct, and professional?'],
];

$recommendations = [
    'accept'         => ['th'=>'ยอมรับ', 'en'=>'Accept', 'color'=>'#198754'],
    'minor_revision' => ['th'=>'แก้ไขเล็กน้อย', 'en'=>'Minor Revision', 'color'=>'#fd7e14'],
    'major_revision' => ['th'=>'แก้ไขหลัก', 'en'=>'Major Revision', 'color'=>'#dc3545'],
    'reject'         => ['th'=>'ปฏิเสธ', 'en'=>'Reject', 'color'=>'#6c757d'],
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
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_reviewer.php'; ?>

  <main class="dashboard-content">
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title">
          <i class="fas fa-star me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
        </h1>
        <p class="dash-breadcrumb">
          <a href="<?= $appUrl ?>/reviewer/assigned-papers.php" style="color:var(--blue-mid);">
            <?= $_lang==='th' ? 'บทความที่ได้รับ' : 'Assigned Papers' ?>
          </a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <code style="color:var(--blue-mid);"><?= e($assignment['paper_code']) ?></code>
        </p>
      </div>
      <?php if ($readOnly && $assignment['status'] === 'completed'): ?>
        <span class="badge rounded-pill px-4 py-2" style="background:#198754;color:#fff;font-size:.85rem;">
          <i class="fas fa-check-circle me-1"></i><?= $_lang==='th' ? 'ส่งผลแล้ว' : 'Submitted' ?>
        </span>
      <?php endif; ?>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Paper Info (left) -->
      <div class="col-lg-5">
        <div class="content-card mb-4" style="position:sticky;top:20px;">
          <div class="content-card-title">
            <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลบทความ' : 'Paper Information' ?>
          </div>
          <code style="font-size:.82rem;color:var(--blue-mid);"><?= e($assignment['paper_code']) ?></code>
          <h5 style="font-weight:800;color:var(--blue-dark);margin:8px 0 4px;font-size:.95rem;">
            <?= e($_lang==='th' ? $assignment['title_th'] : $assignment['title_en']) ?>
          </h5>
          <div style="font-size:.8rem;color:var(--gold);font-weight:600;margin-bottom:12px;">
            <?= e($_lang==='th' ? $assignment['theme_th'] : $assignment['theme_en']) ?>
          </div>
          <div style="font-size:.85rem;color:var(--gray-700);line-height:1.7;max-height:200px;overflow-y:auto;">
            <?= nl2br(e($_lang==='th' ? $assignment['abstract_th'] : $assignment['abstract_en'])) ?>
          </div>

          <?php if ($assignment['keywords'] ?? ''): ?>
            <div class="mt-3 d-flex flex-wrap gap-1">
              <?php foreach (explode(',', $assignment['keywords'] ?? '') as $kw): ?>
                <span class="badge" style="background:var(--blue-dark);color:#fff;font-size:.7rem;font-weight:400;"><?= e(trim($kw)) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($latestFile): ?>
            <div class="mt-4 pt-3" style="border-top:1px solid var(--gray-200);">
              <a href="<?= $appUrl ?>/download.php?file_id=<?= (int)$latestFile['id'] ?>"
                 class="btn-primary-custom d-block text-center" style="font-size:.85rem;">
                <i class="fas fa-download me-2"></i><?= $_lang==='th' ? 'ดาวน์โหลดไฟล์บทความ' : 'Download Paper File' ?>
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Review Form (right) -->
      <div class="col-lg-7">
        <?php if ($readOnly): ?>
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <?= $_lang==='th' ? 'คุณได้ส่งผลประเมินนี้แล้ว ไม่สามารถแก้ไขได้' : 'You have already submitted this review. It cannot be edited.' ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

          <!-- Criteria Scores -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-star-half-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'เกณฑ์การประเมิน (1–10)' : 'Evaluation Criteria (1–10)' ?>
            </div>
            <div class="d-flex flex-column gap-4">
              <?php foreach ($criteria as $i => $crit):
                $fieldName = $crit['key'];
                $currentScore = $_POST[$fieldName] ?? ($existingReview[$fieldName] ?? 7);
              ?>
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-bold mb-0" style="font-size:.85rem;color:var(--blue-dark);">
                      <?= $i+1 ?>. <?= $crit['label'] ?>
                    </label>
                    <span class="score-display fw-bold" id="disp_<?= $i ?>" style="color:var(--blue-dark);font-size:1.1rem;min-width:40px;text-align:right;">
                      <?= $currentScore ?>
                    </span>
                  </div>
                  <div style="font-size:.76rem;color:var(--gray-500);margin-bottom:8px;"><?= $crit['hint'] ?></div>
                  <input type="range" class="score-slider" name="<?= $fieldName ?>"
                         min="1" max="10" step="1"
                         value="<?= (int)$currentScore ?>"
                         id="slider_<?= $i ?>"
                         oninput="document.getElementById('disp_<?= $i ?>').textContent=this.value"
                         <?= $readOnly ? 'disabled' : '' ?>>
                  <div class="d-flex justify-content-between" style="font-size:.72rem;color:var(--gray-400);">
                    <span><?= $_lang==='th' ? 'ต่ำสุด' : 'Poor' ?></span>
                    <span><?= $_lang==='th' ? 'ดีเยี่ยม' : 'Excellent' ?></span>
                  </div>
                </div>
              <?php endforeach; ?>

              <!-- Overall Score Display -->
              <div class="p-3 rounded text-center" style="background:var(--blue-dark);color:#fff;">
                <div style="font-size:.8rem;opacity:.7;"><?= $_lang==='th' ? 'คะแนนเฉลี่ยรวม' : 'Overall Average Score' ?></div>
                <div style="font-size:2rem;font-weight:800;" id="overallScore">
                  <?= $existingReview ? number_format($existingReview['overall_score'], 1) : '7.0' ?>
                </div>
                <div style="font-size:.75rem;opacity:.6;">/10</div>
              </div>
            </div>
          </div>

          <!-- Recommendation -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-thumbs-up me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อเสนอแนะ' : 'Recommendation' ?>
            </div>
            <div class="row g-2">
              <?php foreach ($recommendations as $rCode => $rInfo): ?>
                <div class="col-6">
                  <label class="d-block" style="cursor:pointer;">
                    <input type="radio" name="recommendation" value="<?= $rCode ?>"
                           class="d-none rec-radio"
                           <?= ($existingReview['recommendation'] ?? '') === $rCode ? 'checked' : '' ?>
                           <?= $readOnly ? 'disabled' : '' ?>>
                    <div class="rec-option p-3 rounded text-center" style="border:2px solid <?= $rInfo['color'] ?>;<?= ($existingReview['recommendation'] ?? '') === $rCode ? "background:{$rInfo['color']};color:#fff;" : '' ?>">
                      <div style="font-weight:700;font-size:.88rem;">
                        <?= $_lang==='th' ? $rInfo['th'] : $rInfo['en'] ?>
                      </div>
                    </div>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Comments -->
          <div class="content-card mb-4">
            <div class="content-card-title">
              <i class="fas fa-comment-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ความเห็น' : 'Comments' ?>
            </div>
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ความเห็นถึงผู้แต่ง' : 'Comments to Author' ?>
                  <?php if (!$readOnly): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <textarea name="comments_to_author" class="form-control" rows="5"
                          placeholder="<?= $_lang==='th' ? 'ข้อเสนอแนะที่สร้างสรรค์สำหรับผู้แต่ง...' : 'Constructive feedback for the author...' ?>"
                          <?= $readOnly ? 'readonly' : '' ?>><?= e($existingReview['comments_to_author'] ?? '') ?></textarea>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ความเห็นถึงบรรณาธิการ (ลับ)' : 'Confidential Comments to Editor' ?>
                </label>
                <textarea name="comments_to_editor" class="form-control" rows="3"
                          placeholder="<?= $_lang==='th' ? 'ความเห็นที่ผู้แต่งจะไม่เห็น...' : 'Comments not visible to the author...' ?>"
                          <?= $readOnly ? 'readonly' : '' ?>><?= e($existingReview['comments_to_editor'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <?php if (!$readOnly): ?>
            <div class="d-flex gap-3 justify-content-end">
              <button type="submit" name="save_draft" value="1" class="btn-outline-custom">
                <i class="fas fa-save me-2"></i><?= $_lang==='th' ? 'บันทึกฉบับร่าง' : 'Save Draft' ?>
              </button>
              <button type="submit" class="btn-primary-custom" data-confirm="<?= $_lang==='th' ? 'ยืนยันการส่งผลประเมิน? ไม่สามารถแก้ไขได้ภายหลัง' : 'Submit this review? It cannot be edited after submission.' ?>">
                <i class="fas fa-paper-plane me-2"></i><?= $_lang==='th' ? 'ส่งผลประเมิน' : 'Submit Review' ?>
              </button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
// Live overall score update
const sliders = document.querySelectorAll('.score-slider');
const overallEl = document.getElementById('overallScore');
function updateOverall() {
  if (!sliders.length || !overallEl) return;
  let sum = 0;
  sliders.forEach(s => sum += parseInt(s.value || 7));
  overallEl.textContent = (sum / sliders.length).toFixed(1);
}
sliders.forEach(s => s.addEventListener('input', updateOverall));
updateOverall();

// Recommendation visual toggle
document.querySelectorAll('.rec-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    const colors = {
      'accept': '#198754', 'minor_revision': '#fd7e14',
      'major_revision': '#dc3545', 'reject': '#6c757d'
    };
    document.querySelectorAll('.rec-option').forEach(opt => {
      const r = opt.closest('label').querySelector('input');
      const c = colors[r.value] || '#6c757d';
      opt.style.background = r.checked ? c : '';
      opt.style.color = r.checked ? '#fff' : '';
    });
  });
});
</script>
</body>
</html>
