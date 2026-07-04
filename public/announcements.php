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

// ── Category type map → CSS class & display label ─────────────────────────────
// Maps each announcement's raw category key to one of the 5 filter buckets.
$catMap = [
  // th key / en key  =>  bucket
  'บทคัดย่อ'    => 'announcements',
  'Abstract'    => 'announcements',
  'ผู้บรรยายพิเศษ'     => 'announcements',
  'Keynote'     => 'announcements',
  'ลงทะเบียน'   => 'updates',
  'Registration'=> 'updates',
  'ทุนสนับสนุน' => 'news',
  'Support'     => 'news',
  'การตีพิมพ์'  => 'news',
  'Publication' => 'news',
  'ข้อมูลทั่วไป'=> 'reminders',
  'General'     => 'reminders',
];

$bucketLabel = [
  'all'           => ['th'=>'ทั้งหมด',   'en'=>'All'],
  'announcements' => ['th'=>'ประกาศ',     'en'=>'Announcements'],
  'news'          => ['th'=>'ข่าวสาร',    'en'=>'News'],
  'updates'       => ['th'=>'อัปเดต',     'en'=>'Updates'],
  'reminders'     => ['th'=>'แจ้งเตือน',  'en'=>'Reminders'],
];

// ── Static Announcements ──────────────────────────────────────────────────────
$announcements = [
  [
    'id'       => 1,
    'category' => ['th' => 'บทคัดย่อ', 'en' => 'Abstract'],
    'bucket'   => 'announcements',
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
    'category' => ['th' => 'ผู้บรรยายพิเศษ', 'en' => 'Keynote'],
    'bucket'   => 'announcements',
    'icon'     => 'chalkboard-teacher',
    'date'     => '15 July 2026',
    'title'    => ['th' => 'ประกาศรายชื่อผู้บรรยายพิเศษICALGC 2026', 'en' => 'ICALGC 2026 Keynote Speakers Announced'],
    'body'     => [
      'th' => 'เราได้รับเกียรติจากนักวิชาการระดับโลกมาเป็นผู้บรรยายพิเศษในการประชุม ICALGC 2026 รายชื่อผู้บรรยายพิเศษทั้งหมดจะประกาศเร็วๆ นี้ ติดตามข่าวสารผ่านเว็บไซต์ของเรา',
      'en' => 'We are honored to announce that world-class academics have agreed to serve as keynote speakers at ICALGC 2026. Full speaker lineup will be announced soon. Stay tuned to our website.',
    ],
  ],
  [
    'id'       => 3,
    'category' => ['th' => 'ลงทะเบียน', 'en' => 'Registration'],
    'bucket'   => 'updates',
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
    'bucket'   => 'news',
    'icon'     => 'star',
    'date'     => '20 July 2026',
    'title'    => ['th' => 'ทุนสนับสนุนสำหรับนักศึกษาระดับบัณฑิตศึกษา', 'en' => 'Funding Support for Graduate Students'],
    'body'     => [
      'th' => 'มีทุนสนับสนุนบางส่วนสำหรับนักศึกษาระดับบัณฑิตศึกษาที่นำเสนอบทคัดย่อในการประชุม สนใจสอบถามได้ที่อีเมล icalgc@swu.ac.th',
      'en' => 'Partial funding support is available for graduate students presenting papers at the conference. For inquiries, contact us at icalgc@swu.ac.th.',
    ],
  ],
  [
    'id'       => 5,
    'category' => ['th' => 'การตีพิมพ์', 'en' => 'Publication'],
    'bucket'   => 'news',
    'icon'     => 'book',
    'date'     => '25 July 2026',
    'title'    => ['th' => 'บทคัดย่อที่ผ่านการพิจารณาจะได้รับการตีพิมพ์ใน Proceedings', 'en' => 'Accepted Papers Will Be Published in Conference Proceedings'],
    'body'     => [
      'th' => 'บทคัดย่อที่ผ่านการพิจารณาจากผู้ทรงคุณวุฒิและนำเสนอในการประชุม ICALGC 2026 จะได้รับการตีพิมพ์ใน Proceedings ของการประชุมซึ่งจะเผยแพร่ผ่านเว็บไซต์ของการประชุม',
      'en' => 'Papers accepted through peer review and presented at ICALGC 2026 will be published in the conference proceedings, available through the conference website.',
    ],
  ],
  [
    'id'       => 6,
    'category' => ['th' => 'ข้อมูลทั่วไป', 'en' => 'General'],
    'bucket'   => 'reminders',
    'icon'     => 'info-circle',
    'date'     => '10 July 2026',
    'title'    => ['th' => 'ข้อมูลที่พักสำหรับผู้เข้าร่วมจากต่างจังหวัด/ต่างประเทศ', 'en' => 'Accommodation Information for Out-of-Town Participants'],
    'body'     => [
      'th' => 'คณะกรรมการจัดงานได้รวบรวมรายชื่อโรงแรมใกล้เคียงสำหรับผู้เข้าร่วมที่เดินทางมาจากต่างจังหวัดหรือต่างประเทศ ดูรายละเอียดได้ที่หน้า Venue & Contact',
      'en' => 'The organizing committee has compiled a list of nearby hotels for participants traveling from other provinces or countries. See details on the Venue & Contact page.',
    ],
  ],
];

