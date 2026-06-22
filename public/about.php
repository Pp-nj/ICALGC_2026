<?php
require_once __DIR__ . '/../app/helpers/init.php';
$pageTitle = lang()==='th' ? 'เกี่ยวกับการประชุม' : 'About the Conference';
$activeNav = 'about';
$_lang     = lang();
$appUrl    = APP_URL;
require_once __DIR__ . '/../app/helpers/header.php';
?>

<!-- Page Banner -->
<div style="background:linear-gradient(135deg,var(--blue-dark),#0057b7);padding:60px 0;color:var(--white);text-align:center;">
  <div class="container">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);"><?= $_lang==='th'?'ข้อมูลการประชุม':'Conference Info' ?></span>
    <h1 style="font-size:2.2rem;font-weight:800;color:var(--white);margin-top:12px;"><?= $_lang==='th'?'เกี่ยวกับการประชุม':'About the Conference' ?></h1>
    <p style="color:rgba(255,255,255,.8);font-size:1rem;"><?= e(CONF_NAME_EN) ?></p>
  </div>
</div>

<section class="page-section">
  <div class="container">
    <div class="row g-5">

      <!-- Main Content -->
      <div class="col-lg-8">

        <!-- Background -->
        <div class="content-card" id="background">
          <div class="content-card-title">
            <i class="fas fa-info-circle me-2 text-gold" style="color:var(--gold);"></i>
            <?= $_lang==='th' ? 'ภูมิหลัง' : 'Conference Background' ?>
          </div>

          <?php if ($_lang === 'th'): ?>
            <p>การประชุมวิชาการนานาชาติว่าด้วยภาษาอาเซียนในบริบทโลก 2026 (ICALGC 2026) เป็นการประชุมวิชาการนานาชาติที่จัดขึ้นโดยคณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ร่วมกับมหาวิทยาลัยกวางตุ้งเพื่อการศึกษาต่างประเทศ สาธารณรัฐประชาชนจีน</p>
            <p>ในยุคโลกาภิวัตน์ที่โลกเชื่อมโยงกันมากขึ้น ภาษาอาเซียนกลายเป็นเครื่องมือสำคัญในการสื่อสาร การแลกเปลี่ยนวัฒนธรรม และการพัฒนาความร่วมมือระหว่างประเทศ การประชุมนี้จึงมุ่งสร้างพื้นที่ให้นักวิชาการจากทั่วโลกได้มาแลกเปลี่ยนองค์ความรู้และประสบการณ์ด้านภาษาศาสตร์ การสอนภาษา วรรณกรรม และวัฒนธรรมอาเซียน</p>
            <p>ICALGC 2026 จะจัดขึ้น ณ มหาวิทยาลัยศรีนครินทรวิโรฒ ประสานมิตร กรุงเทพมหานคร ในวันที่ 25 พฤศจิกายน 2569 โดยมีรูปแบบการนำเสนอทั้งแบบ Onsite และ Online</p>
          <?php else: ?>
            <p>The International Conference on ASEAN Languages in Global Contexts 2026 (ICALGC 2026) is an international academic conference organized by the Faculty of Humanities, Srinakharinwirot University, in collaboration with Guangdong University of Foreign Studies, People's Republic of China.</p>
            <p>In an increasingly interconnected world, ASEAN languages serve as vital instruments for communication, cultural exchange, and international cooperation. This conference aims to provide a platform for scholars worldwide to exchange knowledge and experiences in linguistics, language teaching, literature, and ASEAN culture.</p>
            <p>ICALGC 2026 will take place at Srinakharinwirot University Prasarnmit Campus, Bangkok, on November 25, 2026, featuring both onsite and online presentation formats.</p>
          <?php endif; ?>
        </div>

        <!-- Objectives -->
        <div class="content-card" id="objectives">
          <div class="content-card-title">
            <i class="fas fa-bullseye me-2" style="color:var(--gold);"></i>
            <?= $_lang==='th' ? 'วัตถุประสงค์' : 'Objectives' ?>
          </div>

          <?php
          $objectives = $_lang==='th' ? [
            'เป็นศูนย์กลางนานาชาติสำหรับการแลกเปลี่ยนองค์ความรู้ด้านภาษาอาเซียน ระหว่างนักวิชาการ นักวิจัย และผู้ทรงคุณวุฒิจากทั่วโลก',
            'สร้างเครือข่ายความร่วมมือทางวิชาการระหว่างนักวิชาการไทย อาเซียน และนานาชาติ เพื่อส่งเสริมการวิจัยและการพัฒนาภาษาอาเซียน',
            'เสริมสร้างภาพลักษณ์ระดับโลกของคณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ ในฐานะสถาบันชั้นนำด้านมนุษยศาสตร์',
            'เผยแพร่ผลงานวิจัยด้านภาษาศาสตร์ วรรณกรรม และวัฒนธรรมอาเซียนสู่ชุมชนวิชาการสากล',
            'ส่งเสริมการอนุรักษ์และพัฒนาภาษาของกลุ่มประเทศอาเซียนในบริบทโลกาภิวัตน์',
          ] : [
            'Serve as an international hub for knowledge exchange on ASEAN languages among scholars, researchers, and experts worldwide.',
            'Build collaborative academic networks among Thai, ASEAN, and international scholars to promote research and development of ASEAN languages.',
            'Enhance the global image of the Faculty of Humanities, Srinakharinwirot University as a leading institution in the humanities.',
            'Disseminate research findings in linguistics, literature, and ASEAN culture to the international academic community.',
            'Promote the preservation and development of ASEAN languages in the context of globalization.',
          ];
          ?>
          <ol style="padding-left:20px;line-height:2;">
            <?php foreach ($objectives as $obj): ?>
              <li style="margin-bottom:10px;color:var(--gray-700);"><?= e($obj) ?></li>
            <?php endforeach; ?>
          </ol>
        </div>

        <!-- Scope -->
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-globe-asia me-2" style="color:var(--gold);"></i>
            <?= $_lang==='th' ? 'ขอบเขตการนำเสนอ' : 'Scope of the Conference' ?>
          </div>
          <?php if ($_lang==='th'): ?>
            <p>การประชุมรับบทความวิจัยและบทความวิชาการที่เกี่ยวข้องกับ:</p>
            <ul style="line-height:2;color:var(--gray-700);">
              <li>ภาษาศาสตร์และการสื่อสารข้ามวัฒนธรรมในอาเซียน</li>
              <li>การเรียนการสอนภาษาในบริบทโลกาภิวัตน์</li>
              <li>วรรณกรรมและวัฒนธรรมอาเซียน</li>
              <li>เทคโนโลยีภาษาและนวัตกรรมดิจิทัล</li>
              <li>การแปลและล่ามในบริบทอาเซียน</li>
              <li>นโยบายภาษาและการวางแผนภาษา</li>
              <li>ภาษาชนกลุ่มน้อยและการอนุรักษ์ภาษา</li>
            </ul>
          <?php else: ?>
            <p>The conference welcomes research papers and academic articles related to:</p>
            <ul style="line-height:2;color:var(--gray-700);">
              <li>ASEAN Linguistics and Cross-Cultural Communication</li>
              <li>Language Teaching and Learning in a Globalized World</li>
              <li>ASEAN Literature and Culture</li>
              <li>Language Technology and Digital Innovation</li>
              <li>Translation and Interpretation in ASEAN Contexts</li>
              <li>Language Policy and Language Planning</li>
              <li>Minority Languages and Language Preservation</li>
            </ul>
          <?php endif; ?>
        </div>

        <!-- Organizers -->
        <div class="content-card">
          <div class="content-card-title">
            <i class="fas fa-university me-2" style="color:var(--gold);"></i>
            <?= $_lang==='th' ? 'หน่วยงานจัดงาน' : 'Organizing Institutions' ?>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 rounded text-center" style="border:1px solid var(--gray-200);">
                <img src="<?= $appUrl ?>/assets/images/logo-swu.png" alt="SWU" style="height:60px;margin-bottom:12px;" onerror="this.style.display='none'">
                <div style="font-weight:700;color:var(--blue-dark);font-size:.9rem;">
                  <?= $_lang==='th'
                    ? 'คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ'
                    : 'Faculty of Humanities, Srinakharinwirot University' ?>
                </div>
                <div style="font-size:.8rem;color:var(--gray-500);margin-top:4px;">Bangkok, Thailand</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded text-center" style="border:1px solid var(--gray-200);">
                <img src="<?= $appUrl ?>/assets/images/logo-gduf.png" alt="GDUF" style="height:60px;margin-bottom:12px;" onerror="this.style.display='none'">
                <div style="font-weight:700;color:var(--blue-dark);font-size:.9rem;">
                  Guangdong University of Foreign Studies
                </div>
                <div style="font-size:.8rem;color:var(--gray-500);margin-top:4px;">Guangzhou, China</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        <div class="cfa-toc" style="position:sticky;top:100px;">
          <div class="cfa-toc-title"><i class="fas fa-list me-2"></i><?= $_lang==='th'?'สารบัญ':'Contents' ?></div>
          <a href="#background"><?= $_lang==='th'?'ภูมิหลัง':'Background' ?></a>
          <a href="#objectives"><?= $_lang==='th'?'วัตถุประสงค์':'Objectives' ?></a>
        </div>

        <!-- Conference Details Card -->
        <div class="content-card mt-3">
          <div class="content-card-title"><i class="fas fa-calendar-alt me-2" style="color:var(--gold);"></i>Conference Details</div>
          <div style="font-size:.88rem;color:var(--gray-700);line-height:2;">
            <div class="mb-2"><i class="fas fa-calendar text-blue me-2" style="color:var(--blue-mid);"></i><strong><?= $_lang==='th'?'วันที่':'Date' ?>:</strong><br>&nbsp;&nbsp;<?= e(CONF_DATE_EN) ?></div>
            <div class="mb-2"><i class="fas fa-clock text-blue me-2" style="color:var(--blue-mid);"></i><strong><?= $_lang==='th'?'เวลา':'Time' ?>:</strong><br>&nbsp;&nbsp;<?= e(CONF_TIME) ?></div>
            <div><i class="fas fa-map-marker-alt text-blue me-2" style="color:var(--blue-mid);"></i><strong><?= $_lang==='th'?'สถานที่':'Venue' ?>:</strong><br>&nbsp;&nbsp;<?= e(CONF_VENUE_EN) ?></div>
          </div>
          <a href="<?= $appUrl ?>/register.php" class="btn-primary-custom d-block text-center mt-3">
            <i class="fas fa-user-plus me-2"></i><?= t('nav.register') ?>
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
