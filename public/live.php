<?php
require_once __DIR__ . '/../app/helpers/init.php';
$pageTitle = lang()==='th' ? 'ถ่ายทอดสด' : 'Live Streaming';
$activeNav = 'live';
$_lang     = lang();
$appUrl    = APP_URL;
require_once __DIR__ . '/../app/helpers/header.php';
?>
<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);">Live</span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;">
      <?= $_lang==='th'?'ถ่ายทอดสดการประชุม':'Conference Live Streaming' ?>
    </h1>
  </div>
</div>
<section class="page-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">

        <!-- Live Status -->
        <div class="content-card text-center mb-4">
          <div class="mb-4">
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                  style="width:80px;height:80px;background:var(--blue-light);">
              <i class="fas fa-broadcast-tower fa-2x" style="color:var(--blue-dark);"></i>
            </span>
          </div>
          <h2 style="color:var(--blue-dark);font-weight:800;">
            <?= $_lang==='th' ? 'การถ่ายทอดสดจะเริ่มในวันจัดงาน' : 'Live Stream Will Begin on Conference Day' ?>
          </h2>
          <p style="color:var(--gray-700);margin-bottom:24px;">
            <?= $_lang==='th'
              ? 'การถ่ายทอดสดการประชุม ICALGC 2026 จะเริ่มในวันที่ 25 พฤศจิกายน 2569 กรุณาติดตามลิงก์ด้านล่าง'
              : 'The ICALGC 2026 conference live stream will begin on November 25, 2026. Please check back for the link.' ?>
          </p>

          <!-- Placeholder Streaming Links -->
          <div class="row g-3 justify-content-center">
            <div class="col-md-5">
              <div class="p-4 rounded" style="background:var(--gray-100);border:2px solid var(--gray-200);">
                <i class="fab fa-youtube fa-2x mb-3" style="color:#ff0000;"></i>
                <h4 style="font-size:1rem;font-weight:700;color:var(--blue-dark);">YouTube Live</h4>
                <p style="font-size:.85rem;color:var(--gray-500);">
                  <?= $_lang==='th'?'ช่อง YouTube ของคณะมนุษยศาสตร์ มศว':'Faculty of Humanities SWU YouTube Channel' ?>
                </p>
                <a href="#" class="btn-primary-custom" style="display:inline-block;opacity:.6;pointer-events:none;">
                  <?= $_lang==='th'?'ดูถ่ายทอดสด (เร็วๆ นี้)':'Watch Live (Coming Soon)' ?>
                </a>
              </div>
            </div>
            <div class="col-md-5">
              <div class="p-4 rounded" style="background:var(--gray-100);border:2px solid var(--gray-200);">
                <i class="fab fa-zoom fa-2x mb-3" style="color:#2d8cff;"></i>
                <h4 style="font-size:1rem;font-weight:700;color:var(--blue-dark);">Zoom Webinar</h4>
                <p style="font-size:.85rem;color:var(--gray-500);">
                  <?= $_lang==='th'?'สำหรับผู้ลงทะเบียนเข้าร่วมออนไลน์':'For registered online participants' ?>
                </p>
                <a href="#" class="btn-outline-custom" style="display:inline-block;opacity:.6;pointer-events:none;">
                  <?= $_lang==='th'?'เข้าร่วม Zoom (เร็วๆ นี้)':'Join Zoom (Coming Soon)' ?>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Schedule -->
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-calendar-alt me-2" style="color:var(--gold);"></i>
            <?= $_lang==='th'?'กำหนดการ 25 พฤศจิกายน 2569':'Schedule – November 25, 2026' ?>
          </div>
          <?php
          $schedule = $_lang==='th' ? [
            ['time'=>'08:30', 'event'=>'ลงทะเบียน'],
            ['time'=>'09:00', 'event'=>'พิธีเปิดการประชุม'],
            ['time'=>'09:30', 'event'=>'การบรรยายพิเศษ (Keynote 1)'],
            ['time'=>'10:30', 'event'=>'พักรับประทานอาหารว่าง'],
            ['time'=>'11:00', 'event'=>'การบรรยายพิเศษ (Keynote 2)'],
            ['time'=>'12:00', 'event'=>'พักรับประทานอาหารกลางวัน'],
            ['time'=>'13:30', 'event'=>'การนำเสนอบทความ (ภาคบ่าย)'],
            ['time'=>'16:00', 'event'=>'พิธีมอบใบประกาศนียบัตร'],
            ['time'=>'16:30', 'event'=>'พิธีปิดการประชุม'],
          ] : [
            ['time'=>'08:30', 'event'=>'Registration & Welcome Coffee'],
            ['time'=>'09:00', 'event'=>'Opening Ceremony'],
            ['time'=>'09:30', 'event'=>'Keynote Speaker 1'],
            ['time'=>'10:30', 'event'=>'Coffee Break'],
            ['time'=>'11:00', 'event'=>'Keynote Speaker 2'],
            ['time'=>'12:00', 'event'=>'Lunch Break'],
            ['time'=>'13:30', 'event'=>'Paper Presentations (Afternoon Session)'],
            ['time'=>'16:00', 'event'=>'Certificate Presentation Ceremony'],
            ['time'=>'16:30', 'event'=>'Closing Ceremony'],
          ];
          ?>
          <div class="table-responsive">
            <table class="table-custom">
              <thead>
                <tr>
                  <th style="width:120px;"><?= $_lang==='th'?'เวลา':'Time' ?></th>
                  <th><?= $_lang==='th'?'กิจกรรม':'Activity' ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($schedule as $s): ?>
                  <tr>
                    <td><strong style="color:var(--blue-mid);"><?= e($s['time']) ?></strong></td>
                    <td><?= e($s['event']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
