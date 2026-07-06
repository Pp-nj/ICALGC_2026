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
            <span class="ann-date-badge"><i class="far fa-calendar-alt" style="margin-right:4px;opacity:.7;"></i>6 July 2026</span>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag ann-cat--announcements"><?= $_lang==='th' ? 'บทคัดย่อ' : 'Abstract' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? 'เปิดรับบทคัดย่อ ICALGC 2026 แล้ววันนี้!'
                : 'ICALGC 2026 Abstract Submission is Now Open!' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? 'ขอเชิญนักวิชาการ นักวิจัย อาจารย์ นักศึกษาระดับบัณฑิตศึกษา และผู้สนใจจากทั่วโลก ส่งบทคัดย่อเพื่อนำเสนอในการประชุมวิชาการนานาชาติ ICALGC 2026 หัวข้อ "ภาษาอาเซียนในบริบทโลก" หมดเขตส่งบทคัดย่อ: 31 สิงหาคม 2569'
                : 'We invite academics, researchers, lecturers, graduate students, and interested parties from around the world to submit abstracts for the ICALGC 2026 International Conference on the theme "ASEAN Languages in Global Contexts." Abstract submission deadline: August 31, 2026.' ?>
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
            <img src="<?= $appUrl ?>/assets/images/announcements2.jpg" alt="">
            <span class="ann-date-badge"><i class="far fa-calendar-alt" style="margin-right:4px;opacity:.7;"></i>6 July 2026</span>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag ann-cat--updates"><?= $_lang==='th' ? 'ลงทะเบียน' : 'Registration' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? 'เปิดลงทะเบียนเข้าร่วมประชุม ICALGC 2026'
                : 'Registration for ICALGC 2026 is Now Open' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? 'ลงทะเบียนเข้าร่วมการประชุมวิชาการนานาชาติ ICALGC 2026 ได้แล้ววันนี้ ในรูปแบบ Onsite ณ มหาวิทยาลัยศรีนครินทรวิโรฒ ประสานมิตร'
                : 'Register to attend ICALGC 2026 today, in person at Srinakharinwirot University Prasarnmit Campus.' ?>
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
    </div>

    <div class="text-center mt-5">
      <a href="<?= $appUrl ?>/announcements.php" class="btn-primary-custom">
        <?= t('announce.view_all') ?> <i class="fas fa-arrow-right ms-2"></i>
      </a>
    </div>

  </div>
</section>

<!-- ════════════════════════════════════
     CONFERENCE STATS STRIP
     ════════════════════════════════════ 
<section style="background:var(--blue-dark);padding:50px 0;color:var(--white);">
  <div class="container">
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3">
        <div style="font-size:2.4rem;font-weight:800;color:var(--gold);">7</div>
        <div style="font-size:.88rem;color:rgba(255,255,255,.7);">
          <?= $_lang==='th' ? 'หัวข้อการประชุม' : 'Conference Themes' ?>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div style="font-size:2.4rem;font-weight:800;color:var(--gold);">2</div>
        <div style="font-size:.88rem;color:rgba(255,255,255,.7);">
          <?= $_lang==='th' ? 'มหาวิทยาลัยเจ้าภาพ' : 'Host Universities' ?>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div style="font-size:2.4rem;font-weight:800;color:var(--gold);">10+</div>
        <div style="font-size:.88rem;color:rgba(255,255,255,.7);">
          <?= $_lang==='th' ? 'ประเทศที่เข้าร่วม' : 'Participating Countries' ?>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div style="font-size:2.4rem;font-weight:800;color:var(--gold);">1</div>
        <div style="font-size:.88rem;color:rgba(255,255,255,.7);">
          <?= $_lang==='th' ? 'วันประชุม' : 'Conference Day' ?>
        </div>
      </div>
    </div>
  </div>
</section>-->

<?php
$inlineJs = "initCountdown('" . CONF_DATE . "');";
require_once __DIR__ . '/../app/helpers/footer.php';
?>
