<?php
/**
 * Shared HTML Header / Navbar
 * Variables expected:
 *   $pageTitle  - <title> tag content
 *   $activeNav  - current nav item key (e.g. 'home', 'about')
 *   $extraCss   - (optional) additional CSS files array
 *   $bodyClass  - (optional) class(es) for <body>
 */
if (!defined('ROOT_PATH')) require_once __DIR__ . '/init.php';

use App\Core\Auth;
use App\Core\Notification;
use App\Core\Database;

$_lang        = lang();
$isLoggedIn   = Auth::isLoggedIn();
$currentUser  = Auth::user();
$unreadCount  = ($isLoggedIn) ? Notification::countUnread($currentUser['id']) : 0;
$_activeNav   = $activeNav ?? '';
$_pageTitle   = $pageTitle ?? 'ICALGC 2026';

// Fetch ticker dates
$tickerDates = [];
try {
    $db   = Database::getInstance();
    $stmt = $db->query("SELECT title_th, title_en, event_date FROM important_dates WHERE is_active = TRUE ORDER BY event_date ASC");
    $tickerDates = $stmt->fetchAll();
} catch (\Throwable $e) {}

$appUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($_pageTitle) ?> — ICALGC 2026</title>
  <meta name="description" content="<?= e(CONF_NAME_EN) ?> — <?= e(CONF_DATE_EN) ?>">
  <meta property="og:title" content="<?= e($_pageTitle) ?> — ICALGC 2026">
  <meta property="og:type" content="website">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Global Styles -->
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">

  <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= e($css) ?>">
  <?php endforeach; endif; ?>

  <link rel="icon" type="image/x-icon" href="<?= $appUrl ?>/assets/images/favicon.ico">
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<!-- ════════════════════════════════════
     IMPORTANT DATES TICKER
     ════════════════════════════════════ -->
<div class="ticker-wrap" aria-label="<?= t('dates.title') ?>">
  <div class="ticker-content" id="tickerContent">
    <?php
    // Custom ticker items (hardcoded) — interspersed every N date items
    // Example: ['text' => 'ข้อความประกาศ', 'link' => $appUrl . '/publication.php']
    $customTickerItems = [
      ['text' => 'ฟรี❗ไม่มีค่าใช้จ่ายในการส่งบทคัดย่อและการนำเสนอ', 'link' => null],
    ];
    $insertEvery = 2; // insert one custom item after every 2 date items

    $renderTickerItem = function ($html) {
        return '<span class="ticker-item"><span class="ticker-dot">◆</span>' . $html . '</span>';
    };
    $renderCustomItem = function ($item) {
        $itemHtml = '<span class="ticker-dot">◆</span>' . e($item['text']);
        if (!empty($item['link'])) {
            return '<a class="ticker-item" href="' . e($item['link']) . '">' . $itemHtml . '</a>';
        }
        return '<span class="ticker-item">' . $itemHtml . '</span>';
    };

    $tickerItems = '';
    $customIndex = 0;
    $dateCount   = 0;
    foreach ($tickerDates as $d) {
      $title   = $_lang === 'th' ? $d['title_th'] : $d['title_en'];
      $daysLeft = daysUntil($d['event_date']);
      $dateStr  = humanDate($d['event_date']);
      if ($daysLeft > 0)       $tag = t('dates.days_left', ['n' => $daysLeft]);
      elseif ($daysLeft === 0) $tag = t('dates.today');
      else                     $tag = t('dates.passed');
      $tickerItems .= $renderTickerItem(e($title) . ' — ' . e($dateStr) . ' (' . e($tag) . ')');
      $dateCount++;

      if (!empty($customTickerItems) && $dateCount % $insertEvery === 0) {
          $tickerItems .= $renderCustomItem($customTickerItems[$customIndex % count($customTickerItems)]);
          $customIndex++;
      }
    }
    // Append any remaining custom items that didn't get a slot yet
    if (!empty($customTickerItems) && $customIndex === 0) {
        $tickerItems .= $renderCustomItem($customTickerItems[0]);
    }

    // Duplicate for seamless loop
    echo $tickerItems . $tickerItems;
    ?>
  </div>
</div>

