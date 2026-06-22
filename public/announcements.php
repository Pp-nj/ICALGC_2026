<?php
/**
 * Announcements Page — Static content (no database)
 * Edit $announcements array below to update content.
 */
require_once __DIR__ . '/../app/helpers/init.php';

$pageTitle = lang()==='th' ? 'ประกาศ' : 'Announcements';
$activeNav = 'announcements';
$_lang     = lang();
$appUrl    = APP_URL;

// ── Static Announcements (edit here) ──────────────────────────────────────
// Add or remove items from this array to update the announcements page.
$announcements = [
  [
    'id'       => 1,
    'category' => ['th' => 'บทคัดย่อ', 'en' => 'Abstract'],
    'icon'     => 'file-alt',
    'date'     => '1 July 2026',
    'title'    => ['th' => 'เปิดรับบทคัดย่อ ICALGC 2026 แล้ววันนี้', 'en' => 'ICALGC 2026 Abstract Submission is Now Open'],
    'body'     => [
      'th' => 'ขอเชิญนักวิชาการ นักวิจัย อาจารย์ นักศึกษาระดับบัณฑิตศึกษา และผู้สนใจจากทั่วโลก ส่งบทคัดย่อเพื่อนำเสนอในการประชุมวิชาการนานาชาติ ICALGC 2026 หัวข้อ "ภาษาอาเซียนในบริบทโลก" หมดเขตส่งบทคัดย่อ: 30 สิงหาคม 2569',
      'en' => 'We invite academics, researchers, lecturers, graduate students, and interested parties from around the world to submit abstracts for the ICALGC 2026 International Conference on the theme "ASEAN Languages in Global Contexts." Abstract submission deadline: August 30, 2026.',
    ],
  ],
  [
    'id'       => 2,
    'category' => ['th' => 'วิทยากร', 'en' => 'Keynote'],
    'icon'     => 'chalkboard-teacher',
    'date'     => '15 July 2026',
    'title'    => ['th' => 'ประกาศรายชื่อวิทยากรหลัก ICALGC 2026', 'en' => 'ICALGC 2026 Keynote Speakers Announced'],
    'body'     => [
      'th' => 'เราได้รับเกียรติจากนักวิชาการระดับโลกมาเป็นวิทยากรหลักในการประชุม ICALGC 2026 รายชื่อวิทยากรทั้งหมดจะประกาศเร็วๆ นี้ ติดตามข่าวสารผ่านเว็บไซต์ของเรา',
      'en' => 'We are honored to announce that world-class academics have agreed to serve as keynote speakers at ICALGC 2026. Full speaker lineup will be announced soon. Stay tuned to our website.',
    ],
  ],
  [
    'id'       => 3,
    'category' => ['th' => 'ลงทะเบียน', 'en' => 'Registration'],
    'icon'     => 'user-plus',
    'date'     => '1 July 2026',
    'title'    => ['th' => 'เปิดลงทะเบียนเข้าร่วมประชุม', 'en' => 'Conference Registration is Now Open'],
    'body'     => [
      'th' => 'ลงทะเบียนเข้าร่วมการประชุมวิชาการนานาชาติ ICALGC 2026 ได้แล้ววันนี้ ทั้งในรูปแบบ Onsite ณ มหาวิทยาลัยศรีนครินทรวิโรฒ ประสานมิตร และ Online ผ่านระบบ Zoom',
      'en' => 'Register to attend ICALGC 2026 today, either in person at Srinakharinwirot University Prasarnmit Campus or online via Zoom.',
    ],
  ],
  [
    'id'       => 4,
    'category' => ['th' => 'ทุนสนับสนุน', 'en' => 'Support'],
    'icon'     => 'star',
    'date'     => '20 July 2026',
    'title'    => ['th' => 'ทุนสนับสนุนสำหรับนักศึกษาระดับบัณฑิตศึกษา', 'en' => 'Funding Support for Graduate Students'],
    'body'     => [
      'th' => 'มีทุนสนับสนุนบางส่วนสำหรับนักศึกษาระดับบัณฑิตศึกษาที่นำเสนอบทความในการประชุม สนใจสอบถามได้ที่อีเมล icalgc@swu.ac.th',
      'en' => 'Partial funding support is available for graduate students presenting papers at the conference. For inquiries, contact us at icalgc@swu.ac.th.',
    ],
  ],
  [
    'id'       => 5,
    'category' => ['th' => 'การตีพิมพ์', 'en' => 'Publication'],
    'icon'     => 'book',
    'date'     => '25 July 2026',
    'title'    => ['th' => 'บทความที่ผ่านการพิจารณาจะได้รับการตีพิมพ์ใน Proceedings', 'en' => 'Accepted Papers Will Be Published in Conference Proceedings'],
    'body'     => [
      'th' => 'บทความที่ผ่านการพิจารณาจากผู้ทรงคุณวุฒิและนำเสนอในการประชุม ICALGC 2026 จะได้รับการตีพิมพ์ใน Proceedings ของการประชุมซึ่งจะเผยแพร่ผ่านเว็บไซต์ของการประชุม',
      'en' => 'Papers accepted through peer review and presented at ICALGC 2026 will be published in the conference proceedings, available through the conference website.',
    ],
  ],
  [
    'id'       => 6,
    'category' => ['th' => 'ข้อมูลทั่วไป', 'en' => 'General'],
    'icon'     => 'info-circle',
    'date'     => '10 July 2026',
    'title'    => ['th' => 'ข้อมูลที่พักสำหรับผู้เข้าร่วมจากต่างจังหวัด/ต่างประเทศ', 'en' => 'Accommodation Information for Out-of-Town Participants'],
    'body'     => [
      'th' => 'คณะกรรมการจัดงานได้รวบรวมรายชื่อโรงแรมใกล้เคียงสำหรับผู้เข้าร่วมที่เดินทางมาจากต่างจังหวัดหรือต่างประเทศ ดูรายละเอียดได้ที่หน้า Venue & Contact',
      'en' => 'The organizing committee has compiled a list of nearby hotels for participants traveling from other provinces or countries. See details on the Venue & Contact page.',
    ],
  ],
];

