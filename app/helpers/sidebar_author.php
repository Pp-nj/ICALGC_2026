<?php
/**
 * Author Sidebar — included in all author dashboard pages
 * Expects: $activeMenu (string) to mark current link
 */
use App\Core\Auth;
$user   = Auth::user();
$appUrl = APP_URL;
$_lang  = lang();
$_menu  = $activeMenu ?? '';
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="<?= $appUrl ?>/assets/images/swu_Logo.png" alt="SWU" onerror="this.style.display='none'">
    <img src="<?= $appUrl ?>/assets/images/Guangdong University of Foreign Studies.png" alt="Guangdong" onerror="this.style.display='none'">
    <h6>ICALGC 2026</h6>
    <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:4px;">
      <?= e($user['name'] ?? '') ?>
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'แดชบอร์ด':'Dashboard' ?></div>
    <a class="sidebar-link <?= $_menu==='dashboard'?'active':'' ?>" href="<?= $appUrl ?>/author/dashboard.php">
      <i class="fas fa-tachometer-alt"></i><?= t('author.dashboard') ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'บทคัดย่อ':'Papers' ?></div>
    <a class="sidebar-link <?= $_menu==='submit'?'active':'' ?>" href="<?= $appUrl ?>/author/submit.php">
      <i class="fas fa-file-upload"></i><?= t('author.submit_paper') ?>
    </a>
    <a class="sidebar-link <?= $_menu==='my-papers'?'active':'' ?>" href="<?= $appUrl ?>/author/my-papers.php">
      <i class="fas fa-file-alt"></i><?= t('author.my_papers') ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'บัญชี':'Account' ?></div>
    <a class="sidebar-link <?= $_menu==='profile'?'active':'' ?>" href="<?= $appUrl ?>/author/profile.php">
      <i class="fas fa-user"></i><?= $_lang==='th'?'ข้อมูลส่วนตัว':'My Profile' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='certificates'?'active':'' ?>" href="<?= $appUrl ?>/author/certificates.php">
      <i class="fas fa-certificate"></i><?= t('author.certificates') ?>
    </a>
    <a class="sidebar-link <?= $_menu==='notifications'?'active':'' ?>" href="<?= $appUrl ?>/author/notifications.php">
      <i class="fas fa-bell"></i><?= t('notif.title') ?>
    </a>
  </div>

  <div class="sidebar-section" style="margin-top:auto;padding-top:20px;border-top:1px solid rgba(255,255,255,.1);">
    <a class="sidebar-link" href="<?= $appUrl ?>/">
      <i class="fas fa-home"></i><?= $_lang==='th'?'หน้าหลักเว็บไซต์':'Main Website' ?>
    </a>
    <a class="sidebar-link" href="<?= $appUrl ?>/logout.php">
      <i class="fas fa-sign-out-alt"></i><?= t('nav.logout') ?>
    </a>
  </div>
</aside>
