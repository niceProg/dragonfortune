@props(['collapsed' => false, 'open' => true])

<aside class="df-sidebar"
       :class="{ 'collapsed': {{ $collapsed ? 'true' : 'false' }} }"
       x-show="{{ $open ? 'true' : 'false' }}"
       x-transition:enter="slide-in"
       x-transition:leave="transition ease-in-out duration-300"
       x-transition:leave-start="transform translate-x-0"
       x-transition:leave-end="transform -translate-x-full">

    <!-- Sidebar Header -->
    <div class="df-sidebar-header">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3v18h18"/>
                    <path d="M7 12l3-3 3 3 5-5"/>
                </svg>
            </div>
            <div x-show="!{{ $collapsed ? 'true' : 'false' }}">
                <div class="fw-semibold" style="font-size: 1rem;">Dragon Fortune</div>
                <div class="small text-muted">Pro</div>
            </div>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div class="df-sidebar-content df-scrollbar">
        {{ $slot }}
    </div>

    <!-- Sidebar Footer -->
    <div class="df-sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div x-show="!{{ $collapsed ? 'true' : 'false' }}">
                <div class="fw-semibold small">Abdul Aziz</div>
                <div class="small text-muted">abdulaziz@dragonfortune.ai</div>
            </div>
        </div>
    </div>
</aside>
