<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e40af">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'TAMUIN — Sistem Buku Tamu & Presensi Acara')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col pb-20 md:pb-10 antialiased">

    @php
        $currentUser = auth()->user();
        $activeEventId = session('active_event_id');
        if (!$activeEventId && $currentUser) {
            $activeEventId = \App\Models\Event::accessibleBy($currentUser)->orderByDesc('event_date')->value('id');
        }
        $publicSlug = request()->route('slug')
            ?? (\App\Models\Event::where('is_registration_open', true)->orderByDesc('event_date')->value('registration_slug') ?? 'seminar-digital-ai-skills-hub-2026');
    @endphp

    <!-- Header Utama -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-3">
            <!-- Identitas Aplikasi & Navigasi Desktop -->
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ $currentUser ? ($currentUser->isAdmin() ? route('admin.dashboard') : route('kiosk.index')) : route('public.landing', $publicSlug) }}"
                   class="flex items-center gap-2.5 shrink-0">
                    <div class="w-8 h-8 rounded-lg bg-blue-700 text-white font-bold flex items-center justify-center text-sm">
                        T
                    </div>
                    <div class="leading-tight">
                        <span class="font-bold text-slate-900 tracking-tight text-sm sm:text-base block">TAMUIN</span>
                        <span class="text-[11px] text-slate-500 hidden sm:block">Sistem Presensi & Buku Tamu</span>
                    </div>
                </a>

                @auth
                <nav class="hidden md:flex items-center gap-1 ml-5 border-l border-slate-200 pl-5">
                    @if($currentUser->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            Dashboard
                        </a>
                    @endif

                    @if($activeEventId)
                        <a href="{{ route('events.guests.index', $activeEventId) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('events.guests.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            Data Tamu
                        </a>
                    @endif

                    <a href="{{ route('kiosk.index') }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('kiosk.*') ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        Kiosk Check-in
                    </a>

                    @if($currentUser->isAdmin())
                        <a href="{{ route('admin.reports.index') }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            Laporan
                        </a>
                        <a href="{{ route('admin.petugas.index') }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.petugas.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            Petugas
                        </a>
                    @endif

                    <a href="{{ route('events.index') }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('events.index', 'events.create', 'events.edit', 'events.form-fields.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Manajemen Acara
                    </a>
                </nav>
                @else
                <nav class="hidden md:flex items-center gap-1 ml-5 border-l border-slate-200 pl-5">
                    <a href="{{ route('public.landing', $publicSlug) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('public.landing') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Informasi Acara
                    </a>
                    <a href="{{ route('public.register.form', $publicSlug) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('public.register.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Registrasi Tamu
                    </a>
                    <a href="{{ route('public.find-qr', $publicSlug) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('public.find-qr') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Cek Tiket QR
                    </a>
                </nav>
                @endauth
            </div>

            <!-- Kanan: Informasi Pengguna & Tombol Menu Mobile -->
            <div class="flex items-center gap-2.5 shrink-0">
                @auth
                    <div class="hidden sm:block text-right">
                        <div class="text-xs font-semibold text-slate-800 leading-none">{{ $currentUser->name }}</div>
                        <div class="text-[10px] font-medium text-slate-500 uppercase mt-0.5">{{ $currentUser->role }}</div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="hidden md:block">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Keluar
                        </button>
                    </form>
                @else
                    @if(!request()->routeIs('login'))
                        <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                            Login Petugas
                        </a>
                    @endif
                @endauth

                <button type="button" onclick="document.getElementById('mobileDrawer').classList.toggle('hidden')"
                        aria-label="Menu Navigasi"
                        class="md:hidden w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 flex items-center justify-center hover:bg-slate-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Menu Navigasi Mobile (Slide-down) -->
        <div id="mobileDrawer" class="hidden md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1 shadow-md">
            @auth
                <div class="pb-2.5 mb-2 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-900">{{ $currentUser->name }}</div>
                        <div class="text-[11px] text-slate-500 capitalize">{{ $currentUser->role }}</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 text-xs font-semibold">
                            Keluar
                        </button>
                    </form>
                </div>

                @if($currentUser->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        Dashboard Kehadiran
                    </a>
                @endif
                @if($activeEventId)
                    <a href="{{ route('events.guests.index', $activeEventId) }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('events.guests.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        Data Tamu & Impor
                    </a>
                @endif
                <a href="{{ route('kiosk.index') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('kiosk.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                    Kiosk Check-in
                </a>
                @if($currentUser->isAdmin())
                    <a href="{{ route('admin.reports.index') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.reports.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        Laporan Kehadiran
                    </a>
                    <a href="{{ route('admin.petugas.index') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.petugas.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        Kelola Akun Petugas
                    </a>
                @endif
                <a href="{{ route('events.index') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('events.index', 'events.create', 'events.edit', 'events.form-fields.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                    Manajemen Acara
                </a>
                <a href="{{ route('public.landing', $publicSlug) }}" target="_blank" class="block px-3 py-2 rounded-lg text-xs font-semibold text-blue-700 hover:bg-blue-50">
                    Buka Halaman Registrasi Publik
                </a>
            @else
                <a href="{{ route('public.landing', $publicSlug) }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('public.landing') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                    Informasi Acara
                </a>
                <a href="{{ route('public.register.form', $publicSlug) }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('public.register.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                    Formulir Registrasi Tamu
                </a>
                <a href="{{ route('public.find-qr', $publicSlug) }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('public.find-qr') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                    Cari / Kirim Ulang QR Code
                </a>
                <a href="{{ route('login') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('login') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                    Login Petugas / Admin
                </a>
            @endauth
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 py-5 sm:py-6">
        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs sm:text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs sm:text-sm text-rose-800">
                <div class="font-semibold mb-1">Periksa kembali data yang dimasukkan:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Navigasi Bawah Khusus Mobile -->
    @auth
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-40">
            @if($currentUser->isAdmin())
                <div class="grid grid-cols-5 h-15 max-w-lg mx-auto">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('admin.dashboard') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('admin.dashboard'))
                            <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                        <span class="text-[10px] leading-none">Dashboard</span>
                    </a>

                    <a href="{{ $activeEventId ? route('events.guests.index', $activeEventId) : route('events.index') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('events.guests.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('events.guests.*'))
                            <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <span class="text-[10px] leading-none">Data Tamu</span>
                    </a>

                    <a href="{{ route('kiosk.index') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('kiosk.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('kiosk.*'))
                            <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 7V4h3M4 17v3h3M20 7V4h-3M20 17v3h-3M7 12h10"/></svg>
                        <span class="text-[10px] leading-none">Kiosk</span>
                    </a>

                    <a href="{{ route('admin.reports.index') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('admin.reports.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('admin.reports.*'))
                            <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        <span class="text-[10px] leading-none">Laporan</span>
                    </a>

                    <a href="{{ route('events.index') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('events.index', 'events.create', 'events.edit', 'events.form-fields.*', 'admin.petugas.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('events.index', 'events.create', 'events.edit', 'events.form-fields.*', 'admin.petugas.*'))
                            <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span class="text-[10px] leading-none">Acara</span>
                    </a>
                </div>
            @else
                <div class="grid grid-cols-3 h-15 max-w-lg mx-auto">
                    <a href="{{ route('events.index') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('events.index', 'events.create', 'events.edit', 'events.form-fields.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('events.index', 'events.create', 'events.edit', 'events.form-fields.*'))
                            <span class="absolute top-0 inset-x-6 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span class="text-[11px] leading-none">Acara</span>
                    </a>

                    <a href="{{ $activeEventId ? route('events.guests.index', $activeEventId) : route('events.index') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('events.guests.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('events.guests.*'))
                            <span class="absolute top-0 inset-x-6 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <span class="text-[11px] leading-none">Data Tamu</span>
                    </a>

                    <a href="{{ route('kiosk.index') }}"
                       class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('kiosk.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                        @if(request()->routeIs('kiosk.*'))
                            <span class="absolute top-0 inset-x-6 h-0.5 bg-blue-700"></span>
                        @endif
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 7V4h3M4 17v3h3M20 7V4h-3M20 17v3h-3M7 12h10"/></svg>
                        <span class="text-[11px] leading-none">Kiosk Check-in</span>
                    </a>
                </div>
            @endif
        </nav>
    @else
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-40">
            <div class="grid grid-cols-4 h-15 max-w-lg mx-auto">
                <a href="{{ route('public.landing', $publicSlug) }}"
                   class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('public.landing') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                    @if(request()->routeIs('public.landing'))
                        <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                    @endif
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                    <span class="text-[10px] leading-none">Acara</span>
                </a>

                <a href="{{ route('public.register.form', $publicSlug) }}"
                   class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('public.register.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                    @if(request()->routeIs('public.register.*'))
                        <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                    @endif
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <span class="text-[10px] leading-none">Registrasi</span>
                </a>

                <a href="{{ route('public.find-qr', $publicSlug) }}"
                   class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('public.find-qr', 'public.ticket.*') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                    @if(request()->routeIs('public.find-qr', 'public.ticket.*'))
                        <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                    @endif
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span class="text-[10px] leading-none">Tiket QR</span>
                </a>

                <a href="{{ route('login') }}"
                   class="flex flex-col items-center justify-center py-2 gap-1 relative {{ request()->routeIs('login') ? 'text-blue-700 font-semibold' : 'text-slate-500 font-medium' }}">
                    @if(request()->routeIs('login'))
                        <span class="absolute top-0 inset-x-4 h-0.5 bg-blue-700"></span>
                    @endif
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="text-[10px] leading-none">Login</span>
                </a>
            </div>
        </nav>
    @endauth

</body>
</html>
