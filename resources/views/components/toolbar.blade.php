@props(['title' => 'Dashboard', 'subtitle' => 'BTCUSD · 1D · Bitstamp'])

<header class="df-toolbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Mobile Sidebar Toggle -->
        <button class="btn-df-ghost d-md-none" @click="sidebarOpen = !sidebarOpen">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="6" width="18" height="2"/>
                <rect x="3" y="11" width="18" height="2"/>
                <rect x="3" y="16" width="18" height="2"/>
            </svg>
        </button>

        <!-- Desktop Sidebar Toggle -->
        <button class="btn-df-ghost d-none d-md-block" @click="sidebarCollapsed = !sidebarCollapsed">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="6" width="18" height="2"/>
                <rect x="3" y="11" width="18" height="2"/>
                <rect x="3" y="16" width="18" height="2"/>
            </svg>
        </button>

        <div class="d-flex flex-column">
            <h1 class="h6 mb-0 fw-semibold">{{ $title }}</h1>
            {{-- <p class="small text-muted mb-0">{{ $subtitle }}</p> --}}
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <!-- Theme Toggle -->
        <button class="btn-df-ghost" @click="document.documentElement.classList.toggle('dark')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5"/>
                <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
        </button>

        <button class="btn-df-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                <path d="M3 6h18"/>
                <path d="M3 12h18"/>
                <path d="M3 18h18"/>
            </svg>
            Indicators
        </button>

        <button class="btn-df-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
            </svg>
            Settings
        </button>

        <button class="btn-df-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
            </svg>
            Fullscreen
        </button>
    </div>
</header>