<!-- ════════════════════════════════════
     NAVBAR
     ════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg navbar-main" id="mainNavbar">
  <div class="container-fluid px-4">
    <!-- Brand Logos -->
    <a class="navbar-brand" href="<?= $appUrl ?>/">
      <img src="<?= asset_ver('/assets/images/swu_Logo.png') ?>"
           alt="Srinakharinwirot University"
           class="brand-logo"
           onerror="this.style.display='none'">
      <span class="brand-divider d-none d-sm-block"></span>
      <img src="<?= asset_ver('/assets/images/Guangdong University of Foreign Studies.png') ?>"
           alt="Guangdong University of Foreign Studies"
           class="brand-logo"
           onerror="this.style.display='none'">
      <span class="text-white fw-bold d-none d-md-inline ms-2" style="font-size:1.2rem;">ICALGC 2026</span>
    </a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="filter:invert(1);"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= $_activeNav==='home'?'active':'' ?>" href="<?= $appUrl ?>/"><?= t('nav.home') ?></a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_activeNav==='about'?'active':'' ?>" href="<?= $appUrl ?>/about.php"><?= t('nav.about') ?></a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_activeNav==='pub'?'active':'' ?>" href="<?= $appUrl ?>/publication.php"><?= t('nav.publication') ?></a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_activeNav==='live'?'active':'' ?>" href="<?= $appUrl ?>/live.php"><?= t('nav.live') ?></a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_activeNav==='photo'?'active':'' ?>" href="<?= $appUrl ?>/photo.php"><?= t('nav.photo') ?></a>
        </li>
      </ul>

      <ul class="navbar-nav  gap-2 align-items-center">
        <!-- Language Switcher -->
        <li class="nav-item d-flex align-items-center gap-1">
          <a href="?lang=th" class="nav-link nav-lang-switch <?= $_lang==='th'?'active-lang':'' ?>"
             title="ภาษาไทย">TH</a>
          <span class="text-white opacity-50">|</span>
          <a href="?lang=en" class="nav-link nav-lang-switch <?= $_lang==='en'?'active-lang':'' ?>"
             title="English">EN</a>
        </li>

        <?php if ($isLoggedIn): ?>
          <!-- Notifications -->
          <li class="nav-item dropdown">
            <a class="nav-link position-relative p-2" href="#" id="notifToggle" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-bell"></i>
              <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
              <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0" aria-labelledby="notifToggle">
              <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                <strong style="font-size:.9rem;color:var(--blue-dark);"><?= t('notif.title') ?></strong>
                <?php if ($unreadCount > 0): ?>
                  <a href="#" onclick="markAllNotifRead(); return false;" style="font-size:.78rem; color:var(--blue-mid);">
                    <?= t('notif.mark_all_read') ?>
                  </a>
                <?php endif; ?>
              </div>
              <?php
              $notifs = ($isLoggedIn) ? Notification::getUnread($currentUser['id']) : [];
              if (empty($notifs)): ?>
                <div class="p-4 text-center text-muted" style="font-size:.85rem;">
                  <i class="far fa-bell-slash mb-2 d-block fa-lg"></i>
                  <?= t('notif.no_notif') ?>
                </div>
              <?php else: foreach ($notifs as $n): ?>
                <div class="notif-item unread" onclick="markNotifRead(<?= (int)$n['id'] ?>)">
                  <div class="notif-item-title"><?= e($_lang==='th' ? $n['title_th'] : $n['title_en']) ?></div>
                  <div class="notif-item-msg"><?= e($_lang==='th' ? $n['message_th'] : $n['message_en']) ?></div>
                  <div class="notif-item-time"><?= humanDate($n['created_at']) ?></div>
                </div>
              <?php endforeach; endif; ?>
              <div class="p-2 border-top text-center">
                <a href="<?= $appUrl ?>/<?= $currentUser['role'] ?>/notifications.php" style="font-size:.82rem;color:var(--blue-mid);">
                  <?= t('notif.view_all') ?>
                </a>
              </div>
            </div>
          </li>

          <!-- User Menu -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
              <span class="rounded-circle bg-gold text-blue d-inline-flex align-items-center justify-content-center"
                    style="width:32px;height:32px;font-size:.9rem;background:var(--gold);color:var(--blue-dark);">
                <i class="fas fa-user"></i>
              </span>
              <span class="d-none d-lg-inline text-white" style="font-size:.85rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?= e($currentUser['name']) ?>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:200px;">
              <li class="px-3 py-2 border-bottom">
                <div style="font-weight:700;font-size:.88rem;color:var(--blue-dark);"><?= e($currentUser['name']) ?></div>
                <div style="font-size:.78rem;color:var(--gray-500);"><?= e(ucfirst($currentUser['role'])) ?></div>
              </li>
              <li>
                <a class="dropdown-item py-2" href="<?= $appUrl ?>/<?= $currentUser['role'] ?>/dashboard.php">
                  <i class="fas fa-tachometer-alt me-2 text-blue" style="color:var(--blue-mid);"></i><?= t('nav.dashboard') ?>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2" href="<?= $appUrl ?>/<?= $currentUser['role'] ?>/profile.php">
                  <i class="fas fa-user me-2" style="color:var(--blue-mid);"></i><?= t('nav.dashboard') === t('nav.dashboard') ? 'My Profile' : t('nav.dashboard') ?>
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item py-2 text-danger" href="<?= $appUrl ?>/logout.php">
                  <i class="fas fa-sign-out-alt me-2"></i><?= t('nav.logout') ?>
                </a>
              </li>
            </ul>
          </li>

        <?php else: ?>
          <li class="nav-item">
            <a href="<?= $appUrl ?>/login.php" class="nav-link btn-nav-login">
              <i class="fas fa-sign-in-alt me-1"></i><?= t('nav.login') ?>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Back to Top -->
<button id="backToTop" aria-label="Back to top" title="Back to top">
  <i class="fas fa-chevron-up"></i>
</button>
