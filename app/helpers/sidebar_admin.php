<?php
/**
 * Admin Sidebar — included in all admin dashboard pages
 * Expects: $activeMenu (string) to mark current link
 */
use App\Core\Auth;
use App\Core\Notification;
$user   = Auth::user();
$appUrl = APP_URL;
$_lang  = lang();
$_menu  = $activeMenu ?? '';
$_adminUnread = Notification::countUnread((int)$user['id']);
?>
<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-mobile-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle sidebar">
  <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-logo">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <img src="<?= $appUrl ?>/assets/images/swu_Logo.png" alt="SWU" onerror="this.style.display='none'">
        <img src="<?= $appUrl ?>/assets/images/Guangdong University of Foreign Studies.png" alt="Guangdong" onerror="this.style.display='none'">
        <h6>ICALGC 2026</h6>
        <div style="font-size:.7rem;color:rgba(255,255,255,.4);margin-top:2px;text-transform:uppercase;letter-spacing:.05em;">
          <?= $_lang==='th' ? 'ผู้ดูแลระบบ' : 'Administrator' ?>
        </div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:4px;">
          <?= e($user['name'] ?? '') ?>
        </div>
      </div>
      <!-- Close button on mobile -->
      <button class="sidebar-close-btn d-lg-none" id="sidebarClose" aria-label="Close sidebar">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'หลัก':'Main' ?></div>
    <a class="sidebar-link <?= $_menu==='dashboard'?'active':'' ?>" href="<?= $appUrl ?>/admin/dashboard.php">
      <i class="fas fa-tachometer-alt"></i><?= t('author.dashboard') ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'บทคัดย่อ':'Papers' ?></div>
    <a class="sidebar-link <?= $_menu==='papers'?'active':'' ?>" href="<?= $appUrl ?>/admin/papers.php">
      <i class="fas fa-file-alt"></i><?= $_lang==='th'?'จัดการบทคัดย่อ':'Manage Papers' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='assign-reviewer'?'active':'' ?>" href="<?= $appUrl ?>/admin/assign-reviewer.php">
      <i class="fas fa-user-check"></i><?= $_lang==='th'?'มอบหมายผู้ทรงคุณวุฒิ':'Assign Reviewer' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='final-decision'?'active':'' ?>" href="<?= $appUrl ?>/admin/final-decision.php">
      <i class="fas fa-gavel"></i><?= $_lang==='th'?'ตัดสินผลบทคัดย่อ':'Final Decision' ?>
    </a>
    <a class="sidebar-link <?= $_menu==='publications'?'active':'' ?>" href="<?= $appUrl ?>/admin/publications.php">
      <i class="fas fa-globe"></i><?= $_lang==='th'?'เผยแพร่บทคัดย่อ':'Publish Papers' ?>
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
    <a class="sidebar-link <?= $_menu==='admins'?'active':'' ?>" href="<?= $appUrl ?>/admin/admins.php">
      <i class="fas fa-user-shield"></i><?= $_lang==='th'?'จัดการแอดมิน':'Manage Admins' ?>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $_lang==='th'?'ระบบ':'System' ?></div>
    <a class="sidebar-link <?= $_menu==='notifications'?'active':'' ?>" href="<?= $appUrl ?>/admin/notifications.php">
      <i class="fas fa-bell"></i><?= $_lang==='th'?'การแจ้งเตือน':'Notifications' ?>
      <?php if ($_adminUnread > 0): ?>
        <span class="badge rounded-pill ms-auto" style="background:var(--gold);color:var(--blue-dark);font-size:.65rem;"><?= $_adminUnread ?></span>
      <?php endif; ?>
    </a>
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

<script>
(function() {
  var toggle   = document.getElementById('sidebarToggle');
  var close    = document.getElementById('sidebarClose');
  var overlay  = document.getElementById('sidebarOverlay');
  var sidebar  = document.getElementById('adminSidebar');

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
