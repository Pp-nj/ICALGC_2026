<?php
/**
 * Admin Sidebar — included in all admin dashboard pages
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
      <?= $_lang==='th' ? 'ผู้ดูแลระบบ' : 'Administrator' ?>
    </div>
    <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:4px;">
      <?= e($user['name'] ?? '') ?>
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'หลัก':'Main' ?></div>
    <a class="sidebar-link <?= $_menu==='dashboard'?'active':'' ?>" href="<?= $appUrl ?>/admin/dashboard.php">
      <i class="fas fa-tachometer-alt"></i><?= t('author.dashboard') ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'บทความ':'Papers' ?></div>
    <a class="sidebar-link <?= $_menu==='papers'?'active':'' ?>" href="<?= $appUrl ?>/admin/papers.php">
      <i class="fas fa-file-alt"></i><?= $_lang==='th'?'จัดการบทความ':'Manage Papers' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='assign-reviewer'?'active':'' ?>" href="<?= $appUrl ?>/admin/assign-reviewer.php">
      <i class="fas fa-user-check"></i><?= $_lang==='th'?'มอบหมายผู้ทรงคุณวุฒิ':'Assign Reviewer' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='final-decision'?'active':'' ?>" href="<?= $appUrl ?>/admin/final-decision.php">
      <i class="fas fa-gavel"></i><?= $_lang==='th'?'ตัดสินผลบทความ':'Final Decision' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='publications'?'active':'' ?>" href="<?= $appUrl ?>/admin/publications.php">
      <i class="fas fa-globe"></i><?= $_lang==='th'?'เผยแพร่บทความ':'Publish Papers' ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'ผู้ใช้งาน':'Users' ?></div>
    <a class="sidebar-link <?= $_menu==='users'?'active':'' ?>" href="<?= $appUrl ?>/admin/users.php">
      <i class="fas fa-users"></i><?= $_lang==='th'?'จัดการผู้ใช้':'Manage Users' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='reviewers'?'active':'' ?>" href="<?= $appUrl ?>/admin/reviewers.php">
      <i class="fas fa-user-tie"></i><?= $_lang==='th'?'ผู้ทรงคุณวุฒิ':'Reviewers' ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'ระบบ':'System' ?></div>
    <a class="sidebar-link <?= $_menu==='certificates'?'active':'' ?>" href="<?= $appUrl ?>/admin/certificates.php">
      <i class="fas fa-certificate"></i><?= t('author.certificates') ?>
    </a>
    <a class="sidebar-link <?= $_menu==='reports'?'active':'' ?>" href="<?= $appUrl ?>/admin/reports.php">
      <i class="fas fa-chart-bar"></i><?= $_lang==='th'?'รายงานสถิติ':'Reports' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='important-dates'?'active':'' ?>" href="<?= $appUrl ?>/admin/important-dates.php">
      <i class="fas fa-calendar-alt"></i><?= $_lang==='th'?'วันสำคัญ':'Important Dates' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='themes'?'active':'' ?>" href="<?= $appUrl ?>/admin/themes.php">
      <i class="fas fa-tags"></i><?= $_lang==='th'?'หัวข้อการประชุม':'Conference Themes' ?>
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
