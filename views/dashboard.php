<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f4f8f4">
  <title><?= escape($platformName) ?> — English made a little easier</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <div class="brand-mark"><img src="/assets/img/qreative-logo.webp" alt="Qreative" width="901" height="318"></div>
        <div><div class="brand-name"><?= escape($platformName) ?></div><div class="brand-caption">English, made a little easier</div></div>
      </div>
      <div class="side-label">Workspace</div>
      <nav class="nav-list" id="mainNav"></nav>
      <div class="side-bottom">
        <div class="help-card">
          <div class="help-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M9.6 9a2.5 2.5 0 1 1 4.3 1.7c-1 .9-1.9 1.2-1.9 2.8M12 17.2v.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
          <strong>Butuh bantuan?</strong>
          <p>Tim kami siap membantu perjalanan belajarmu.</p>
          <button data-toast="Pusat bantuan siap membantu kamu.">Kunjungi pusat bantuan <span aria-hidden="true">→</span></button>
        </div>
        <div class="profile-mini">
          <div class="avatar" id="sideAvatar">SA</div>
          <div><strong id="sideName">Sarah Anderson</strong><span id="sideRole">Murid · Level B1</span></div>
          <span class="profile-more" aria-hidden="true">···</span>
        </div>
      </div>
    </aside>

    <main class="main">
      <header class="topbar">
        <div class="breadcrumb">Workspace <span aria-hidden="true">/</span> <b id="crumb">Dashboard</b></div>
        <div class="mobile-brand"><div class="brand-mark"><img src="/assets/img/qreative-logo.webp" alt="" width="901" height="318"></div><?= escape($platformName) ?></div>
        <div class="top-actions">
          <label class="search-wrap">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            <input id="courseSearch" type="search" placeholder="Cari kursus..." aria-label="Cari kursus">
          </label>
          <button class="icon-button" id="notificationButton" aria-label="Notifikasi">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="notification-dot"></span>
          </button>
          <div class="role-select">
            <div class="role-trigger role-badge" aria-label="Role akun saat ini">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20v-1.5a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4V20M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
              <span><?= escape($dashboardRole['label']) ?></span>
            </div>
          </div>
          <form class="logout-form" method="post" action="/logout.php">
            <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
            <button class="icon-button" type="submit" aria-label="Keluar" title="Keluar">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </form>
          <button class="icon-button mobile-menu" id="mobileMenu" aria-label="Buka menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </button>
        </div>
      </header>

      <div class="page-content" id="pageContent">
      <section class="heading-row">
        <div>
          <div class="eyebrow" id="eyebrow"><?= escape(date('l, F j, Y')) ?></div>
          <h1 id="welcome"><?= escape($dashboardRole['greeting']) ?></h1>
          <p class="subtitle" id="subtitle"><?= escape($dashboardRole['subtitle']) ?></p>
        </div>
        <div class="date-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="16" rx="3" stroke="currentColor" stroke-width="1.6"/><path d="M7.5 3v4M16.5 3v4M4 9.5h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          <span><?= escape(date('j F Y')) ?></span>
        </div>
      </section>

      <section class="stats" id="stats" aria-label="Ringkasan"></section>

      <section class="dashboard-grid">
        <article class="panel continue-card" id="featuredCard"></article>
        <article class="panel section-panel">
          <div class="section-head"><h2 id="scheduleTitle">Jadwal hari ini</h2><a class="text-button" href="/schedule.php">Lihat semua <span aria-hidden="true">→</span></a></div>
          <div class="schedule-list" id="scheduleList"></div>
        </article>
      </section>

      <section class="panel course-section">
        <div class="section-head"><div><h2 id="courseTitle">Lanjutkan belajarmu</h2></div><button class="text-button" data-nav="Kursus">Semua kursus <span aria-hidden="true">→</span></button></div>
        <div class="course-grid" id="courseGrid"></div>
      </section>

      <section class="lower-grid">
        <article class="panel section-panel">
          <div class="section-head"><h2 id="activityTitle">Aktivitas terbaru</h2><button class="text-button" data-nav="Aktivitas">Lihat aktivitas <span aria-hidden="true">→</span></button></div>
          <div class="activity-list" id="activityList"></div>
        </article>
        <article class="panel section-panel">
          <div class="section-head"><h2>Pengumuman</h2><button class="text-button" data-toast="Semua pengumuman terbaru ditampilkan.">Lihat semua <span aria-hidden="true">→</span></button></div>
          <div class="announcement" id="announcement"></div>
        </article>
      </section>
      </div>
    </main>
  </div>
  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  <script>
    window.QREAT_DASHBOARD_ROLE = <?= json_encode(
        $dashboardRole,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ) ?>;
  </script>
  <script src="/assets/js/app.js" defer></script>
</body>
</html>
