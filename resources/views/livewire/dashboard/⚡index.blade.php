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
            ->whereIn('status', [ProgramBanjar::STATUS_BERJALAN, ProgramBanjar::STATUS_SELESAI])
            ->latest('tanggal_mulai')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function recentGaleri()
    {
        return DokumentasiKegiatan::query()
            ->where('status', DokumentasiKegiatan::STATUS_PUBLISHED)
            ->latest('tanggal')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function portalItems()
    {
        return $this->recentGaleri
            ->map(fn (DokumentasiKegiatan $item) => [
                'type' => 'Galeri',
                'title' => $item->judul,
                'date' => $item->tanggal->format('d M Y'),
                'image' => $this->storageUrl($item->fotos[0] ?? $item->foto),
                'url' => route('dokumentasi.index'),
            ])
            ->filter(fn (array $item) => filled($item['image']))
            ->take(5)
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
};
?>

<section class="flex w-full flex-col gap-6">
    @if (! auth()->check() || auth()->user()->hasRole('Masyarakat'))
        <div class="overflow-hidden bg-[#34A99D] text-white shadow-sm">
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
                        <button type="button" class="flex shrink-0 items-center gap-2 rounded-md border border-white/30 px-3 py-2 text-white/90 transition hover:bg-white/10">
                            <flux:avatar :initials="auth()->user()->initials()" size="xs" />
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
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

                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Profil') }}
                            </flux:menu.item>

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                    {{ __('Keluar') }}
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
                class="relative min-h-[520px] overflow-hidden bg-[#17645D]"
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
                            class="relative mx-auto w-full max-w-6xl px-6 lg:px-8"
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
                                class="flex touch-pan-y select-none overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                            >
                                @foreach ($this->portalItems as $item)
                                    <div data-carousel-card class="mr-4 w-64 shrink-0 overflow-hidden rounded-lg border-2 border-white/85 bg-white/10 shadow-lg backdrop-blur last:mr-0 sm:w-72 lg:w-80">
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

        <div class="mx-auto w-full max-w-6xl space-y-5 px-6 lg:px-8">
            <div>
                <h2 class="text-4xl font-bold tracking-normal text-[#34A99D]">{{ __('Program') }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Menyajikan informasi terbaru tentang kegiatan, pengumuman, dan kabar dari Banjar Puluk-Puluk.') }}
                </p>
            </div>

            @if ($this->recentProgram->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->recentProgram as $program)
                        <article class="overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-zinc-900 dark:ring-zinc-700">
                            <a href="{{ route('program.index') }}" wire:navigate class="block">
                                @if ($program->gambar)
                                    <img src="{{ $this->storageUrl($program->gambar) }}" alt="{{ $program->judul }}" class="aspect-[16/9] w-full object-cover">
                                @else
                                    <div class="flex aspect-[16/9] w-full items-center justify-center bg-[#EAF8F6] text-sm font-medium text-[#34A99D]">
                                        {{ __('Program') }}
                                    </div>
                                @endif
                            </a>

                            <div class="relative flex min-h-56 flex-col p-6 pb-16">
                                <a href="{{ route('program.index') }}" wire:navigate class="block">
                                    <h3 class="line-clamp-2 text-lg font-semibold leading-7 text-zinc-700 dark:text-zinc-100">{{ $program->judul }}</h3>
                                </a>
                                <p class="mt-3 line-clamp-3 text-sm leading-6 text-zinc-900 dark:text-zinc-300">{{ $program->deskripsi }}</p>

                                <div class="mt-auto flex items-end justify-between pt-8">
                                    <div class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                                        <p class="font-medium uppercase">{{ $program->user->name ?? __('Admin') }}</p>
                                        <p>{{ ($program->tanggal_mulai ?? $program->tanggal)->format('d M Y') }}</p>
                                    </div>

                                    <div class="absolute bottom-0 right-0 rounded-tl-lg bg-[#34A99D] px-4 py-2 text-center text-sm font-bold leading-4 text-white shadow-sm">
                                        <span class="block">{{ ($program->tanggal_mulai ?? $program->tanggal)->format('d M') }}</span>
                                        <span class="block">{{ ($program->tanggal_mulai ?? $program->tanggal)->format('Y') }}</span>
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
                <h2 class="text-4xl font-bold tracking-normal text-[#34A99D]">{{ __('Galeri') }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Kumpulan foto kegiatan dan dokumentasi terbaru Banjar Puluk-Puluk.') }}
                </p>
            </div>

            @if ($this->recentGaleri->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->recentGaleri as $galeri)
                        <article class="overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-zinc-900 dark:ring-zinc-700">
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
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Dashboard') }}</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Ringkasan layanan masyarakat Banjar Puluk-Puluk.') }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total User') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['users']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Pengaduan') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['pengaduan']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Menunggu') }}</p>
                <p class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['pending']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Diproses') }}</p>
                <p class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-400">{{ number_format($this->stats['diproses']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Selesai') }}</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($this->stats['selesai']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Program') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['program']) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Galeri') }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['dokumentasi']) }}</p>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
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

        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
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
                        <span class="inline-flex w-fit rounded-md border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
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
