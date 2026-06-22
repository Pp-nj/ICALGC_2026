<?php
/**
 * Reviewer Sidebar — included in all reviewer dashboard pages
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
    <img src="<?= $appUrl ?>/assets/images/logo-swu.png" alt="SWU" onerror="this.style.display='none'">
    <h6>ICALGC 2026</h6>
    <div style="font-size:.7rem;color:rgba(255,255,255,.4);margin-top:2px;text-transform:uppercase;letter-spacing:.05em;">
      <?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'Reviewer' ?>
    </div>
    <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:4px;">
      <?= e($user['name'] ?? '') ?>
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'หลัก':'Main' ?></div>
    <a class="sidebar-link <?= $_menu==='dashboard'?'active':'' ?>" href="<?= $appUrl ?>/reviewer/dashboard.php">
      <i class="fas fa-tachometer-alt"></i><?= t('author.dashboard') ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'บทความ':'Papers' ?></div>
    <a class="sidebar-link <?= $_menu==='assigned'?'active':'' ?>" href="<?= $appUrl ?>/reviewer/assigned-papers.php">
      <i class="fas fa-tasks"></i><?= $_lang==='th'?'บทความที่ได้รับมอบหมาย':'Assigned Papers' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='history'?'active':'' ?>" href="<?= $appUrl ?>/reviewer/history.php">
      <i class="fas fa-history"></i><?= $_lang==='th'?'ประวัติการประเมิน':'Review History' ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'บัญชี':'Account' ?></div>
    <a class="sidebar-link <?= $_menu==='profile'?'active':'' ?>" href="<?= $appUrl ?>/reviewer/profile.php">
      <i class="fas fa-user"></i><?= $_lang==='th'?'ข้อมูลส่วนตัว':'My Profile' ?>
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
