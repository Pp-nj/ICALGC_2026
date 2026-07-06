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
               p.keywords, p.submitter_id,
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

    // Paper files (PDF + DOCX)
    $fStmt = $db->prepare("SELECT * FROM paper_files WHERE paper_id = :pid ORDER BY file_type, uploaded_at DESC");
    $fStmt->execute([':pid' => $assignment['paper_id']]);
    $paperFiles = $fStmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    redirect($appUrl . '/reviewer/assigned-papers.php');
}

$readOnly = ($assignment['assignment_status'] === 'completed' || $assignment['assignment_status'] === 'declined');
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$readOnly) {
    Auth::verifyCsrf(post('csrf_token'));

    $scoreFields = ['score_relevance', 'score_methodology', 'score_originality', 'score_contribution', 'score_writing'];
    $scoreMaxes  = [10, 25, 25, 25, 15];
    $scores = [];
    foreach ($scoreFields as $i => $field) {
        $level = intPost($field);
        $step  = $scoreMaxes[$i] / 5;
        $scores[$field] = $level * $step;
        if ($level < 1 || $level > 5)
            $errors[] = ($_lang==='th' ? "เกณฑ์ที่ " . ($i+1) . " ต้องเลือกระดับคะแนน 1–5" : "Criterion " . ($i+1) . " must have a score level of 1–5.");
    }

    $commentsAuthor = trim(post('comment_for_author'));
    $commentsEditor = trim(post('comment_for_editor'));
    $recommendation = post('recommendation');

    $allowedRec = ['accept', 'minor_revision', 'major_revision', 'reject'];
    if (!in_array($recommendation, $allowedRec))
        $errors[] = $_lang==='th' ? 'กรุณาเลือกข้อเสนอแนะ' : 'Please select a recommendation.';
    if (!$commentsAuthor)
        $errors[] = $_lang==='th' ? 'กรุณากรอกความเห็นถึงผู้แต่ง' : 'Comments to author are required.';

    if (empty($errors)) {
        try {
            $overallScore = array_sum(array_values($scores));

            if ($existingReview) {
                $upd = $db->prepare("
                    UPDATE reviews SET
                        score_relevance = :sr, score_methodology = :sm,
                        score_originality = :so, score_contribution = :sc,
                        score_writing = :sw,
                        score_overall = :os, recommendation = :rec,
                        comment_for_author = :ca, comment_for_editor = :ce,
                        review_status = 'submitted', reviewed_at = NOW()
                    WHERE id = :rid
                ");
                $upd->execute([
                    ':sr' => $scores['score_relevance'], ':sm' => $scores['score_methodology'],
                    ':so' => $scores['score_originality'], ':sc' => $scores['score_contribution'],
                    ':sw' => $scores['score_writing'],
                    ':os' => $overallScore, ':rec' => $recommendation,
                    ':ca' => $commentsAuthor, ':ce' => $commentsEditor,
                    ':rid' => $existingReview['id'],
                ]);
            } else {
                $ins = $db->prepare("
                    INSERT INTO reviews
                        (assignment_id, paper_id, reviewer_id, score_relevance, score_methodology,
                         score_originality, score_contribution, score_writing,
                         score_overall, recommendation,
                         comment_for_author, comment_for_editor, review_status, reviewed_at)
                    VALUES
                        (:aid, :pid, :uid, :sr, :sm, :so, :sc, :sw, :os, :rec, :ca, :ce, 'submitted', NOW())
                ");
                $ins->execute([
                    ':aid' => $assignmentId, ':pid' => $assignment['paper_id'], ':uid' => $uid,
                    ':sr' => $scores['score_relevance'], ':sm' => $scores['score_methodology'],
                    ':so' => $scores['score_originality'], ':sc' => $scores['score_contribution'],
                    ':sw' => $scores['score_writing'],
                    ':os' => $overallScore, ':rec' => $recommendation,
                    ':ca' => $commentsAuthor, ':ce' => $commentsEditor,
                ]);
            }

            // Mark assignment as completed
            $db->prepare("UPDATE review_assignments SET assignment_status = 'completed' WHERE id = :aid")
               ->execute([':aid' => $assignmentId]);

            // Notify all admins
            Notification::notifyAdmins(
                'review_result',
                'มีผลประเมินบทคัดย่อใหม่',
                'New Review Submitted',
                "ผู้ทรงคุณวุฒิส่งผลประเมินบทคัดย่อ {$assignment['paper_code']} แล้ว",
                "Reviewer has submitted review for {$assignment['paper_code']}.",
                $assignment['paper_id']
            );

            $msg = $_lang==='th' ? 'ส่งผลประเมินเรียบร้อย' : 'Review submitted successfully.';
            flashSet('success', $msg);
            redirect($appUrl . '/reviewer/review.php?assignment_id=' . $assignmentId);

        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.';
        }
    }
}

$pageTitle  = $_lang==='th' ? 'ประเมินบทคัดย่อ' : 'Evaluate Abstract';
$activeMenu = 'assigned';

$scoreMaxes = [10, 25, 25, 25, 15];
$criteria = $_lang==='th' ? [
    ['key'=>'score_relevance',    'label'=>'ความสอดคล้องของบทคัดย่อกับหัวข้อการประชุม',                       'max'=>10],
    ['key'=>'score_methodology',  'label'=>'ความคิดริเริ่มและประโยชน์ต่อวงการวิชาการหรือวิชาชีพ',              'max'=>25],
    ['key'=>'score_originality',  'label'=>'ความเหมาะสมของแนวคิดหรือทฤษฎีที่ใช้',                                                      'max'=>25],
    ['key'=>'score_contribution', 'label'=>'ระเบียบวิธีวิจัยมีความเหมาะสมและความสอดคล้องกับวัตถุประสงค์',                                              'max'=>25],
    ['key'=>'score_writing',      'label'=>'การใช้ภาษาและสามารถสื่อสารเชิงวิชาการได้อย่างถูกต้องและเหมาะสม',                               'max'=>15],
] : [
    ['key'=>'score_relevance',    'label'=>'Relevance of the Abstract to the Conference Theme',         'max'=>10],
    ['key'=>'score_methodology',  'label'=>'Originality and Benefit to the Academic or Professional Field',   'max'=>25],
    ['key'=>'score_originality',  'label'=>'Appropriateness of the Concept or Theory Used',                         'max'=>25],
    ['key'=>'score_contribution', 'label'=>'Appropriateness of Research Methodology',                                   'max'=>25],
    ['key'=>'score_writing',      'label'=>'Language Use and Academic Communication',                  'max'=>15],
];

$recommendations = [
    'accept'         => ['th'=>'รับนำเสนอ', 'en'=>'Accept', 'color'=>'#198754'],
    'minor_revision' => ['th'=>'รับนำเสนอโดยปรับแก้เล็กน้อย', 'en'=>'Accept with Minor Revision', 'color'=>'#fd7e14'],
    'major_revision' => ['th'=>'รับนำเสนอโดยปรับแก้สาระสำคัญ', 'en'=>'Accept with Major Revision', 'color'=>'#dc3545'],
    'reject'         => ['th'=>'ไม่รับนำเสนอ', 'en'=>'Reject', 'color'=>'#6c757d'],
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
            <?= $_lang==='th' ? 'บทคัดย่อที่ได้รับ' : 'Assigned Papers' ?>
          </a>
          <i class="fas fa-chevron-right mx-1" style="font-size:.7rem;"></i>
          <code style="color:var(--blue-mid);"><?= e($assignment['paper_code']) ?></code>
        </p>
      </div>
      <?php if ($readOnly && $assignment['assignment_status'] === 'completed'): ?>
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
            <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ข้อมูลบทคัดย่อ' : 'Paper Information' ?>
          </div>
          <code style="font-size:.82rem;color:var(--blue-mid);"><?= e($assignment['paper_code']) ?></code>
          <h5 style="font-weight:800;color:var(--blue-dark);margin:8px 0 4px;font-size:.95rem;">
            <?= e($_lang==='th' ? $assignment['title_th'] : $assignment['title_en']) ?>
          </h5>
          <div style="font-size:.8rem;color:var(--gold);font-weight:600;margin-bottom:12px;">
            <?= e($_lang==='th' ? $assignment['theme_th'] : $assignment['theme_en']) ?>
          </div>
          <?php if ($assignment['keywords'] ?? ''): ?>
            <div class="mt-3 d-flex flex-wrap gap-1">
              <?php foreach (explode(',', $assignment['keywords'] ?? '') as $kw): ?>
                <span class="badge" style="background:var(--blue-dark);color:#fff;font-size:.7rem;font-weight:400;"><?= e(trim($kw)) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php foreach ($paperFiles as $pf): ?>
            <div class="mt-3">
              <a href="<?= $appUrl ?>/download.php?file_id=<?= (int)$pf['id'] ?>"
                 class="btn-primary-custom d-block text-center" style="font-size:.85rem;">
                <i class="fas fa-download me-2"></i><?= $_lang==='th' ? 'ดาวน์โหลดไฟล์' : 'Download File' ?> (<?= strtoupper($pf['file_type']) ?>)
              </a>
            </div>
          <?php endforeach; ?>
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
              <i class="fas fa-star-half-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'เกณฑ์การประเมิน' : 'Evaluation Criteria' ?>
            </div>
            <div class="d-flex flex-column gap-4">
              <?php foreach ($criteria as $i => $crit):
                $fieldName    = $crit['key'];
                $maxScore     = $crit['max'];
                $step         = $maxScore / 5;
                $storedScore  = (float)($_POST[$fieldName] ?? ($existingReview[$fieldName] ?? 0));
                $currentLevel = ($storedScore > 0) ? max(1, min(5, (int)round($storedScore / $step))) : 0;
              ?>
                <div class="criterion-block">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <label class="form-label fw-bold mb-0" style="font-size:1rem;color:var(--blue-dark);flex:1;padding-right:8px;">
                      <?= $i+1 ?>. <?= $crit['label'] ?>
                    </label>
                    <div class="text-end" style="white-space:nowrap;">
                      <span class="score-display fw-bold" id="disp_<?= $i ?>" style="color:var(--blue-dark);font-size:1.1rem;">
                        <?= $currentLevel > 0 ? $currentLevel * $step : '–' ?>
                      </span>
                      <span style="color:var(--gray-400);font-size:.9rem;"> / <?= $maxScore ?></span>
                    </div>
                  </div>
                  <input type="hidden" name="<?= $fieldName ?>" id="inp_<?= $i ?>" value="<?= $currentLevel ?>">
                  <div class="score-btn-group d-flex gap-2" style="width:100%;justify-content:space-between;">
                    <?php for ($lvl = 1; $lvl <= 5; $lvl++): ?>
                      <button type="button"
                              class="score-btn <?= $readOnly ? 'score-btn-readonly' : '' ?> <?= $currentLevel === $lvl ? 'score-btn-active' : '' ?>"
                              data-criterion="<?= $i ?>"
                              data-level="<?= $lvl ?>"
                              data-score="<?= $lvl * $step ?>"
                              data-max="<?= $maxScore ?>"
                              <?= $readOnly ? 'disabled' : '' ?>>
                        <?= $lvl ?>
                      </button>
                    <?php endfor; ?>
                  </div>
                  <div class="d-flex justify-content-between mt-1" style="font-size:.7rem;color:var(--gray-400);">
                    <span><?= $_lang==='th' ? 'ต่ำสุด' : 'Poor' ?></span>
                    <span><?= $_lang==='th' ? 'ดีเยี่ยม' : 'Excellent' ?></span>
                  </div>
                </div>
              <?php endforeach; ?>

              <!-- Overall Score Display -->
              <div class="p-3 rounded text-center" style="background:var(--blue-dark);color:#fff;">
                <div style="font-size:.8rem;opacity:.7;"><?= $_lang==='th' ? 'คะแนนรวมทั้งหมด' : 'Total Score' ?></div>
                <div style="font-size:2rem;font-weight:800;" id="overallScore">
                  <?= $existingReview ? number_format($existingReview['score_overall'], 0) : '–' ?>
                </div>
                <div style="font-size:.75rem;opacity:.6;">/100</div>
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
                <textarea name="comment_for_author" class="form-control" rows="5"
                          placeholder="<?= $_lang==='th' ? 'ข้อเสนอแนะที่สร้างสรรค์สำหรับผู้แต่ง...' : 'Constructive feedback for the author...' ?>"
                          <?= $readOnly ? 'readonly' : '' ?>><?= e($existingReview['comment_for_author'] ?? '') ?></textarea>
              </div>
              <div>
                <label class="form-label fw-bold" style="font-size:.85rem;">
                  <?= $_lang==='th' ? 'ความเห็นถึงบรรณาธิการ (ลับ)' : 'Confidential Comments to Editor' ?>
                </label>
                <textarea name="comment_for_editor" class="form-control" rows="3"
                          placeholder="<?= $_lang==='th' ? 'ความเห็นที่ผู้แต่งจะไม่เห็น...' : 'Comments not visible to the author...' ?>"
                          <?= $readOnly ? 'readonly' : '' ?>><?= e($existingReview['comment_for_editor'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <?php if (!$readOnly): ?>
            <div class="d-flex gap-3 justify-content-end">
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
// Score button logic
const overallEl = document.getElementById('overallScore');
function updateOverall() {
  if (!overallEl) return;
  let sum = 0;
  let allSelected = true;
  document.querySelectorAll('[id^="inp_"]').forEach(inp => {
    const v = parseInt(inp.dataset.actualScore || 0);
    if (!v) allSelected = false;
    sum += v;
  });
  overallEl.textContent = allSelected ? sum : '–';
}

document.querySelectorAll('.score-btn:not([disabled])').forEach(btn => {
  btn.addEventListener('click', function () {
    const ci = this.dataset.criterion;
    const level = parseInt(this.dataset.level);
    const score = parseFloat(this.dataset.score);
    const inp = document.getElementById('inp_' + ci);
    inp.value = level;
    inp.dataset.actualScore = score;
    // Update display
    const dispEl = document.getElementById('disp_' + ci);
    if (dispEl) dispEl.textContent = score;
    // Highlight active button
    document.querySelectorAll(`.score-btn[data-criterion="${ci}"]`).forEach(b => b.classList.remove('score-btn-active'));
    this.classList.add('score-btn-active');
    updateOverall();
  });
});

// Init actual scores from existing values
document.querySelectorAll('[id^="inp_"]').forEach(inp => {
  const level = parseInt(inp.value || 0);
  if (level > 0) {
    const ci = inp.id.replace('inp_', '');
    const btn = document.querySelector(`.score-btn[data-criterion="${ci}"][data-level="${level}"]`);
    if (btn) inp.dataset.actualScore = btn.dataset.score;
  }
});
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
