<?php /* Dipasang di <head>: menerapkan tema sebelum halaman tampil agar tidak berkedip. */ ?>
  <script>
    (function () {
      var key = "qreative-theme", root = document.documentElement;
      var systemDark = window.matchMedia("(prefers-color-scheme: dark)");
      function saved() { try { return localStorage.getItem(key); } catch (error) { return null; } }
      function apply(theme) {
        root.dataset.theme = theme;
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.content = theme === "dark" ? "#144047" : "#f4f8f4";
        document.querySelectorAll("[data-theme-toggle]").forEach(function (button) {
          button.setAttribute("aria-pressed", String(theme === "dark"));
          button.setAttribute("aria-label", theme === "dark" ? "Ganti ke mode terang" : "Ganti ke mode gelap");
          button.title = button.getAttribute("aria-label");
        });
      }
      // Tanpa pilihan tersimpan, ikuti pengaturan perangkat.
      apply(saved() || (systemDark.matches ? "dark" : "light"));
      systemDark.addEventListener("change", function (event) { if (!saved()) apply(event.matches ? "dark" : "light"); });
      document.addEventListener("DOMContentLoaded", function () { apply(root.dataset.theme); });
      document.addEventListener("click", function (event) {
        if (!event.target.closest("[data-theme-toggle]")) return;
        var next = root.dataset.theme === "dark" ? "light" : "dark";
        try { localStorage.setItem(key, next); } catch (error) {}
        apply(next);
      });
    })();
  </script>
