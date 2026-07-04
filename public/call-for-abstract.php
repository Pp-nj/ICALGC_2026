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
    <div class="section-divider"></div>
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
            <p>ขอเชิญนักวิชาการ นักวิจัย อาจารย์ นักศึกษาระดับบัณฑิตศึกษา และผู้สนใจจากทั่วโลก ส่งบทคัดย่อเพื่อนำเสนอในการประชุม ICALGC 2026 ในรูปแบบบรรยาย (Oral Presentation) </p>
            <ul style="line-height:2;color:var(--gray-700);">
              <li><strong>ภาษาที่ใช้:</strong> ไทยหรืออังกฤษ</li>
              <li><strong>ความยาวบทคัดย่อ:</strong> 250–350 คำ</li>
              <li><strong>รูปแบบ:</strong> PDF หรือ DOCX</li>
              <li><strong>ขนาดไฟล์:</strong> ไม่เกิน 20 MB</li>
            </ul>
          <?php else: ?>
            <p>We invite academics, researchers, lecturers, graduate students, and interested parties from around the world to submit abstracts for presentation at ICALGC 2026.</p>
            <ul style="line-height:2;color:var(--gray-700);">
              <li><strong>Language:</strong> Thai or English</li>
              <li><strong>Abstract Length:</strong> 250–350 words</li>
              <li><strong>Format:</strong> PDF or DOCX</li>
              <li><strong>File Size:</strong> Not exceeding 20 MB</li>
            </ul>
          <?php endif; ?>
        </div>

        <!-- Conference Tracks -->
        <div class="content-card cfa-section" id="tracks" style="padding:0;overflow:hidden;">
          <div style="padding:20px 24px 0;"><div class="content-card-title" style="margin-bottom:16px;"><i class="fas fa-road me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'สาขาหัวข้อการประชุม (Conference Tracks)':'Conference Tracks' ?></div></div>

          <div class="row g-0">

            <!-- Track 1: Navy Blue (ASEAN) -->
            <div class="col-md-6">
              <div style="background:#0a1f44;min-height:100%;padding:24px;color:#fff;display:flex;flex-direction:column;">

                <!-- Track Header -->
                <div style="background:#1a3a6b;border-radius:10px;padding:14px 16px;margin-bottom:16px;text-align:center;">
                  <span style="display:inline-block;background:#c9a227;color:#0a1f44;font-weight:800;font-size:.75rem;letter-spacing:2px;padding:3px 12px;border-radius:20px;margin-bottom:8px;">TRACK 1</span>
                  <div style="font-weight:800;font-size:1rem;line-height:1.4;color:#fff;">ASEAN Languages, ASEAN Studies,<br>and ASEAN Language Teaching</div>
                  <div style="font-size:.8rem;color:#a0b8d8;margin-top:6px;">(Vietnamese, Cambodian/Khmer, Myanmar/Burmese, Lao, Indonesia and Malay Languages)</div>
                </div>

                <!-- Banner Image Placeholder -->
                <div style="position:relative;border-radius:10px;overflow:hidden;margin-bottom:16px;background:#1a3a6b;height:140px;display:flex;align-items:center;justify-content:center;border:2px dashed #2a5a9b;">
                  <div style="text-align:center;color:#6a9abf;">
                    <i class="fas fa-image" style="font-size:2rem;margin-bottom:6px;display:block;"></i>
                    <span style="font-size:.75rem;"><?= $_lang==='th'?'[รูปสถานที่สำคัญของประเทศในอาเซียน]':'[Banner: ASEAN Landmark / Heritage Image]' ?></span>
                  </div>
                  <img src="<?= $appUrl ?>/assets/images/track-asean.jpg" alt="ASEAN"
                       style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                       onerror="this.remove()">
                </div>

                <!-- Scope -->
                <div style="background:#1a3a6b;border-radius:10px;padding:14px;">
                  <div style="font-weight:700;font-size:.85rem;color:#c9a227;margin-bottom:10px;border-bottom:1px solid #2a5a9b;padding-bottom:6px;">
                    <i class="fas fa-crosshairs me-1"></i>
                    <?= $_lang==='th'?'ขอบเขตหัวข้อที่ครอบคลุม':'Scope & Topics Covered' ?>
                  </div>
                  <div class="row g-0">
                    <?php
                    $track1Items = $_lang==='th' ? [
                      ['icon'=>'language',         'text'=>'ภาษาศาสตร์ภาษาอาเซียน'],
                      ['icon'=>'globe-asia',        'text'=>'อาเซียนศึกษา'],
                      ['icon'=>'chalkboard-teacher','text'=>'การสอนภาษาในอาเซียน'],
                      ['icon'=>'book-open',         'text'=>'วรรณกรรมอาเซียน'],
                      ['icon'=>'exchange-alt',      'text'=>'การแปลและการล่าม'],
                      ['icon'=>'users',             'text'=>'ภาษาและวัฒนธรรมอาเซียน'],
                      ['icon'=>'laptop-code',       'text'=>'เทคโนโลยีทางภาษา'],
                      ['icon'=>'scroll',            'text'=>'นโยบายภาษาในอาเซียน'],
                    ] : [
                      ['icon'=>'language',         'text'=>'ASEAN Linguistics'],
                      ['icon'=>'globe-asia',        'text'=>'ASEAN Studies'],
                      ['icon'=>'chalkboard-teacher','text'=>'Language Teaching in ASEAN'],
                      ['icon'=>'book-open',         'text'=>'ASEAN Literature'],
                      ['icon'=>'exchange-alt',      'text'=>'Translation & Interpretation'],
                      ['icon'=>'users',             'text'=>'Language & Culture in ASEAN'],
                      ['icon'=>'laptop-code',       'text'=>'Language Technology'],
                      ['icon'=>'scroll',            'text'=>'Language Policy in ASEAN'],
                    ];
                    $half = (int)ceil(count($track1Items)/2);
                    $col1 = array_slice($track1Items,0,$half);
                    $col2 = array_slice($track1Items,$half);
                    ?>
                    <div class="col-6">
                      <?php foreach($col1 as $item): ?>
                        <div style="display:flex;align-items:flex-start;gap:6px;margin-bottom:7px;">
                          <i class="fas fa-<?= $item['icon'] ?>" style="color:#c9a227;font-size:.75rem;margin-top:3px;flex-shrink:0;"></i>
                          <span style="font-size:.78rem;color:#d0e4f5;line-height:1.3;"><?= $item['text'] ?></span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                    <div class="col-6">
                      <?php foreach($col2 as $item): ?>
                        <div style="display:flex;align-items:flex-start;gap:6px;margin-bottom:7px;">
                          <i class="fas fa-<?= $item['icon'] ?>" style="color:#c9a227;font-size:.75rem;margin-top:3px;flex-shrink:0;"></i>
                          <span style="font-size:.78rem;color:#d0e4f5;line-height:1.3;"><?= $item['text'] ?></span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <!-- ASEAN Country Flags -->
                <div style="margin-top:auto;padding-top:16px;">
                  <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
                    <?php
                    $aseanFlags = [
                      ['flag'=>'/vietnam.webp','name'=>'Vietnam'],
                      ['flag'=>'/cambodia.webp','name'=>'Cambodia'],
                      ['flag'=>'/myanmar.webp','name'=>'Myanmar'],
                      ['flag'=>'/laos.webp','name'=>'Laos'],
                      ['flag'=>'/indonesia.webp','name'=>'Indonesia'],
                      ['flag'=>'/malaysia.webp','name'=>'Malaysia'],
                    ];
                    foreach($aseanFlags as $f): ?>
                      <div style="text-align:center;min-width:44px;">
                        <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;border:2px solid #2a5a9b;display:flex;align-items:center;justify-content:center;background:#1a3a6b;margin:0 auto;">
                          <img src="/assets/images/flags/<?= $f['flag'] ?>" alt="<?= $f['name'] ?>" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div style="font-size:.62rem;color:#a0b8d8;margin-top:4px;line-height:1.2;"><?= $f['name'] ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

              </div>
            </div>

            <!-- Track 2: Gold/Brown (Thai) -->
            <div class="col-md-6">
              <div style="background:#4a2c0a;min-height:100%;padding:24px;color:#fff;display:flex;flex-direction:column;">

                <!-- Track Header -->
                <div style="background:#6b3f15;border-radius:10px;padding:14px 16px;margin-bottom:16px;text-align:center;">
                  <span style="display:inline-block;background:#c9a227;color:#4a2c0a;font-weight:800;font-size:.75rem;letter-spacing:2px;padding:3px 12px;border-radius:20px;margin-bottom:8px;">TRACK 2</span>
                  <div style="font-weight:800;font-size:1rem;line-height:1.4;color:#fff;">Thai Language, Thai Studies,<br>and Thai Language Teaching</div>
                  <div style="font-size:.8rem;color:#d4b896;margin-top:6px;">(Thai Language, Literature, Folklore <br> and Thai Language Pedagog)</div>
                </div>

                <!-- Banner Image Placeholder -->
                <div style="position:relative;border-radius:10px;overflow:hidden;margin-bottom:16px;background:#6b3f15;height:140px;display:flex;align-items:center;justify-content:center;border:2px dashed #9b6f3a;">
                  <div style="text-align:center;color:#c4945a;">
                    <i class="fas fa-image" style="font-size:2rem;margin-bottom:6px;display:block;"></i>
                    <span style="font-size:.75rem;"><?= $_lang==='th'?'[รูปสถานที่สำคัญ/ศิลปวัฒนธรรมของไทย]':'[Banner: Thai Cultural Heritage / Art Image]' ?></span>
                  </div>
                  <img src="<?= $appUrl ?>/assets/images/track-thai.webp" alt="Thai"
                       style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                       onerror="this.remove()">
                </div>

                <!-- Scope -->
                <div style="background:#6b3f15;border-radius:10px;padding:14px;">
                  <div style="font-weight:700;font-size:.85rem;color:#c9a227;margin-bottom:10px;border-bottom:1px solid #9b6f3a;padding-bottom:6px;">
                    <i class="fas fa-crosshairs me-1"></i>
                    <?= $_lang==='th'?'ขอบเขตหัวข้อที่ครอบคลุม':'Scope & Topics Covered' ?>
                  </div>
                  <div class="row g-0">
                    <?php
                    $track2Items = $_lang==='th' ? [
                      ['icon'=>'language',          'text'=>'ภาษาศาสตร์ภาษาไทย'],
                      ['icon'=>'chalkboard-teacher','text'=>'การสอนภาษาไทย'],
                      ['icon'=>'book-open',         'text'=>'วรรณกรรมไทย'],
                      ['icon'=>'globe',             'text'=>'ภาษาไทยสำหรับชาวต่างชาติ'],
                      ['icon'=>'theater-masks',     'text'=>'วัฒนธรรมและภาษาไทย'],
                      ['icon'=>'laptop-code',       'text'=>'ภาษาไทยในยุคดิจิทัล'],
                      ['icon'=>'search',            'text'=>'ไทยศึกษา'],
                      ['icon'=>'shield-alt',        'text'=>'การอนุรักษ์ภาษาไทย'],
                    ] : [
                      ['icon'=>'language',          'text'=>'Thai Linguistics'],
                      ['icon'=>'chalkboard-teacher','text'=>'Thai Language Teaching'],
                      ['icon'=>'book-open',         'text'=>'Thai Literature'],
                      ['icon'=>'globe',             'text'=>'Thai for Foreign Learners'],
                      ['icon'=>'theater-masks',     'text'=>'Thai Culture & Language'],
                      ['icon'=>'laptop-code',       'text'=>'Thai Language in Digital Era'],
                      ['icon'=>'search',            'text'=>'Thai Studies'],
                      ['icon'=>'shield-alt',        'text'=>'Thai Language Preservation'],
                    ];
                    $half2 = (int)ceil(count($track2Items)/2);
                    $col1t2 = array_slice($track2Items,0,$half2);
                    $col2t2 = array_slice($track2Items,$half2);
                    ?>
                    <div class="col-6">
                      <?php foreach($col1t2 as $item): ?>
                        <div style="display:flex;align-items:flex-start;gap:6px;margin-bottom:7px;">
                          <i class="fas fa-<?= $item['icon'] ?>" style="color:#c9a227;font-size:.75rem;margin-top:3px;flex-shrink:0;"></i>
                          <span style="font-size:.78rem;color:#f0dfc0;line-height:1.3;"><?= $item['text'] ?></span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                    <div class="col-6">
                      <?php foreach($col2t2 as $item): ?>
                        <div style="display:flex;align-items:flex-start;gap:6px;margin-bottom:7px;">
                          <i class="fas fa-<?= $item['icon'] ?>" style="color:#c9a227;font-size:.75rem;margin-top:3px;flex-shrink:0;"></i>
                          <span style="font-size:.78rem;color:#f0dfc0;line-height:1.3;"><?= $item['text'] ?></span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <!-- ASEAN Country Flags -->
                <div style="margin-top:auto;padding-top:16px;">
                  <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
                    <?php
                    $aseanFlags = [
                      ['flag'=>'Thailand.webp','name'=>'Thailand']
                    ];
                    foreach($aseanFlags as $f): ?>
                      <div style="text-align:center;min-width:44px;">
                        <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;border:2px solid #2a5a9b;display:flex;align-items:center;justify-content:center;background:#1a3a6b;margin:0 auto;">
                          <img src="/assets/images/flags/<?= $f['flag'] ?>" alt="<?= $f['name'] ?>" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div style="font-size:.62rem;color:#a0b8d8;margin-top:4px;line-height:1.2;"><?= $f['name'] ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

              </div>
            </div>

          </div><!-- /row -->
        </div><!-- /tracks -->

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
            <a href="<?= $appUrl ?>/assets/template/Template-Abstract.docx"
               class="btn-primary-custom d-flex align-items-center gap-2" download>
              <i class="fas fa-file-word"></i>
              <?= $_lang==='th'?'รูปแบบบทคัดย่อ (DOCX)':'Template-Abstract (DOCX)' ?>
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
              <li>ต้องระบุชื่อบทคัดย่อทั้งในภาษาไทย/ภาษาอาเซียนและภาษาอังกฤษ</li>
              <li>ต้องระบุชื่อผู้แต่งทุกคน สังกัด และอีเมลของผู้แต่งผู้รับผิดชอบ</li>
              <li>ส่งบทคัดย่อในรูปแบบ PDF และ DOCX เท่านั้น ขนาดไม่เกิน 20 MB</li>
              <li>ไม่ใส่ตารางรูปภาพและแผนภูมิ</li>
              <li>ผลการพิจารณาจากผู้ทรงคุณวุฒิถือเป็นที่สิ้นสุด</li>
            </ol>
          <?php else: ?>
            <ol style="line-height:2;color:var(--gray-700);padding-left:20px;">
              <li>The abstract must be an original research work, not previously published or presented.</li>
              <li>The abstract must be 250–350 words and include 3–5 keywords.</li>
              <li>The title must be provided in both Thai/ASEAN languages ​​and English.</li>
              <li>All authors must be listed with affiliations and the corresponding author's email.</li>
              <li>Submit in PDF and DOCX format only, maximum 20 MB.</li>
              <li>Do not include tables, images, or charts.</li>
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
              <li>บทคัดย่อที่นำเสนอและได้รับการยอมรับจะได้รับการเผยแพร่ใน Handbook ของการประชุม</li>
              <li>คณะกรรมการสงวนสิทธิ์ในการปฏิเสธบทคัดย่อที่ไม่ตรงตามเกณฑ์ที่กำหนด</li>
              <li>ไม่เสียค่าใช้จ่ายในการส่งบทคัดย่อและการนำเสนอ</li>
            </ul>
          <?php else: ?>
            <ul style="line-height:2.2;color:var(--gray-700);">
              <li>Authors must register in the system before submitting an abstract.</li>
              <li>Accepted abstracts must be presented at the conference; otherwise, they will not be published.</li>
              <li>Accepted and presented papers will be published in the conference handbook.</li>
              <li>The committee reserves the right to reject papers that do not meet the specified criteria.</li>
              <li>There are no fees for submitting abstracts and presentations.</li>
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
          <a href="#tracks"><?= $_lang==='th'?'สาขาหัวข้อการประชุม':'Conference Tracks' ?></a>
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
