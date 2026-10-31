<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f4f8f4">
<?php require __DIR__ . '/theme-head.php'; ?>
  <meta name="description" content="<?= escape(APP_TAGLINE) ?>">
  <title>Masuk · <?= escape($platformName) ?></title>
  <link rel="stylesheet" href="<?= escape(asset_url('assets/css/app.css')) ?>">
</head>
<body class="auth-page">
  <?php $themeToggleClass = 'auth-theme-toggle'; require __DIR__ . '/theme-toggle.php'; ?>
  <main class="auth-shell">
    <section class="auth-card">
      <a class="auth-brand" href="/login.php" aria-label="<?= escape($platformName) ?>">
        <span class="brand-mark"><img src="<?= escape(asset_url('assets/img/qreative-logo.webp')) ?>" alt="<?= escape($platformName) ?>" width="901" height="318"></span>
      </a>
      <p class="auth-tagline"><?= escape(APP_TAGLINE) ?></p>

      <?php if ($error !== ''): ?>
        <div class="auth-error" role="alert"><?= escape($error) ?></div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="/login.php">
        <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
        <label for="email">Email atau username</label>
        <input id="email" name="email" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" required maxlength="254" value="<?= escape($email) ?>" placeholder="nama@email.com atau username">
        <label for="password">Kata sandi</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="4096" placeholder="Masukkan kata sandi">
        <button class="primary-button auth-submit" type="submit">Masuk <span aria-hidden="true">→</span></button>
      </form>
    </section>
  </main>
</body>
</html>
