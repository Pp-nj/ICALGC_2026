<?php
/**
 * Shared HTML Footer
 * Variables expected:
 *   $extraJs  - (optional) additional JS files array
 */
$_lang  = lang();
$appUrl = APP_URL;
?>
<!-- ════════════════════════════════════
     FOOTER
     ════════════════════════════════════ -->
<footer class="footer-main">
  <div class="container">
    <div class="row g-4">

      <!-- Column 1: Brand -->
      <div class="col-lg-4 col-md-6">
        <img src="<?= asset_ver('/assets/images/swu_Logo.png') ?>"
             alt="SWU Logo"
             class="footer-logo"
             onerror="this.style.display='none'">
          <img src="<?= asset_ver('/assets/images/Guangdong University of Foreign Studies.png') ?>"
            alt="Guangdong University of Foreign Studies"
            class="footer-logo"
            onerror="this.style.display='none'">
        <p class="footer-desc">
          <?php if ($_lang === 'th'): ?>
            <?= e(CONF_NAME_TH) ?>
            <br><br>
            คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ<br>
            ร่วมกับ Guangdong University of Foreign Studies
          <?php else: ?>
            <?= e(CONF_NAME_EN) ?>
            <br><br>
            Faculty of Humanities, Srinakharinwirot University<br>
            in collaboration with Guangdong University of Foreign Studies
          <?php endif; ?>
        </p>
        <div class="footer-social">
          <a href="https://www.facebook.com/swu.humanities" class="social-btn" title="Facebook" aria-label="Facebook">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="https://www.instagram.com/swu.humanities" class="social-btn" title="Instagram" aria-label="Instagram">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="https://hu.swu.ac.th/" class="social-btn" title="Website" aria-label="Website">
            <i class="fas fa-globe"></i>
          </a>
          <a href="mailto:icalgc2026@gmail.com" class="social-btn" title="Email" aria-label="Email">
            <i class="fas fa-envelope"></i>
          </a>
        </div>
      </div>

      <!-- Column 2: Quick Links -->
      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="footer-heading"><?= $_lang==='th' ? 'ลิงก์ด่วน' : 'Quick Links' ?></h6>
        <a class="footer-link" href="<?= $appUrl ?>/"><?= t('nav.home') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/#about"><?= t('nav.about') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/call-for-abstract.php"><?= t('nav.call_abstract') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/publication.php"><?= t('nav.publication') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/live.php"><?= t('nav.live') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/photo.php"><?= t('nav.photo') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/keynote.php"><?= t('nav.keynote') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/venue.php"><?= t('nav.venue') ?></a>
      </div>

      <!-- Column 3: For Authors -->
      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="footer-heading"><?= $_lang==='th' ? 'สำหรับผู้แต่ง' : 'For Authors' ?></h6>
        <a class="footer-link" href="<?= $appUrl ?>/register.php"><?= t('nav.register') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/login.php"><?= t('nav.login') ?></a>
        <a class="footer-link" href="<?= $appUrl ?>/author/submit.php">
          <?= $_lang==='th' ? 'ส่งบทคัดย่อ' : 'Submit Paper' ?>
        </a>
        <a class="footer-link" href="<?= $appUrl ?>/author/my-papers.php">
          <?= $_lang==='th' ? 'บทคัดย่อของฉัน' : 'My Papers' ?>
        </a>
        <a class="footer-link" href="<?= $appUrl ?>/announcements.php"><?= t('nav.announcements') ?></a>
      </div>

      <!-- Column 4: Contact -->
      <div class="col-lg-4 col-md-6">
        <h6 class="footer-heading"><?= $_lang==='th' ? 'ติดต่อเรา' : 'Contact Us' ?></h6>
        <div class="footer-contact-item">
          <i class="fas fa-map-marker-alt"></i>
          <span>
            <?php if ($_lang==='th'): ?>
              คณะมนุษยศาสตร์ มหาวิทยาลัยศรีนครินทรวิโรฒ<br>
              114 ซอยสุขุมวิท 23 แขวงคลองเตยเหนือ<br>
              เขตวัฒนา กรุงเทพมหานคร 10110
            <?php else: ?>
              Faculty of Humanities, Srinakharinwirot University<br>
              114 Sukhumvit 23 Soi, Klongtoey Nua,<br>
              Wattana, Bangkok 10110, Thailand
            <?php endif; ?>
          </span>
        </div>
        <div class="footer-contact-item">
          <i class="fas fa-phone"></i>
          <span>+66 (0) 2-649-5000 ext. XXXX</span>
        </div>
        <div class="footer-contact-item">
          <i class="fas fa-envelope"></i>
          <a href="mailto:icalgc2026@gmail.com" style="color:rgba(255,255,255,.7);">icalgc2026@gmail.com</a>
        </div>
        <div class="footer-contact-item">
          <i class="fas fa-globe"></i>
          <a href="<?= $appUrl ?>" style="color:rgba(255,255,255,.7);"><?= $appUrl ?></a>
        </div>
      </div>

    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <p class="mb-1">
        &copy; <?= date('Y') ?> <?= e(CONF_SHORT) ?> — <?= e(CONF_NAME_EN) ?>
      </p>
      <p class="mb-0">
        <?= $_lang==='th' ? 'จัดโดย' : 'Organized by' ?>
        Faculty of Humanities, Srinakharinwirot University
        <?= $_lang==='th' ? 'ร่วมกับ' : 'in collaboration with' ?>
        Guangdong University of Foreign Studies
      </p>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Main JS -->
<script src="<?= $appUrl ?>/assets/js/main.js"></script>

<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= e($js) ?>"></script>
<?php endforeach; endif; ?>

<?php if (!empty($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>

</body>
</html>
