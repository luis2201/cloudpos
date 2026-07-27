@props(['name', 'size' => 20])

<svg {{ $attributes->merge(['class' => 'icon', 'width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('home')
            <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z" />
            @break
        @case('income')
            <path d="M12 2v14m0 0 5-5m-5 5-5-5" />
            <path d="M4 15v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" />
            @break
        @case('cart')
            <circle cx="9" cy="20" r="1" /><circle cx="18" cy="20" r="1" />
            <path d="M3 4h2l2.4 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 8H7" />
            @break
        @case('cash')
            <rect x="2.5" y="5" width="19" height="14" rx="2" />
            <circle cx="12" cy="12" r="3" /><path d="M6 9h.01M18 15h.01" />
            @break
        @case('box')
            <path d="m21 8-9 5-9-5 9-5 9 5Z" /><path d="m3 8 9 5 9-5v9l-9 5-9-5Z" /><path d="M12 13v9" />
            @break
        @case('receipt')
            <path d="M6 2h12v20l-3-2-3 2-3-2-3 2Z" /><path d="M9 7h6M9 11h6M9 15h3" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V21h-4v-.08A1.7 1.7 0 0 0 8.94 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.57 15 1.7 1.7 0 0 0 3 14H3v-4h.08A1.7 1.7 0 0 0 4.6 8.94a1.7 1.7 0 0 0-.34-1.88L4.2 7l2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.57 1.7 1.7 0 0 0 10 3V3h4v.08a1.7 1.7 0 0 0 1.06 1.52 1.7 1.7 0 0 0 1.88-.34L17 4.2 19.83 7l-.06.06A1.7 1.7 0 0 0 19.43 9 1.7 1.7 0 0 0 21 10h.08v4H21a1.7 1.7 0 0 0-1.6 1Z" />
            @break
        @case('company')
            <path d="M3 21h18M5 21V5l7-3 7 3v16M9 8h1M14 8h1M9 12h1M14 12h1M10 21v-5h4v5" />
            @break
        @case('category')
            <rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
            @break
        @case('tag')
            <path d="M20.6 13.6 11 4H4v7l9.6 9.6a2 2 0 0 0 2.8 0l4.2-4.2a2 2 0 0 0 0-2.8Z" /><circle cx="7.5" cy="7.5" r="1" />
            @break
        @case('ruler')
            <path d="m16 2 6 6L8 22l-6-6Z" /><path d="m14 4 2 2M11 7l2 2M8 10l2 2M5 13l2 2" />
            @break
        @case('package')
            <path d="m16.5 9.4-9-5.2M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7Z" /><path d="M3.3 7 12 12l8.7-5M12 22V12" />
            @break
        @case('card')
            <rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20M6 15h2" />
            @break
        @case('bottle')
            <path d="M9 2h6M10 2v5l-2 3v10a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V10l-2-3V2" /><path d="M8 12h8M8 18h8" />
            @break
        @case('store')
            <path d="M3 10v10h18V10M5 4h14l2 6a3 3 0 0 1-5 2 3 3 0 0 1-4 0 3 3 0 0 1-4 0 3 3 0 0 1-5-2Z" /><path d="M9 20v-5h6v5" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            @break
        @case('shield')
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" /><path d="m9 12 2 2 4-4" />
            @break
        @case('edit')
            <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
            @break
        @case('logout')
            <path d="M10 17l5-5-5-5M15 12H3M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
            @break
        @case('tax')
            <path d="M19 5 5 19M7 5h.01M17 19h.01" /><circle cx="7" cy="5" r="2.5" /><circle cx="17" cy="19" r="2.5" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" />
            @break
        @case('filter')
            <path d="M4 5h16M7 12h10M10 19h4" />
            @break
        @case('sort')
            <path d="m8 9 4-4 4 4M16 15l-4 4-4-4" />
            @break
        @case('arrow-right')
            <path d="M5 12h14m-6-6 6 6-6 6" />
            @break
        @case('arrow-left')
            <path d="M19 12H5m6 6-6-6 6-6" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('help')
            <circle cx="12" cy="12" r="9" /><path d="M9.7 9a2.5 2.5 0 1 1 3.5 2.3c-.8.35-1.2.8-1.2 1.7M12 17h.01" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
