<?php
require_once __DIR__ . '/../app/settings.php';
$platformName = app_settings(database())['platform_name'] ?? 'Qreative LMS';
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
      <div class="manage-top-actions">
        <a class="secondary-button" href="/">Dashboard</a>
        <a class="secondary-button" href="/courses.php">Kursus</a>
        <a class="secondary-button" href="/schedule.php"><?= $user['role'] === 'guru' ? 'Jadwal mengajar' : 'Jadwal' ?></a>
        <a class="secondary-button" href="/messages.php">Pesan</a>
        <?php if ($user['role'] === 'admin'): ?>
          <a class="secondary-button" href="/users.php">Pengguna</a>
          <a class="secondary-button" href="/finance.php">Keuangan</a>
          <a class="secondary-button" href="/reports.php">Laporan</a>
          <a class="secondary-button" href="/settings.php">Pengaturan</a>
        <?php elseif ($user['role'] === 'murid'): ?>
          <a class="secondary-button" href="/finance.php">Pembayaran</a>
        <?php endif; ?>
        <form class="logout-form" method="post" action="/logout.php">
          <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
          <button class="secondary-button" type="submit">Keluar</button>
        </form>
      </div>
    </header>

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
