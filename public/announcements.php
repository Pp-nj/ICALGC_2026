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
    'image'    => '/assets/images/announcements1.jpg',
    'date'     => ['th' => '6 ก.ค. 2569', 'en' => '6 Jul 2026'],
    'title'    => ['th' => '🚨 เปิดรับบทคัดย่อ ICALGC 2026 แล้ววันนี้', 'en' => '🚨 ICALGC 2026 Abstract Submission is Now Open'],
    'body'     => [
      'th' => "✨คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ร่วมกับ Guangdong University of Foreign Studies สาธารณรัฐประชาชนจีน เป็นเจ้าภาพในการจัดประชุมวิชาการระดับนานาชาติ
              หัวข้อ “ภาษาอาเซียนในบริบทโลก” 
              📅 วันที่ 25 พฤศจิกายน 2569 
              📍 ณ หอดนตรีและการแสดงอโศกมนตรี ชั้น 4 อาคารนวัตกรรม : ศาสตราจารย์ ดร.สาโรช บัวศรี มหาวิทยาลัยศรีนครินทรวิโรฒ \n
              หัวข้อในการประชุม: 
              • ภาษา วรรณคดี ศิลปวัฒนรรรม และการสอนภาษาไทย 
              • ภาษาอาเซียน การสอนภาษาอาเซียน อาเซียนศึกษา 
              • ภาษาและการเรียนการสอนภาษาลาว 
              • ภาษาและการเรียนการสอนภาษาเวียดนาม 
              • ภาษาและการเรียนการสอนภาษามลายู 
              • ภาษาและการเรียนการสอนภาษาอินโดนีเซีย 
              • ภาษาและการเรียนการสอนภาษาพม่า - ภาษาและการเรียนการสอนภาษาเขมร\n
              กำหนดการ 
              ✍🏻 เปิดรับบทคัดย่อ : 6 กรกฎาคม 2569 – 31 สิงหาคม 2569 
              📄 ได้รับผลการพิจารณา : ภายใน 15 กันยายน 2569
              🎤 นำเสนอ : 25 พฤศจิกายน 2569",
      'en' => "✨The Faculty of Humanities, Srinakharinwirot University in collaboration with Guangdong University of Foreign Studies, China, cordially invites researchers, scholars, faculty members, students, and interested participants to submit abstracts for the International Conference on 
              'ASEAN Languages in the Global Context.'
              📅 Conference Date : 25 November 2026
              📍 Venue : Asok-Montri Music and Performing Arts Hall, 4th Floor, Innovation Building: Professor Dr. Saroj Buasri, Srinakharinwirot University\n
              Conference Themes
              • Thai Language, Literature, Arts and Culture, and Thai Language Education
              • ASEAN Languages, ASEAN Language Education, and ASEAN Studies
              • Lao Language and Lao Language Education
              • Vietnamese Language and Vietnamese Language Education
              • Malay Language and Malay Language Education
              • Indonesian Language and Indonesian Language Education
              • Burmese Language and Burmese Language Education
              • Khmer Language and Khmer Language Education\n
              Important Dates
              ✍🏻 Abstract Submission : 6 July 2026 - 31 August 2026
              📄 Notification of Acceptance : By 15 September 2026
              🎤 Conference Presentation : 25 November 2026",
    ],
  ],
  [
    'id'       => 2,
    'category' => ['th' => 'ลงทะเบียน', 'en' => 'Announcements'],
    'bucket'   => 'updates',
    'icon'     => 'user-plus',
    'image'    => '/assets/images/announcements2.jpg',
    'date'     => ['th' => '6 ก.ค. 2569', 'en' => '6 Jul 2026'],
    'title'    => ['th' => '📌 เปิดลงทะเบียนเข้าร่วมประชุม', 'en' => '📌 Conference Registration is Now Open'],
    'body'     => [
      'th' => 'ลงทะเบียนเข้าร่วมการประชุมวิชาการนานาชาติ ICALGC 2026 ได้แล้ววันนี้ ในรูปแบบ Onsite ณ มหาวิทยาลัยศรีนครินทรวิโรฒ ประสานมิตร',
      'en' => 'Register to attend ICALGC 2026 today, in person at Srinakharinwirot University Prasarnmit Campus.',
    ],
  ],
  [
    'id'       => 3,
    'category' => ['th' => 'ผู้บรรยายพิเศษ', 'en' => 'Keynote'],
    'bucket'   => 'announcements',
    'icon'     => 'fa-microphone-alt',
    'image'    => '/assets/images/announcements3.jpg',
    'date'     => ['th' => '14 ก.ค. 2569', 'en' => '14 Jul 2026'],
    'title'    => ['th' => '🎤 ปาฐกถาพิเศษจากวิทยากรผู้ทรงคุณวุฒิ', 'en' => '🎤 Special keynote lectures by distinguished speakers'],
    'body'     => [
      'th' => "✨ คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ร่วมกับ คณะเอเชียอาคเนย์ศึกษา Guangdong University of Foreign Studies
                ขอเชิญชวนผู้สนใจรับฟังปาฐกถาพิเศษจากวิทยากรผู้ทรงคุณวุฒิ
                ในการประชุมวิชาการระดับนานาชาติในหัวข้อ \n
                'ภาษาอาเซียนในบริบทโลก' 
                ASEAN Languages in Global Context \n
                พบกับ Keynote Speakers ผู้เชี่ยวชาญด้านภาษาและภูมิภาคอาเซียน ที่จะมาร่วมแลกเปลี่ยนมุมมองทางวิชาการเกี่ยวกับบทบาทของภาษาอาเซียนในบริบทโลกปัจจุบัน\n
                🎤 หัวข้อ: ภาษาอาเซียนในบริบทโลก
                ✨Professor Liu Zhiqiang, Ph.D.
                คณบดีคณะเอเชียอาคเนย์ศึกษา มหาวิทยาลัยภาษาและการค้าต่างประเทศกวางตุ้ง
                ✨ผู้ช่วยศาสตราจารย์ ดร.อัญชลี จันทร์เสม
                คณบดีคณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ\n
                🎤 หัวข้อ: การสอนภาษาไทยในประเทศจีนในยุค AI 
                ✨Associate Professor Luo Yiyuan
                ผู้รับผิดชอบหลักสูตรระดับปริญญาตรี ภาควิชาภาษาไทย มหาวิทยาลัยภาษาและการค้าต่างประเทศกวางตุ้ง  \n
                📅 วันที่ 25 พฤศจิกายน 2569
                📍 ณ หอดนตรีและการแสดงอโศกมนตรี 1
                ชั้น 4 อาคารนวัตกรรม: ศาสตราจารย์ ดร.สาโรช บัวศรี มหาวิทยาลัยศรีนครินทรวิโรฒ\n
                ร่วมเปิดมุมมองใหม่ของภาษาอาเซียนในเวทีวิชาการระดับนานาชาติไปพร้อมกัน",
      'en' => "✨ The Faculty of Humanities, Srinakharinwirot University, in collaboration with the Faculty of Southeast Asian Studies, Guangdong University of Foreign Studies,
                cordially invites all interested individuals to attend special keynote lectures by distinguished speakers
                at the International Academic Conference on the topic: \n
                'ASEAN Languages in Global Context' \n
                Meet expert Keynote Speakers specializing in ASEAN languages and the region, who will join us to share their academic perspectives on the role of ASEAN languages in today's global context.\n              
                🎤 Topic: ASEAN Languages in Global Context
                ✨Professor Liu Zhiqiang, Ph.D.
                Dean of the Faculty of Southeast Asian Studies, Guangdong University of Foreign Studies
                ✨Asst. Prof. Dr. Anchalee Jansem
                Dean, Faculty of Humanities, Srinakharinwirot University\n
                🎤 Teaching Thai Language in China in the AI Era
                ✨Associate Professor Luo Yiyuan
                Undergraduate Program Coordinator, Department of Thai Language, Guangdong University of Foreign Studies  \n
                📅 Conference Date : 25 November 2026
                📍 Venue : Asok-Montri Music and Performing Arts Hall, 4th Floor, Innovation Building: Professor Dr. Saroj Buasri, Srinakharinwirot University\n
                Join us in exploring new perspectives on ASEAN languages ​​at an international academic forum.",
    ],
  ],
  [
    'id'       => 4,
    'category' => ['th' => 'ผู้บรรยายพิเศษ', 'en' => 'Keynote'],
    'bucket'   => 'announcements',
    'icon'     => 'fa-microphone-alt',
    'image'    => '/assets/images/announcements4.jpg',
    'date'     => ['th' => '18 ส.ค. 2569', 'en' => '18 Aug 2026'],
    'title'    => ['th' => '🎤 พบกับการเสวนาในหัวข้อ “การสอนภาษาอาเซียนในบริบทโลก”', 'en' => '🎤 Join our Panel Discussion on “Teaching ASEAN Languages in a Global Context”'],
    'body'     => [
      'th' => "🌏คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ร่วมกับ คณะเอเชียอาคเนย์ศึกษา Guangdong University of Foreign Studies สาธารณรัฐประชาชนจีน เป็นเจ้าภาพในการจัดประชุมวิชาการระดับนานาชาติ  “ภาษาอาเซียนในบริบทโลก” International Academic Conference: ASEAN Languages in Global Context \n
              พบกับการเสวนาในหัวข้อ
              🎓 “การสอนภาษาอาเซียนในบริบทโลก”
              “Teaching ASEAN Languages in a Global Context” \n
              ร่วมแลกเปลี่ยนมุมมองการสอนภาษาอาเซียนกับอาจารย์ผู้เชี่ยวชาญจาก 3 มหาวิทยาลัย ได้แก่ \n
              ✨อาจารย์ ดร. ณประภาพร รุจจนเวท
              คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ
              ✨Associate Professor Chen Shi, Ph.D.
              Faculty of Southeast Asian Studies, Guangdong University of Foreign Studies
              ✨ผู้ช่วยศาสตราจารย์ ดร. ภัสธิดา บุญชวลิต
              คณะมนุษยศาสตร์ มหาวิทยาลัยรามคำแหง \n
              📅 25 พฤศจิกายน 2569 
              ⏰ เวลา 10.00–10.45 น.
              📍 หอศิลป์และการแสดงอโศกมนตรี 1 ชั้น 4 อาคารนวัตกรรม: ศาสตราจารย์ ดร.สาโรช บัวศรี มหาวิทยาลัยศรีนครินทรวิโรฒ \n
              มาร่วมเปิดมุมมองใหม่เกี่ยวกับการเรียนการสอนภาษาอาเซียน และบทบาทของภาษาในโลกยุคไร้พรมแดนไปด้วยกัน 🌏✨",
      'en' => "🌏 International Academic Conference: ASEAN Languages in Global Context \n
              Join our Panel Discussion on
              🎓 “Teaching ASEAN Languages in a Global Context”
              with experts from 3 universities. \n
              ✨ Dr. Naprapaporn Rootjanawat
              Faculty of Humanities, Srinakharinwirot University
              ✨Associate Professor Chen Shi, Ph.D.
              Faculty of Southeast Asian Studies, Guangdong University of Foreign Studies
              ✨ Assistant Professor Dr. Patthida Bunchavalit
              Faculty of Humanities, Ramkhamhaeng University \n
              📅 November 25, 2026
              ⏰ 10:00–10:45 AM
              📍 Asok-Montri Music and Performing Arts Hall: MPA Hall 4 FL., Innovation Building: Prof. Dr. Saroj Buasri, Srinakharinwirot University \n
              Join us as we explore new perspectives on teaching ASEAN languages and the role of languages in an increasingly interconnected world. 🌏✨",
    ],
  ],
  [
    'id'       => 5,
    'category' => ['th' => 'บทคัดย่อ', 'en' => 'Abstract'],
    'bucket'   => 'announcements',
    'icon'     => 'file-alt',
    'image' => ['th' => '/assets/images/announcements5-th.jpg','en' => '/assets/images/announcements5-en.jpg',],
    'date'     => ['th' => '1 ต.ค. 2569', 'en' => '1 Oct 2026'],
    'title'    => ['th' => '📣 ปิดรับบทคัดย่อ', 'en' => '📣 Abstract Submission Closed'],
    'body'     => [
      'th' => "✨การประชุมวิชาการระดับนานาชาติ
                International Conference on ASEAN Languages in Global Contexts\n
                📣 ปิดรับบทคัดย่อ 
                ขอขอบคุณทุกท่านที่ให้ความสนใจ
                Thank You for Being Part of ICALGC 2026\n
                📝 ติดตามประกาศผลการพิจารณาบทคัดย่อ วันที่ 31 ตุลาคม 2569
                🚨 ผ่านทาง 🚨
                🌐 เว็บไซต์: https://icalgc.swu.ac.th
                🌐 เฟซบุ๊ก: Icalgc 2026-International Conference on ASEAN Languages in Global Contexts
                คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ",
      'en' => "✨International Conference on ASEAN Languages in Global Contexts\n
                📣 Abstract Submission Closed
                Thank You for Being Part of ICALGC 2026\n
                📝 Stay Tuned for Abstract Review Results on October 31, 2026
                🚨 via  🚨
                🌐 Website: https://icalgc.swu.ac.th
                🌐 Facebook: Icalgc 2026-International Conference on ASEAN Languages in Global Contexts
                คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ",
    ],
  ],

];

