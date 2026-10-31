const pageContent = document.getElementById("pageContent");
const sidebar = document.getElementById("sidebar");
const mobileMenu = document.getElementById("mobileMenu");
const searchWrap = document.getElementById("searchWrap");
const courseSearch = document.getElementById("courseSearch");
let toastTimer;
let navigationId = 0;

function toast(message) {
  const element = document.getElementById("toast");
  element.textContent = message;
  element.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => element.classList.remove("show"), 2600);
}

function setSidebarOpen(open) {
  sidebar.classList.toggle("open", open);
  mobileMenu.setAttribute("aria-expanded", String(open));
}

// Pencarian kursus hanya relevan di dashboard (yang punya daftar kursus).
function filterCourses(query) {
  const grid = document.getElementById("courseGrid");
  if (!grid) return;
  const term = query.trim().toLowerCase();
  let visible = 0;
  grid.querySelectorAll(".course-card").forEach(card => {
    const match = card.dataset.search.includes(term);
    card.hidden = !match;
    if (match) visible++;
  });
  const empty = document.getElementById("courseSearchEmpty");
  empty.hidden = visible > 0;
  empty.textContent = `Tidak ada kursus yang cocok dengan “${query.trim()}”.`;
}

function syncSearch() {
  searchWrap.hidden = !document.getElementById("courseGrid");
  courseSearch.value = "";
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

// Semua halaman memakai kerangka yang sama, jadi cukup tukar isi konten,
// menu (status aktif & jumlah pesan), notifikasi, dan judul.
function applyPageDocument(doc) {
  const nextContent = doc.getElementById("pageContent");
  if (!nextContent) return false;

  pageContent.replaceChildren(...Array.from(nextContent.childNodes, node => document.importNode(node, true)));
  for (const id of ["mainNav", "notificationButton", "crumb"]) {
    const next = doc.getElementById(id);
    const current = document.getElementById(id);
    if (next && current) current.replaceWith(document.importNode(next, true));
  }
  document.title = doc.title;
  pageContent.classList.remove("is-loading");
  syncSearch();
  updateTransactionFields();
  scrollMessagesToLatest();
  setSidebarOpen(false);
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
    if (requestId !== navigationId) return;
    const doc = new DOMParser().parseFromString(html, "text/html");
    const finalUrl = new URL(response.url || destination.href, location.href);

    if (finalUrl.pathname === "/login.php" || !doc.getElementById("pageContent")) {
      if (!response.ok) {
        pageContent.classList.remove("is-loading");
        toast("Halaman tidak dapat dimuat. Periksa akses akun lalu coba lagi.");
        return;
      }
      location.assign(finalUrl.href);
      return;
    }
    applyPageDocument(doc);
    if (push) history.pushState({}, "", finalUrl.pathname + finalUrl.search);
    window.scrollTo({ top: 0 });
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
  const link = event.target.closest("a[href]");
  if (link && link.closest(".app") && !link.hasAttribute("download") && link.target !== "_blank" && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
    const destination = new URL(link.href, location.href);
    const isPage = destination.pathname === "/" || destination.pathname.endsWith(".php");
    if (destination.origin === location.origin && isPage && destination.pathname !== "/logout.php") {
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
    const url = new URL(form.action, location.href);
    for (const [key, value] of new FormData(form)) url.searchParams.set(key, value);
    navigateTo(url.href);
    return;
  }
  navigateTo(form.action || location.href, { method, body: new FormData(form) });
});

document.addEventListener("change", event => {
  if (event.target.id === "kind") updateTransactionFields();
});
window.addEventListener("popstate", () => navigateTo(location.href, { push: false }));
courseSearch.addEventListener("input", event => filterCourses(event.target.value));

mobileMenu.addEventListener("click", event => {
  event.stopPropagation();
  setSidebarOpen(!sidebar.classList.contains("open"));
});
// Tutup laci menu saat area gelap di luar sidebar disentuh, tombol Esc ditekan, atau layar melebar.
document.addEventListener("click", event => {
  if (sidebar.classList.contains("open") && !sidebar.contains(event.target)) setSidebarOpen(false);
});
document.addEventListener("keydown", event => {
  if (event.key === "Escape" && sidebar.classList.contains("open")) setSidebarOpen(false);
});
window.matchMedia("(min-width: 1025px)").addEventListener("change", event => {
  if (event.matches) setSidebarOpen(false);
});

updateTransactionFields();
scrollMessagesToLatest();
