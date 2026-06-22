<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Database;

$pageTitle = lang()==='th' ? 'เชิญชวนส่งบทคัดย่อ' : 'Call for Abstract';
$activeNav = 'cfa';
$_lang     = lang();
$appUrl    = APP_URL;

// Fetch themes from DB
$themes = [];
try {
    $db   = Database::getInstance();
    $stmt = $db->query("SELECT * FROM conference_themes WHERE is_active = TRUE ORDER BY code");
    $themes = $stmt->fetchAll();
} catch (\Throwable $e) {}

// Fetch important dates
$importantDates = [];
try {
    $db   = Database::getInstance();
    $stmt = $db->query("SELECT * FROM important_dates WHERE is_active = TRUE ORDER BY sort_order");
    $importantDates = $stmt->fetchAll();
} catch (\Throwable $e) {}

require_once __DIR__ . '/../app/helpers/header.php';
?>

<!-- Banner -->
<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);">ICALGC 2026</span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;"><?= t('cfa.title') ?></h1>
    <p style="color:rgba(255,255,255,.8);"><?= e(CONF_NAME_EN) ?></p>
    <a href="<?= $appUrl ?>/author/submit.php" class="btn-hero-primary mt-3" style="display:inline-block;">
      <i class="fas fa-file-upload me-2"></i>
      <?= $_lang==='th'?'ส่งบทคัดย่อเลย':'Submit Your Abstract Now' ?>
    </a>
  </div>
</div>

