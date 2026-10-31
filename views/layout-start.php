<?php
/**
 * Kerangka aplikasi bersama: sidebar, topbar, dan pembuka area konten.
 * Membutuhkan $user, $csrfToken, dan $pageTitle. Tutup dengan layout-end.php.
 */
require_once __DIR__ . '/../app/layout.php';

$platformName = app_settings(database())['platform_name'] ?? 'Qreative LMS';
$navPath = current_nav_path();
$isDashboard = $navPath === '/';
$unreadCount = unread_message_count(database(), (int) $user['id']);
$roleLabel = role_label($user['role']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f4f8f4">
  <title><?= escape($pageTitle) ?> · <?= escape($platformName) ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="/" aria-label="<?= escape($platformName) ?> — ke dashboard">
        <span class="brand-mark"><img src="/assets/img/qreative-logo.webp" alt="<?= escape($platformName) ?>" width="901" height="318"></span>
      </a>
      <div class="side-label">Workspace</div>
      <nav class="nav-list" id="mainNav" aria-label="Menu utama">
        <?php foreach (app_navigation($user['role']) as $navItem): ?>
          <?php $isCurrent = $navItem['href'] === $navPath; ?>
          <a class="nav-item<?= $isCurrent ? ' active' : '' ?>" href="<?= escape($navItem['href']) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
            <?= icon($navItem['icon'], 18) ?><span><?= escape($navItem['label']) ?></span>
            <?php if ($navItem['href'] === '/messages.php' && $unreadCount > 0): ?><span class="nav-count"><?= $unreadCount ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="side-bottom">
        <?php if ($user['role'] !== 'admin'): ?>
          <div class="help-card">
            <div class="help-icon"><?= icon('message', 16) ?></div>
            <strong>Butuh bantuan?</strong>
            <p>Kirim pesan ke guru atau admin kapan saja.</p>
            <a class="help-link" href="/messages.php">Buka pesan <span aria-hidden="true">→</span></a>
          </div>
        <?php endif; ?>
        <div class="profile-mini">
          <div class="avatar"><?= escape(name_initials($user['name'])) ?></div>
          <div><strong><?= escape($user['name']) ?></strong><span><?= escape($roleLabel) ?> · Akun aktif</span></div>
        </div>
      </div>
    </aside>

    <main class="main">
      <header class="topbar">
        <div class="breadcrumb">Workspace <span aria-hidden="true">/</span> <b id="crumb"><?= escape($pageTitle) ?></b></div>
        <a class="mobile-brand" href="/" aria-label="<?= escape($platformName) ?> — ke dashboard"><span class="brand-mark"><img src="/assets/img/qreative-logo.webp" alt="<?= escape($platformName) ?>" width="901" height="318"></span></a>
        <div class="top-actions">
          <label class="search-wrap" id="searchWrap"<?= $isDashboard ? '' : ' hidden' ?>>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            <input id="courseSearch" type="search" placeholder="Cari kursus..." aria-label="Cari kursus">
          </label>
          <a class="icon-button" id="notificationButton" href="/messages.php" aria-label="Pesan<?= $unreadCount > 0 ? ' (' . $unreadCount . ' belum dibaca)' : '' ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php if ($unreadCount > 0): ?><span class="notification-dot"></span><?php endif; ?>
          </a>
          <div class="role-trigger role-badge" aria-label="Role akun saat ini">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20v-1.5a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4V20M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            <span><?= escape($roleLabel) ?></span>
          </div>
          <form class="logout-form" method="post" action="/logout.php">
            <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
            <button class="icon-button" type="submit" aria-label="Keluar" title="Keluar">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </form>
          <button class="icon-button mobile-menu" id="mobileMenu" type="button" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </button>
        </div>
      </header>

      <div class="page-content" id="pageContent">