// ── Sort newest → oldest (by English date; same date → higher id first) ─────────
usort($announcements, function ($a, $b) {
  $cmp = strtotime($b['date']['en']) <=> strtotime($a['date']['en']);
  return $cmp !== 0 ? $cmp : $b['id'] <=> $a['id'];
});

// ── Resolve per-language image ────────────────────────────────────────────────
// 'image' can be a single path (shared) or ['th' => ..., 'en' => ...].
// Falls back to the other language if the current one is missing.
foreach ($announcements as &$a) {
  if (is_array($a['image'] ?? null)) {
    $a['image'] = $a['image'][$_lang] ?? $a['image']['th'] ?? $a['image']['en'] ?? '';
  }
}
unset($a);

// ── Filtering & Pagination ─────────────────────────────────────────────────────
$activeFilter = sanitize(get('cat', 'all'));

$filtered = $activeFilter && $activeFilter !== 'all'
    ? array_values(array_filter($announcements, fn($a) => $a['bucket'] === $activeFilter))
    : array_values($announcements);

$displayed = $filtered;

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
                <div class="announce-card-img <?= $catClass ?>" <?= !empty($ann['image']) ? 'style="background-image:url(\''.e($appUrl.'/'.$ann['image']).'\');background-size:cover;background-position:center;"' : '' ?>>
                  <?php if (empty($ann['image'])): ?>
                    <i class="fas fa-<?= e($ann['icon']) ?>"></i>
                  <?php endif; ?>
                  <span class="ann-date-badge">
                    <i class="far fa-calendar-alt" style="margin-right:4px;opacity:.7;"></i><?= e($ann['date'][$_lang]) ?>
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

        <!-- ── Pagination Dots ── -->
    <div class="ann-pagination-dots" id="ann-dots"></div>

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
      'image'  => !empty($a['image']) ? $appUrl . '/' . $a['image'] : '',
      'date'    => $a['date'][$_lang],
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
  if (a.image) {
    cover.classList.add('has-image');
    cover.style.background = '';
    cover.innerHTML = `<img src="${a.image}" alt="">`;
  } else {
    cover.classList.remove('has-image');
    cover.innerHTML = `<i class="fas fa-${a.icon}"></i>`;
    cover.style.background = `linear-gradient(135deg, ${color}, #0057b7)`;
  }

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

