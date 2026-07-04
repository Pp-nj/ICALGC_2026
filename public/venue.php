<?php
require_once __DIR__ . '/../app/helpers/init.php';
$pageTitle = lang()==='th' ? 'สถานที่และติดต่อ' : 'Venue & Contact';
$activeNav = 'venue';
$_lang     = lang();
$appUrl    = APP_URL;
require_once __DIR__ . '/../app/helpers/header.php';

// Static hotel data
$hotels = [
  ['name'=>'Asoke Residence Sukhumvit by UHG ', 'address'=>'Sukhumvit Soi 21, Bangkok', 'km'=>'200 m', 'price'=>'฿2,600/night', 'link'=>'https://www.asokeresidence.com/', 'stars'=>4],
  ['name'=>'Somerset Maison Asoke Bangkok', 'address'=>'Sukhumvit Soi 23, Bangkok', 'km'=>'650 m', 'price'=>'฿4,500/night', 'link'=>'https://www.discoverasr.com/en/somerset-serviced-residence/thailand/somerset-maison-asoke-bangkok?utm_source=google&utm_medium=maps&utm_campaign=hq-google-maps-alwayson--all-en-th-th-somersetmaisonasokebangkok--gbp&--&cid=map::gg::hq:ind:::all:en:th:th:somersetmaisonasokebangkok:0:gbp:0:::', 'stars'=>4],
  ['name'=>'Arte Hotel Bangkok ', 'address'=>'Sukhumvit Soi 19, Bangkok', 'km'=>'1.4 km', 'price'=>'฿3,500/night', 'link'=>'https://www.artehotelbangkok.com/', 'stars'=>4],
  ['name'=>'Grand Mercure Bangkok Asoke Residence ', 'address'=>'Sukhumvit Soi 19, Bangkok', 'km'=>'1.2 km', 'price'=>'฿4,000/night', 'link'=>'https://all.accor.com/hotel/6162/index.en.shtml?utm_campaign=seo+maps&utm_medium=seo+maps&utm_source=google+Maps', 'stars'=>4],
  ['name'=>'Lancaster Bangkok  ', 'address'=>'New Phetchaburi Road, Bangkok', 'km'=>'1.5 km', 'price'=>'฿6,500/night', 'link'=>'https://lancasterbangkok.com/', 'stars'=>5],
];
?>

<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);"><?= $_lang==='th'?'ข้อมูลสถานที่':'Venue Information' ?></span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;"><?= $_lang==='th'?'สถานที่และติดต่อ':'Venue & Contact' ?></h1>
  </div>
  <div class="section-divider"></div>
</div>

