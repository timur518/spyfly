<x-filament-widgets::widget>
    <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-slate-950/95 text-slate-100 shadow-[0_30px_80px_rgba(2,6,23,0.55)]">
        <div class="bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.12),transparent_35%),linear-gradient(180deg,rgba(15,23,42,0.96),rgba(2,6,23,0.98))] p-6 sm:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.45em] text-amber-300/80">Панель состояния</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Система в полёте</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                        Четыре опорные цифры помогают быстро понять, как живут пользователи, поиски и сигналы.
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 self-start rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-2 text-xs font-medium uppercase tracking-[0.3em] text-amber-100">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_16px_rgba(74,222,128,0.8)]"></span>
                    live system
                </div>
            </div>

            <div class="relative mt-6">
                <div class="h-px bg-gradient-to-r from-transparent via-amber-300/70 to-transparent"></div>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-between px-3">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-300 shadow-[0_0_16px_rgba(245,158,11,0.9)]"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-sky-300 shadow-[0_0_16px_rgba(125,211,252,0.9)]"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-300 shadow-[0_0_16px_rgba(110,231,183,0.9)]"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-300 shadow-[0_0_16px_rgba(251,113,133,0.9)]"></span>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($stats as $stat)
                    <article class="relative overflow-hidden rounded-[1.5rem] border border-white/8 bg-white/[0.04] p-5 backdrop-blur-sm">
                        <div class="absolute inset-x-0 top-0 h-1" style="background-image: {{ $stat['accent'] }}"></div>

                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">
                                {{ $stat['label'] }}
                            </p>

                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-slate-900/80 text-sm text-white shadow-inner shadow-black/20">
                                {{ $stat['glyph'] }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-end gap-2">
                            <div class="font-mono text-4xl font-semibold tracking-tight text-white">
                                {{ $stat['value'] }}
                            </div>
                            <div class="pb-1 text-[0.68rem] font-medium uppercase tracking-[0.3em] text-slate-500">
                                {{ $stat['unit'] }}
                            </div>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            {{ $stat['hint'] }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