// ── Filtering & Pagination ─────────────────────────────────────────────────────
$activeFilter = sanitize(get('cat', 'all'));
$currentPage  = max(1, intGet('page', 1));
$perPage      = 3;

$filtered = $activeFilter && $activeFilter !== 'all'
    ? array_values(array_filter($announcements, fn($a) => $a['bucket'] === $activeFilter))
    : array_values($announcements);

$total = count($filtered);
$pg    = paginate($total, $perPage, $currentPage);
$displayed = array_slice($filtered, $pg['offset'], $perPage);

// Count per bucket for filter badges
$bucketCounts = ['all' => count($announcements)];
foreach ($announcements as $a) {
  $b = $a['bucket'];
  $bucketCounts[$b] = ($bucketCounts[$b] ?? 0) + 1;
}

require_once __DIR__ . '/../app/helpers/header.php';
?>

<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);"><?= $_lang==='th'?'ข่าวสาร':'News & Updates' ?></span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;"><?= t('announce.title') ?></h1>
    <p style="color:rgba(255,255,255,.8);">ICALGC 2026</p>
    <div class="section-divider"></div>
  </div>
</div>

<section class="page-section">
  <div class="container">

    <!-- ── Filter Tabs ── -->
    <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
      <?php foreach ($bucketLabel as $bucket => $labels):
        $cnt = $bucketCounts[$bucket] ?? 0;
        if ($bucket !== 'all' && $cnt === 0) continue;
        $isActive = $activeFilter === $bucket;
        $cls = 'ann-filter-btn ann-cat--' . $bucket . ($isActive ? ' active' : '');
      ?>
        <a href="?cat=<?= urlencode($bucket) ?>"
           class="<?= $cls ?>"
           style="text-decoration:none;">
          <?= e($labels[$_lang]) ?> (<?= $bucket === 'all' ? count($announcements) : $cnt ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <!-- ── Slider ── -->
    <div class="ann-slider-outer px-4">
      <button class="ann-arrow ann-arrow--prev" id="ann-prev" aria-label="Previous">
        <i class="fas fa-chevron-left"></i>
      </button>

      <div class="ann-slider-track" id="ann-track">
        <?php if (empty($displayed)): ?>
          <div style="padding:40px;color:var(--gray-500);text-align:center;width:100%;">
            <?= $_lang==='th'?'ไม่มีรายการในหมวดหมู่นี้':'No items in this category.' ?>
          </div>
        <?php else: ?>
          <?php foreach ($displayed as $ann):
            $bucket = $ann['bucket'];
            $catClass = 'ann-cat--' . $bucket;
          ?>
            <div class="ann-slide">
              <div class="announce-card h-100">

                <!-- Cover with date badge -->
                <div class="announce-card-img <?= $catClass ?>">
                  <i class="fas fa-<?= e($ann['icon']) ?>"></i>
                  <span class="ann-date-badge">
                    <i class="far fa-calendar-alt" style="margin-right:4px;opacity:.7;"></i><?= e($ann['date']) ?>
                  </span>
                </div>

                <!-- Body -->
                <div class="announce-card-body">
                  <span class="announce-tag <?= $catClass ?>"><?= e($ann['category'][$_lang]) ?></span>
                  <h3 class="announce-card-title"><?= e($ann['title'][$_lang]) ?></h3>
                  <p class="announce-card-text"><?= e($ann['body'][$_lang]) ?></p>
                </div>

                <!-- Footer: read more -->
                <div class="announce-card-footer">
                  <button class="ann-read-btn"
                          onclick="openAnnModal(<?= $ann['id'] ?>)"
                          type="button">
                    <?= $_lang==='th'?'อ่านเพิ่มเติม':'Read more' ?> <i class="fas fa-arrow-right" style="font-size:.75rem;"></i>
                  </button>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <button class="ann-arrow ann-arrow--next" id="ann-next" aria-label="Next">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>

    <!-- ── Numeric Pagination ── -->
    <?php if ($pg['total_pages'] > 1): ?>
      <div class="ann-pagination">
        <a href="?cat=<?= urlencode($activeFilter) ?>&page=1"
           class="ann-page-btn <?= $pg['page']===1?'disabled':'' ?>">«</a>
        <a href="?cat=<?= urlencode($activeFilter) ?>&page=<?= max(1,$pg['page']-1) ?>"
           class="ann-page-btn <?= $pg['page']===1?'disabled':'' ?>">‹</a>

        <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
          <a href="?cat=<?= urlencode($activeFilter) ?>&page=<?= $i ?>"
             class="ann-page-btn <?= $i===$pg['page']?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <a href="?cat=<?= urlencode($activeFilter) ?>&page=<?= min($pg['total_pages'],$pg['page']+1) ?>"
           class="ann-page-btn <?= $pg['page']===$pg['total_pages']?'disabled':'' ?>">›</a>
        <a href="?cat=<?= urlencode($activeFilter) ?>&page=<?= $pg['total_pages'] ?>"
           class="ann-page-btn <?= $pg['page']===$pg['total_pages']?'disabled':'' ?>">»</a>
      </div>
      <p style="text-align:center;font-size:.82rem;color:var(--gray-500);margin-top:10px;">
        <?= t('common.page') ?> <?= $pg['page'] ?> <?= t('common.of') ?> <?= $pg['total_pages'] ?>
      </p>
    <?php endif; ?>

  </div>
</section>

<!-- ── Modal ── -->
<div class="ann-modal-backdrop" id="ann-modal-backdrop" role="dialog" aria-modal="true">
  <div class="ann-modal" id="ann-modal">
    <div class="ann-modal-header" id="ann-modal-header">
      <h4 id="ann-modal-title"></h4>
      <button class="ann-modal-close" onclick="closeAnnModal()" aria-label="Close">✕</button>
    </div>
    <div class="ann-modal-body">
      <div class="ann-modal-cover" id="ann-modal-cover"></div>
      <p class="ann-modal-meta" id="ann-modal-meta"></p>
      <div class="ann-modal-full" id="ann-modal-full"></div>
    </div>
    <div class="ann-modal-footer">
      <button onclick="closeAnnModal()">
        <?= $_lang==='th'?'ปิดหน้าต่าง':'Close' ?>
      </button>
    </div>
  </div>
</div>

<!-- ── Embedded announcement data for JS modal ── -->
<script>
const ANN_DATA = <?php
  $jsData = [];
  foreach ($announcements as $a) {
    $jsData[$a['id']] = [
      'id'     => $a['id'],
      'bucket' => $a['bucket'],
      'icon'   => $a['icon'],
      'date'   => $a['date'],
      'cat'    => $a['category'][$_lang],
      'title'  => $a['title'][$_lang],
      'body'   => $a['body'][$_lang],
    ];
  }
  echo json_encode($jsData, JSON_UNESCAPED_UNICODE);
?>;

// Category colour map (matches CSS --cat-color values)
const BUCKET_COLOR = {
  announcements: '#1d4ed8',
  news:          '#15803d',
  updates:       '#854d0e',
  reminders:     '#9d174d',
  general:       '#374151',
};

function openAnnModal(id) {
  const a = ANN_DATA[id];
  if (!a) return;
  const color = BUCKET_COLOR[a.bucket] || '#003087';

  document.getElementById('ann-modal-header').style.background = color;
  document.getElementById('ann-modal-title').textContent = a.title;

  const cover = document.getElementById('ann-modal-cover');
  cover.innerHTML = `<i class="fas fa-${a.icon}"></i>`;
  cover.style.background = `linear-gradient(135deg, ${color}, #0057b7)`;

  document.getElementById('ann-modal-meta').innerHTML =
    `<i class="far fa-calendar-alt" style="margin-right:6px;"></i>${a.date}
     &nbsp;·&nbsp;
     <span style="font-weight:700;">${a.cat}</span>`;
  document.getElementById('ann-modal-full').textContent = a.body;

  const backdrop = document.getElementById('ann-modal-backdrop');
  backdrop.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeAnnModal() {
  document.getElementById('ann-modal-backdrop').classList.remove('open');
  document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('ann-modal-backdrop').addEventListener('click', function(e) {
  if (e.target === this) closeAnnModal();
});

// Close on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAnnModal(); });

// ── Slider: mouse drag + touch + arrow buttons ────────────────────────────────
(function() {
  const track = document.getElementById('ann-track');
  const prev  = document.getElementById('ann-prev');
  const next  = document.getElementById('ann-next');
  if (!track) return;

  const scrollBy = () => {
    const slide = track.querySelector('.ann-slide');
    return slide ? slide.offsetWidth + 24 : 300;
  };

  prev.addEventListener('click', () => track.scrollBy({ left: -scrollBy(), behavior: 'smooth' }));
  next.addEventListener('click', () => track.scrollBy({ left:  scrollBy(), behavior: 'smooth' }));

  // Mouse drag
  let isDown = false, startX = 0, scrollLeft = 0;
  track.addEventListener('mousedown', e => {
    isDown = true; startX = e.pageX - track.offsetLeft; scrollLeft = track.scrollLeft;
    track.style.cursor = 'grabbing';
  });
  track.addEventListener('mouseleave', () => { isDown = false; track.style.cursor = 'grab'; });
  track.addEventListener('mouseup',    () => { isDown = false; track.style.cursor = 'grab'; });
  track.addEventListener('mousemove', e => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - track.offsetLeft;
    track.scrollLeft = scrollLeft - (x - startX);
  });
})();
</script>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
