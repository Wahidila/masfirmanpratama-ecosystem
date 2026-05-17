(function () {
  const THEME_KEY = "prototype-admin-theme";

  function getStoredTheme() {
    const stored = localStorage.getItem(THEME_KEY);
    if (stored === "light" || stored === "dark") return stored;
    return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
  }

  function iconSvg(name) {
    if (!window.lucide || !window.lucide.icons || !window.lucide.icons[name]) return "";
    return window.lucide.icons[name].toSvg({ width: 18, height: 18, strokeWidth: 2.2 });
  }

  function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem(THEME_KEY, theme);

    const themeToggle = document.getElementById("themeToggle");
    if (!themeToggle) return;

    const isDark = theme === "dark";
    themeToggle.setAttribute("aria-pressed", String(isDark));
    themeToggle.setAttribute("aria-label", isDark ? "Aktifkan light mode" : "Aktifkan dark mode");

    const label = themeToggle.querySelector(".theme-toggle__label");
    const icon = themeToggle.querySelector(".theme-toggle__icon");

    if (label) label.textContent = isDark ? "Light Mode" : "Dark Mode";
    if (icon) icon.innerHTML = iconSvg(isDark ? "sun" : "moon");
  }

  function initThemeToggle() {
    applyTheme(getStoredTheme());

    const themeToggle = document.getElementById("themeToggle");
    if (!themeToggle) return;

    themeToggle.addEventListener("click", function () {
      const currentTheme = document.documentElement.dataset.theme === "dark" ? "dark" : "light";
      applyTheme(currentTheme === "dark" ? "light" : "dark");
    });
  }

  function showToast(title, message) {
    const toastStack = document.getElementById("toastStack");
    if (!toastStack) return;

    const toast = document.createElement("div");
    toast.className = "toast-card";
    toast.role = "status";
    toast.innerHTML =
      '<div class="toast-card__icon" aria-hidden="true">' + iconSvg("check") + "</div>" +
      '<div class="toast-card__copy">' +
      '<div class="toast-card__title">' + title + "</div>" +
      '<p class="toast-card__text">' + message + "</p>" +
      "</div>";

    toastStack.appendChild(toast);

    window.setTimeout(function () {
      toast.remove();
    }, 3400);
  }

  function addRipple(event) {
    const target = event.currentTarget;
    const wave = document.createElement("span");
    const rect = target.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);

    wave.className = "js-ripple__wave";
    wave.style.width = size + "px";
    wave.style.height = size + "px";
    wave.style.left = event.clientX - rect.left - size / 2 + "px";
    wave.style.top = event.clientY - rect.top - size / 2 + "px";

    target.appendChild(wave);
    window.setTimeout(function () {
      wave.remove();
    }, 700);
  }

  function initRipple() {
    document.querySelectorAll(".js-ripple").forEach(function (element) {
      element.addEventListener("click", addRipple);
    });
  }

  function setButtonLoading(button, active) {
    const labelText = button.dataset.originalLabel || button.textContent.trim();

    if (!button.dataset.originalLabel) {
      button.dataset.originalLabel = labelText;
    }

    if (active) {
      button.disabled = true;
      button.setAttribute("aria-busy", "true");
      button.innerHTML = '<span class="button-spinner" aria-hidden="true"></span><span>' + (button.dataset.loadingLabel || "Memproses...") + "</span>";
      return;
    }

    button.disabled = false;
    button.removeAttribute("aria-busy");
    button.innerHTML = button.dataset.originalHtml || button.innerHTML;
  }

  function preserveButtonMarkup() {
    document.querySelectorAll("[data-loading-button]").forEach(function (button) {
      button.dataset.originalHtml = button.innerHTML;
      button.dataset.originalLabel = button.textContent.trim();
    });
  }

  function initLoadingButtons() {
    preserveButtonMarkup();

    document.querySelectorAll("[data-loading-button]").forEach(function (button) {
      button.addEventListener("click", function () {
        setButtonLoading(button, true);
        window.setTimeout(function () {
          button.innerHTML = button.dataset.originalHtml;
          button.disabled = false;
          button.removeAttribute("aria-busy");
          showToast("Aksi selesai", button.dataset.completeMessage || "Perintah berhasil dijalankan.");
          if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
          }
        }, 1200);
      });
    });
  }

  function getTabs() {
    return Array.from(document.querySelectorAll('[role="tab"]'));
  }

  function getPanels() {
    return Array.from(document.querySelectorAll('[role="tabpanel"]'));
  }

  function activateTab(targetTab) {
    const tabs = getTabs();
    const panels = getPanels();

    tabs.forEach(function (tab) {
      const selected = tab === targetTab;
      tab.setAttribute("aria-selected", String(selected));
      tab.tabIndex = selected ? 0 : -1;
    });

    panels.forEach(function (panel) {
      const selected = panel.id === targetTab.getAttribute("aria-controls");
      panel.hidden = !selected;
      if (selected) {
        panel.removeAttribute("aria-hidden");
      } else {
        panel.setAttribute("aria-hidden", "true");
      }
    });
  }

  function initTabs() {
    const tabs = getTabs();
    if (!tabs.length) return;

    tabs.forEach(function (tab, index) {
      tab.addEventListener("click", function () {
        activateTab(tab);
      });

      tab.addEventListener("keydown", function (event) {
        let nextIndex = index;
        if (event.key === "ArrowRight") nextIndex = (index + 1) % tabs.length;
        if (event.key === "ArrowLeft") nextIndex = (index - 1 + tabs.length) % tabs.length;
        if (event.key === "Home") nextIndex = 0;
        if (event.key === "End") nextIndex = tabs.length - 1;

        if (nextIndex !== index) {
          event.preventDefault();
          tabs[nextIndex].focus();
          activateTab(tabs[nextIndex]);
        }
      });
    });
  }

  function getTableRows() {
    return Array.from(document.querySelectorAll("#reviewTableBody tr[data-name]"));
  }

  function sortRows() {
    const tbody = document.getElementById("reviewTableBody");
    const sortSelect = document.getElementById("reviewSort");
    if (!tbody || !sortSelect) return;

    const rows = getTableRows();
    const mode = sortSelect.value;

    rows.sort(function (a, b) {
      if (mode === "highest") {
        return Number(b.dataset.amount) - Number(a.dataset.amount);
      }

      if (mode === "priority") {
        const priorityMap = { high: 2, normal: 1 };
        const diff = priorityMap[b.dataset.priority] - priorityMap[a.dataset.priority];
        if (diff !== 0) return diff;
      }

      return new Date(b.dataset.date) - new Date(a.dataset.date);
    });

    rows.forEach(function (row) {
      tbody.appendChild(row);
    });
  }

  function updatePendingMetric() {
    const pendingCount = getTableRows().filter(function (row) {
      return row.dataset.statusState === "pending";
    }).length;

    const pendingMetric = document.getElementById("pendingMetricValue");
    if (pendingMetric) pendingMetric.textContent = String(pendingCount);
  }

  function updateVisibleCount(visibleCount) {
    const counter = document.getElementById("visibleResultCount");
    if (!counter) return;
    counter.textContent = visibleCount + " data tampil";
  }

  function filterRows() {
    const searchInput = document.getElementById("reviewSearch");
    const priorityOnly = document.getElementById("priorityOnly");
    const empty = document.getElementById("filterEmptyState");
    if (!searchInput || !priorityOnly || !empty) return;

    const term = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;

    getTableRows().forEach(function (row) {
      const haystack = [row.dataset.name, row.dataset.type, row.dataset.statusState].join(" ");
      const matchesText = !term || haystack.includes(term);
      const matchesPriority = !priorityOnly.checked || row.dataset.priority === "high";
      const visible = matchesText && matchesPriority;

      row.hidden = !visible;
      if (visible) visibleCount += 1;
    });

    empty.hidden = visibleCount !== 0;
    updateVisibleCount(visibleCount);
  }

  function initFilterControls() {
    const searchInput = document.getElementById("reviewSearch");
    const priorityOnly = document.getElementById("priorityOnly");
    const sortSelect = document.getElementById("reviewSort");
    const resetButtons = document.querySelectorAll("[data-reset-filters]");

    if (searchInput) searchInput.addEventListener("input", filterRows);
    if (priorityOnly) priorityOnly.addEventListener("change", filterRows);

    if (sortSelect) {
      sortSelect.addEventListener("change", function () {
        sortRows();
        filterRows();
      });
    }

    resetButtons.forEach(function (resetButton) {
      resetButton.addEventListener("click", function () {
        if (searchInput) searchInput.value = "";
        if (priorityOnly) priorityOnly.checked = false;
        if (sortSelect) sortSelect.value = "recent";
        sortRows();
        filterRows();
      });
    });

    sortRows();
    filterRows();
    updatePendingMetric();
  }

  function setRowStatus(row, nextState) {
    const status = row.querySelector("[data-status]");
    if (!status) return;

    const map = {
      pending: { className: "status-chip status-chip--pending", text: "Pending" },
      approved: { className: "status-chip status-chip--success", text: "Approved" },
      rejected: { className: "status-chip status-chip--danger", text: "Rejected" },
      review: { className: "status-chip status-chip--pending", text: "Under Review" }
    };

    row.dataset.statusState = nextState;
    status.className = map[nextState].className;
    status.textContent = map[nextState].text;
  }

  function initRowActions() {
    document.querySelectorAll(".js-row-action").forEach(function (button) {
      button.addEventListener("click", function () {
        const row = button.closest("tr");
        if (!row) return;

        const name = button.dataset.name || "Affiliator";
        const action = button.dataset.action;

        if (action === "approve") {
          setRowStatus(row, "approved");
          row.querySelectorAll(".js-row-action").forEach(function (item) { item.disabled = true; });
          showToast("Approval berhasil", name + " dipindahkan ke status approved.");
        }

        if (action === "review") {
          setRowStatus(row, "review");
          showToast("Masuk review", name + " ditandai untuk pemeriksaan lanjutan.");
        }

        if (action === "reject") {
          setRowStatus(row, "rejected");
          row.querySelectorAll(".js-row-action").forEach(function (item) { item.disabled = true; });
          showToast("Pengajuan ditolak", name + " dipindahkan ke status rejected.");
        }

        updatePendingMetric();
        filterRows();
      });
    });
  }

  function switchToTabById(tabId) {
    const targetTab = document.getElementById(tabId);
    if (!targetTab) return;
    activateTab(targetTab);
    targetTab.focus();
  }

  function initStateActions() {
    const retryButton = document.getElementById("retryLoadButton");
    const loadSampleButton = document.getElementById("loadSampleButton");
    const priorityButton = document.getElementById("priorityQueueButton");

    if (retryButton) {
      retryButton.addEventListener("click", function () {
        switchToTabById("tab-loading");
        window.setTimeout(function () {
          switchToTabById("tab-live");
          showToast("Data dipulihkan", "Antrean approval berhasil dimuat ulang.");
        }, 1200);
      });
    }

    if (loadSampleButton) {
      loadSampleButton.addEventListener("click", function () {
        switchToTabById("tab-live");
        showToast("Mode aktif", "Data contoh ditampilkan kembali untuk review.");
      });
    }

    if (priorityButton) {
      priorityButton.addEventListener("click", function () {
        const priorityOnly = document.getElementById("priorityOnly");
        if (!priorityOnly) return;
        priorityOnly.checked = true;
        switchToTabById("tab-live");
        filterRows();
        showToast("Filter prioritas aktif", "Hanya pengajuan prioritas tinggi yang ditampilkan.");
      });
    }
  }

  function initActionToasts() {
    document.querySelectorAll("[data-toast-message]").forEach(function (button) {
      button.addEventListener("click", function () {
        showToast("Aksi diproses", button.dataset.toastMessage);
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (window.lucide && typeof window.lucide.createIcons === "function") {
      window.lucide.createIcons();
    }

    initThemeToggle();
    initRipple();
    initTabs();
    initLoadingButtons();
    initFilterControls();
    initRowActions();
    initStateActions();
    initActionToasts();
  });
})();
