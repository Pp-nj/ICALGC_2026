<?php
require_once __DIR__ . '/../app/helpers/init.php';
$pageTitle = lang()==='th' ? 'วิทยากรหลัก' : 'Keynote Speakers';
$activeNav = 'keynote';
$_lang     = lang();
$appUrl    = APP_URL;
require_once __DIR__ . '/../app/helpers/header.php';
?>
<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);">Keynote</span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;">
      <?= $_lang==='th'?'วิทยากรหลัก':'Keynote Speakers' ?>
    </h1>
    <p style="color:rgba(255,255,255,.8);">ICALGC 2026 — <?= e(CONF_DATE_EN) ?></p>
  </div>
</div>

<section class="page-section">
  <div class="container">
    <!-- Poster placeholder area -->
    <div class="section-header">
      <span class="section-label"><?= $_lang==='th'?'ผู้บรรยายพิเศษ':'Distinguished Speakers' ?></span>
      <h2 class="section-title"><?= $_lang==='th'?'วิทยากรหลัก ICALGC 2026':'ICALGC 2026 Keynote Speakers' ?></h2>
      <div class="section-divider"></div>
    </div>

    <div class="row g-4 justify-content-center">
      <!-- Placeholder cards for 4 keynote speakers -->
      <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="col-lg-3 col-md-6">
          <div class="content-card text-center card-hover">
            <!-- Keynote Poster Placeholder -->
            <div class="rounded-lg mb-4 d-flex align-items-center justify-content-center"
                 style="height:280px;background:linear-gradient(135deg,var(--blue-dark),var(--blue-mid));border-radius:var(--radius-lg);">
              <div>
                <i class="fas fa-user-tie fa-4x mb-3" style="color:var(--gold);opacity:.6;"></i>
                <p style="color:rgba(255,255,255,.5);font-size:.85rem;margin:0;">
                  <?= $_lang==='th'?'โปสเตอร์วิทยากร':'Speaker Poster' ?><br>
                  <?= $_lang==='th'?'(เร็วๆ นี้)':'(Coming Soon)' ?>
                </p>
              </div>
            </div>
            <h3 style="font-size:1rem;font-weight:800;color:var(--blue-dark);">
              <?= $_lang==='th'?'รอประกาศ':'To Be Announced' ?>
            </h3>
            <p style="font-size:.85rem;color:var(--gray-500);">
              <?= $_lang==='th'?'สถาบัน: รอประกาศ':'Institution: To Be Announced' ?>
            </p>
            <span class="keyword-tag" style="background:var(--blue-dark);color:var(--gold);">Keynote <?= $i ?></span>
          </div>
        </div>
      <?php endfor; ?>
    </div>

    <div class="text-center mt-5 p-4 rounded" style="background:var(--blue-light);">
      <i class="fas fa-bell fa-2x mb-3" style="color:var(--blue-dark);"></i>
      <h4 style="color:var(--blue-dark);">
        <?= $_lang==='th'?'ติดตามประกาศรายชื่อวิทยากร':'Stay Tuned for Speaker Announcements' ?>
      </h4>
      <p style="color:var(--gray-700);">
        <?= $_lang==='th'
          ? 'รายชื่อวิทยากรหลักจะประกาศเร็วๆ นี้ ติดตามข่าวสารผ่านเว็บไซต์และโซเชียลมีเดียของเรา'
          : 'Keynote speakers will be announced soon. Follow our website and social media for updates.' ?>
      </p>
      <a href="<?= $appUrl ?>/announcements.php" class="btn-primary-custom mt-2">
        <i class="fas fa-bullhorn me-2"></i><?= t('nav.announcements') ?>
      </a>
    </div>

  </div>
</section>
<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
