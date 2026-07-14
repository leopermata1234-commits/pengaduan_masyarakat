<?php

use App\Models\DokumentasiKegiatan;
use App\Models\Pengaduan;
use App\Models\ProgramBanjar;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        if (Auth::user()?->hasRole('Masyarakat')) {
            return [];
        }

        $pengaduan = $this->pengaduanQuery();

        return [
            'users' => User::count(),
            'pengaduan' => (clone $pengaduan)->count(),
            'pending' => (clone $pengaduan)->where('status', Pengaduan::STATUS_PENDING)->count(),
            'diproses' => (clone $pengaduan)->where('status', Pengaduan::STATUS_DIPROSES)->count(),
            'selesai' => (clone $pengaduan)->where('status', Pengaduan::STATUS_SELESAI)->count(),
            'program' => ProgramBanjar::count(),
            'dokumentasi' => DokumentasiKegiatan::count(),
        ];
    }

    #[Computed]
    public function recentPengaduan()
    {
        if (Auth::user()?->hasRole('Masyarakat')) {
            return collect();
        }

        return $this->pengaduanQuery()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentProgram()
    {
        return ProgramBanjar::query()
            ->with('user')
            ->whereIn('status', ProgramBanjar::PUBLIC_STATUSES)
            ->latest('tanggal_mulai')
            ->limit(3)
            ->get();
    }

    #[Computed]
    public function recentGaleri()
    {
        return DokumentasiKegiatan::query()
            ->where('status', DokumentasiKegiatan::STATUS_PUBLISHED)
            ->latest('tanggal')
            ->limit(3)
            ->get();
    }

    #[Computed]
    public function publicPengaduanStats(): array
    {
        $query = Pengaduan::query()->where('visibilitas', Pengaduan::VISIBILITAS_PUBLIK);

        return [
            'total' => (clone $query)->count(),
            'menunggu' => (clone $query)->where('status', Pengaduan::STATUS_MENUNGGU)->count(),
            'diproses' => (clone $query)->where('status', Pengaduan::STATUS_DIPROSES)->count(),
            'selesai' => (clone $query)->where('status', Pengaduan::STATUS_SELESAI)->count(),
        ];
    }

    #[Computed]
    public function recentPublicPengaduan()
    {
        return Pengaduan::query()
            ->where('visibilitas', Pengaduan::VISIBILITAS_PUBLIK)
            ->with('user')
            ->latest()
            ->limit(3)
            ->get();
    }

    private function publicStatusClasses(Pengaduan $pengaduan): string
    {
        return match ($pengaduan->status) {
            Pengaduan::STATUS_SELESAI => 'bg-emerald-100 text-emerald-800',
            Pengaduan::STATUS_DIPROSES => 'bg-sky-100 text-sky-800',
            Pengaduan::STATUS_DITOLAK => 'bg-red-100 text-red-800',
            default => 'bg-amber-100 text-amber-800',
        };
    }

    #[Computed]
    public function portalItems()
    {
        return DokumentasiKegiatan::query()
            ->where('status', DokumentasiKegiatan::STATUS_PUBLISHED)
            ->latest('tanggal')
            ->limit(20)
            ->get()
            ->flatMap(fn (DokumentasiKegiatan $item) => collect($item->fotos ?: ($item->foto ? [$item->foto] : []))
                ->map(fn (string $foto) => [
                    'type' => 'Galeri',
                    'title' => $item->judul,
                    'date' => $item->tanggal->format('d M Y'),
                    'image' => $this->storageUrl($foto),
                    'url' => route('dokumentasi.index'),
                ]))
            ->filter(fn (array $item) => filled($item['image']))
            ->take(20)
            ->values();
    }

    /**
     * @return array{items: array<int, array{label: string, count: int, x: float, y: float}>, total: int, max: int, points: string, area: string}
     */
    #[Computed]
    public function pengaduanTrend(): array
    {
        if (Auth::user()?->hasRole('Masyarakat')) {
            return [
                'items' => [],
                'total' => 0,
                'max' => 0,
                'points' => '',
                'area' => '',
            ];
        }

        $today = CarbonImmutable::today();
        $items = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($today) {
                $date = $today->subDays($daysAgo);

                return [
                    'label' => $date->format('d M'),
                    'count' => (clone $this->pengaduanQuery())
                        ->whereBetween('created_at', [$date->startOfDay(), $date->endOfDay()])
                        ->count(),
                ];
            })
            ->values();

        $max = max(1, (int) $items->max('count'));
        $width = 600;
        $height = 160;
        $itemCount = $items->count();
        $gap = $itemCount > 1 ? $width / ($itemCount - 1) : $width;

        $items = $items
            ->map(function (array $item, int $index) use ($gap, $height, $itemCount, $max) {
                $x = $itemCount > 1 ? $index * $gap : 0;
                $y = $height - (($item['count'] / $max) * ($height - 20)) - 10;

                return [
                    ...$item,
                    'x' => $x,
                    'y' => $y,
                ];
            });

        $points = $items
            ->map(fn (array $item) => sprintf('%.2f,%.2f', $item['x'], $item['y']))
            ->implode(' ');

        return [
            'items' => $items->all(),
            'total' => (int) $items->sum('count'),
            'max' => $max,
            'points' => $points,
            'area' => "0,{$height} {$points} {$width},{$height}",
        ];
    }

    private function pengaduanQuery(): Builder
    {
        return Pengaduan::query()->visibleTo(Auth::user());
    }

    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return '/storage/'.Str::of($path)->ltrim('/');
    }

    private function programStatusClasses(ProgramBanjar $program): string
    {
        return match ($program->status) {
            ProgramBanjar::STATUS_BERJALAN => 'bg-[#9bd329] text-[#20320b]',
            ProgramBanjar::STATUS_SELESAI => 'bg-[#d9a2a0] text-[#4b2322]',
            default => 'bg-[#e6c879] text-[#49370d]',
        };
    }
};
?>

