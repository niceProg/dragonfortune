@props(['items' => []])

<ul class="df-sidebar-menu">
    @foreach($items as $item)
        <li class="df-sidebar-menu-item">
            <a href="{{ $item['url'] ?? '#' }}"
               class="df-sidebar-menu-button {{ $item['active'] ?? false ? 'active' : '' }}">
                @if(isset($item['icon']))
                    {!! $item['icon'] !!}
                @endif
                <span>{{ $item['title'] ?? $item['name'] ?? 'Item' }}</span>
            </a>
        </li>
    @endforeach
</ul>
