<footer class="bg-slate-950 text-slate-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <x-brand-mark size="md" :dark="true" />
                <p class="mt-3 text-sm text-slate-400 max-w-sm">Program affiliate resmi MasFirmanPratama.com — Mind Power &amp; Life Mastery.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm text-slate-300 hover:text-white transition">Masuk</a>
                <span class="text-slate-700">·</span>
                <a href="{{ route('register') }}" class="text-sm text-slate-300 hover:text-white transition">Daftar</a>
            </div>
        </div>
        <div class="mt-8 pt-6 border-t border-slate-800 text-xs text-slate-500">
            &copy; {{ date('Y') }} MasFirmanPratama.com — Program Affiliate Mind Power &amp; Life Mastery.
        </div>
    </div>
</footer>
