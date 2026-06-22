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
        <p class="hero-subtitle2"><?= t('hero.subtitle2') ?></p>

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
            <?= e(CONF_DATE_EN) ?>
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
          <p><?= $_lang==='th' ? 'วิทยากรผู้ทรงคุณวุฒิ' : 'Distinguished keynote speakers' ?></p>
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
     ABOUT SECTION
     ════════════════════════════════════ -->
<section class="about-section page-section" id="about">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Left: Content -->
      <div class="col-lg-6" data-aos="fade-right">
        <div class="section-label"><?= t('nav.about') ?></div>
        <h2 class="section-title text-start mb-3"><?= t('about.title') ?></h2>
        <div class="section-divider" style="margin:0 0 24px;"></div>

        <?php if ($_lang === 'th'): ?>
          <p style="color:var(--gray-700);line-height:1.8;margin-bottom:20px;">
            การประชุมวิชาการนานาชาติว่าด้วยภาษาอาเซียนในบริบทโลก 2026 (ICALGC 2026)
            เป็นเวทีแลกเปลี่ยนความรู้ทางวิชาการระดับนานาชาติ
            จัดขึ้นโดยคณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ
            ร่วมกับมหาวิทยาลัยกวางตุ้งเพื่อการศึกษาต่างประเทศ
          </p>
        <?php else: ?>
          <p style="color:var(--gray-700);line-height:1.8;margin-bottom:20px;">
            The International Conference on ASEAN Languages in Global Contexts 2026 (ICALGC 2026)
            is an international academic forum organized by the Faculty of Humanities,
            Srinakharinwirot University, in collaboration with Guangdong University of Foreign Studies.
          </p>
        <?php endif; ?>

        <h5 style="color:var(--blue-dark);margin-bottom:16px;font-size:1rem;">
          <i class="fas fa-bullseye me-2 text-gold"></i>
          <?= $_lang==='th' ? 'วัตถุประสงค์' : 'Objectives' ?>
        </h5>

        <ul class="about-objectives">
          <li>
            <span class="obj-icon"><i class="fas fa-check"></i></span>
            <span><?= t('about.obj1') ?></span>
          </li>
          <li>
            <span class="obj-icon"><i class="fas fa-check"></i></span>
            <span><?= t('about.obj2') ?></span>
          </li>
          <li>
            <span class="obj-icon"><i class="fas fa-check"></i></span>
            <span><?= t('about.obj3') ?></span>
          </li>
        </ul>

        <a href="<?= $appUrl ?>/about.php" class="btn-primary-custom">
          <?= t('about.read_more') ?> <i class="fas fa-arrow-right ms-2"></i>
        </a>
      </div>

      <!-- Right: Image -->
      <div class="col-lg-6">
        <div class="about-image-wrap">
          <img src="<?= $appUrl ?>/assets/images/about-conference.jpg"
               alt="ICALGC 2026"
               onerror="this.src='https://placehold.co/600x380/003087/c9a227?text=ICALGC+2026'">
          <div class="about-image-badge">
            <i class="fas fa-university me-2"></i>ICALGC 2026
          </div>
        </div>
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

    <div class="timeline-scroll">
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
        ?>
        <div class="timeline-card <?= $cardClass ?>">
          <div class="timeline-icon">
            <i class="fas fa-<?= $icon ?>"></i>
          </div>
          <div class="timeline-title"><?= e($title) ?></div>
          <div class="timeline-date"><?= humanDate($date['event_date']) ?></div>
          <span class="timeline-badge <?= $badgeClass ?>"><?= e($badgeText) ?></span>
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
        <div class="announce-card">
          <div class="announce-card-img">
            <i class="fas fa-file-alt"></i>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag"><?= $_lang==='th' ? 'บทคัดย่อ' : 'Abstract' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? 'เปิดรับบทคัดย่อ ICALGC 2026 แล้ววันนี้!'
                : 'ICALGC 2026 Abstract Submission is Now Open!' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? 'ขอเชิญนักวิชาการ นักวิจัย และนักศึกษาระดับบัณฑิตศึกษาส่งบทคัดย่อเพื่อนำเสนอในการประชุมวิชาการนานาชาติ ICALGC 2026'
                : 'We invite academics, researchers, and graduate students to submit abstracts for presentation at the ICALGC 2026 International Conference.' ?>
            </p>
          </div>
          <div class="announce-card-footer">
            <span class="announce-date"><i class="far fa-calendar me-1"></i>1 July 2026</span>
            <a href="<?= $appUrl ?>/announcements.php" class="btn-outline-custom" style="padding:6px 16px;font-size:.8rem;">
              <?= t('announce.read_more') ?>
            </a>
          </div>
        </div>
      </div>

      <!-- Announcement Card 2 -->
      <div class="col-md-4">
        <div class="announce-card">
          <div class="announce-card-img">
            <i class="fas fa-chalkboard-teacher"></i>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag"><?= $_lang==='th' ? 'วิทยากร' : 'Keynote' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? 'ประกาศรายชื่อวิทยากรหลัก ICALGC 2026'
                : 'ICALGC 2026 Keynote Speakers Announced' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? 'เราได้รับเกียรติจากนักวิชาการระดับโลกมาเป็นวิทยากรหลักในการประชุม ICALGC 2026 ติดตามรายชื่อได้เร็วๆ นี้'
                : 'We are honored to announce world-class academics as keynote speakers for ICALGC 2026. Stay tuned for updates.' ?>
            </p>
          </div>
          <div class="announce-card-footer">
            <span class="announce-date"><i class="far fa-calendar me-1"></i>15 July 2026</span>
            <a href="<?= $appUrl ?>/announcements.php" class="btn-outline-custom" style="padding:6px 16px;font-size:.8rem;">
              <?= t('announce.read_more') ?>
            </a>
          </div>
        </div>
      </div>

      <!-- Announcement Card 3 -->
      <div class="col-md-4">
        <div class="announce-card">
          <div class="announce-card-img">
            <i class="fas fa-university"></i>
          </div>
          <div class="announce-card-body">
            <span class="announce-tag"><?= $_lang==='th' ? 'ลงทะเบียน' : 'Registration' ?></span>
            <h3 class="announce-card-title">
              <?= $_lang==='th'
                ? 'เปิดลงทะเบียนเข้าร่วมประชุม ICALGC 2026'
                : 'Registration for ICALGC 2026 is Now Open' ?>
            </h3>
            <p class="announce-card-text">
              <?= $_lang==='th'
                ? 'ลงทะเบียนเข้าร่วมการประชุมวิชาการนานาชาติ ICALGC 2026 ตั้งแต่วันที่ 1 กรกฎาคม 2569'
                : 'Register to attend the ICALGC 2026 International Conference starting July 1, 2026.' ?>
            </p>
          </div>
          <div class="announce-card-footer">
            <span class="announce-date"><i class="far fa-calendar me-1"></i>1 July 2026</span>
            <a href="<?= $appUrl ?>/register.php" class="btn-outline-custom" style="padding:6px 16px;font-size:.8rem;">
              <?= t('nav.register') ?>
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
     CONFERENCE STATS STRIP
     ════════════════════════════════════ -->
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
</section>

<?php
$inlineJs = "initCountdown('" . CONF_DATE . "');";
require_once __DIR__ . '/../app/helpers/footer.php';
?>
