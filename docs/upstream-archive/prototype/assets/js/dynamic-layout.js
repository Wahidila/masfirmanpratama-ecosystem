(() => {
  const page = window.location.pathname.split("/").pop() || "index.html";

  const pageConfig = {
    "index.html": {
      header: "landing",
      footer: "primary",
      ctaLabel: "Member Area",
      ctaHref: "login.html"
    },
    "book-detail.html": {
      header: "landing",
      footer: "primary",
      ctaLabel: "Member Area",
      ctaHref: "login.html"
    },
    "login.html": {
      header: "affiliate-auth",
      footer: "primary",
      ctaLabel: "Daftar",
      ctaHref: "register.html"
    },
    "register.html": {
      header: "affiliate-auth",
      footer: "primary",
      ctaLabel: "Login",
      ctaHref: "login.html"
    },
    "register-pending.html": {
      header: "affiliate-auth",
      footer: "primary",
      ctaLabel: "Login",
      ctaHref: "login.html"
    },
    "product-detail.html": {
      header: "store",
      footer: "primary"
    },
    "cart.html": {
      header: "store",
      footer: "primary"
    },
    "checkout.html": {
      header: "store",
      footer: "primary"
    },
    "checkout-success.html": {
      header: "store",
      footer: "primary"
    }
  };

  const config = pageConfig[page];
  if (!config) {
    return;
  }

  const isHomePage = page === "index.html";
  const programLink = isHomePage ? "#kategori" : "index.html#kategori";
  const bookLink = isHomePage ? "#buku" : "index.html#buku";
  const testimonialLink = isHomePage ? "#testimoni" : "index.html#testimoni";

  const headerEl = document.getElementById("app-header");
  const footerEl = document.getElementById("app-footer");

  if (headerEl) {
    headerEl.innerHTML = renderHeader(config, { programLink, bookLink, testimonialLink });
  }

  if (footerEl) {
    footerEl.innerHTML = renderFooter();
  }

  function renderHeader(pageSettings, links) {
    if (pageSettings.header === "landing") {
      return `
  <nav id="navbar" class="fixed w-full z-50 transition-all duration-300 glass border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">
        <a href="index.html" class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
          <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary-500/30">
            <i data-lucide="brain-circuit" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-2xl tracking-tight text-slate-900">Firman<span class="text-primary-600">Pratama</span></span>
        </a>

        <div class="hidden md:flex items-center space-x-8">
          <a href="${links.programLink}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Program</a>
          <a href="${links.bookLink}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Buku</a>
          <a href="${links.testimonialLink}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Testimoni</a>
          <div class="h-6 w-px bg-slate-200"></div>
          <a href="${pageSettings.ctaHref}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">${pageSettings.ctaLabel}</a>
          <a href="https://wa.me/6281230633464" class="ripple bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-full font-medium transition-colors shadow-lg shadow-primary-500/30">Konsultasi Sekarang</a>
        </div>

        <div class="md:hidden flex items-center">
          <button id="mobile-menu-btn" class="text-slate-600 hover:text-primary-600 focus:outline-none p-2 rounded-lg hover:bg-slate-100 transition-colors" type="button" aria-label="Buka menu navigasi">
            <i data-lucide="menu" class="w-6 h-6"></i>
          </button>
        </div>
      </div>
    </div>

    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-slate-100 absolute w-full shadow-xl">
      <div class="px-4 pt-2 pb-6 space-y-2">
        <a href="${links.programLink}" class="block px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Program AMC</a>
        <a href="${links.bookLink}" class="block px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Buku & Karya</a>
        <a href="${links.testimonialLink}" class="block px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Testimoni</a>
        <div class="border-t border-slate-100 my-2"></div>
        <a href="${pageSettings.ctaHref}" class="block px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">${pageSettings.ctaLabel}</a>
        <a href="https://wa.me/6281230633464" class="block w-full text-center bg-primary-600 text-white px-4 py-3 rounded-lg font-medium mt-4 shadow-md">Konsultasi Sekarang</a>
      </div>
    </div>
  </nav>`;
    }

    if (pageSettings.header === "affiliate-auth") {
      return `
  <nav class="glass sticky top-0 z-30 border-b border-slate-100">
    <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="affiliate.html" class="inline-flex items-center gap-2 text-lg font-extrabold text-slate-900">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-600 text-white shadow-lg shadow-primary-500/30">
          <i data-lucide="network" class="h-4 w-4"></i>
        </span>
        Mas Firman <span class="text-primary-600">Affiliate</span>
      </a>
      <a href="${pageSettings.ctaHref}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary-200 hover:text-primary-700">${pageSettings.ctaLabel}</a>
    </div>
  </nav>`;
    }

    return `
  <nav class="glass sticky top-0 z-30 border-b border-slate-100">
    <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="index.html" class="inline-flex items-center gap-2 text-lg font-extrabold text-slate-900">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-600 text-white shadow-lg shadow-primary-500/30">
          <i data-lucide="shopping-bag" class="h-4 w-4"></i>
        </span>
        Mas Firman <span class="text-primary-600">Store</span>
      </a>
      <div class="flex items-center gap-2">
        <a href="product-detail.html" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary-200 hover:text-primary-700">Produk</a>
        <a href="cart.html" class="inline-flex items-center gap-1 rounded-full bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700">
          <i data-lucide="shopping-cart" class="h-4 w-4"></i>
          Keranjang
        </a>
      </div>
    </div>
  </nav>`;
  }

  function renderFooter() {
    return `
  <footer class="bg-slate-950 text-slate-300 py-16 border-t border-slate-800 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
        <div>
          <div class="flex items-center gap-2 mb-6">
            <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center text-white">
              <i data-lucide="brain-circuit" class="w-5 h-5"></i>
            </div>
            <span class="font-bold text-xl text-white">Firman<span class="text-primary-500">Pratama</span></span>
          </div>
          <p class="text-sm mb-6 text-slate-400 leading-relaxed">Pakar Pikiran No.1 Indonesia. Penulis Buku, Konsultan Bisnis & Pencipta Metode AMC.</p>
          <div class="flex space-x-4">
            <a href="https://facebook.com/wahanasejati" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-primary-600 text-slate-400 hover:text-white transition-all"><i data-lucide="facebook" class="w-5 h-5"></i></a>
            <a href="https://youtube.com/@CahayaKehidupan" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-primary-600 text-slate-400 hover:text-white transition-all"><i data-lucide="youtube" class="w-5 h-5"></i></a>
            <a href="https://instagram.com/firmanpratama_pakarpikiran" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-primary-600 text-slate-400 hover:text-white transition-all"><i data-lucide="instagram" class="w-5 h-5"></i></a>
          </div>
        </div>

        <div>
          <h4 class="text-white font-bold mb-6">Layanan Pilihan</h4>
          <ul class="space-y-4 text-sm">
            <li><a href="index.html#kategori" class="hover:text-primary-400 transition-colors">Kelas Biasa AMC</a></li>
            <li><a href="index.html#kategori" class="hover:text-primary-400 transition-colors">Kelas Privat AMC</a></li>
            <li><a href="index.html#kategori" class="hover:text-primary-400 transition-colors">Kelas Platinum</a></li>
            <li><a href="index.html#buku" class="hover:text-primary-400 transition-colors">Pembelian Karya</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-bold mb-6">Komunitas Web</h4>
          <ul class="space-y-4 text-sm">
            <li><a href="index.html" class="hover:text-primary-400 transition-colors">Profil Pribadi</a></li>
            <li><a href="index.html#testimoni" class="hover:text-primary-400 transition-colors">Testimoni Alumni</a></li>
            <li><a href="index.html" class="hover:text-primary-400 transition-colors">Artikel Keajaiban</a></li>
            <li><a href="affiliate.html" class="hover:text-primary-400 transition-colors">Afiliasi Program</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-bold mb-6">Pusat Layanan</h4>
          <ul class="space-y-4 text-sm">
            <li class="flex items-start gap-3">
              <i data-lucide="map-pin" class="w-5 h-5 text-primary-500 flex-shrink-0 mt-0.5"></i>
              <span>Wahana Sejati, Jakarta - Surabaya HQ</span>
            </li>
            <li class="flex items-center gap-3">
              <i data-lucide="phone" class="w-5 h-5 text-primary-500 flex-shrink-0"></i>
              <span>081.2306.33.464</span>
            </li>
            <li class="flex items-center gap-3">
              <i data-lucide="mail" class="w-5 h-5 text-primary-500 flex-shrink-0"></i>
              <span>admin@masfirmanpratama.com</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
        <p>&copy; 2026 Firman Pratama - AMC. All rights reserved.</p>
        <div class="flex gap-6">
          <a href="#" class="hover:text-white transition-colors">Kebijakan Privasi</a>
          <a href="#" class="hover:text-white transition-colors">Syarat & Ketentuan</a>
        </div>
      </div>
    </div>
  </footer>`;
  }
})();
