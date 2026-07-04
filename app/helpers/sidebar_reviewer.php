<?php
/**
 * Reviewer Sidebar — included in all reviewer dashboard pages
 * Expects: $activeMenu (string) to mark current link
 */
use App\Core\Auth;
use App\Core\Notification;
$user   = Auth::user();
$appUrl = APP_URL;
$_lang  = lang();
$_menu  = $activeMenu ?? '';
$_rvUnread = Notification::countUnread((int)$user['id']);
?>
<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-mobile-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle sidebar">
  <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

<aside class="sidebar" id="reviewerSidebar">
  <div class="sidebar-logo">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <img src="<?= $appUrl ?>/assets/images/swu_Logo.png" alt="SWU" onerror="this.style.display='none'">
        <img src="<?= $appUrl ?>/assets/images/Guangdong University of Foreign Studies.png" alt="Guangdong" onerror="this.style.display='none'">
        <h6>ICALGC 2026</h6>
        <div style="font-size:.7rem;color:rgba(255,255,255,.4);margin-top:2px;text-transform:uppercase;letter-spacing:.05em;">
          <?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'Reviewer' ?>
        </div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:4px;">
          <?= e($user['name'] ?? '') ?>
        </div>
      </div>
      <button class="sidebar-close-btn d-lg-none" id="sidebarClose" aria-label="Close sidebar">
        <i class="fas fa-times"></i>
      </button>
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
    <a class="sidebar-link <?= $_menu==='notifications'?'active':'' ?>" href="<?= $appUrl ?>/reviewer/notifications.php">
      <i class="fas fa-bell"></i><?= $_lang==='th'?'การแจ้งเตือน':'Notifications' ?>
      <?php if ($_rvUnread > 0): ?>
        <span class="badge rounded-pill ms-auto" style="background:var(--gold);color:var(--blue-dark);font-size:.65rem;"><?= $_rvUnread ?></span>
      <?php endif; ?>
    </a>
    <a class="sidebar-link <?= $_menu==='certificates'?'active':'' ?>" href="<?= $appUrl ?>/reviewer/certificates.php">
      <i class="fas fa-certificate"></i><?= $_lang==='th'?'ใบรับรอง':'Certificates' ?>
    </a>
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

<script>
(function() {
  var toggle   = document.getElementById('sidebarToggle');
  var close    = document.getElementById('sidebarClose');
  var overlay  = document.getElementById('sidebarOverlay');
  var sidebar  = document.getElementById('reviewerSidebar');

  function openSidebar() {
    sidebar.classList.add('sidebar-open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('sidebar-open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (toggle)  toggle.addEventListener('click', openSidebar);
  if (close)   close.addEventListener('click', closeSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
})();
</script>
