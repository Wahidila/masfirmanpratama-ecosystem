(function () {
  const STORAGE_KEY = "affiliate-tour-state";

  const state = {
    active: false,
    flow: "",
    steps: [],
    index: 0,
    currentElement: null,
    overlayEl: null,
    bubbleEl: null,
    titleEl: null,
    textEl: null,
    stepEl: null,
    prevBtn: null,
    nextBtn: null,
    skipBtn: null,
  };

  function clearHighlight() {
    if (state.currentElement) {
      state.currentElement.classList.remove("tour-highlight");
      state.currentElement = null;
    }
  }

  function hideTour() {
    if (!state.overlayEl || !state.bubbleEl) return;
    clearHighlight();
    state.overlayEl.classList.add("d-none");
    state.bubbleEl.classList.add("d-none");
    state.active = false;
  }

  function saveState(nextIndex, nextFlow) {
    const payload = {
      flow: nextFlow || state.flow,
      index: nextIndex,
      ts: Date.now(),
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
  }

  function clearSavedState() {
    localStorage.removeItem(STORAGE_KEY);
  }

  function getSavedState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (error) {
      return null;
    }
  }

  function positionBubble(target) {
    const rect = target.getBoundingClientRect();
    const bubble = state.bubbleEl;

    bubble.style.top = "0px";
    bubble.style.left = "0px";

    const bubbleRect = bubble.getBoundingClientRect();
    const viewportW = window.innerWidth;
    const viewportH = window.innerHeight;
    const offset = 12;

    let top = rect.bottom + offset;
    let left = rect.left;

    if (top + bubbleRect.height > viewportH - 8) {
      top = rect.top - bubbleRect.height - offset;
    }

    if (left + bubbleRect.width > viewportW - 8) {
      left = viewportW - bubbleRect.width - 8;
    }

    if (left < 8) left = 8;
    if (top < 8) top = 8;

    bubble.style.top = top + "px";
    bubble.style.left = left + "px";
  }

  function renderStep() {
    const step = state.steps[state.index];
    if (!step) {
      hideTour();
      return;
    }

    clearHighlight();

    const target = document.querySelector(step.selector);
    if (!target) {
      state.titleEl.textContent = step.title || "Langkah";
      state.textEl.textContent = "Elemen target tidak ditemukan di halaman ini.";
      state.stepEl.textContent = `Langkah ${state.index + 1}/${state.steps.length}`;
      return;
    }

    state.currentElement = target;
    target.classList.add("tour-highlight");
    target.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });

    state.titleEl.textContent = step.title || "Langkah";
    state.textEl.textContent = step.content || "";
    state.stepEl.textContent = `Langkah ${state.index + 1}/${state.steps.length}`;

    state.prevBtn.disabled = state.index === 0;
    if (state.index === state.steps.length - 1) {
      state.nextBtn.textContent = step.nextPage ? "Lanjut Halaman" : "Selesai";
    } else {
      state.nextBtn.textContent = "Lanjut";
    }

    positionBubble(target);
  }

  function nextStep() {
    const step = state.steps[state.index];
    if (!step) return;

    const isLast = state.index >= state.steps.length - 1;

    if (isLast) {
      if (step.nextPage) {
        const nextFlow = step.nextFlow || state.flow;
        const nextIndex = typeof step.nextIndex === "number" ? step.nextIndex : 0;
        saveState(nextIndex, nextFlow);
        window.location.href = step.nextPage;
        return;
      }
      clearSavedState();
      hideTour();
      return;
    }

    state.index += 1;
    renderStep();
  }

  function prevStep() {
    if (state.index <= 0) return;
    state.index -= 1;
    renderStep();
  }

  function buildTourUI() {
    if (state.overlayEl && state.bubbleEl) return;

    const overlay = document.createElement("div");
    overlay.className = "tour-overlay d-none";

    const bubble = document.createElement("div");
    bubble.className = "tour-bubble d-none";
    bubble.innerHTML =
      '<div class="p-3 border-bottom">' +
      '<div class="tour-step mb-1" id="tourStepText"></div>' +
      '<div class="tour-title" id="tourTitleText"></div>' +
      '</div>' +
      '<div class="p-3">' +
      '<p class="tour-text mb-3" id="tourContentText"></p>' +
      '<div class="d-flex justify-content-between gap-2">' +
      '<button type="button" class="btn btn-sm btn-outline-secondary" id="tourPrevBtn">Kembali</button>' +
      '<div class="d-flex gap-2">' +
      '<button type="button" class="btn btn-sm btn-light" id="tourSkipBtn">Lewati</button>' +
      '<button type="button" class="btn btn-sm btn-critical" id="tourNextBtn">Lanjut</button>' +
      '</div>' +
      '</div>' +
      '</div>';

    document.body.appendChild(overlay);
    document.body.appendChild(bubble);

    state.overlayEl = overlay;
    state.bubbleEl = bubble;
    state.titleEl = bubble.querySelector("#tourTitleText");
    state.textEl = bubble.querySelector("#tourContentText");
    state.stepEl = bubble.querySelector("#tourStepText");
    state.prevBtn = bubble.querySelector("#tourPrevBtn");
    state.nextBtn = bubble.querySelector("#tourNextBtn");
    state.skipBtn = bubble.querySelector("#tourSkipBtn");

    state.prevBtn.addEventListener("click", prevStep);
    state.nextBtn.addEventListener("click", nextStep);
    state.skipBtn.addEventListener("click", function () {
      clearSavedState();
      hideTour();
    });
  }

  function resolveNavIcon(link) {
    const href = (link.getAttribute("href") || "").toLowerCase();
    const text = (link.textContent || "").toLowerCase();

    if (href.includes("dashboard") || text.includes("dashboard")) return "layout-dashboard";
    if (href.includes("links") || text.includes("link referral")) return "link-2";
    if (href.includes("commissions") || text.includes("komisi")) return "wallet";
    if (href.includes("withdrawals") || text.includes("penarikan")) return "hand-coins";
    if (href.includes("marketing") || text.includes("materi")) return "megaphone";
    if (href.includes("leaderboard") || text.includes("leaderboard")) return "trophy";
    if (href.includes("profile") || text.includes("profil")) return "user-round";
    if (text.includes("data affiliator")) return "users";
    if (text.includes("produk")) return "package";
    if (text.includes("pengaturan")) return "settings";
    if (href.includes("admin") || text.includes("ringkasan sistem")) return "shield-check";

    return null;
  }

  function enhanceNavigation() {
    const navLinks = document.querySelectorAll("a.nav-pill");
    navLinks.forEach(function (link) {
      if (link.querySelector("[data-lucide]")) return;

      const iconName = resolveNavIcon(link);
      if (!iconName) return;

      const icon = document.createElement("i");
      icon.setAttribute("data-lucide", iconName);
      icon.className = "nav-pill-icon";
      link.prepend(icon);
    });

    if (window.lucide && typeof window.lucide.createIcons === "function") {
      window.lucide.createIcons();
    }
  }

  function startTour(config, startIndex) {
    if (!config || !Array.isArray(config.steps) || config.steps.length === 0) return;

    state.flow = config.flow || "default";
    state.steps = config.steps;
    state.index = startIndex || 0;

    buildTourUI();
    state.overlayEl.classList.remove("d-none");
    state.bubbleEl.classList.remove("d-none");
    state.active = true;

    renderStep();
  }

  function setupTourButton(config) {
    const btn = document.getElementById("tourStartBtn");
    if (!btn) return;
    btn.addEventListener("click", function () {
      clearSavedState();
      startTour(config, 0);
    });
  }

  function initPageTour() {
    const config = window.affiliateTourConfig;
    enhanceNavigation();
    if (!config) return;

    setupTourButton(config);

    const saved = getSavedState();
    if (!saved || saved.flow !== (config.flow || "default")) return;

    const targetIndex = Number(saved.index);
    if (Number.isNaN(targetIndex)) {
      clearSavedState();
      return;
    }

    if (targetIndex >= config.steps.length) {
      clearSavedState();
      return;
    }

    clearSavedState();
    startTour(config, targetIndex);
  }

  window.addEventListener("resize", function () {
    if (!state.active || !state.currentElement) return;
    positionBubble(state.currentElement);
  });

  window.addEventListener("scroll", function () {
    if (!state.active || !state.currentElement) return;
    positionBubble(state.currentElement);
  }, true);

  document.addEventListener("DOMContentLoaded", initPageTour);
})();