// ── Slider: mouse drag + touch + arrow buttons + dots (เหมือน activities.php) ──
(function() {
  const track = document.getElementById('ann-track');
  const prev  = document.getElementById('ann-prev');
  const next  = document.getElementById('ann-next');
  const dotsContainer = document.getElementById('ann-dots');
  if (!track) return;

  function getSlides() {
    return Array.from(track.querySelectorAll('.ann-slide'));
  }

  function getScrollAmount() {
    const slides = getSlides();
    return slides.length ? slides[0].offsetWidth + 24 : 300;
  }

  // ── ปุ่มถัดไป/ก่อนหน้า (loop เมื่อสุดขอบ) ──
  next.addEventListener('click', () => {
    const slides = getSlides();
    if (slides.length === 0) return;
    const scrollAmount  = getScrollAmount();
    const maxScrollLeft = track.scrollWidth - track.clientWidth;

    if (Math.ceil(track.scrollLeft) >= maxScrollLeft - 10) {
      track.scrollTo({ left: 0, behavior: 'smooth' });
    } else {
      track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
  });

  prev.addEventListener('click', () => {
    const slides = getSlides();
    if (slides.length === 0) return;
    const scrollAmount  = getScrollAmount();
    const maxScrollLeft = track.scrollWidth - track.clientWidth;

    if (track.scrollLeft <= 10) {
      track.scrollTo({ left: maxScrollLeft, behavior: 'smooth' });
    } else {
      track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    }
  });

  // ── Mouse drag (คงเดิม) ──
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

  // ── Pagination Dots (สร้าง/อัปเดตจากตำแหน่ง scroll จริง) ──
  function generateDots() {
    if (!dotsContainer) return;
    dotsContainer.innerHTML = '';

    const slides = getSlides();
    const maxScrollLeft = track.scrollWidth - track.clientWidth;
    if (slides.length === 0 || maxScrollLeft <= 0) return;

    const scrollAmount = getScrollAmount();
    const numDots = Math.ceil(maxScrollLeft / scrollAmount) + 1;

    for (let i = 0; i < numDots; i++) {
      const dot = document.createElement('span');
      dot.classList.add('ann-dot');
      dot.addEventListener('click', () => {
        track.scrollTo({ left: scrollAmount * i, behavior: 'smooth' });
      });
      dotsContainer.appendChild(dot);
    }
    updatePagination();
  }

  function updatePagination() {
    if (!dotsContainer) return;
    const currentDots = dotsContainer.querySelectorAll('.ann-dot');
    if (currentDots.length === 0) return;

    const maxScrollLeft = track.scrollWidth - track.clientWidth;
    const scrollAmount  = getScrollAmount();
    const currentScroll = track.scrollLeft;

    let currentIndex = 0;
    if (Math.ceil(currentScroll) >= maxScrollLeft - 10) {
      currentIndex = currentDots.length - 1;
    } else {
      currentIndex = Math.round(currentScroll / scrollAmount);
    }
    if (currentIndex >= currentDots.length) currentIndex = currentDots.length - 1;
    if (currentIndex < 0) currentIndex = 0;

    currentDots.forEach(d => d.classList.remove('active'));
    if (currentDots[currentIndex]) currentDots[currentIndex].classList.add('active');
  }

  track.addEventListener('scroll', () => {
    window.requestAnimationFrame(updatePagination);
  });
  window.addEventListener('resize', generateDots);

  generateDots(); // รันครั้งแรกตอนโหลดหน้า
})();
</script>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
