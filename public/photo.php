<?php
require_once __DIR__ . '/../app/helpers/init.php';
$pageTitle = lang()==='th' ? 'ภาพกิจกรรม' : 'Photo Gallery';
$activeNav = 'photo';
$_lang     = lang();
$appUrl    = APP_URL;
require_once __DIR__ . '/../app/helpers/header.php';
?>
<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);"><?= $_lang==='th'?'ภาพถ่าย':'Photo Gallery' ?></span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;">
      <?= $_lang==='th'?'ภาพกิจกรรม ICALGC 2026':'ICALGC 2026 Photo Gallery' ?>
    </h1>
    <div class="section-divider"></div>
  </div>
</div>
<section class="page-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <div class="content-card">
          <div class="mb-4">
            <i class="fab fa-google-drive fa-4x" style="color:#4285f4;"></i>
          </div>
          <h2 style="color:var(--blue-dark);font-weight:800;margin-bottom:16px;">
            <?= $_lang==='th'?'ดูภาพถ่ายทั้งหมดบน Google Drive':'View All Photos on Google Drive' ?>
          </h2>
          <p style="color:var(--gray-700);margin-bottom:28px;">
            <?= $_lang==='th'
              ? 'ภาพถ่ายทั้งหมดจากการประชุม ICALGC 2026 จัดเก็บบน Google Drive เพื่อความสะดวกในการเข้าถึงและดาวน์โหลด'
              : 'All photos from ICALGC 2026 are stored on Google Drive for easy access and download.' ?>
          </p>
          <a href="https://drive.google.com" target="_blank" rel="noopener noreferrer" class="btn-primary-custom" style="font-size:1rem;padding:14px 36px;">
            <i class="fab fa-google-drive me-2"></i>
            <?= $_lang==='th'?'เปิด Google Drive':'Open Google Drive' ?>
          </a>
          <p class="mt-4" style="font-size:.82rem;color:var(--gray-500);">
            <i class="fas fa-info-circle me-1"></i>
            <?= $_lang==='th'
              ? 'ภาพถ่ายจะพร้อมหลังการประชุม (25 พฤศจิกายน 2569)'
              : 'Photos will be available after the conference (November 25, 2026).' ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