// Filters
$categories = array_unique(array_column(array_column($announcements, 'category'), $_lang));
sort($categories);
$activeFilter = sanitize(get('cat', 'all'));
$currentPage  = max(1, intGet('page', 1));
$perPage      = 3;

// Filter
$filtered = $activeFilter && $activeFilter !== 'all'
    ? array_filter($announcements, fn($a) => $a['category'][$_lang] === $activeFilter)
    : $announcements;
$filtered   = array_values($filtered);
$total      = count($filtered);
$pg         = paginate($total, $perPage, $currentPage);
$displayed  = array_slice($filtered, $pg['offset'], $perPage);

require_once __DIR__ . '/../app/helpers/header.php';
?>

<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);"><?= $_lang==='th'?'ข่าวสาร':'News & Updates' ?></span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;"><?= t('announce.title') ?></h1>
    <p style="color:rgba(255,255,255,.8);">ICALGC 2026</p>
  </div>
</div>

<section class="page-section">
  <div class="container">

    <!-- Filter Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
      <a href="?cat=all" class="btn <?= $activeFilter==='all'?'btn-primary':'btn-outline-secondary' ?> rounded-pill px-4 fw-bold" style="<?= $activeFilter==='all'?'background:var(--blue-dark);color:var(--gold);border-color:var(--blue-dark);':'' ?>">
        <?= $_lang==='th'?'ทั้งหมด':'All' ?> (<?= $total ?>)
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="?cat=<?= urlencode($cat) ?>" class="btn rounded-pill px-4 fw-bold" style="<?= $activeFilter===$cat?'background:var(--blue-dark);color:var(--gold);border:2px solid var(--blue-dark);':'border:2px solid var(--gray-200);color:var(--gray-700);background:var(--white);' ?>">
          <?= e($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Cards -->
    <div class="row g-4">
      <?php foreach ($displayed as $ann): ?>
        <div class="col-md-4">
          <div class="announce-card h-100">
            <div class="announce-card-img">
              <i class="fas fa-<?= e($ann['icon']) ?>"></i>
            </div>
            <div class="announce-card-body">
              <span class="announce-tag"><?= e($ann['category'][$_lang]) ?></span>
              <h3 class="announce-card-title"><?= e($ann['title'][$_lang]) ?></h3>
              <p class="announce-card-text"><?= e($ann['body'][$_lang]) ?></p>
            </div>
            <div class="announce-card-footer">
              <span class="announce-date"><i class="far fa-calendar me-1"></i><?= e($ann['date']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination Dots + Buttons -->
    <?php if ($pg['total_pages'] > 1): ?>
      <div class="mt-5 d-flex flex-column align-items-center gap-3">

        <!-- Navigation Buttons -->
        <div class="d-flex gap-3">
          <?php if ($pg['has_prev']): ?>
            <a href="?cat=<?= urlencode($activeFilter) ?>&page=<?= $pg['page']-1 ?>"
               class="btn-outline-custom d-flex align-items-center gap-2">
              <i class="fas fa-chevron-left"></i><?= t('common.prev') ?>
            </a>
          <?php endif; ?>
          <?php if ($pg['has_next']): ?>
            <a href="?cat=<?= urlencode($activeFilter) ?>&page=<?= $pg['page']+1 ?>"
               class="btn-primary-custom d-flex align-items-center gap-2">
              <?= t('common.next') ?><i class="fas fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>

        <!-- Pagination Dots -->
        <div class="d-flex gap-2">
          <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
            <a href="?cat=<?= urlencode($activeFilter) ?>&page=<?= $i ?>"
               class="rounded-circle d-inline-block"
               style="width:<?= $i===$pg['page']?'28':'12' ?>px;height:12px;background:<?= $i===$pg['page']?'var(--gold)':'var(--gray-200)' ?>;border-radius:6px;transition:all .3s;"></a>
          <?php endfor; ?>
        </div>

        <p style="font-size:.85rem;color:var(--gray-500);">
          <?= t('common.page') ?> <?= $pg['page'] ?> <?= t('common.of') ?> <?= $pg['total_pages'] ?>
        </p>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
