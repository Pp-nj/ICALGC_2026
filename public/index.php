<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Database;

$pageTitle  = CONF_SHORT;
$activeNav  = 'home';

// Fetch important dates
$importantDates = [];
try {
    $db   = Database::getInstance();
    $stmt = $db->query("SELECT * FROM important_dates WHERE is_active = TRUE ORDER BY sort_order ASC");
    $importantDates = $stmt->fetchAll();
} catch (\Throwable $e) {}

$_lang  = lang();
$appUrl = APP_URL;

require_once __DIR__ . '/../app/helpers/header.php';
?>

<!-- ════════════════════════════════════
     HERO SECTION
     ════════════════════════════════════ -->
<section class="hero-section">
  <div class="hero-bg"></div>
  <div class="hero-pattern"></div>

  <div class="container hero-content">
    <div class="row align-items-center g-5">

      <!-- Left: Text -->
      <div class="col-lg-7">
        <div class="hero-badge">
          <i class="fas fa-star me-1"></i>
          <?= $_lang==='th' ? 'การประชุมวิชาการนานาชาติ' : 'International Academic Conference' ?>
        </div>

        <h1 class="hero-title"><?= t('hero.title') ?></h1>

        <p class="hero-subtitle"><?= t('hero.subtitle') ?></p>
        
        <div class="hero-meta">
          <div class="hero-meta-item">
            <i class="fas fa-calendar-alt"></i>
            <span><?= t('hero.date') ?></span>
          </div>
          <div class="hero-meta-item">
            <i class="fas fa-map-marker-alt"></i>
            <span><?= t('hero.venue') ?></span>
          </div>
        </div>

        <div class="hero-buttons">
          <a href="<?= $appUrl ?>/register.php" class="btn-hero-primary">
            <i class="fas fa-user-plus me-2"></i><?= t('hero.btn_register') ?>
          </a>
          <a href="<?= $appUrl ?>/login.php" class="btn-hero-outline">
            <i class="fas fa-sign-in-alt me-2"></i><?= t('hero.btn_login') ?>
          </a>
          <a href="<?= $appUrl ?>/call-for-abstract.php" class="btn-hero-outline">
            <i class="fas fa-file-alt me-2"></i><?= t('hero.btn_abstract') ?>
          </a>
        </div>
      </div>

      <!-- Right: Countdown -->
      <div class="col-lg-5">
        <div class="countdown-card">
          <p class="countdown-title">
            <i class="fas fa-hourglass-half me-2"></i>
            <?= t('hero.countdown_label') ?>
          </p>
          <div class="countdown-grid">
            <div class="countdown-unit">
              <span class="countdown-number" id="cd-days">--</span>
              <span class="countdown-label"><?= t('hero.countdown_days') ?></span>
            </div>
            <div class="countdown-unit">
              <span class="countdown-number" id="cd-hours">--</span>
              <span class="countdown-label"><?= t('hero.countdown_hours') ?></span>
            </div>
            <div class="countdown-unit">
              <span class="countdown-number" id="cd-mins">--</span>
              <span class="countdown-label"><?= t('hero.countdown_mins') ?></span>
            </div>
            <div class="countdown-unit">
              <span class="countdown-number" id="cd-secs">--</span>
              <span class="countdown-label"><?= t('hero.countdown_secs') ?></span>
            </div>
          </div>
          <p class="mt-3 mb-0" style="color:rgba(255,255,255,.6);font-size:.78rem;letter-spacing:1px;">
            <i class="fas fa-map-pin me-1 text-gold"></i>
            <?php if ($_lang === 'th'): ?>
            <?= e(CONF_DATE_TH) ?>
          <?php else: ?>
            <?= e(CONF_DATE_EN) ?>
          <?php endif; ?>
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ════════════════════════════════════
     QUICK ACCESS CARDS
     ════════════════════════════════════ -->
<section class="quick-section">
  <div class="container">
    <div class="row g-4">

      <div class="col-sm-6 col-lg-3">
        <a href="#important-dates" class="quick-card card-hover text-decoration-none">
          <div class="quick-card-icon"><i class="fas fa-calendar-check"></i></div>
          <h4><?= t('quick.important_dates') ?></h4>
          <p><?= $_lang==='th' ? 'กำหนดการและวันสำคัญต่างๆ' : 'Key deadlines and milestones' ?></p>
        </a>
      </div>

      <div class="col-sm-6 col-lg-3">
        <a href="<?= $appUrl ?>/keynote.php" class="quick-card card-hover text-decoration-none">
          <div class="quick-card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
          <h4><?= t('quick.keynote') ?></h4>
          <p><?= $_lang==='th' ? 'รายชื่อผู้บรรยายพิเศษ' : 'List keynote speakers' ?></p>
        </a>
      </div>

      <div class="col-sm-6 col-lg-3">
        <a href="<?= $appUrl ?>/announcements.php" class="quick-card card-hover text-decoration-none">
          <div class="quick-card-icon"><i class="fas fa-bullhorn"></i></div>
          <h4><?= t('quick.announcements') ?></h4>
          <p><?= $_lang==='th' ? 'ข่าวสารและประกาศล่าสุด' : 'Latest news and updates' ?></p>
        </a>
      </div>

      <div class="col-sm-6 col-lg-3">
        <a href="<?= $appUrl ?>/venue.php" class="quick-card card-hover text-decoration-none">
          <div class="quick-card-icon"><i class="fas fa-map-marked-alt"></i></div>
          <h4><?= t('quick.venue') ?></h4>
          <p><?= $_lang==='th' ? 'สถานที่จัดงานและข้อมูลติดต่อ' : 'Venue details and contact info' ?></p>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════
     ANNOUNCEMENTS PREVIEW
     ════════════════════════════════════ -->
