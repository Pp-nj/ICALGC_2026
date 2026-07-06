<?php
require_once __DIR__ . '/../app/helpers/init.php';
$pageTitle = lang()==='th' ? 'ผู้บรรยายพิเศษ' : 'Keynote Speakers';
$activeNav = 'keynote';
$_lang     = lang();
$appUrl    = APP_URL;
require_once __DIR__ . '/../app/helpers/header.php';
?>
<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 16px;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);">
      <?= $_lang==='th'?'ผู้บรรยาย':'Keynote' ?>
    </span>
    <h1 style="font-size:clamp(1.5rem,5vw,2.2rem);font-weight:800;color:var(--white);margin-top:12px;">
      <?= $_lang==='th'?'ผู้บรรยายพิเศษ':'Keynote Speakers' ?>
    </h1>
    <p style="color:rgba(255,255,255,.8);">ICALGC 2026 — <?= e(CONF_DATE_EN) ?></p>
  </div>
</div>

<section class="page-section">
  <div class="container">
    <!-- Poster placeholder area -->
    <div class="section-header">
      <div class="section-divider"></div>
    </div>

    <div class="row g-4 justify-content-center">

      <!-- ===== Keynote 1 ===== -->
      <div class="col-12 col-sm-6 col-lg-3 d-flex">
        <div class="content-card text-center card-hover h-100 w-100 d-flex flex-column">
          <img src="<?= e($appUrl) ?>/assets/images/keynote 1.jpg" style="aspect-ratio:1/1;height:auto;object-fit:cover;object-position:center top;width:100%;border-radius:var(--radius-lg);" class="mb-4" loading="lazy">
          <h3 style="font-size:1rem;font-weight:800;color:var(--blue-dark);">
            <?= $_lang==='th'?'Professor Liu Zhiqiang, Ph.D.':'Professor Liu Zhiqiang, Ph.D.' ?>
          </h3>
          <p style="font-size:.85rem;color:var(--gray-500);flex-grow:1;">
            <?= $_lang==='th'?'คณบดีคณะเอเชียอาคเนย์ศึกษา มหาวิทยาลัยภาษาและการค้าต่างประเทศกวางตุ้ง':'Dean, Faculty of Southeast Asian Studies, Guangdong University of Foreign Studies' ?>
          </p>
          <span class="keyword-tag" style="background:var(--blue-dark);color:var(--gold);align-self:center;"><?= $_lang==='th'?'ผู้บรรยายพิเศษ 1':'Keynote 1' ?></span>
        </div>
      </div>

      <!-- ===== Keynote 2 ===== -->
      <div class="col-12 col-sm-6 col-lg-3 d-flex">
        <div class="content-card text-center card-hover h-100 w-100 d-flex flex-column">
          <img src="<?= e($appUrl) ?>/assets/images/keynote 2.jpg" style="aspect-ratio:1/1;height:auto;object-fit:cover;object-position:center top;width:100%;border-radius:var(--radius-lg);" class="mb-4" loading="lazy">
          <h3 style="font-size:1rem;font-weight:800;color:var(--blue-dark);">
            <?= $_lang==='th'?'ผศ.ดร.อัญชลี จันทร์เสม':'Asst. Prof. Dr. Anchalee Jansem' ?>
          </h3>
          <p style="font-size:.85rem;color:var(--gray-500);flex-grow:1;">
            <?= $_lang==='th'?'คณบดีคณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ':'Dean, Faculty of Humanities, Srinakharinwirot University' ?>
          </p>
          <span class="keyword-tag" style="background:var(--blue-dark);color:var(--gold);align-self:center;"><?= $_lang==='th'?'ผู้บรรยายพิเศษ 2':'Keynote 1' ?></span>
        </div>
      </div>

      <!-- ===== Keynote 3 ===== -->
      <div class="col-12 col-sm-6 col-lg-3 d-flex">
        <div class="content-card text-center card-hover h-100 w-100 d-flex flex-column">
          <img src="<?= e($appUrl) ?>/assets/images/keynote 3.jpg" style="aspect-ratio:1/1;height:auto;object-fit:cover;object-position:center top;width:100%;border-radius:var(--radius-lg);" class="mb-4" loading="lazy">
          <h3 style="font-size:1rem;font-weight:800;color:var(--blue-dark);">
            <?= $_lang==='th'?'Associate Professor Luo Yiyuan':'Associate Professor Luo Yiyuan' ?>
          </h3>
          <p style="font-size:.85rem;color:var(--gray-500);flex-grow:1;">
            <?= $_lang==='th'?'ผู้รับผิดชอบหลักสูตรระดับปริญญาตรี ภาควิชาภาษาไทย มหาวิทยาลัยภาษาและการค้าต่างประเทศกวางตุ้ง':'Undergraduate Program Coordinator, Department of Thai Language, Guangdong University of Foreign Studies' ?>
          </p>
          <span class="keyword-tag" style="background:var(--blue-dark);color:var(--gold);align-self:center;"><?= $_lang==='th'?'ผู้บรรยายพิเศษ 3':'Keynote 3' ?></span>
        </div>
      </div>


    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
