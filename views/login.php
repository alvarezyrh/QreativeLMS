<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f4f8f4">
  <title>Masuk · <?= escape($platformName) ?></title>
  <link rel="stylesheet" href="<?= escape(asset_url('assets/css/app.css')) ?>">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-card">
      <a class="auth-brand" href="/login.php" aria-label="Qreative LMS">
        <span class="brand-mark"><img src="<?= escape(asset_url('assets/img/qreative-logo.webp')) ?>" alt="Qreative" width="901" height="318"></span>
      </a>

      <div class="auth-heading">
        <span class="eyebrow">SELAMAT DATANG KEMBALI</span>
        <h1>Belajar dimulai dari sini.</h1>
        <p>Masuk untuk melanjutkan perjalanan bahasa Inggrismu.</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="auth-error" role="alert"><?= escape($error) ?></div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="/login.php">
        <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="username" required maxlength="254" value="<?= escape($email) ?>" placeholder="nama@email.com">
        <label for="password">Kata sandi</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="4096" placeholder="Masukkan kata sandi">
        <button class="primary-button auth-submit" type="submit">Masuk ke dashboard <span aria-hidden="true">→</span></button>
      </form>

      <details class="demo-accounts">
        <summary>Akun demo untuk mencoba dashboard</summary>
        <p>Kata sandi semua akun: <code>Qreative123!</code></p>
        <ul>
          <li>Admin: <code>admin@qreative.test</code></li>
          <li>Guru: <code>guru@qreative.test</code></li>
          <li>Murid: <code>murid@qreative.test</code></li>
        </ul>
      </details>
    </section>
    <p class="auth-footer"><?= escape($platformName) ?> <span aria-hidden="true">·</span> English made a little easier</p>
  </main>
</body>
</html>