<section class="announce-section page-section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?= $_lang==='th' ? 'ข่าวสาร' : 'Latest News' ?></span>
      <h2 class="section-title"><?= t('announce.title') ?></h2>
      <div class="section-divider"></div>
    </div>

    <div class="row g-4">
      <!-- Announcement Card 1 -->
      <div class="col-md-4">
        <div class="announce-card h-100">
          <div class="announce-card-img">
            <img src="<?= $appUrl ?>/assets/images/announcements1.jpg" alt="">
            <span class="ann-date-badge"><i class="far fa-calendar-alt" style="margin-right:4px;opacity:.7;"></i>
            <?= $_lang==='th'
                ? '6 ก.ค. 2569'
                : '6 Jul 2026' ?></span>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag ann-cat--announcements"><?= $_lang==='th' ? 'บทคัดย่อ' : 'Abstract' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? '🚨 เปิดรับบทคัดย่อ ICALGC 2026 แล้ววันนี้'
                : '🚨 ICALGC 2026 Abstract Submission is Now Open' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? "✨คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ร่วมกับ Guangdong University of Foreign Studies สาธารณรัฐประชาชนจีน เป็นเจ้าภาพในการจัดประชุมวิชาการระดับนานาชาติ
              หัวข้อ “ภาษาอาเซียนในบริบทโลก” 
              📅 วันที่ 25 พฤศจิกายน 2569 
              📍 ณ หอดนตรีและการแสดงอโศกมนตรี ชั้น 4 อาคารนวัตกรรม : ศาสตราจารย์ ดร.สาโรช บัวศรี มหาวิทยาลัยศรีนครินทรวิโรฒ"
                : "✨The Faculty of Humanities, Srinakharinwirot University in collaboration with Guangdong University of Foreign Studies, China, cordially invites researchers, scholars, faculty members, students, and interested participants to submit abstracts for the International Conference on 
              'ASEAN Languages in the Global Context.'
              📅 Conference Date : 25 November 2026
              📍 Venue : Asok-Montri Music and Performing Arts Hall, 4th Floor, Innovation Building: Professor Dr. Saroj Buasri, Srinakharinwirot University" ?>
            </p>
          </div>
          <div class="announce-card-footer">
            <a href="<?= $appUrl ?>/announcements.php" class="ann-read-btn">
              <?= t('announce.read_more') ?> <i class="fas fa-arrow-right" style="font-size:.75rem;"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Announcement Card 2 -->
        <div class="col-md-4">
        <div class="announce-card h-100">
          <div class="announce-card-img">
            <img src="<?= $appUrl ?>/assets/images/announcements3.jpg" alt="">
            <span class="ann-date-badge"><i class="far fa-calendar-alt" style="margin-right:4px;opacity:.7;"></i>
              <?= $_lang==='th'
                ? '14 ก.ค. 2569'
                : '14 Jul 2026' ?></span></span>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag ann-cat--announcements"><?= $_lang==='th' ? 'ผู้บรรยายพิเศษ' : 'Keynote' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? '🎤 ปาฐกถาพิเศษจากวิทยากรผู้ทรงคุณวุฒิ'
                : '🎤 Special keynote lectures by distinguished speakers' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? "✨ คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ร่วมกับ คณะเอเชียอาคเนย์ศึกษา Guangdong University of Foreign Studies
                ขอเชิญชวนผู้สนใจรับฟังปาฐกถาพิเศษจากวิทยากรผู้ทรงคุณวุฒิ
                ในการประชุมวิชาการระดับนานาชาติในหัวข้อ \n
                'ภาษาอาเซียนในบริบทโลก' 
                ASEAN Languages in Global Context \n"
                : "✨ The Faculty of Humanities, Srinakharinwirot University, in collaboration with the Faculty of Southeast Asian Studies, Guangdong University of Foreign Studies,
                cordially invites all interested individuals to attend special keynote lectures by distinguished speakers
                at the International Academic Conference on the topic: \n
                'ASEAN Languages in Global Context' \n" ?>
            </p>
          </div>
          <div class="announce-card-footer">
            <a href="<?= $appUrl ?>/announcements.php" class="ann-read-btn">
              <?= t('announce.read_more') ?> <i class="fas fa-arrow-right" style="font-size:.75rem;"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Announcement Card 3 -->
       <div class="col-md-4">
        <div class="announce-card h-100">
          <div class="announce-card-img">
            <img src="<?= $appUrl ?>/assets/images/announcements4.jpg" alt="">
            <span class="ann-date-badge"><i class="far fa-calendar-alt" style="margin-right:4px;opacity:.7;"></i>
              <?= $_lang==='th'
                ? '18 ส.ค. 2569'
                : '18 Aug 2026' ?></span></span>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag ann-cat--announcements"><?= $_lang==='th' ? 'ผู้บรรยายพิเศษ' : 'Keynote' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? '🎤 พบกับการเสวนาในหัวข้อ “การสอนภาษาอาเซียนในบริบทโลก”'
                : '🎤 Join our Panel Discussion on “Teaching ASEAN Languages in a Global Context”' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? "🌏คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ร่วมกับ คณะเอเชียอาคเนย์ศึกษา Guangdong University of Foreign Studies สาธารณรัฐประชาชนจีน เป็นเจ้าภาพในการจัดประชุมวิชาการระดับนานาชาติ  “ภาษาอาเซียนในบริบทโลก” International Academic Conference: ASEAN Languages in Global Context \n
                พบกับการเสวนาในหัวข้อ
                🎓 “การสอนภาษาอาเซียนในบริบทโลก”
                “Teaching ASEAN Languages in a Global Context” \n"
                : "✨ 🌏 International Academic Conference: ASEAN Languages in Global Context \n
                Join our Panel Discussion on
                🎓 “Teaching ASEAN Languages in a Global Context”
                with experts from 3 universities. \n" ?>
            </p>
          </div>
          <div class="announce-card-footer">
            <a href="<?= $appUrl ?>/announcements.php" class="ann-read-btn">
              <?= t('announce.read_more') ?> <i class="fas fa-arrow-right" style="font-size:.75rem;"></i>
            </a>
          </div>
        </div>
      </div>

    </div>

    <div class="text-center mt-5">
      <a href="<?= $appUrl ?>/announcements.php" class="btn-primary-custom">
        <?= t('announce.view_all') ?> <i class="fas fa-arrow-right ms-2"></i>
      </a>
    </div>

  </div>
