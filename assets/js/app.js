    const icon = (name, size = 17) => {
      const paths = {
        grid: '<rect x="3" y="3" width="7" height="7" rx="1.8"/><rect x="14" y="3" width="7" height="7" rx="1.8"/><rect x="3" y="14" width="7" height="7" rx="1.8"/><rect x="14" y="14" width="7" height="7" rx="1.8"/>',
        book: '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5ZM4 17a2 2 0 0 1 2-2h14M8 7h8M8 10h6"/>',
        calendar: '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        users: '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/>',
        chart: '<path d="M4 19V5M4 19h17M8 15l4-4 3 2 5-6"/>',
        message: '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/>',
        settings: '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.6a8 8 0 0 1-1.6.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.6-.9l-1.7.6-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.9l-1.4-1.1 1.4-2.4 1.7.6a8 8 0 0 1 1.6-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.6.9l1.7-.6 1.4 2.4-1.4 1.1a7 7 0 0 1-.1 1.8Z" transform="translate(-1 -1)"/>',
        clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        people: '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/>',
        check: '<path d="m5 12 4 4L19 6"/>',
        award: '<circle cx="12" cy="8" r="6"/><path d="m8.2 13-1.1 8 4.9-2.5 4.9 2.5-1.1-8"/>'
      };
      return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name] || paths.grid}</svg>`;
    };

    const role = window.QREAT_DASHBOARD_ROLE;
    const navIcons = {grid:"grid",book:"book",calendar:"calendar",users:"users",message:"message",chart:"chart",settings:"settings"};
    let toastTimer;

    function escapeHtml(value) {
      return String(value).replace(/[&<>"']/g, character => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
      })[character]);
    }

    function toast(message) {
      const element = document.getElementById("toast");
      element.textContent = message;
      element.classList.add("show");
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => element.classList.remove("show"), 2600);
    }

    function render() {
      document.getElementById("sideName").textContent = role.name;
      document.getElementById("sideRole").textContent = role.detail;
      document.getElementById("sideAvatar").textContent = role.initials;
      document.getElementById("welcome").textContent = role.greeting;
      document.getElementById("subtitle").textContent = role.subtitle;
      document.getElementById("scheduleTitle").textContent = role.scheduleTitle;
      document.getElementById("courseTitle").textContent = role.courseTitle;
      document.getElementById("activityTitle").textContent = role.activityTitle;
      document.getElementById("crumb").textContent = "Dashboard";
      const mainNav = document.getElementById("mainNav");
      if (!mainNav.dataset.rendered) {
        mainNav.innerHTML = role.nav.map(([label, symbol, count, href], i) => {
          const tag = href ? "a" : "button";
          const target = href ? `href="${escapeHtml(href)}"` : `type="button" data-nav="${escapeHtml(label)}"`;
          return `<${tag} class="nav-item ${i === 0 ? "active" : ""}" ${target}>${icon(navIcons[symbol] || "grid", 18)}<span>${escapeHtml(label)}</span>${count ? `<span class="nav-count">${escapeHtml(count)}</span>` : ""}</${tag}>`;
        }).join("");
        mainNav.dataset.rendered = "true";
      }
      document.getElementById("stats").innerHTML = role.stats.map(([label, value, foot, symbol], i) =>
        `<article class="stat-card"><div class="stat-top"><span>${escapeHtml(label)}</span><span class="stat-icon ${i === 1 ? "sky" : i === 2 ? "peach" : ""}">${icon(symbol, 15)}</span></div><div class="stat-value">${escapeHtml(value)}<span class="stat-foot ${foot.includes("menunggu") || foot.includes("ditinjau") ? "neutral" : ""}">${escapeHtml(foot)}</span></div></article>`
      ).join("");
      const featured = role.featured;
      const roleMessage = role.label === "Admin" ? "Membuka ringkasan laporan platform." : role.label === "Guru" ? "Ruang kelas siap dibuka." : "Selamat belajar! Lesson kamu siap dilanjutkan.";
      const roleNote = role.label === "Murid" ? "✦  5 hari beruntun" : role.label === "Guru" ? "✦  Kelas siap" : "✦  Semua sistem aktif";
      const featuredHref = Number.isInteger(featured.course_id)
        ? `/course.php?id=${featured.course_id}`
        : featured.action === "Jelajahi kursus" ? "/courses.php" : "";
      const featuredAction = featuredHref
        ? `<a class="primary-button" href="${featuredHref}">${escapeHtml(featured.action)} <span aria-hidden="true">→</span></a>`
        : `<button class="primary-button" data-toast="${roleMessage}">${escapeHtml(featured.action)} <span aria-hidden="true">→</span></button>`;
      document.getElementById("featuredCard").innerHTML = `<div class="continue-copy"><span class="mini-tag">${icon("book", 11)} ${escapeHtml(featured.tag)}</span><h2>${escapeHtml(featured.title)}</h2><p>${escapeHtml(featured.description)}</p><div class="lesson-meta"><span>${icon("book", 12)} ${escapeHtml(featured.lesson)}</span><span>${icon("clock", 12)} ${escapeHtml(featured.duration)}</span></div>${featured.progress ? `<div class="progress-row"><div class="progress-track"><span style="width:${escapeHtml(featured.progress)}"></span></div><small>${escapeHtml(featured.progress)} selesai</small></div>` : ""}${featuredAction}</div><div class="art-panel"><div class="book-art">${icon("book", 56)}</div><div class="floating-note">${roleNote}</div></div>`;
      document.getElementById("scheduleList").innerHTML = role.schedule.map(([time, title, info], i) =>
        `<div class="schedule-item"><div class="schedule-time">${time}</div><div class="schedule-mark ${i === 1 ? "blue" : i === 2 ? "orange" : ""}"></div><div class="schedule-info"><strong>${title}</strong><span>${info}</span></div></div>`
      ).join("");
      renderCourses();
      document.getElementById("activityList").innerHTML = role.activities.map(([symbol, title, meta, action], i) =>
        `<div class="activity"><span class="activity-icon ${i === 1 ? "blue" : i === 2 ? "gold" : ""}">${icon(symbol, 15)}</span><div class="activity-text"><strong>${escapeHtml(title)}</strong><span>${escapeHtml(meta)}</span></div><span class="activity-score">${escapeHtml(action)}</span></div>`
      ).join("");
      const [tag, title, description, time] = role.announcement;
      document.getElementById("announcement").innerHTML = `<span class="mini-tag">${escapeHtml(tag)}</span><h3>${escapeHtml(title)}</h3><p>${escapeHtml(description)}</p><small>${escapeHtml(time)}</small>`;
      dashboardContent = pageContent.innerHTML;
    }

    function renderCourses(query = "") {
      const courseGrid = document.getElementById("courseGrid");
      if (!courseGrid) return;
      if (role.courses.length === 0) {
        courseGrid.textContent = role.label === "Murid"
          ? "Daftar kursus untuk memulai progres belajarmu."
          : "Belum ada kursus. Buka menu Kursus untuk mulai mengelola materi.";
        courseGrid.classList.add("empty-state");
        return;
      }
      courseGrid.classList.remove("empty-state");
      const filtered = role.courses.filter(course => `${course.name} ${course.by} ${course.level}`.toLowerCase().includes(query.toLowerCase()));
      if (!filtered.length) {
        const emptyState = document.createElement("div");
        emptyState.className = "empty-state";
        emptyState.textContent = `Tidak ada kursus yang cocok dengan “${query}”.`;
        courseGrid.replaceChildren(emptyState);
        return;
      }
      courseGrid.innerHTML = filtered.map(course =>
        `<a class="course-card" href="${Number.isInteger(course.id) ? `/course.php?id=${course.id}` : "/courses.php"}"><div class="course-cover ${escapeHtml(course.cover)}"><span class="course-level">${escapeHtml(course.level)}</span><span class="course-cover-icon">${icon(course.symbol, 26)}</span></div><div class="course-body"><h3>${escapeHtml(course.name)}</h3><p>${escapeHtml(course.by)}</p><div class="course-progress"><div class="progress-track"><span style="--progress:${Number(course.progress)}%;width:${Number(course.progress)}%"></span></div><small>${Number(course.progress)}%</small></div></div></a>`
      ).join("");
    }

    const pageContent = document.getElementById("pageContent");
    let dashboardContent = "";
    let navigationId = 0;

    function setActiveNavigation(pathname) {
      const currentPath = pathname === "/" ? "/" : pathname;
      document.querySelectorAll("#mainNav .nav-item").forEach(item => {
        const itemPath = item.getAttribute("href");
        const isActive = itemPath !== null && new URL(itemPath, location.href).pathname === currentPath;
        item.classList.toggle("active", isActive);
        if (isActive) item.setAttribute("aria-current", "page");
        else item.removeAttribute("aria-current");
      });
    }

    function updatePageHeading(label) {
      document.getElementById("crumb").textContent = label;
    }

    function updateTransactionFields() {
      const kind = document.getElementById("kind");
      const fields = document.querySelector("[data-income-fields]");
      const student = document.getElementById("finance_student");
      const status = document.getElementById("finance_status");
      if (!kind || !fields || !student || !status) return;

      const showIncomeFields = kind.value === "income";
      fields.hidden = !showIncomeFields;
      student.required = showIncomeFields;
      status.querySelector('option[value="pending"]').disabled = !showIncomeFields;
      if (!showIncomeFields) status.value = "paid";
    }

    function scrollMessagesToLatest() {
      const messageList = document.querySelector(".message-list");
      if (messageList) messageList.scrollTop = messageList.scrollHeight;
    }

    function showSectionPlaceholder(label) {
      pageContent.innerHTML = `<section class="manage-panel menu-placeholder"><span class="eyebrow">WORKSPACE</span><h1>${escapeHtml(label)}</h1><p>Halaman ${escapeHtml(label.toLowerCase())} belum tersedia. Fitur yang sudah tersedia dapat dibuka melalui menu Kursus, Pengguna, Keuangan, Laporan, atau Pengaturan sesuai role akun.</p></section>`;
      pageContent.classList.remove("is-loading");
      updatePageHeading(label);
      document.title = `${label} · Qreative LMS`;
      document.getElementById("sidebar").classList.remove("open");
    }

    function applyPageDocument(doc, pageUrl) {
      const parsedPageContent = doc.getElementById("pageContent");
      if (parsedPageContent) {
        pageContent.innerHTML = dashboardContent;
        pageContent.classList.remove("is-loading");
        updatePageHeading("Dashboard");
      } else {
        const shell = doc.querySelector(".manage-shell");
        if (!shell) return false;

        const contentNodes = Array.from(shell.children)
          .filter(node => !node.classList.contains("manage-topbar"))
          .map(node => document.importNode(node, true));
        pageContent.replaceChildren(...contentNodes);
        pageContent.classList.remove("is-loading");
        const heading = pageContent.querySelector(".manage-heading h1");
        updatePageHeading(heading?.textContent.trim() || "Workspace");
        updateTransactionFields();
        scrollMessagesToLatest();
      }

      document.title = doc.title;
      const parsedUrl = new URL(pageUrl, location.href);
      setActiveNavigation(parsedUrl.pathname);
      document.getElementById("sidebar").classList.remove("open");
      document.getElementById("courseSearch").value = "";
      return true;
    }

    async function navigateTo(url, { push = true, method = "GET", body = null } = {}) {
      const destination = new URL(url, location.href);
      if (destination.origin !== location.origin) {
        location.assign(destination.href);
        return;
      }

      const requestId = ++navigationId;
      pageContent.classList.add("is-loading");
      try {
        const response = await fetch(destination.href, {
          method,
          body,
          headers: { Accept: "text/html" },
          credentials: "same-origin"
        });
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, "text/html");
        const finalUrl = new URL(response.url || destination.href, location.href);

        if (finalUrl.pathname === "/login.php" || !doc.querySelector(".manage-shell, #pageContent")) {
          location.assign(finalUrl.href);
          return;
        }
        if (!response.ok) {
          pageContent.classList.remove("is-loading");
          toast("Halaman tidak dapat dimuat. Periksa akses akun lalu coba lagi.");
          return;
        }
        if (requestId !== navigationId) return;
        if (!applyPageDocument(doc, finalUrl.href)) {
          location.assign(finalUrl.href);
          return;
        }
        if (push) history.pushState({}, "", finalUrl.pathname + finalUrl.search);
      } catch (error) {
        if (requestId !== navigationId) return;
        pageContent.classList.remove("is-loading");
        if (method === "GET") {
          location.assign(destination.href);
        } else {
          toast("Perubahan belum dapat dikonfirmasi. Muat ulang halaman sebelum mencoba kembali.");
        }
      }
    }

    document.addEventListener("click", event => {
      const navButton = event.target.closest("#mainNav [data-nav]");
      if (navButton) {
        showSectionPlaceholder(navButton.dataset.nav);
        document.querySelectorAll("#mainNav .nav-item").forEach(item => item.classList.toggle("active", item === navButton));
        return;
      }
      const link = event.target.closest("a[href]");
      if (link && (pageContent.contains(link) || link.closest("#mainNav")) && !link.hasAttribute("download") && link.target !== "_blank") {
        const destination = new URL(link.href, location.href);
        if (destination.origin === location.origin && destination.pathname.endsWith(".php")) {
          event.preventDefault();
          navigateTo(destination.href);
          return;
        }
      }
      const actionButton = event.target.closest("[data-toast]");
      if (actionButton) toast(actionButton.dataset.toast);
    });
    document.addEventListener("submit", event => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || !pageContent.contains(form) || event.defaultPrevented) return;
      if (form.action.endsWith("/logout.php") || form.hasAttribute("data-no-spa")) return;

      event.preventDefault();
      const method = (form.method || "get").toUpperCase();
      if (method === "GET") {
        const query = new URLSearchParams(new FormData(form));
        const url = new URL(form.action, location.href);
        for (const [key, value] of query) url.searchParams.set(key, value);
        navigateTo(url.href);
        return;
      }
      navigateTo(form.action || location.href, { method, body: new FormData(form) });
    });
    document.addEventListener("change", event => {
      if (event.target.id === "kind") updateTransactionFields();
    });
    window.addEventListener("popstate", () => navigateTo(location.href, { push: false }));
    document.getElementById("courseSearch").addEventListener("input", event => renderCourses(event.target.value));
    document.getElementById("notificationButton").addEventListener("click", () => toast("Tugas Speaking practice sudah dinilai — skor 92/100."));
    document.getElementById("mobileMenu").addEventListener("click", () => document.getElementById("sidebar").classList.toggle("open"));
    render();
    scrollMessagesToLatest();