<section class="page-section">
  <div class="container">
    <div class="row g-5">

      <!-- Left: Venue Info + Hotels -->
      <div class="col-lg-7">

        <!-- Conference Venue -->
        <div class="content-card mb-4">
          <div class="content-card-title"><i class="fas fa-university me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'สถานที่จัดงาน':'Conference Venue' ?></div>
          <div class="row g-3 align-items-center">
            <div class="col-md-5">
              <img src="<?= $appUrl ?>/assets/images/venue-swu.jpg" alt="SWU Campus"
                   style="width:100%;border-radius:var(--radius-lg);object-fit:cover;height:200px;"
                   onerror="this.src='https://placehold.co/400x200/003087/c9a227?text=SWU+Campus'">
            </div>
            <div class="col-md-7">
              <h3 style="font-size:1.1rem;font-weight:800;color:var(--blue-dark);margin-bottom:10px;">
                <?= $_lang==='th'?'มหาวิทยาลัยศรีนครินทรวิโรฒ ประสานมิตร':'Srinakharinwirot University Prasarnmit Campus' ?>
              </h3>
              <div class="footer-contact-item"><i class="fas fa-map-marker-alt" style="color:var(--blue-mid);"></i>
                <span style="color:var(--blue-mid);"><?= $_lang==='th'?'114 ซอยสุขุมวิท 23 แขวงคลองเตยเหนือ เขตวัฒนา กรุงเทพมหานคร 10110':'114 Sukhumvit Soi 23, Klongtoey Nua, Wattana, Bangkok 10110' ?></span>
              </div>
              <div class="footer-contact-item"><i class="fas fa-train" style="color:var(--blue-mid);"></i>
                <span style="color:var(--blue-mid);" ><?= $_lang==='th'?'BTS สถานีอโศก หรือ MRT สถานนีเพรชบุรี':'BTS Asok Station or MRT Phetchaburi' ?></span>
              </div>
              <div class="footer-contact-item"><i class="fas fa-car" style="color:var(--blue-mid);"></i>
                <span style="color:var(--blue-mid);"><?= $_lang==='th'?'มีที่จอดรถภายในมหาวิทยาลัย':'Parking available on campus' ?></span>
              </div>
              <a href="https://maps.google.com/?q=Srinakharinwirot+University" target="_blank" rel="noopener noreferrer" class="btn-primary-custom mt-3 d-inline-block">
                <i class="fas fa-directions me-2"></i><?= $_lang==='th'?'นำทาง':'Get Directions' ?>
              </a>
            </div>
          </div>
        </div>

        <!-- Nearby Hotels Carousel -->
        <div class="content-card mb-4">
          <div class="content-card-title"><i class="fas fa-hotel me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'โรงแรมใกล้เคียง':'Nearby Hotels' ?></div>

          <div id="hotelCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
              <?php foreach ($hotels as $i => $hotel): ?>
                <div class="carousel-item <?= $i===0?'active':'' ?>">
                  <div class="p-3 rounded" style="background:var(--gray-100);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h4 style="font-size:1rem;font-weight:700;color:var(--blue-dark);margin:0;"><?= e($hotel['name']) ?></h4>
                      <span style="color:var(--gold);font-size:.85rem;">
                        <?= str_repeat('★', $hotel['stars']) ?>
                      </span>
                    </div>
                    <div class="mb-3" style="font-size:.85rem;color:var(--gray-700);">
                      <div><i class="fas fa-map-marker-alt me-1 text-muted"></i><?= e($hotel['address']) ?></div>
                      <div><i class="fas fa-walking me-1 text-muted"></i><?= e($hotel['km']) ?> <?= $_lang==='th'?'จากสถานที่จัดงาน':'from venue' ?></div>
                      <div><i class="fas fa-tag me-1 text-muted"></i><?= $_lang==='th'?'เริ่มต้น':'From' ?> <?= e($hotel['price']) ?></div>
                    </div>
                    <a href="<?= e($hotel['link']) ?>" target="_blank" class="btn-outline-custom" style="padding:8px 20px;font-size:.85rem;">
                      <i class="fas fa-external-link-alt me-1"></i><?= $_lang==='th'?'จองห้องพัก':'Book Now' ?>
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="d-flex gap-2 mt-3 justify-content-center">
              <button class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-target="#hotelCarousel" data-bs-slide="prev">
                <i class="fas fa-chevron-left"></i>
              </button>
              <?php foreach ($hotels as $i => $h): ?>
                <button data-bs-target="#hotelCarousel" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i===0?'btn btn-sm btn-secondary':'btn btn-sm btn-outline-secondary' ?> rounded-circle"
                        style="width:10px;height:10px;padding:0;min-width:10px;"></button>
              <?php endforeach; ?>
              <button class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-target="#hotelCarousel" data-bs-slide="next">
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>

        <script>
        (function(){
          var carousel = document.getElementById('hotelCarousel');
          if (!carousel) return;
          var dots = carousel.parentElement.querySelectorAll('[data-bs-slide-to]');
          carousel.addEventListener('slide.bs.carousel', function(e){
            dots.forEach(function(dot, idx){
              dot.classList.toggle('btn-secondary', idx === e.to);
              dot.classList.toggle('btn-outline-secondary', idx !== e.to);
            });
          });
        })();
        </script>

      </div>

      <!-- Right: Contact -->
      <div class="col-lg-5">

        <!-- Contact Info -->
        <div class="content-card mb-4">
          <div class="content-card-title"><i class="fas fa-address-card me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'ติดต่อเรา':'Contact Us' ?></div>

          <div class="mb-4">
            <div style="font-weight:700;color:var(--blue-dark);margin-bottom:12px;">
              <?= $_lang==='th'?'คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ':'Faculty of Humanities, Srinakharinwirot University' ?>
            </div>
            <div class="footer-contact-item"><i class="fas fa-map-marker-alt" style="color:var(--blue-mid);"></i>
              <span style="color:var(--blue-mid);" ><?= $_lang==='th'?'114 ซอยสุขุมวิท 23 เขตวัฒนา กรุงเทพฯ 10110':'114 Sukhumvit 23, Wattana, Bangkok 10110' ?></span>
            </div>
            <div class="footer-contact-item"><i class="fas fa-phone" style="color:var(--blue-mid);"></i>
              <span style="color:var(--blue-mid);" >+66 (0) 2-649-5000 ext. XXXX</span>
            </div>
            <div class="footer-contact-item"><i class="fas fa-envelope" style="color:var(--blue-mid);"></i>
              <a href="mailto:icalgc@swu.ac.th" style="color:var(--blue-mid);">icalgc@swu.ac.th</a>
            </div>
            <div class="footer-contact-item"><i class="fas fa-clock" style="color:var(--blue-mid);"></i>
              <span style="color:var(--blue-mid);"><?= $_lang==='th'?'จันทร์–ศุกร์ 08:30–16:30 น.':'Mon–Fri 08:30–16:30' ?></span>
            </div>
          </div>

          <!-- Social Media -->
          <div class="content-card-title" style="border-top:1px solid var(--gray-200);padding-top:16px;margin-top:0;">
            <i class="fas fa-share-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'โซเชียลมีเดีย':'Social Media' ?>
          </div>
          <div class="d-flex flex-nowrap gap-2 mt-3">
            <a href="https://www.facebook.com/swu.humanities" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center justify-content-center gap-1 rounded flex-fill" style="background:#1877f2;color:#fff;font-size:.78rem;text-decoration:none;padding:8px 4px;">
              <i class="fab fa-facebook-f"></i> Facebook
            </a>
            <a href="https://www.instagram.com/swu.humanities" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center justify-content-center gap-1 rounded flex-fill" style="background:#e1306c;color:#fff;font-size:.78rem;text-decoration:none;padding:8px 4px;">
              <i class="fab fa-instagram"></i> Instagram
            </a>
            <a href="https://hu.swu.ac.th/" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center justify-content-center gap-1 rounded flex-fill" style="background:var(--blue-mid);color:#fff;font-size:.78rem;text-decoration:none;padding:8px 4px;">
              <i class="fas fa-globe"></i> Website
            </a>
            <a href="mailto:icalgc@swu.ac.th" class="d-flex align-items-center justify-content-center gap-1 rounded flex-fill" style="background:var(--blue-dark);color:#fff;font-size:.78rem;text-decoration:none;padding:8px 4px;">
              <i class="fas fa-envelope"></i> Email
            </a>
          </div>
        </div>

        <!-- How to Get There -->
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-route me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'การเดินทาง':'How to Get There' ?></div>
          <div class="d-flex flex-column gap-3">
            <?php
            $transport = $_lang==='th' ? [
              ['icon'=>'train','color'=>'#00aa00','title'=>'BTS','desc'=>'สถานีอโศก ทางออก 3 → ต่อวินมอเตอร์ไซต์ หรือเดินผ่านซอยสุขุมวิท 23'],
              ['icon'=>'subway','color'=>'#003087','title'=>'MRT','desc'=>'สถานีสุขุมวิท ทางออก 2 → อาคาร Interchange 21 ต่อวินมอเตอร์ไซค์ หรือเดินเข้าซอยสุขุมวิท 23'],
              ['icon'=>'subway','color'=>'#003087','title'=>'MRT','desc'=>'สถานีเพรชบุรี ทางออก 2 → เดินย้อนมาทางสี่แยกอโศกมนตรี ประมาณ 5 นาที ถึงประตู 4 หรือ 5'],
              ['icon'=>'car','color'=>'#888','title'=>'รถยนต์ส่วนตัว','desc'=>'ใกล้ทางด่วนพิเศษ ที่จอดรถภายในมหาวิทยาลัย'],
            ] : [
              ['icon'=>'train','color'=>'#00aa00','title'=>'BTS','desc'=>'Asok Station Exit 3 → Take a motorcycle taxi or walk through Sukhumvit Soi 23.'],
              ['icon'=>'subway','color'=>'#003087','title'=>'MRT Subway','desc'=>'Sukhumvit Station Exit 2 → From Interchange 21 building, take a motorcycle taxi or walk into Sukhumvit Soi 23.'],
              ['icon'=>'subway','color'=>'#003087','title'=>'MRT Subway','desc'=>'Phetchaburi Station Exit 2 → Walk back towards Asok Montri intersection for about 5 minutes, and you wll reach Gate 4 or 5.'],
              ['icon'=>'car','color'=>'#888','title'=>'Private Car','desc'=>'Near expressway, campus parking available'],
            ];
            foreach ($transport as $t): ?>
              <div class="d-flex gap-3 p-3 rounded" style="background:var(--gray-100);">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:40px;height:40px;background:<?= e($t['color']) ?>;color:#fff;">
                  <i class="fas fa-<?= e($t['icon']) ?>"></i>
                </div>
                <div>
                  <div style="font-weight:700;font-size:.9rem;color:var(--blue-dark);"><?= e($t['title']) ?></div>
                  <div style="font-size:.82rem;color:var(--gray-700);"><?= e($t['desc']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