</section>

<!-- ════════════════════════════════════
     IMPORTANT DATES
     ════════════════════════════════════ -->
<section class="dates-section" id="important-dates">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?= $_lang==='th' ? 'ปฏิทินการประชุม' : 'Conference Calendar' ?></span>
      <h2 class="section-title"><?= t('dates.title') ?></h2>
      <div class="section-divider"></div>
    </div>

    <div class="h-timeline">
      <div class="h-timeline-line"></div>
      <?php foreach ($importantDates as $idx => $date): ?>
        <?php
        $daysLeft = daysUntil($date['event_date']);
        $cardClass  = '';
        $badgeClass = '';
        $badgeText  = '';

        if ($daysLeft < 0) {
            $cardClass  = 'card-passed';
            $badgeClass = 'badge-passed';
            $badgeText  = t('dates.passed');
        } elseif ($daysLeft === 0) {
            $cardClass  = 'card-today';
            $badgeClass = 'badge-today';
            $badgeText  = t('dates.today');
        } else {
            $cardClass  = 'card-upcoming';
            $badgeClass = 'badge-upcoming';
            $badgeText  = t('dates.days_left', ['n' => $daysLeft]);
        }

        $icons = ['calendar-plus', 'file-alt', 'check-circle', 'star', 'certificate'];
        $icon  = $icons[$idx] ?? 'calendar';
        $title = $_lang==='th' ? $date['title_th'] : $date['title_en'];
        $position = ($idx % 2 === 0) ? 'above' : 'below';
        ?>
        <div class="h-timeline-item <?= $cardClass ?> <?= $position ?>">
          <div class="h-timeline-content">
            <div class="h-timeline-title"><?= e($title) ?></div>
            <div class="h-timeline-date"><?= humanDate($date['event_date']) ?></div>
            <span class="timeline-badge <?= $badgeClass ?>"><?= e($badgeText) ?></span>
          </div>
          <div class="h-timeline-connector"></div>
          <div class="h-timeline-node">
            <i class="fas fa-<?= $icon ?>"></i>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<?php
$inlineJs = "initCountdown('" . CONF_DATE . "');";
require_once __DIR__ . '/../app/helpers/footer.php';
?>
