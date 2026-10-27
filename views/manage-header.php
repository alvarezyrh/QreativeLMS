<?php
require_once __DIR__ . '/../app/settings.php';
$platformName = app_settings(database())['platform_name'] ?? 'Qreative LMS';
$currentNavPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($currentNavPath === '/course.php') {
    $currentNavPath = '/courses.php';
}
$navCurrent = static fn (string $path): string => $currentNavPath === $path ? ' aria-current="page"' : '';
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
<body class="manage-page">
  <main class="manage-shell">
    <header class="manage-topbar">
      <a class="auth-brand" href="/">
        <span class="brand-mark"><img src="/assets/img/qreative-logo.webp" alt="" width="901" height="318"></span>
        <span class="brand-caption"><?= escape($user['name']) ?> · <?= escape(ucfirst($user['role'])) ?></span>
      </a>
      <nav class="manage-top-actions" aria-label="Menu utama">
        <a class="secondary-button" href="/"<?= $navCurrent('/') ?>>Dashboard</a>
        <a class="secondary-button" href="/courses.php"<?= $navCurrent('/courses.php') ?>>Kursus</a>
        <a class="secondary-button" href="/schedule.php"<?= $navCurrent('/schedule.php') ?>><?= $user['role'] === 'guru' ? 'Jadwal mengajar' : 'Jadwal' ?></a>
        <a class="secondary-button" href="/messages.php"<?= $navCurrent('/messages.php') ?>>Pesan</a>
        <?php if ($user['role'] === 'admin'): ?>
          <a class="secondary-button" href="/users.php"<?= $navCurrent('/users.php') ?>>Pengguna</a>
          <a class="secondary-button" href="/finance.php"<?= $navCurrent('/finance.php') ?>>Keuangan</a>
          <a class="secondary-button" href="/reports.php"<?= $navCurrent('/reports.php') ?>>Laporan</a>
          <a class="secondary-button" href="/settings.php"<?= $navCurrent('/settings.php') ?>>Pengaturan</a>
        <?php elseif ($user['role'] === 'murid'): ?>
          <a class="secondary-button" href="/finance.php"<?= $navCurrent('/finance.php') ?>>Pembayaran</a>
        <?php endif; ?>
        <form class="logout-form" method="post" action="/logout.php">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <button class="secondary-button" type="submit">Keluar</button>
        </form>
      </nav>
    </header>
    <script>
      (() => {
        const tab = document.querySelector('.manage-top-actions [aria-current="page"]');
        if (tab) tab.scrollIntoView({ block: 'nearest', inline: 'center' });
      })();
    </script>

    <section class="manage-heading">
      <div>
        <div class="eyebrow">WORKSPACE · <?= escape(strtoupper($user['role'])) ?></div>
        <h1><?= escape($pageTitle) ?></h1>
        <p class="subtitle"><?= escape($pageDescription) ?></p>
      </div>
    </section>

    <?php if ($flash !== null): ?>
      <div class="manage-alert <?= $flash['type'] === 'error' ? 'is-error' : '' ?>" role="status"><?= escape($flash['message']) ?></div>
    <?php endif; ?>