<section @class([
    'flex w-full flex-col gap-8',
    'public-portal bg-[#fffdf8]' => ! auth()->check() || auth()->user()->hasRole('Masyarakat'),
    'admin-dashboard bg-transparent' => auth()->check() && ! auth()->user()->hasRole('Masyarakat'),
])>
    @if (! auth()->check() || auth()->user()->hasRole('Masyarakat'))
        <div class="portal-header overflow-hidden text-white shadow-lg">
            <div class="flex w-full flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <a href="{{ route('beranda') }}" wire:navigate class="flex min-w-0 items-center gap-3">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-[#34A99D] ring-2 ring-white/70">
                        <x-app-logo-icon class="h-8 w-8" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-lg font-semibold">{{ __('Banjar Puluk-Puluk') }}</span>
                        <span class="block truncate text-sm text-white/85">{{ __('Layanan Masyarakat') }}</span>
                    </span>
                </a>

                <nav class="flex gap-2 overflow-x-auto text-sm font-medium">
                    <a href="{{ route('beranda') }}" wire:navigate class="shrink-0 border-b-2 border-white px-2 py-2">{{ __('Beranda') }}</a>
                    <a href="{{ route('profil-banjar.index') }}" wire:navigate class="shrink-0 border-b-2 border-transparent px-2 py-2 text-white/90 hover:border-white/80">{{ __('Profil Banjar') }}</a>
                    <a href="{{ route('program.index') }}" wire:navigate class="shrink-0 border-b-2 border-transparent px-2 py-2 text-white/90 hover:border-white/80">{{ __('Program') }}</a>
                    <a href="{{ route('dokumentasi.index') }}" wire:navigate class="shrink-0 border-b-2 border-transparent px-2 py-2 text-white/90 hover:border-white/80">{{ __('Galeri') }}</a>
                    <a href="{{ route('pengaduan.index') }}" class="shrink-0 rounded-md bg-teal-600 px-4 py-2 text-white shadow-sm transition hover:bg-teal-700">{{ __('Pengaduan') }}</a>
                    @auth
                    <flux:dropdown position="bottom" align="end">
                        <button type="button" class="flex shrink-0 items-center gap-3 rounded-xl px-2 py-1.5 text-white transition hover:bg-white/10">
                            <flux:avatar :initials="auth()->user()->initials()" size="sm" />
                            <span class="hidden text-base font-medium sm:inline">{{ auth()->user()->name }}</span>
                        </button>

                        <flux:menu>
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>

                            <flux:menu.separator />

                            <flux:menu.item :href="route('profile.edit')" icon="identification" wire:navigate>
                                {{ __('Profil') }}
                            </flux:menu.item>

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                    @else
                        <a href="{{ route('login') }}" class="shrink-0 rounded-md border border-white/40 px-4 py-2 text-white transition hover:bg-white/10">
                            {{ __('Masuk') }}
                        </a>
                    @endauth
                </nav>
            </div>

            @php($heroImage = $this->portalItems->first()['image'] ?? null)

            <div
                class="relative min-h-[calc(100vh-5rem)] overflow-hidden bg-[#17645D]"
                @if ($heroImage)
                    style="background-image: linear-gradient(rgba(14, 31, 28, .58), rgba(14, 31, 28, .74)), url('{{ $heroImage }}'); background-size: cover; background-position: center;"
                @endif
            >
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(45,212,191,.35),transparent_32%),linear-gradient(145deg,rgba(255,255,255,.14)_0_18%,transparent_18%_34%,rgba(255,255,255,.10)_34%_52%,transparent_52%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-[#17645D]/80 to-transparent"></div>

                <div class="relative flex min-h-[360px] flex-col items-center justify-center px-6 py-16 text-center">
                    <h1 class="max-w-4xl text-4xl font-bold leading-tight tracking-normal text-white sm:text-5xl lg:text-6xl">
                        {{ __('Selamat Datang') }}<br>
                        {{ __('Website Resmi Banjar Puluk-Puluk') }}
                    </h1>
                    <p class="mt-6 max-w-3xl text-base font-semibold leading-7 text-white/90 sm:text-xl">
                        {{ __('Sumber informasi terbaru, galeri kegiatan, dan layanan pengaduan masyarakat.') }}
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ auth()->check() ? route('pengaduan.create') : route('login') }}" class="inline-flex min-w-48 items-center justify-center gap-2 rounded-xl bg-[#d4a16d] px-6 py-3.5 text-sm font-extrabold text-white shadow-[0_5px_0_#765034] transition hover:-translate-y-0.5 hover:bg-[#dfad77]">
                            <span aria-hidden="true" class="text-lg">+</span>{{ __('Buat Pengaduan') }}
                        </a>
                        @auth
                            <a href="{{ route('pengaduan.index') }}" class="inline-flex min-w-48 items-center justify-center rounded-xl border border-white/40 bg-white/10 px-6 py-3.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">
                                {{ __('Lacak Pengaduan Saya') }}
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="relative pb-8">
                    @if ($this->portalItems->isNotEmpty())
                        <div
                            x-data="{
                                timer: null,
                                resumeTimer: null,
                                dragging: false,
                                dragStartX: 0,
                                dragStartScroll: 0,
                                start() {
                                    this.stop();
                                    this.timer = setInterval(() => this.advance(1), 2000);
                                },
                                stop() {
                                    if (this.timer) clearInterval(this.timer);
                                    if (this.resumeTimer) clearTimeout(this.resumeTimer);
                                    this.timer = null;
                                    this.resumeTimer = null;
                                },
                                advance(direction) {
                                    const track = this.$refs.track;
                                    const card = track.querySelector('[data-carousel-card]');
                                    const amount = card ? card.getBoundingClientRect().width + 16 : 320;
                                    const maxScroll = track.scrollWidth - track.clientWidth;

                                    if (maxScroll <= 1) return;

                                    if (direction > 0 && track.scrollLeft >= maxScroll - 8) {
                                        track.scrollTo({ left: 0, behavior: 'smooth' });
                                        return;
                                    }

                                    if (direction < 0 && track.scrollLeft <= 8) {
                                        track.scrollTo({ left: maxScroll, behavior: 'smooth' });
                                        return;
                                    }

                                    track.scrollTo({
                                        left: direction > 0
                                            ? Math.min(track.scrollLeft + amount, maxScroll)
                                            : Math.max(track.scrollLeft - amount, 0),
                                        behavior: 'smooth',
                                    });
                                },
                                move(direction) {
                                    this.stop();
                                    this.advance(direction);

                                    this.resumeTimer = setTimeout(() => this.start(), 650);
                                },
                                next() {
                                    this.move(1);
                                },
                                prev() {
                                    this.move(-1);
                                },
                                beginDrag(event) {
                                    this.stop();
                                    this.dragging = true;
                                    this.dragStartX = event.clientX;
                                    this.dragStartScroll = this.$refs.track.scrollLeft;
                                    this.$refs.track.setPointerCapture(event.pointerId);
                                },
                                drag(event) {
                                    if (! this.dragging) return;

                                    event.preventDefault();
                                    this.$refs.track.scrollLeft = this.dragStartScroll - (event.clientX - this.dragStartX);
                                },
                                endDrag(event) {
                                    if (! this.dragging) return;

                                    this.dragging = false;
                                    if (this.$refs.track.hasPointerCapture(event.pointerId)) {
                                        this.$refs.track.releasePointerCapture(event.pointerId);
                                    }
                                    this.start();
                                },
                            }"
                            x-init="$nextTick(() => start())"
                            class="relative mx-auto w-full max-w-[100rem] px-6 lg:px-12"
                        >
                            <button type="button" x-on:click="prev()" class="absolute left-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-2xl font-semibold text-[#17645D] shadow-md transition hover:bg-white">
                                &lsaquo;
                            </button>

                            <div
                                x-ref="track"
                                x-on:pointerdown="beginDrag($event)"
                                x-on:pointermove="drag($event)"
                                x-on:pointerup="endDrag($event)"
                                x-on:pointercancel="endDrag($event)"
                                x-on:dragstart.prevent
                                :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                                class="portal-carousel flex touch-pan-y select-none overflow-x-auto pb-3"
                            >
                                @foreach ($this->portalItems as $item)
                                    <div data-carousel-card class="mr-4 w-64 shrink-0 overflow-hidden rounded-lg border-2 border-white/85 bg-white/10 shadow-lg backdrop-blur last:mr-0 sm:w-72 lg:w-80 xl:w-[calc((100%-4rem)/5)]">
                                        <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" draggable="false" class="pointer-events-none aspect-[4/3] w-full object-cover">
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" x-on:click="next()" class="absolute right-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-2xl font-semibold text-[#17645D] shadow-md transition hover:bg-white">
                                &rsaquo;
                            </button>
                        </div>
                    @else
                        <div class="mx-auto max-w-xl rounded-lg border border-white/30 bg-white/15 p-5 text-center text-sm text-white/90 backdrop-blur">
                            {{ __('Belum ada galeri yang dapat ditampilkan.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @auth
        <div class="relative mx-auto w-full max-w-6xl space-y-8 px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm font-extrabold uppercase tracking-[.18em] text-[#13746e]">{{ __('Layanan Utama') }}</p>
                <h2 class="mt-2 font-serif text-3xl font-bold tracking-tight text-[#2f241b] sm:text-4xl">{{ __('Pengaduan Masyarakat') }}</h2>
                <p class="mx-auto mt-3 max-w-3xl text-sm leading-6 text-[#625b53] sm:text-base">{{ __('Sampaikan keluhan, aspirasi, atau usulan Anda. Setiap laporan tercatat dan dapat dipantau hingga selesai ditangani.') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-[#dfd4c6] bg-white p-5 shadow-[0_8px_22px_rgba(62,44,29,.09)]">
                    <p class="text-sm font-semibold text-[#756b62]">{{ __('Total Pengaduan Publik') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-[#2f241b]">{{ number_format($this->publicPengaduanStats['total']) }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-[0_8px_22px_rgba(62,44,29,.07)]">
                    <p class="text-sm font-semibold text-amber-800">{{ __('Menunggu') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ number_format($this->publicPengaduanStats['menunggu']) }}</p>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-[0_8px_22px_rgba(62,44,29,.07)]">
                    <p class="text-sm font-semibold text-sky-800">{{ __('Diproses') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-sky-700">{{ number_format($this->publicPengaduanStats['diproses']) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-[0_8px_22px_rgba(62,44,29,.07)]">
                    <p class="text-sm font-semibold text-emerald-800">{{ __('Selesai') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ number_format($this->publicPengaduanStats['selesai']) }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.35fr_.65fr]">
                <article class="rounded-2xl border border-[#dfd4c6] bg-white p-6 shadow-[0_8px_22px_rgba(62,44,29,.09)]">
                    <h3 class="text-xl font-bold text-[#2f241b]">{{ __('Bagaimana Pengaduan Ditangani?') }}</h3>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            ['01', __('Kirim Laporan'), __('Ceritakan masalah dan lampirkan foto pendukung.')],
                            ['02', __('Diverifikasi'), __('Petugas memeriksa kelengkapan dan validitas laporan.')],
                            ['03', __('Diproses'), __('Laporan diteruskan untuk ditangani oleh petugas.')],
                            ['04', __('Selesai'), __('Hasil penanganan dan bukti penyelesaian diberikan.')],
                        ] as [$nomor, $judul, $deskripsi])
                            <div class="relative rounded-xl border border-[#e8ddd0] bg-[#fffaf2] p-4">
                                <span class="text-xs font-extrabold text-[#13746e]">{{ $nomor }}</span>
                                <p class="mt-2 font-bold text-[#3b3027]">{{ $judul }}</p>
                                <p class="mt-2 text-xs leading-5 text-[#756b62]">{{ $deskripsi }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-2xl border border-[#13746e]/20 bg-[#eaf7f4] p-6 shadow-[0_8px_22px_rgba(27,93,84,.09)]">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#13746e] text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-[#214b46]">{{ __('Privasi Anda Terlindungi') }}</h3>
                    <p class="mt-3 text-sm leading-6 text-[#4e6d68]">{{ __('Pilih sifat Privat jika laporan hanya boleh dilihat oleh Anda dan petugas berwenang.') }}</p>
                    <a href="{{ auth()->check() ? route('pengaduan.create') : route('login') }}" class="mt-5 inline-flex font-bold text-[#13746e] hover:text-[#0f625d]">{{ __('Mulai membuat laporan') }} <span class="ml-1">&rarr;</span></a>
                </article>
            </div>

            @if ($this->recentPublicPengaduan->isNotEmpty())
                <div>
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <h3 class="font-serif text-2xl font-bold text-[#2f241b]">{{ __('Pengaduan Publik Terbaru') }}</h3>
                            <p class="mt-1 text-sm text-[#756b62]">{{ __('Perkembangan laporan terbaru dari masyarakat.') }}</p>
                        </div>
                        @auth
                            <a href="{{ route('pengaduan.index') }}" class="shrink-0 text-sm font-bold text-[#13746e]">{{ __('Lihat Semua') }} &rarr;</a>
                        @endauth
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        @foreach ($this->recentPublicPengaduan as $pengaduan)
                            <article class="rounded-2xl border border-[#dfd4c6] bg-white p-5 shadow-[0_8px_22px_rgba(62,44,29,.08)]">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $this->publicStatusClasses($pengaduan) }}">{{ $pengaduan->status }}</span>
                                    <span class="text-xs text-[#8a8179]">{{ $pengaduan->created_at->format('d M Y') }}</span>
                                </div>
                                <h4 class="mt-4 line-clamp-2 font-bold leading-6 text-[#3b3027]">{{ $pengaduan->judul }}</h4>
                                <p class="mt-2 text-xs text-[#756b62]">{{ $pengaduan->user->name ?? __('Masyarakat') }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endauth

        @guest
            <div class="mx-auto w-full max-w-6xl px-6 lg:px-8">
                <article class="rounded-2xl border border-[#dfd4c6] bg-white p-6 shadow-[0_8px_22px_rgba(62,44,29,.09)]">
                    <div class="text-center">
                        <p class="text-sm font-extrabold uppercase tracking-[.18em] text-[#13746e]">{{ __('Proses Pengaduan') }}</p>
                        <h2 class="mt-2 font-serif text-3xl font-bold tracking-tight text-[#2f241b]">{{ __('Bagaimana Pengaduan Ditangani?') }}</h2>
                        <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-[#625b53]">{{ __('Setiap laporan melalui proses yang jelas dan dapat dipertanggungjawabkan.') }}</p>
                    </div>
                    <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            ['01', __('Kirim Laporan'), __('Ceritakan masalah dan lampirkan foto pendukung.')],
                            ['02', __('Diverifikasi'), __('Petugas memeriksa kelengkapan dan validitas laporan.')],
                            ['03', __('Diproses'), __('Laporan diteruskan untuk ditangani oleh petugas.')],
                            ['04', __('Selesai'), __('Hasil penanganan dan bukti penyelesaian diberikan.')],
                        ] as [$nomor, $judul, $deskripsi])
                            <div class="rounded-xl border border-[#e8ddd0] bg-[#fffaf2] p-4">
                                <span class="text-xs font-extrabold text-[#13746e]">{{ $nomor }}</span>
                                <p class="mt-2 font-bold text-[#3b3027]">{{ $judul }}</p>
                                <p class="mt-2 text-xs leading-5 text-[#756b62]">{{ $deskripsi }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        @endguest

        <div class="mx-auto w-full max-w-6xl space-y-5 px-6 lg:px-8">
            <div>
                <h2 class="font-serif text-4xl font-bold tracking-tight text-[#2f241b]">{{ __('Program & Kegiatan') }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Menyajikan informasi terbaru tentang kegiatan, pengumuman, dan kabar dari Banjar Puluk-Puluk.') }}
                </p>
            </div>

            @if ($this->recentProgram->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->recentProgram as $program)
                        <article class="overflow-hidden rounded-2xl bg-white shadow-[0_8px_22px_rgba(62,44,29,.12)] ring-1 ring-[#dfd4c6] transition hover:-translate-y-1 hover:shadow-[0_16px_30px_rgba(62,44,29,.18)]">
                            <a href="{{ route('program.index') }}" wire:navigate class="block">
                                @if ($program->gambar)
                                    <img src="{{ $this->storageUrl($program->gambar) }}" alt="{{ $program->judul }}" class="aspect-[16/9] w-full object-cover">
                                @else
                                    <div class="flex aspect-[16/9] w-full items-center justify-center bg-[#EAF8F6] text-sm font-medium text-[#34A99D]">
                                        {{ __('Program') }}
                                    </div>
                                @endif
                            </a>

                            <div class="relative flex min-h-56 flex-col p-6">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('program.index') }}" wire:navigate class="block min-w-0 flex-1">
                                        <h3 class="line-clamp-2 text-lg font-semibold leading-7 text-zinc-700 dark:text-zinc-100">{{ $program->judul }}</h3>
                                    </a>
                                    <span class="shrink-0 rounded-lg px-2.5 py-1.5 text-[11px] font-extrabold uppercase {{ $this->programStatusClasses($program) }}">
                                        {{ $program->status }}
                                    </span>
                                </div>
                                <p class="mt-3 line-clamp-3 text-sm leading-6 text-zinc-900 dark:text-zinc-300">{{ $program->deskripsi }}</p>

                                <div class="mt-auto border-t border-[#eee5d9] pt-4">
                                    <div class="space-y-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                                        <p class="font-medium uppercase">{{ $program->user->name ?? __('Admin') }}</p>
                                        <p class="flex items-center gap-1.5 font-semibold text-[#655a50]">
                                            <span class="text-[#13746e]">{{ __('Tanggal') }}:</span>
                                            {{ ($program->tanggal_mulai ?? $program->tanggal)->format('d M Y') }}
                                            @if (($program->tanggal_selesai ?? null) && ! $program->tanggal_selesai->isSameDay($program->tanggal_mulai ?? $program->tanggal))
                                                <span aria-hidden="true">&ndash;</span> {{ $program->tanggal_selesai->format('d M Y') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="flex justify-center">
                    <a href="{{ route('program.index') }}" wire:navigate class="rounded-md bg-[#34A99D] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#2C9086]">
                        {{ __('Selengkapnya') }}
                    </a>
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 bg-white p-6 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    {{ __('Belum ada program yang dapat ditampilkan.') }}
                </div>
            @endif
        </div>

        <div class="mx-auto w-full max-w-6xl space-y-5 px-6 lg:px-8">
            <div>
                <h2 class="font-serif text-4xl font-bold tracking-tight text-[#2f241b]">{{ __('Galeri Kegiatan') }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Kumpulan foto kegiatan dan dokumentasi terbaru Banjar Puluk-Puluk.') }}
                </p>
            </div>

            @if ($this->recentGaleri->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->recentGaleri as $galeri)
                        <article class="overflow-hidden rounded-2xl bg-white shadow-[0_8px_22px_rgba(62,44,29,.12)] ring-1 ring-[#dfd4c6] transition hover:-translate-y-1 hover:shadow-[0_16px_30px_rgba(62,44,29,.18)]">
                            <a href="{{ route('dokumentasi.index') }}" wire:navigate class="block">
                                @if (($galeri->fotos[0] ?? $galeri->foto) !== null)
                                    <img src="{{ $this->storageUrl($galeri->fotos[0] ?? $galeri->foto) }}" alt="{{ $galeri->judul }}" class="aspect-[16/9] w-full object-cover">
                                @else
                                    <div class="flex aspect-[16/9] w-full items-center justify-center bg-[#EAF8F6] text-sm font-medium text-[#34A99D]">
                                        {{ __('Galeri') }}
                                    </div>
                                @endif
                            </a>

                            <div class="relative flex min-h-48 flex-col p-6 pb-16">
                                <a href="{{ route('dokumentasi.index') }}" wire:navigate class="block">
                                    <h3 class="line-clamp-2 text-lg font-semibold leading-7 text-zinc-700 dark:text-zinc-100">{{ $galeri->judul }}</h3>
                                </a>
                                <p class="mt-3 line-clamp-3 text-sm leading-6 text-zinc-900 dark:text-zinc-300">{{ $galeri->deskripsi }}</p>

                                <div class="mt-auto flex items-end justify-between pt-8">
                                    <div class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                                        <p>{{ $galeri->tanggal->format('d M Y') }}</p>
                                    </div>

                                    <div class="absolute bottom-0 right-0 rounded-tl-lg bg-[#34A99D] px-4 py-2 text-center text-sm font-bold leading-4 text-white shadow-sm">
                                        <span class="block">{{ $galeri->tanggal->format('d M') }}</span>
                                        <span class="block">{{ $galeri->tanggal->format('Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="flex justify-center">
                    <a href="{{ route('dokumentasi.index') }}" wire:navigate class="rounded-md bg-[#34A99D] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#2C9086]">
                        {{ __('Selengkapnya') }}
                    </a>
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 bg-white p-6 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    {{ __('Belum ada galeri yang dapat ditampilkan.') }}
                </div>
            @endif
        </div>

        <x-portal-footer />
    @else
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Layanan') }}</span>
                <span>/</span>
                <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Dashboard') }}</span>
            </div>

            <div class="flex flex-col gap-1">
                <h1 class="font-serif text-3xl font-bold tracking-tight text-[#2f241b] sm:text-4xl">{{ __('Dashboard') }}</h1>
                <p class="mt-2 text-sm leading-6 text-[#625b53] sm:text-base">{{ __('Ringkasan layanan masyarakat Banjar Puluk-Puluk.') }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="admin-stat-card rounded-2xl border border-[#dfd4c6] bg-white p-5">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total User') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['users']) }}</p>
            </div>
            <div class="admin-stat-card rounded-2xl border border-[#dfd4c6] bg-white p-5">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Pengaduan') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['pengaduan']) }}</p>
            </div>
            <div class="admin-stat-card rounded-2xl border border-[#dfd4c6] bg-white p-5">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Menunggu') }}</p>
                <p class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['pending']) }}</p>
            </div>
            <div class="admin-stat-card rounded-2xl border border-[#dfd4c6] bg-white p-5">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Diproses') }}</p>
                <p class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-400">{{ number_format($this->stats['diproses']) }}</p>
            </div>
            <div class="admin-stat-card rounded-2xl border border-[#dfd4c6] bg-white p-5">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Selesai') }}</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($this->stats['selesai']) }}</p>
            </div>
            <div class="admin-stat-card rounded-2xl border border-[#dfd4c6] bg-white p-5">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Program') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['program']) }}</p>
            </div>
            <div class="admin-stat-card rounded-2xl border border-[#dfd4c6] bg-white p-5">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Galeri') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['dokumentasi']) }}</p>
            </div>
        </div>

        <div class="admin-dashboard-panel rounded-2xl border border-[#dfd4c6] bg-white p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Statistik Pengaduan') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tren pengaduan masuk selama 7 hari terakhir.') }}</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total 7 Hari') }}</p>
                    <p class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->pengaduanTrend['total']) }}</p>
                </div>
            </div>

            <div class="mt-5">
                <svg viewBox="0 0 600 160" role="img" aria-label="{{ __('Grafik pengaduan 7 hari terakhir') }}" class="h-48 w-full overflow-visible">
                    <line x1="0" y1="150" x2="600" y2="150" class="stroke-zinc-200 dark:stroke-zinc-700" stroke-width="1" />
                    <line x1="0" y1="90" x2="600" y2="90" class="stroke-zinc-100 dark:stroke-zinc-800" stroke-width="1" />
                    <line x1="0" y1="30" x2="600" y2="30" class="stroke-zinc-100 dark:stroke-zinc-800" stroke-width="1" />

                    @if ($this->pengaduanTrend['points'] !== '')
                        <polygon points="{{ $this->pengaduanTrend['area'] }}" class="fill-sky-100/70 dark:fill-sky-950/40" />
                        <polyline points="{{ $this->pengaduanTrend['points'] }}" fill="none" class="stroke-sky-600 dark:stroke-sky-400" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />

                        @foreach ($this->pengaduanTrend['items'] as $index => $item)
                            <circle cx="{{ $item['x'] }}" cy="{{ $item['y'] }}" r="4" class="fill-white stroke-sky-600 dark:fill-zinc-900 dark:stroke-sky-400" stroke-width="3" />
                        @endforeach
                    @endif
                </svg>

                <div class="mt-3 grid grid-cols-7 gap-2 text-center text-xs text-zinc-500 dark:text-zinc-400">
                    @foreach ($this->pengaduanTrend['items'] as $item)
                        <div class="min-w-0">
                            <p class="truncate">{{ $item['label'] }}</p>
                            <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">{{ number_format($item['count']) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="admin-dashboard-panel overflow-hidden rounded-2xl border border-[#dfd4c6] bg-white">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Pengaduan Terbaru') }}</h2>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->recentPengaduan as $pengaduan)
                    <div class="flex flex-col gap-2 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="font-medium text-zinc-950 dark:text-white">{{ $pengaduan->judul }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $pengaduan->user->name }} &middot; {{ $pengaduan->created_at->format('d M Y') }} &middot; {{ $pengaduan->visibilitas }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-bold {{ $this->publicStatusClasses($pengaduan) }}">
                            {{ $pengaduan->status }}
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengaduan.') }}</p>
                @endforelse
            </div>
        </div>
    @endif
</section>