<section class="page-section">
  <div class="container">
    <div class="row g-5">

      <!-- Main Content -->
      <div class="col-lg-8">

        <!-- Submission Details -->
        <div class="content-card cfa-section" id="submission-details">
          <div class="content-card-title"><i class="fas fa-file-alt me-2" style="color:var(--gold);"></i><?= t('cfa.submission_details') ?></div>

          <?php if ($_lang==='th'): ?>
            <p>ขอเชิญนักวิชาการ นักวิจัย อาจารย์ นักศึกษาระดับบัณฑิตศึกษา และผู้สนใจจากทั่วโลก ส่งบทคัดย่อเพื่อนำเสนอในการประชุม ICALGC 2026 ทั้งในรูปแบบบรรยาย (Oral Presentation) และโปสเตอร์ (Poster Presentation)</p>
            <ul style="line-height:2;color:var(--gray-700);">
              <li><strong>ภาษาที่ใช้:</strong> ไทยหรืออังกฤษ</li>
              <li><strong>ความยาวบทคัดย่อ:</strong> 250–350 คำ</li>
              <li><strong>รูปแบบ:</strong> PDF หรือ DOCX</li>
              <li><strong>ขนาดไฟล์:</strong> ไม่เกิน 20 MB</li>
            </ul>
          <?php else: ?>
            <p>We invite academics, researchers, lecturers, graduate students, and interested parties from around the world to submit abstracts for presentation at ICALGC 2026 in both oral and poster presentation formats.</p>
            <ul style="line-height:2;color:var(--gray-700);">
              <li><strong>Language:</strong> Thai or English</li>
              <li><strong>Abstract Length:</strong> 250–350 words</li>
              <li><strong>Format:</strong> PDF or DOCX</li>
              <li><strong>File Size:</strong> Not exceeding 20 MB</li>
            </ul>
          <?php endif; ?>
        </div>

        <!-- Conference Themes -->
        <div class="content-card cfa-section" id="themes">
          <div class="content-card-title"><i class="fas fa-layer-group me-2" style="color:var(--gold);"></i><?= t('cfa.themes') ?></div>
          <div class="row g-3">
            <?php foreach ($themes as $i => $theme): ?>
              <div class="col-md-6">
                <div class="theme-card">
                  <div class="theme-number"><?= $i+1 ?></div>
                  <div>
                    <div style="font-weight:700;color:var(--blue-dark);font-size:.9rem;line-height:1.4;">
                      <?= e($_lang==='th' ? $theme['name_th'] : $theme['name_en']) ?>
                    </div>
                    <?php if ($theme['description']): ?>
                      <div style="font-size:.8rem;color:var(--gray-500);margin-top:4px;"><?= e($theme['description']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Template Download -->
        <div class="content-card cfa-section" id="template">
          <div class="content-card-title"><i class="fas fa-file-download me-2" style="color:var(--gold);"></i><?= t('cfa.template') ?></div>
          <p style="color:var(--gray-700);">
            <?= $_lang==='th'
              ? 'ดาวน์โหลดแบบฟอร์มสำหรับการเขียนบทคัดย่อตามรูปแบบที่กำหนด'
              : 'Download the abstract template in the required format.' ?>
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a href="<?= $appUrl ?>/assets/templates/abstract-template-en.docx"
               class="btn-primary-custom d-flex align-items-center gap-2" download>
              <i class="fas fa-file-word"></i>
              <?= $_lang==='th'?'แบบฟอร์มภาษาอังกฤษ (DOCX)':'English Template (DOCX)' ?>
            </a>
            <a href="<?= $appUrl ?>/assets/templates/abstract-template-th.docx"
               class="btn-outline-custom d-flex align-items-center gap-2" download>
              <i class="fas fa-file-word"></i>
              <?= $_lang==='th'?'แบบฟอร์มภาษาไทย (DOCX)':'Thai Template (DOCX)' ?>
            </a>
          </div>
        </div>

        <!-- Submission Guidelines -->
        <div class="content-card cfa-section" id="guidelines">
          <div class="content-card-title"><i class="fas fa-list-check me-2" style="color:var(--gold);"></i><?= t('cfa.guidelines') ?></div>
          <?php if ($_lang==='th'): ?>
            <ol style="line-height:2;color:var(--gray-700);padding-left:20px;">
              <li>บทคัดย่อต้องเป็นผลงานวิจัยต้นฉบับ ยังไม่เคยตีพิมพ์หรือนำเสนอที่ใดมาก่อน</li>
              <li>บทคัดย่อต้องมีความยาว 250–350 คำ และระบุคำสำคัญ 3–5 คำ</li>
              <li>ต้องระบุชื่อบทความทั้งในภาษาไทยและภาษาอังกฤษ</li>
              <li>ต้องระบุชื่อผู้แต่งทุกคน สังกัด และอีเมลของผู้แต่งผู้รับผิดชอบ</li>
              <li>ส่งบทคัดย่อในรูปแบบ PDF หรือ DOCX เท่านั้น ขนาดไม่เกิน 20 MB</li>
              <li>ผลการพิจารณาถือเป็นที่สิ้นสุด</li>
            </ol>
          <?php else: ?>
            <ol style="line-height:2;color:var(--gray-700);padding-left:20px;">
              <li>The abstract must be an original research work, not previously published or presented.</li>
              <li>The abstract must be 250–350 words and include 3–5 keywords.</li>
              <li>The title must be provided in both Thai and English.</li>
              <li>All authors must be listed with affiliations and the corresponding author's email.</li>
              <li>Submit in PDF or DOCX format only, maximum 20 MB.</li>
              <li>The review committee's decision is final.</li>
            </ol>
          <?php endif; ?>
        </div>

        <!-- Important Dates -->
        <div class="content-card cfa-section" id="important-dates">
          <div class="content-card-title"><i class="fas fa-calendar-check me-2" style="color:var(--gold);"></i><?= t('cfa.dates') ?></div>
          <div class="table-responsive">
            <table class="table-custom">
              <thead>
                <tr>
                  <th><?= $_lang==='th'?'กิจกรรม':'Activity' ?></th>
                  <th><?= $_lang==='th'?'กำหนดการ':'Date' ?></th>
                  <th><?= $_lang==='th'?'สถานะ':'Status' ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($importantDates as $d):
                  $daysLeft = daysUntil($d['event_date']);
                  if ($daysLeft < 0)       { $status = t('dates.passed');       $color = '#dc3545'; }
                  elseif ($daysLeft === 0) { $status = t('dates.today');        $color = '#198754'; }
                  else                     { $status = t('dates.days_left', ['n' => $daysLeft]); $color = '#0057b7'; }
                ?>
                  <tr>
                    <td style="font-weight:600;"><?= e($_lang==='th'?$d['title_th']:$d['title_en']) ?></td>
                    <td><?= humanDate($d['event_date']) ?></td>
                    <td><span class="status-badge" style="background:<?= $color ?>;color:#fff;"><?= e($status) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Review Process -->
        <div class="content-card cfa-section" id="review-process">
          <div class="content-card-title"><i class="fas fa-search me-2" style="color:var(--gold);"></i><?= t('cfa.review_process') ?></div>
          <?php if ($_lang==='th'): ?>
            <div class="row g-3">
              <?php
              $steps = [
                ['icon'=>'paper-plane','title'=>'ส่งบทคัดย่อ','desc'=>'ผู้แต่งส่งบทคัดย่อผ่านระบบออนไลน์'],
                ['icon'=>'filter','title'=>'คัดกรองเบื้องต้น','desc'=>'คณะกรรมการตรวจสอบความถูกต้องและความสมบูรณ์'],
                ['icon'=>'user-check','title'=>'พิจารณาโดยผู้ทรงคุณวุฒิ','desc'=>'Peer Review แบบ Double-blind โดยผู้ทรงคุณวุฒิ 2 ท่าน'],
                ['icon'=>'check-circle','title'=>'แจ้งผลการพิจารณา','desc'=>'แจ้งผลผ่านระบบและอีเมลภายในกำหนด'],
              ];
              foreach ($steps as $i => $step): ?>
                <div class="col-md-6">
                  <div class="d-flex gap-3 p-3 rounded" style="background:var(--gray-100);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:40px;height:40px;background:var(--blue-dark);color:var(--gold);font-weight:700;"><?= $i+1 ?></div>
                    <div>
                      <div style="font-weight:700;color:var(--blue-dark);font-size:.9rem;"><?= e($step['title']) ?></div>
                      <div style="font-size:.82rem;color:var(--gray-700);"><?= e($step['desc']) ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php
              $steps = [
                ['title'=>'Abstract Submission','desc'=>'Authors submit abstracts via the online system.'],
                ['title'=>'Initial Screening','desc'=>'The committee verifies completeness and relevance.'],
                ['title'=>'Peer Review','desc'=>'Double-blind review by two qualified experts.'],
                ['title'=>'Decision Notification','desc'=>'Results communicated via system and email.'],
              ];
              foreach ($steps as $i => $step): ?>
                <div class="col-md-6">
                  <div class="d-flex gap-3 p-3 rounded" style="background:var(--gray-100);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:40px;height:40px;background:var(--blue-dark);color:var(--gold);font-weight:700;"><?= $i+1 ?></div>
                    <div>
                      <div style="font-weight:700;color:var(--blue-dark);font-size:.9rem;"><?= e($step['title']) ?></div>
                      <div style="font-size:.82rem;color:var(--gray-700);"><?= e($step['desc']) ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Conditions -->
        <div class="content-card cfa-section" id="conditions">
          <div class="content-card-title"><i class="fas fa-file-contract me-2" style="color:var(--gold);"></i><?= t('cfa.conditions') ?></div>
          <?php if ($_lang==='th'): ?>
            <ul style="line-height:2.2;color:var(--gray-700);">
              <li>ผู้แต่งต้องลงทะเบียนในระบบก่อนส่งบทคัดย่อ</li>
              <li>บทคัดย่อที่ได้รับการยอมรับต้องนำเสนอในการประชุม มิฉะนั้นจะไม่ได้รับการตีพิมพ์</li>
              <li>ผู้แต่งต้องชำระค่าลงทะเบียนก่อนวันประชุม</li>
              <li>บทความที่นำเสนอและได้รับการยอมรับจะได้รับการเผยแพร่ใน Proceedings ของการประชุม</li>
              <li>คณะกรรมการสงวนสิทธิ์ในการปฏิเสธบทความที่ไม่ตรงตามเกณฑ์ที่กำหนด</li>
            </ul>
          <?php else: ?>
            <ul style="line-height:2.2;color:var(--gray-700);">
              <li>Authors must register in the system before submitting an abstract.</li>
              <li>Accepted abstracts must be presented at the conference; otherwise, they will not be published.</li>
              <li>Authors must pay the registration fee before the conference date.</li>
              <li>Accepted and presented papers will be published in the conference proceedings.</li>
              <li>The committee reserves the right to reject papers that do not meet the specified criteria.</li>
            </ul>
          <?php endif; ?>

          <div class="mt-4 d-flex gap-3 flex-wrap">
            <a href="<?= $appUrl ?>/author/submit.php" class="btn-primary-custom">
              <i class="fas fa-file-upload me-2"></i>
              <?= $_lang==='th'?'ส่งบทคัดย่อ':'Submit Abstract' ?>
            </a>
            <a href="<?= $appUrl ?>/register.php" class="btn-outline-custom">
              <i class="fas fa-user-plus me-2"></i>
              <?= t('nav.register') ?>
            </a>
          </div>
        </div>

      </div>

      <!-- Sidebar TOC -->
      <div class="col-lg-4">
        <div class="cfa-toc" style="position:sticky;top:100px;">
          <div class="cfa-toc-title"><i class="fas fa-list me-2"></i><?= $_lang==='th'?'สารบัญ':'Contents' ?></div>
          <a href="#submission-details"><?= t('cfa.submission_details') ?></a>
          <a href="#themes"><?= t('cfa.themes') ?></a>
          <a href="#template"><?= t('cfa.template') ?></a>
          <a href="#guidelines"><?= t('cfa.guidelines') ?></a>
          <a href="#important-dates"><?= t('cfa.dates') ?></a>
          <a href="#review-process"><?= t('cfa.review_process') ?></a>
          <a href="#conditions"><?= t('cfa.conditions') ?></a>

          <div class="mt-4 p-3 rounded" style="background:var(--blue-dark);">
            <div style="color:var(--gold);font-weight:700;font-size:.85rem;margin-bottom:8px;">
              <i class="fas fa-clock me-1"></i>
              <?= $_lang==='th'?'หมดเขตส่งบทคัดย่อ':'Submission Deadline' ?>
            </div>
            <div style="color:var(--white);font-size:1rem;font-weight:800;">
              <?= $_lang==='th'?'30 สิงหาคม 2569':'30 August 2026' ?>
            </div>
            <a href="<?= $appUrl ?>/author/submit.php" class="btn-hero-primary d-block text-center mt-3" style="font-size:.85rem;">
              <?= $_lang==='th'?'ส่งเลย':'Submit Now' ?>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
