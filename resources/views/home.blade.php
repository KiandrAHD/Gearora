<x-layout title="Gear Up. Explore More.">
    <section id="tentang" class="overflow-hidden bg-[#EDF1E9]">
        <div class="mx-auto grid max-w-7xl gap-12 px-6 py-20 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:px-10 lg:py-28">
            <div>
                <p class="mb-5 text-xs font-bold uppercase tracking-[0.2em] text-forest">Outdoor supply platform</p>
                <h1 class="max-w-3xl text-5xl font-semibold leading-[1.06] text-navy sm:text-6xl">
                    Siapkan perjalanan,
                    <span class="editorial-accent">jelajahi alam.</span>
                </h1>
                <p class="mt-7 max-w-xl text-lg leading-8 text-[#526267]">
                    GEARORA membantu kamu menemukan, menyewa, dan memahami perlengkapan outdoor sebelum berangkat.
                    Lebih siap. Lebih terarah.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#fondasi" class="inline-flex min-h-12 items-center justify-center rounded-md bg-forest px-6 font-bold text-white shadow-lg shadow-forest/15 transition hover:bg-navy">Jelajahi fondasi</a>
                    <a href="mailto:hello@gearora.test" class="inline-flex min-h-12 items-center justify-center rounded-md border border-forest px-6 font-bold text-forest transition hover:bg-white">Mulai kolaborasi</a>
                </div>
            </div>
            <div class="relative">
                <div class="rounded-[2rem] bg-forest p-8 text-white shadow-2xl shadow-navy/15 sm:p-10">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-sage">Gear up</p>
                    <p class="mt-5 font-heading text-4xl font-semibold leading-tight">Semua yang dibutuhkan untuk langkah berikutnya.</p>
                    <div class="mt-10 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl border border-white/15 bg-white/10 p-4"><strong class="block text-2xl text-sand">01</strong>Rental terencana</div>
                        <div class="rounded-xl border border-white/15 bg-white/10 p-4"><strong class="block text-2xl text-sand">02</strong>Info terpercaya</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fondasi" class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-24">
        <div class="max-w-2xl">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-forest">Project foundation</p>
            <h2 class="mt-3 text-3xl font-semibold text-navy sm:text-4xl">Fondasi siap untuk modul berikutnya.</h2>
            <p class="mt-5 text-[#526267]">Phase 0 dan Phase 1 mengunci requirement, validasi hosting, arsitektur, dan kerangka aplikasi. Fitur bisnis masuk bertahap setelah fondasi lolos quality gate.</p>
        </div>
        <div class="mt-12 grid gap-5 md:grid-cols-3">
            <x-foundation-card number="01" title="Requirement freeze" description="Scope, business rules, risiko, dan pertanyaan terbuka terdokumentasi." />
            <x-foundation-card number="02" title="Laravel foundation" description="Laravel 12, Blade, Tailwind, Vite, routing, logging, dan environment baseline." />
            <x-foundation-card number="03" title="Hosting-aware" description="Deployment tidak bergantung Composer, Node, SSH, worker, atau asumsi cPanel." />
        </div>
    </section>
</x-layout>
