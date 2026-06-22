<?php
require_once __DIR__ . '/../app/helpers/init.php';
$pageTitle = lang()==='th' ? 'สถานที่และติดต่อ' : 'Venue & Contact';
$activeNav = 'venue';
$_lang     = lang();
$appUrl    = APP_URL;
require_once __DIR__ . '/../app/helpers/header.php';

// Static hotel data
$hotels = [
  ['name'=>'Sukhumvit Suite Hotel', 'address'=>'Sukhumvit Soi 13, Bangkok', 'km'=>'0.5 km', 'price'=>'฿1,800/night', 'link'=>'#', 'stars'=>4],
  ['name'=>'Grande Centre Point Terminal 21', 'address'=>'Sukhumvit Soi 16, Bangkok', 'km'=>'1.2 km', 'price'=>'฿3,200/night', 'link'=>'#', 'stars'=>5],
  ['name'=>'Citadines Sukhumvit 23', 'address'=>'Sukhumvit Soi 23, Bangkok', 'km'=>'0.3 km', 'price'=>'฿2,100/night', 'link'=>'#', 'stars'=>4],
  ['name'=>'Park Plaza Bangkok Soi 18', 'address'=>'Sukhumvit Soi 18, Bangkok', 'km'=>'0.8 km', 'price'=>'฿2,600/night', 'link'=>'#', 'stars'=>4],
];
?>

<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);"><?= $_lang==='th'?'ข้อมูลสถานที่':'Venue Information' ?></span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;"><?= $_lang==='th'?'สถานที่และติดต่อ':'Venue & Contact' ?></h1>
  </div>
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
                <span><?= $_lang==='th'?'114 ซอยสุขุมวิท 23 แขวงคลองเตยเหนือ เขตวัฒนา กรุงเทพมหานคร 10110':'114 Sukhumvit Soi 23, Klongtoey Nua, Wattana, Bangkok 10110' ?></span>
              </div>
              <div class="footer-contact-item"><i class="fas fa-train" style="color:var(--blue-mid);"></i>
                <span><?= $_lang==='th'?'BTS สถานีอโศก (ทางออก 2) หรือ MRT สุขุมวิท':'BTS Asok Station (Exit 2) or MRT Sukhumvit' ?></span>
              </div>
              <div class="footer-contact-item"><i class="fas fa-car" style="color:var(--blue-mid);"></i>
                <span><?= $_lang==='th'?'มีที่จอดรถภายในมหาวิทยาลัย':'Parking available on campus' ?></span>
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

        <!-- Google Maps Embed -->
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-map me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'แผนที่':'Map' ?></div>
          <div style="border-radius:var(--radius);overflow:hidden;border:1px solid var(--gray-200);">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.556!2d100.560!3d13.728!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e29ede28ced6dd%3A0x7b70e2fc2e51e5b2!2sSrinakharinwirot%20University!5e0!3m2!1sen!2sth!4v1"
              width="100%"
              height="320"
              style="border:0;"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              title="SWU Map">
            </iframe>
          </div>
        </div>

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
              <span><?= $_lang==='th'?'114 ซอยสุขุมวิท 23 เขตวัฒนา กรุงเทพฯ 10110':'114 Sukhumvit 23, Wattana, Bangkok 10110' ?></span>
            </div>
            <div class="footer-contact-item"><i class="fas fa-phone" style="color:var(--blue-mid);"></i>
              <span>+66 (0) 2-649-5000 ext. XXXX</span>
            </div>
            <div class="footer-contact-item"><i class="fas fa-envelope" style="color:var(--blue-mid);"></i>
              <a href="mailto:icalgc@swu.ac.th" style="color:var(--blue-mid);">icalgc@swu.ac.th</a>
            </div>
            <div class="footer-contact-item"><i class="fas fa-clock" style="color:var(--blue-mid);"></i>
              <span><?= $_lang==='th'?'จันทร์–ศุกร์ 08:30–16:30 น.':'Mon–Fri 08:30–16:30' ?></span>
            </div>
          </div>

          <!-- Social Media -->
          <div class="content-card-title" style="border-top:1px solid var(--gray-200);padding-top:16px;margin-top:0;">
            <i class="fas fa-share-alt me-2" style="color:var(--gold);"></i><?= $_lang==='th'?'โซเชียลมีเดีย':'Social Media' ?>
          </div>
          <div class="d-flex flex-wrap gap-3 mt-3">
            <a href="#" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center gap-2 rounded px-3 py-2" style="background:#1877f2;color:#fff;font-size:.88rem;text-decoration:none;">
              <i class="fab fa-facebook-f"></i> Facebook
            </a>
            <a href="#" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center gap-2 rounded px-3 py-2" style="background:#ff0000;color:#fff;font-size:.88rem;text-decoration:none;">
              <i class="fab fa-youtube"></i> YouTube
            </a>
            <a href="#" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center gap-2 rounded px-3 py-2" style="background:#00b900;color:#fff;font-size:.88rem;text-decoration:none;">
              <i class="fab fa-line"></i> Line
            </a>
            <a href="mailto:icalgc@swu.ac.th" class="d-flex align-items-center gap-2 rounded px-3 py-2" style="background:var(--blue-dark);color:#fff;font-size:.88rem;text-decoration:none;">
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
              ['icon'=>'train','color'=>'#00aa00','title'=>'BTS สกายเทรน','desc'=>'สถานีอโศก (สาย Sukhumvit) ทางออก 2 → เดิน 300 เมตร'],
              ['icon'=>'subway','color'=>'#003087','title'=>'MRT ใต้ดิน','desc'=>'สถานีสุขุมวิท → เดิน 400 เมตร'],
              ['icon'=>'bus','color'=>'#ff6600','title'=>'รถประจำทาง','desc'=>'สาย 2, 25, 38, 48, 113 ลงป้ายสุขุมวิท 23'],
              ['icon'=>'car','color'=>'#888','title'=>'รถยนต์ส่วนตัว','desc'=>'ใกล้ทางด่วนพิเศษ ที่จอดรถภายในมหาวิทยาลัย'],
            ] : [
              ['icon'=>'train','color'=>'#00aa00','title'=>'BTS Skytrain','desc'=>'Asok Station (Sukhumvit Line) Exit 2 → 300m walk'],
              ['icon'=>'subway','color'=>'#003087','title'=>'MRT Subway','desc'=>'Sukhumvit Station → 400m walk'],
              ['icon'=>'bus','color'=>'#ff6600','title'=>'Public Bus','desc'=>'Routes 2, 25, 38, 48, 113 — stop at Sukhumvit 23'],
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
